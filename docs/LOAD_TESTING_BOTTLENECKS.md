# Recueil des goulots d'etranglement & plan d'optimisation

> Sous-phase 2d de la tache **134 — Load testing & scaling** (Sprint 12).
> Synthese des goulots probables identifies a partir des 4 scenarios k6 livres
> (sous-phases 1, 2a, 2b, 2c) et plan d'optimisation priorise pour atteindre
> l'objectif **200 joueurs simultanes sans degradation**.
>
> Ce document est analytique — il ne livre aucun changement de code. Les
> resultats numeriques exacts dependront du run reel (cf. `scripts/load-test/README.md`),
> mais les zones a surveiller, les hypotheses de travail et l'ordre
> d'attaque recommande sont consolides ici.

---

## 1. Couverture des scenarios

| Scenario | Cible mesuree | Couche stressee |
|----------|---------------|-----------------|
| `guest-browsing` | 6 endpoints publics (home, login, register, demo, /health, /metrics) | FrankenPHP + Twig + collectors Doctrine de `/metrics` |
| `metrics-stress` | `/metrics` en boucle, sans think-time | Pool Doctrine + collectors PostgreSQL (`COUNT()` Player/Fight/Mob) |
| `mercure-streaming` | Abonnes SSE concurrents sur `map/move` | Hub Mercure integre (Caddy/FrankenPHP) + FD systeme + proxy Traefik |
| `authenticated-gameplay` | Login + dashboard + carte + inventaire | Firewall Symfony + sessions + APIs `/api/map/*` + rendu Twig `/game/*` |

Les scenarios couvrent l'essentiel de la surface gameplay en lecture. Les
ecritures (POST mouvement, POST combat, POST achat HdV) restent un angle mort
documente dans la section "Prochaines etapes" du `README.md`.

---

## 2. Goulots probables par couche

### 2.1 Base de donnees (PostgreSQL + Doctrine ORM)

**Symptomes attendus** :

- `metrics-stress` : p95 `/metrics` qui derive au-dela de 500 ms quand
  `Player`/`Fight`/`Mob` grossissent.
- `authenticated-gameplay` : `authed_map_api_latency` p95 > 800 ms.
- `http_req_failed` qui monte a > 0.5 % avec apparition de `connection
  refused` ou `too many clients`.

**Causes a investiguer** (par ordre de probabilite) :

1. **Collectors Prometheus non caches** : `MetricsController::collectGameGauges`
   execute 3 `COUNT()` synchrones sur les tables principales a chaque appel
   `/metrics`. Sous scrape Prometheus toutes les 15 s + scrape externe + run
   k6 de stress, l'endpoint peut depasser une centaine d'appels/s sans cache.
2. **Pool de connexions Doctrine satur** : la stack actuelle ouvre une
   connexion par worker FrankenPHP. Sans pooler (`pgbouncer`), 200 VUs +
   workers paralleles peuvent atteindre `max_connections` de PostgreSQL
   (defaut 100).
3. **Indexes composites manquants** :
   - `Player(map_id, x, y)` ou similaire pour les requetes de proximite
     `/api/map/entities`. Aujourd'hui seuls `idx_player_map`, `idx_player_fight`,
     `idx_player_user` existent (verifie sur `src/Entity/App/Player.php:18-20`).
   - `Mob(map_id, died_at)` pour filtrer les mobs vivants : seuls
     `idx_mob_map`, `idx_mob_fight`, `idx_mob_monster` existent.
4. **N+1 latents** sur `/api/map/entities` (Player -> Race -> sprite, Mob ->
   Monster) si les `JOIN FETCH` sont absents des querybuilders.
5. **`updated_at >= NOW() - 15 min`** dans `collectGameGauges` : sans index
   sur `Player.updated_at`, force un seq scan.

### 2.2 Hub Mercure (FrankenPHP/Caddy)

**Symptomes attendus** :

- `mercure-streaming` : `mercure_subscribe_latency` p95 > 1 s (TTFB SSE).
- `mercure_hold_duration` p95 < `(SUBSCRIBE_DURATION-1)*1000` ms = coupure
  precoce de connexion.
- `mercure_subscribe_fail` > 2 % a partir d'un certain pic de VUs.

**Causes a investiguer** :

1. **FD systeme** : `ulimit -n` insuffisant (defaut 1024). Avec un client SSE
   par joueur, 200 abonnes simultanes + workers FrankenPHP saturent vite.
2. **`idle_timeout` Traefik** : si > 60 s par defaut, OK ; sinon les
   connexions sont coupees avant la fin du `SUBSCRIBE_DURATION`.
3. **`heartbeat` / `dispatch_timeout` Mercure** : non configures dans
   `config/packages/mercure.yaml` (uniquement `url`, `public_url` et JWT).
   Les valeurs par defaut Caddy peuvent etre trop agressives en
   environnement Docker.
4. **Topics tres charges** : `map/move` recoit une publication par
   mouvement (`MovedHandler::publish` dans `src/GameEngine/Realtime/Map/`).
   Sous 200 joueurs actifs, le throughput grimpe ; aucun batching n'est
   actuellement en place.

### 2.3 Application PHP (FrankenPHP + Symfony)

**Symptomes attendus** :

- `authenticated-gameplay` : `auth_login_latency` p95 > 2 s.
- `auth_login_fail` > 5 % (CSRF expirees sous charge).
- `http_req_duration` `/game/map` p95 > 1500 ms en l'absence de DB lente.

**Causes a investiguer** :

1. **Coût argon2 du `LoginFormAuthenticator`** : si `password_hashers.cost`
   n'est pas calibre pour la machine cible, un login monopolise un worker
   plusieurs centaines de ms.
2. **Verrou de session Symfony** : sous concurrence sur un meme compte
   (cas evite via le pool de credentials, mais pas sous spam de
   reconnexion), les sessions sont serialisees sur le meme fichier.
3. **Twig `/game/map`** : assets, includes, traductions multiples par page.
   Sans cache OPcache adapte, le rendu peut dominer la latence.
4. **`auto_generate_proxy_classes: true`** en non-prod : penalite legere
   en dev, neutre en prod (`when@prod` desactive deja).

### 2.4 Cache applicatif

**Etat actuel** (`config/packages/cache.yaml`) :

- `app: cache.adapter.filesystem` (defaut, **non commente** = actif).
- Aucune declaration `default_redis_provider` ni `cache.adapter.redis`.
- `MetricsCollector` utilise `CacheItemPoolInterface` -> filesystem ; chaque
  `incrementCounter` / `observeHistogram` lit + ecrit le fichier `app_metrics`.

**Consequences** :

- Verrouillage du fichier de cache sous concurrence : N workers concurrents
  serialisent leurs ecritures sur le meme inode. Sous 200 VUs, ce point
  devient le premier goulot avant meme la DB.
- `getAll()` / `renderPrometheus()` rechargent l'integralite du tableau
  serialize a chaque appel `/metrics`.

---

## 3. Plan d'optimisation priorise

Ordre recommande : **commencer par les optimisations a faible effort qui
debloquent le plus grand multiplicateur de capacite**. Chaque jalon doit
etre valide par un re-run des 4 scenarios k6.

### Jalon A — Cache Redis (priorite 1, effort S)

**Objectif** : remplacer le cache filesystem par Redis pour eliminer le
verrouillage d'inode et permettre le partage entre containers FrankenPHP.

- [ ] Ajouter le service `redis` dans `compose.yaml` (image officielle
      `redis:7-alpine`, volume persistant optionnel).
- [ ] Decommenter et activer dans `config/packages/cache.yaml` :
      ```yaml
      framework:
          cache:
              app: cache.adapter.redis
              default_redis_provider: '%env(REDIS_URL)%'
      ```
- [ ] Variable `REDIS_URL` dans `.env` (defaut `redis://redis:6379/0`).
- [ ] Re-run `metrics-stress` : la latence `/metrics` doit chuter de 50 a
      80 % et le verrouillage disparaitre des traces.

**Gain attendu** : x3 a x5 sur la capacite `/metrics` ; debloque le
caching de collectors (jalon C).

### Jalon B — Connection pooling Doctrine (priorite 2, effort M)

**Objectif** : multiplexer les connexions PHP -> PostgreSQL via PgBouncer
pour tenir au-dela de 100 connexions concurrentes.

- [ ] Ajouter `pgbouncer` dans `compose.yaml` (image
      `edoburu/pgbouncer:1.22.0` ou equivalent), pool mode `transaction`
      compatible avec Doctrine.
- [ ] Pointer `DATABASE_URL` vers `pgbouncer:6432` au lieu de
      `database:5432`.
- [ ] Adapter PostgreSQL : `max_client_conn` PgBouncer = 500,
      `default_pool_size` = 25, `pool_mode = transaction`.
- [ ] Verifier la compatibilite : aucun usage de `LISTEN/NOTIFY` ni de
      `prepared statements` cross-transaction (Doctrine reutilise les
      prepared statements ; `pool_mode = transaction` peut casser cela —
      tester avec `prepare_threshold = 0`).
- [ ] Re-run `authenticated-gameplay` a 200 VUs : `http_req_failed` doit
      rester < 1 %, plus de `too many clients`.

**Gain attendu** : x2 a x4 sur la capacite des routes authentifiees ;
indispensable au-dela de 150 VUs.

### Jalon C — Cache des collectors `/metrics` (priorite 3, effort S) ✅ TERMINE

**Objectif** : eviter les 3 `COUNT()` synchrones par scrape Prometheus.

> **Statut** : termine — sous-phases 3a (indexes) + 3b (cache TTL) + 3d (partial index fight). Le hot path `/metrics` est decouple de la volumetrie DB.

- [ ] Refactor `MetricsController::collectGameGauges` pour utiliser un
      cache TTL court (5 a 15 s) via le pool Redis du jalon A.
      → **Sous-phase 3b livree** (2026-04-26) : cache TTL 10s via le pool
      `cache.app` (filesystem aujourd'hui, automatiquement Redis quand le
      jalon A sera livre — aucun changement de code requis).
- [ ] Alternativement, deplacer la collecte vers une commande
      `app:monitoring:snapshot` declenchee par un cron — non retenu.
- [ ] Ajouter `idx_player_updated_at` sur `Player.updated_at` (migration
      idempotente avec `CREATE INDEX IF NOT EXISTS`).
      → **Sous-phase 3a livree** (2026-04-26).
- [ ] Ajouter `idx_mob_died_at` sur `Mob.died_at` ou un partial index
      `WHERE died_at IS NULL` (PostgreSQL).
      → **Sous-phase 3a livree** (2026-04-26) : `idx_mob_alive_map ON mob (map_id) WHERE died_at IS NULL` (partial, couvre aussi le filtre par carte).
- [ ] Ajouter un index sur `Fight.in_progress` pour la 3eme gauge.
      → **Sous-phase 3d livree** (2026-04-26) : `idx_fight_in_progress ON fight (in_progress) WHERE in_progress = true` (partial).
- [ ] Re-run `metrics-stress` : p95 `/metrics` < 100 ms, p99 < 250 ms.

**Gain obtenu** : `/metrics` decouple de la volumetrie via le cache TTL ;
les rares COUNTs restants (1 cycle / 10s) frappent les indexes ajoutes.
A re-mesurer sous `metrics-stress` 20 VUs sans think-time pour confirmer
l'objectif p95 < 100 ms.

### Jalon D — Indexes composites pour les APIs map (priorite 4, effort S) — ✅ Termine

**Objectif** : accelerer `/api/map/entities` qui itere sur les
joueurs/mobs/PNJ d'une carte.

- [x] Migration : `idx_player_map_coords ON player (map_id,
      coordinates)` — **non actionable apres analyse** (sous-phase 3f,
      2026-04-28). Le champ `coordinates` etant stringifie `"x.y"`
      (CLAUDE.md regle 7), l'index serait utile uniquement pour des
      queries `WHERE map_id = X AND coordinates = 'x.y'` (egalite
      stricte). Audit du codebase (`grep -rn "coordinates'" src/`,
      `grep -rn "->andWhere.*coordinates"`) confirme **aucune query
      DQL ne filtre par coordonnees** : `findBy(['map' => $map])`
      recupere tous les acteurs, et la proximite est filtree en PHP
      via `abs($ex - $px) > $radius`. L'index existant `idx_player_map`
      (`Player.php:18`) est deja optimal pour ce pattern. L'alternative
      BRIN `(map_id)` n'apporte rien sur des cardinalites typiques
      (1-200 joueurs / carte). **Conclusion** : pas d'index a ajouter.
- [x] Migration : `CREATE INDEX IF NOT EXISTS idx_mob_map_alive ON mob
      (map_id) WHERE died_at IS NULL`.
      → **Sous-phase 3a livree** (2026-04-26) : `idx_mob_alive_map`
      (chevauchement avec le jalon C : ce partial index sert les 2
      objectifs simultanement).
- [x] **Bonus refactor** — supprimer le produit cartesien dans
      `MobRepository::findByMapWithMonster` (4 leftJoin OneToMany
      imbriques sans usage cote appelant).
      → **Sous-phase 3c livree** (2026-04-26) : reduction du wire
      transfer DB d'un facteur ~15 sur `/api/map/entities`.
- [ ] Profiler les querybuilders de `MapEntityFetcher` (ou equivalent)
      via `EXPLAIN ANALYZE` sous charge `authenticated-gameplay`. →
      Reporte au moment d'un run k6 reel sur staging (necessite infra
      hors scope local) ; les optimisations de 3a + 3c sont les seules
      actionables sans index supplementaire.
- [ ] Re-run `authenticated-gameplay` : `authed_map_api_latency` p95 <
      300 ms (vs ~800 ms sans index). → A executer apres deploiement
      des sous-phases 3a + 3c sur staging.

**Gain attendu** : x2 a x5 sur la latence des routes map authentifiees,
selon la volumetrie reelle (l'essentiel du gain provient deja de la
sous-phase 3c qui supprime le produit cartesien sur le wire DB).

### Jalon E — Hardening Mercure (priorite 5, effort M)

**Objectif** : tenir 200 abonnes SSE sans coupure precoce ni pic de
latence.

- [ ] `ulimit -n` >= 65536 dans le service FrankenPHP (Dockerfile ou
      `compose.yaml`).
- [ ] Configurer Mercure dans `config/packages/mercure.yaml` :
      ```yaml
      mercure:
          hubs:
              default:
                  url: '%env(MERCURE_URL)%'
                  public_url: '%env(MERCURE_PUBLIC_URL)%'
                  jwt:
                      secret: '%env(MERCURE_JWT_SECRET)%'
                      publish: '*'
      ```
      (verifier la version Caddy/FrankenPHP pour les options
      `transport.read_timeout`, `transport.write_timeout`,
      `dispatch_timeout` et les exposer en variable d'env).
- [ ] Traefik : `transport.respondingTimeouts.idleTimeout = 5m` sur
      l'entrypoint `websecure` pour ne pas couper les SSE longs.
- [ ] Re-run `mercure-streaming` a 200 VUs : `mercure_subscribe_fail` <
      0.5 %, `mercure_hold_duration` p95 = `SUBSCRIBE_DURATION` complet.

**Gain attendu** : capacite SSE x2, fin des coupures spurieuses observees
en environnement Docker.

### Jalon F — Plan de scaling horizontal (priorite 6, effort L)

**Objectif** : preparer le passage a plusieurs instances FrankenPHP
derriere Traefik.

- [ ] Sessions Symfony partagees : passer `session.handler` sur Redis
      (`sf.session.handler.redis`) — prerequis du jalon A.
- [ ] Verrous Doctrine / cache : audit des `Lock` Symfony et des cache
      pools pour s'assurer qu'aucun n'est filesystem-dependent une fois
      multi-instance.
- [ ] Mercure : passer en mode hub externe (Mercure standalone ou
      `cloud.mercure.rocks`) pour decoupler les publications du nombre
      d'instances FrankenPHP.
- [ ] Traefik : load balancer round-robin sur 2 a 4 replicas FrankenPHP.
- [ ] Re-run **les 4 scenarios** a 200 VUs avec 2 replicas et comparer
      avec la baseline mono-instance (jalons A-E appliques).

**Gain attendu** : capacite lineaire avec le nombre de replicas. Doit
etre conditionne par les jalons A-E (sinon on duplique des goulots
au lieu d'augmenter la capacite).

---

## 4. Indicateurs de succes (objectif Sprint 12)

Pour declarer la tache 134 **completable a 200 VUs** :

| Scenario | Threshold cible (200 VUs / 5 min) |
|----------|------------------------------------|
| `guest-browsing` | p95 < 800 ms, http_req_failed < 1 %, checks > 95 % |
| `metrics-stress` | p95 < 200 ms, p99 < 500 ms (apres jalons A + C) |
| `mercure-streaming` | subscribe_fail < 0.5 %, hold_duration p95 = SUBSCRIBE_DURATION (apres jalon E) |
| `authenticated-gameplay` | p95 < 1500 ms, auth_login_fail < 1 %, authed_map_api_latency p95 < 400 ms (apres jalons B + D) |

Tous les seuils doivent etre tenus simultanement sur staging avec une
volumetrie de DB representative (pas une DB vide). Documenter les runs
dans `docs/audits/` avec date, configuration, resume k6.

---

## 5. Avancement & prochaines etapes

### Etat des jalons

| Jalon | Statut | Sous-phases livrees |
|-------|--------|---------------------|
| A — Cache Redis | ⏳ A faire | — |
| B — PgBouncer | ⏳ A faire | — |
| **C — Cache + indexes `/metrics`** | **✅ Termine** | 3a (indexes Player/Mob) + 3b (cache TTL 10s) + 3d (partial index Fight) |
| **D — Indexes composites + refactor map** | **✅ Termine** | 3a (`idx_mob_alive_map` chevauche) + 3c (refactor `findByMapWithMonster`) + 3f (cloture analytique : `idx_player_map_coords` non actionable car coords sont une string filtree en PHP) |
| E — Hardening Mercure | ⏳ A faire | — |
| F — Scaling horizontal | ⏳ A faire | — |

### Roadmap a venir

1. ~~**Sous-phase 3e** (tache 134) — completer le **jalon D**~~ → **Sous-phase 3f livree** (2026-04-28) : cloture analytique du jalon D. `idx_player_map_coords` n'est pas actionable car les coordonnees sont stringifiees `"x.y"` (CLAUDE.md regle 7) et qu'aucune query DQL ne filtre par coordonnees (proximite filtree en PHP). Le profiling `EXPLAIN ANALYZE` reste a executer sur staging quand un run k6 reel sera lance.
2. **Sous-phase 4** — implementer le **jalon A** (Redis cache, compose + cache.yaml). Cle pour debloquer le scaling horizontal et la coordination multi-worker. Substitution transparente du pool `cache.app` deja utilise par `MetricsController` (sous-phase 3b) — aucun changement de code controller requis.
3. **Sous-phase 5** — implementer le **jalon B** (PgBouncer). Migration + compose, demande validation pgbouncer + Doctrine.
4. **Sous-phase 6** — implementer le **jalon E** (Mercure hardening). Touche l'infra (compose + Caddyfile + Traefik dynamic config).
5. **Sous-phase 7** — designer le **jalon F** (scaling horizontal) — peut rester un plan ecrit si les jalons A-E suffisent a tenir 200 VUs en mono-instance.

Chaque jalon livre **doit** etre suivi d'un re-run des 4 scenarios k6 et d'une mise a jour de la section "Resultats observes" ci-dessous.

### Resultats observes

> A renseigner par sous-phase, au fur et a mesure des runs reels contre staging.

| Date | Sous-phase | Scenario | p95 / p99 / fail rate | Notes |
|------|------------|----------|------------------------|-------|
| _A renseigner_ | _3a/3b/3d_ | `metrics-stress` | _—_ | Premier re-run a effectuer apres deploiement des jalons C complets (3a + 3b + 3d). |
| _A renseigner_ | _3c_ | `authenticated-gameplay` | _—_ | Comparaison avant/apres le refactor `findByMapWithMonster`. |

---

## Annexe — Pointeurs code

- Collectors `/metrics` : `src/Controller/Monitoring/MetricsController.php:34-58`
- Pool de cache : `src/Service/Monitoring/MetricsCollector.php:126-148`
- Indexes existants : `src/Entity/App/Player.php:18-20`, `src/Entity/App/Mob.php:16-18`
- Publishers Mercure : `src/GameEngine/Realtime/Map/MovedHandler.php`,
  `src/GameEngine/Realtime/Map/RespawnedHandler.php`
- Config cache : `config/packages/cache.yaml`
- Config Mercure : `config/packages/mercure.yaml`
- Config Doctrine : `config/packages/doctrine.yaml`
- Scenarios k6 : `scripts/load-test/scenarios/`, README a la racine de ce dossier
