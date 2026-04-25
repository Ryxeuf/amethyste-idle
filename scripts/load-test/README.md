# Load testing — Amethyste-Idle

Scenarios [k6](https://k6.io/) pour mesurer la tenue en charge du serveur
(FrankenPHP + Symfony + PostgreSQL + Mercure). Non destructif : aucun scenario
ici n'ecrit en base, tous ciblent des routes publiques.

Premier jalon de la tache **134 — Load testing & scaling** (Sprint 12) :
infrastructure k6 + scenario `guest-browsing`. Jalon 2 : scenario
`metrics-stress` pour isoler la latence de collecte Prometheus / Doctrine
sous charge soutenue. Jalon 3 : scenario `mercure-streaming` pour mesurer
la capacite du hub Mercure (FrankenPHP/Caddy) a tenir des abonnes SSE
concurrents sur le topic `map/move`. Les jalons suivants ajouteront le
scenario authentifie (map, combat, hotel des ventes).

## Prerequis

- [k6](https://grafana.com/docs/k6/latest/set-up/install-k6/) >= 0.50
- Un serveur Amethyste-Idle joignable (dev local, staging ou prod)

Installation rapide :

```bash
# macOS
brew install k6

# Linux (Debian/Ubuntu)
sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg \
  --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" \
  | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update && sudo apt-get install -y k6

# Docker (sans installation locale)
docker run --rm -i -v "$PWD:/src" -w /src grafana/k6 run scripts/load-test/scenarios/guest-browsing.js
```

## Structure

```
scripts/load-test/
├── README.md                 # ce fichier
├── config.js                 # env vars, thresholds et options partagees
└── scenarios/
    ├── guest-browsing.js     # navigation anonyme (home, login, register, /metrics, /health)
    ├── metrics-stress.js     # stress focalise sur /metrics (collecte Prometheus + Doctrine)
    └── mercure-streaming.js  # capacite SSE du hub Mercure sur le topic `map/move`
```

## Scenario : guest-browsing

Simule des visiteurs anonymes qui parcourent les pages publiques. Chaque VU
(virtual user) alterne :

1. Page d'accueil `/`
2. Pages d'authentification `/login` et `/register`
3. Page `/demo`
4. Endpoints de monitoring `/health` + `/metrics`

Entre chaque requete, un delai aleatoire (`THINK_TIME_MIN`..`THINK_TIME_MAX`
secondes) simule le temps de lecture humain.

Execution par defaut (50 VUs, ramp 30s + 1m plateau + 15s ramp-down) :

```bash
k6 run scripts/load-test/scenarios/guest-browsing.js
```

Test de charge cible (200 VUs, 5 minutes) — objectif Sprint 12 :

```bash
BASE_URL=https://staging.amethyste.best \
  VUS=200 DURATION=5m RAMP_UP=1m RAMP_DOWN=30s \
  k6 run scripts/load-test/scenarios/guest-browsing.js
```

## Scenario : metrics-stress

Stress focalise sur l'endpoint Prometheus `/metrics` pour isoler la latence
de la collecte Doctrine sous charge soutenue. Chaque VU tape en boucle
**sans think-time** sur `/metrics`, qui declenche plusieurs `COUNT()` sur
`player`, `fight` et `mob` via les collectors de `App\Monitoring\*`.

Cas d'usage :

- Detecter une regression de requetes Doctrine (index manquant, N+1, join trop large)
- Identifier la saturation du pool de connexions PostgreSQL
- Mesurer l'impact du volume de Fight/Mob sur la latence de collecte

Execution par defaut (20 VUs stress, ramp 30s + 1m plateau + 15s ramp-down) :

```bash
k6 run scripts/load-test/scenarios/metrics-stress.js
```

Run soutenu (20 VUs, 3 minutes) pour detection de regression :

```bash
BASE_URL=https://staging.amethyste.best \
  VUS=20 DURATION=3m RAMP_UP=30s RAMP_DOWN=15s \
  k6 run scripts/load-test/scenarios/metrics-stress.js
```

> **Ne pas lancer en production sans coordination**. La charge est
> intentionnellement agressive (zero think-time) : 20 VUs equivalent a
> plusieurs centaines de VUs realistes. Cibler un environnement de staging
> ou dev uniquement.

Thresholds dedies (plus stricts que le default) :

- `http_req_duration` : p95 < 500ms, p99 < 1.5s (vs 800ms/2s pour `guest-browsing`)
- `http_req_failed` : < 0.5% (vs 1%)
- `metrics_collect_latency` : trend custom (p95 < 500ms, p99 < 1.5s)
- `metrics_payload_fail` : taux custom de payloads invalides (format Prometheus absent)

Si un seuil est depasse, investiguer en priorite :

1. Les logs PostgreSQL (`pg_stat_statements`) pour identifier les requetes lentes
2. L'etat du pool Doctrine (`SHOW processlist` / `pg_stat_activity`)
3. Le code des collectors `App\Monitoring\*` : un `COUNT(*)` sans `WHERE` sur une
   grande table doit etre remplace par un cache (Redis) ou un snapshot asynchrone

## Scenario : mercure-streaming

Mesure la capacite du hub Mercure integre a FrankenPHP (Caddy) a tenir des
abonnes SSE concurrents sur un topic donne (defaut : `map/move`, le plus
actif du jeu). Chaque VU ouvre une requete HTTP vers
`/.well-known/mercure?topic=map/move` avec `Accept: text/event-stream`,
maintient la connexion ouverte pendant `SUBSCRIBE_DURATION` secondes, puis
recommence. La p95 du TTFB SSE + le taux de succes mesurent la tenue en
charge cote hub.

Cas d'usage :

- Detecter les coupures precoces (proxy / load balancer trop strict)
- Mesurer la latence d'etablissement d'un `EventSource` sous charge
- Identifier la limite de connexions concurrentes (FD, pool FrankenPHP)

Execution par defaut (50 VUs = 50 abonnes simultanes, 30s par abonnement) :

```bash
k6 run scripts/load-test/scenarios/mercure-streaming.js
```

Run cible Sprint 12 (200 abonnes pendant 2 minutes) :

```bash
BASE_URL=https://staging.amethyste.best \
  VUS=200 DURATION=2m RAMP_UP=1m RAMP_DOWN=30s SUBSCRIBE_DURATION=30 \
  k6 run scripts/load-test/scenarios/mercure-streaming.js
```

Cibler un autre topic (`map/respawn`, `map/avatar`, `event/announce`) :

```bash
MERCURE_TOPIC=event/announce \
  k6 run scripts/load-test/scenarios/mercure-streaming.js
```

Thresholds dedies :

- `mercure_subscribe_latency` : p95 < 1s, p99 < 3s (TTFB SSE = premier
  heartbeat ou evenement publie)
- `mercure_subscribe_fail` : taux de subscriptions echouees < 2% (status
  inattendu, content-type incorrect, ou connexion coupee avant la duree)
- `mercure_hold_duration` : la p95 doit couvrir `SUBSCRIBE_DURATION - 1s`
  (sinon le hub coupe avant la fin demandee)

Si un seuil est depasse, investiguer en priorite :

1. Les logs Caddy / FrankenPHP : recherche de `connection reset`, `timeout`
   ou `429 Too Many Requests`
2. Les limites systeme : `ulimit -n` (FD ouverts), `net.core.somaxconn`
3. La configuration Mercure : `heartbeat`, `dispatch_timeout`,
   `read_timeout` cote Caddy
4. Le proxy en amont (Traefik) : `idle_timeout`, `transport.respondingTimeouts`

> **Note sur la mesure** : k6 stock ne sait pas decouper un flux SSE
> evenement par evenement. Ce scenario s'appuie sur le timeout HTTP pour
> borner chaque iteration et utilise `timings.waiting` (TTFB) comme proxy
> de la latence d'etablissement. Pour mesurer la latence bout-en-bout d'un
> evenement publie -> recu, prevoir une variante avec l'extension SSE
> (xk6-sse) ou un script Node externe declenche par le scenario.

## Variables d'environnement

| Variable | Defaut | Description |
|----------|--------|-------------|
| `BASE_URL` | `http://localhost` | URL racine cible |
| `VUS` | `50` | Nombre de VUs simultanes au plateau |
| `DURATION` | `1m` | Duree du plateau |
| `RAMP_UP` | `30s` | Duree de montee 0 -> VUs |
| `RAMP_DOWN` | `15s` | Duree de descente VUs -> 0 |
| `THINK_TIME_MIN` | `1` | Attente min entre requetes (s) — `guest-browsing` |
| `THINK_TIME_MAX` | `4` | Attente max entre requetes (s) — `guest-browsing` |
| `SUBSCRIBE_DURATION` | `30` | Duree de l'abonnement SSE par VU (s) — `mercure-streaming` |
| `MERCURE_TOPIC` | `map/move` | Topic SSE cible — `mercure-streaming` |
| `K6_SUMMARY_EXPORT` | `scripts/load-test/last-summary.json` | Chemin du JSON de sortie |

## Seuils (thresholds)

Definis dans `config.js::DEFAULT_THRESHOLDS` :

- `http_req_duration`: p95 < 800ms, p99 < 2s
- `http_req_failed`: < 1% d'erreurs HTTP
- `checks`: > 95% de checks applicatifs satisfaits

k6 retourne un code de sortie non nul si un seuil n'est pas respecte
(utilisable en CI).

## Interpretation des resultats

A la fin du run, k6 affiche un resume texte et exporte un JSON complet. Les
metriques a surveiller en priorite :

- `http_req_duration` (p95/p99) : temps de reponse global — symptome de
  saturation CPU/PHP-FPM ou de requetes Doctrine lentes.
- `http_req_failed` : taux d'erreurs — 5xx cote serveur (FrankenPHP qui
  rejette, timeout DB) ou 4xx (rate-limiting).
- `metrics_endpoint_latency` : l'endpoint `/metrics` fait plusieurs
  `COUNT()` Doctrine sur les tables `player`, `fight`, `mob`. Une latence
  p95 > 500ms suggere d'ajouter un index ou de mettre en cache la collecte.
- `checks` (rate) : proportion d'assertions applicatives passees. Une chute
  signale typiquement des redirections inattendues (session expiree, maintenance
  mode, CSRF).

## Prochaines etapes (Sprint 12)

- Scenario `authenticated-gameplay` : login + fetch carte + mouvement
  (necessite un pool de comptes de test et la gestion CSRF / session cookies).
- Variante `mercure-streaming` avec extension xk6-sse : decouper le flux
  evenement par evenement et mesurer la latence publish -> receive (necessite
  un k6 custom-build ou un harness Node externe).
- Integration CI (GitHub Actions) : run nightly contre staging avec seuils
  adaptes, alerte en cas de regression.
- Recueil des goulots identifies + plan d'optimisation (connection pooling
  Doctrine, cache Redis, horizontal scaling FrankenPHP).
