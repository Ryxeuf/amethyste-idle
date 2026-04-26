# Load testing — Amethyste-Idle

Scenarios [k6](https://k6.io/) pour mesurer la tenue en charge du serveur
(FrankenPHP + Symfony + PostgreSQL + Mercure). Non destructif : aucun scenario
ici n'ecrit en base, tous ciblent des routes publiques.

Premier jalon de la tache **134 — Load testing & scaling** (Sprint 12) :
infrastructure k6 + scenario `guest-browsing`. Jalon 2 : scenario
`metrics-stress` pour isoler la latence de collecte Prometheus / Doctrine
sous charge soutenue. Jalon 3 : scenario `mercure-streaming` pour mesurer
la capacite du hub Mercure (FrankenPHP/Caddy) a tenir des abonnes SSE
concurrents sur le topic `map/move`. Jalon 4 : scenario
`authenticated-gameplay` qui couvre la chaine login (CSRF + session) +
boucle de chargement de la carte (`/game/map`, `/api/map/cells`,
`/api/map/entities`) + page d'inventaire — premiere mesure de la tenue
en charge des routes protegees `^/game` et `^/api/`.

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
    ├── guest-browsing.js          # navigation anonyme (home, login, register, /metrics, /health)
    ├── metrics-stress.js          # stress focalise sur /metrics (collecte Prometheus + Doctrine)
    ├── mercure-streaming.js       # capacite SSE du hub Mercure sur le topic `map/move`
    └── authenticated-gameplay.js  # login + boucle map/inventaire (routes protegees /game et /api/map)
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

## Scenario : authenticated-gameplay

Simule des joueurs connectes qui parcourent les ecrans principaux apres
authentification. Chaque VU iteration :

1. `GET /login` → extraction du `_csrf_token` du HTML
2. `POST /login` (`email`, `password`, `_csrf_token`) → 302 vers `/game`,
   `/game/character/select` ou `/game/character/create`
3. `GET /game` (dashboard)
4. `GET /game/map` (Twig) + `GET /api/map/config` + `GET /api/map/cells` +
   `GET /api/map/entities` (boucle classique du `map_pixi_controller`)
5. `GET /game/inventory`

k6 maintient automatiquement un cookie jar par VU : la session Symfony
reste valide pour toutes les requetes suivantes.

Cas d'usage :

- Mesurer la tenue en charge des routes protegees (`^/game`, `^/api/`)
- Detecter une regression de session / CSRF / firewall Symfony
- Evaluer le cout des API map (`/api/map/cells` charge un radius de tuiles,
  `/api/map/entities` itere sur tous les joueurs/mobs/PNJ visibles)

Prerequis : un (ou plusieurs) compte de test deja provisionne sur la
cible, avec au moins un personnage cree pour eviter la redirection
`/game/character/create`. Definir au choix :

```bash
# Compte unique
TEST_USER_EMAIL=tester@amethyste.test \
  TEST_USER_PASSWORD=motdepasse \
  k6 run scripts/load-test/scenarios/authenticated-gameplay.js

# Pool de comptes (round-robin sur les VUs) — recommande au-dela de 20 VUs
TEST_CREDENTIALS_FILE=scripts/load-test/credentials.json \
  k6 run scripts/load-test/scenarios/authenticated-gameplay.js
```

Format `credentials.json` :

```json
[
  {"email": "u1@amethyste.test", "password": "..."},
  {"email": "u2@amethyste.test", "password": "..."}
]
```

> **Pourquoi un pool ?** Sous forte concurrence sur un meme compte, le
> firewall Symfony serialise les sessions (verrou sur `getSession()`) et
> les metriques de latence `/api/map/*` deviennent dominees par l'attente
> de lock. Pour mesurer la capacite reelle, utiliser un pool d'au moins
> `VUS / 5` comptes distincts.

Run cible Sprint 12 (200 VUs, 5 minutes, pool de 50 comptes) :

```bash
BASE_URL=https://staging.amethyste.best \
  VUS=200 DURATION=5m RAMP_UP=1m RAMP_DOWN=30s \
  TEST_CREDENTIALS_FILE=scripts/load-test/credentials.json \
  k6 run scripts/load-test/scenarios/authenticated-gameplay.js
```

Thresholds dedies (plus larges que `guest-browsing` car les routes
authentifiees declenchent davantage de requetes Doctrine) :

- `http_req_duration` : p95 < 1500ms, p99 < 3s
- `http_req_failed` : < 2%
- `auth_login_fail` : < 5% (taux de logins echoues — cible 0% en
  conditions normales, marge laissee pour les CSRF expirees sous charge)
- `auth_login_latency` : p95 < 2s (login = GET form + POST submit + 302)
- `authed_request_fail` : < 2% (toute requete authentifiee qui ne
  retourne pas 200 + content-type attendu)

Si un seuil est depasse, investiguer en priorite :

1. Le firewall Symfony (`security.yaml`, `LoginFormAuthenticator`) :
   verifier que la session ne refait pas tout le hashing argon a chaque
   requete (cas d'un `password_hashers.cost` mal calibre)
2. Les API `/api/map/*` : un `findByMapWithMonster` non indexe, un
   `findBy(['map' => $map])` sur Player sans index composite (`map_id`,
   `coordinates`)
3. Le pool Doctrine sous une concurrence reelle (200 VUs => 200
   connexions PostgreSQL si pas de pooler)
4. Le rendu Twig de `/game/map` (assets, includes, traductions) sous
   charge — comparer avec `metrics-stress` pour isoler le cout DB du
   cout rendering

## Variables d'environnement

| Variable | Defaut | Description |
|----------|--------|-------------|
| `BASE_URL` | `http://localhost` | URL racine cible |
| `VUS` | `50` | Nombre de VUs simultanes au plateau |
| `DURATION` | `1m` | Duree du plateau |
| `RAMP_UP` | `30s` | Duree de montee 0 -> VUs |
| `RAMP_DOWN` | `15s` | Duree de descente VUs -> 0 |
| `THINK_TIME_MIN` | `1` | Attente min entre requetes (s) — `guest-browsing`, `authenticated-gameplay` |
| `THINK_TIME_MAX` | `4` | Attente max entre requetes (s) — `guest-browsing`, `authenticated-gameplay` |
| `SUBSCRIBE_DURATION` | `30` | Duree de l'abonnement SSE par VU (s) — `mercure-streaming` |
| `MERCURE_TOPIC` | `map/move` | Topic SSE cible — `mercure-streaming` |
| `TEST_USER_EMAIL` | _(aucun)_ | Email du compte unique — `authenticated-gameplay` |
| `TEST_USER_PASSWORD` | _(aucun)_ | Mot de passe du compte unique — `authenticated-gameplay` |
| `TEST_CREDENTIALS_FILE` | _(aucun)_ | Fichier JSON `[{email, password}, ...]` — `authenticated-gameplay` |
| `MAP_RADIUS` | `15` | Radius pour `/api/map/cells` et `/api/map/entities` — `authenticated-gameplay` |
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

- Etendre `authenticated-gameplay` avec un POST de mouvement (`/api/map/move`,
  necessite la gestion du token CSRF de l'API + un parcours coherent pour
  eviter les rejets `next position invalid`) et un POST combat (entree de
  fight + un tour). Mesurera le cout d'ecriture cote DB (UPDATE Player.x/y,
  INSERT FightTurn) et la publication Mercure (`map/move`).
- Variante `mercure-streaming` avec extension xk6-sse : decouper le flux
  evenement par evenement et mesurer la latence publish -> receive (necessite
  un k6 custom-build ou un harness Node externe).
- Integration CI (GitHub Actions) : run nightly contre staging avec seuils
  adaptes, alerte en cas de regression.
- Implementer le plan d'optimisation : voir
  [`docs/LOAD_TESTING_BOTTLENECKS.md`](../../docs/LOAD_TESTING_BOTTLENECKS.md)
  pour la synthese des goulots probables et l'ordre recommande des jalons
  (Redis, PgBouncer, indexes composites, hardening Mercure, scaling
  horizontal).
