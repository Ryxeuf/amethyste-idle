# Load testing — Amethyste-Idle

Scenarios [k6](https://k6.io/) pour mesurer la tenue en charge du serveur
(FrankenPHP + Symfony + PostgreSQL + Mercure). Non destructif : aucun scenario
ici n'ecrit en base, tous ciblent des routes publiques.

Premier jalon de la tache **134 — Load testing & scaling** (Sprint 12) :
infrastructure k6 + scenario `guest-browsing`. Les jalons suivants ajouteront
les scenarios authentifies (map, combat, hotel des ventes) et l'identification
des goulots d'etranglement Mercure / Doctrine.

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
├── README.md            # ce fichier
├── config.js            # env vars, thresholds et options partagees
└── scenarios/
    └── guest-browsing.js  # navigation anonyme (home, login, register, /metrics, /health)
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

## Variables d'environnement

| Variable | Defaut | Description |
|----------|--------|-------------|
| `BASE_URL` | `http://localhost` | URL racine cible |
| `VUS` | `50` | Nombre de VUs simultanes au plateau |
| `DURATION` | `1m` | Duree du plateau |
| `RAMP_UP` | `30s` | Duree de montee 0 -> VUs |
| `RAMP_DOWN` | `15s` | Duree de descente VUs -> 0 |
| `THINK_TIME_MIN` | `1` | Attente min entre requetes (s) |
| `THINK_TIME_MAX` | `4` | Attente max entre requetes (s) |
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
- Scenario `mercure-streaming` : SSE `map/move` avec mesure de latence bout
  en bout.
- Integration CI (GitHub Actions) : run nightly contre staging avec seuils
  adaptes, alerte en cas de regression.
- Recueil des goulots identifies + plan d'optimisation (connection pooling
  Doctrine, cache Redis, horizontal scaling FrankenPHP).
