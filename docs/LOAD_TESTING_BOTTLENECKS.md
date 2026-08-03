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

> ### ⚠️ Mise a jour ZON-24 (2026-07-25) — perimetre requalifie
>
> Cette analyse a ete produite **avant** la suppression du code carte (ZON-21).
> Les scenarios ont ete realignes sur le modele zone (`mercure-streaming` →
> topic `chat/zone/<id>`, `authenticated-gameplay` → `/game/zone`), ce qui
> requalifie une partie du contenu ci-dessous :
>
> | Constat d'origine | Statut apres le pivot |
> |---|---|
> | Indexes composites pour `/api/map/*` (**jalon D**) | **Sans objet** — les endpoints n'existent plus. L'index partiel `idx_mob_alive_map` livre en 3a reste utile (les mobs restent rattaches a une carte, support de donnees des zones). |
> | Topic `map/move` tres charge (**§ Mercure**) | Remplace par les topics de zone : `chat/zone/<id>` (le plus repandu), `zone/<id>/event`, `dungeon/run/<id>`. Le raisonnement sur le fan-out reste valable, la volumetrie est a remesurer. |
> | Cout de `/game/map` et des APIs map (**§ Twig / APIs**) | Remplace par `/game/zone`, dont le cout est d'une autre nature : resolution d'arrivee de voyage, regeneration energie/PV, expedition, puis presence + evenements + filons de la zone. |
> | Collectors `/metrics`, pool Doctrine, hardening Mercure, scaling horizontal | **Inchanges** — independants du modele de monde. Ce sont les jalons A, B, E, F restants. |
>
> **A retenir** : les jalons **C** et **D** sont clos, mais les mesures qui les
> avaient motives portaient sur un profil de charge disparu. Une **passe de
> mesure sur le profil zone** est necessaire avant de conclure sur l'objectif
> « 200 joueurs simultanes » (tache 134).

---

## 1. Couverture des scenarios

| Scenario | Cible mesuree | Couche stressee |
|----------|---------------|-----------------|
| `guest-browsing` | 6 endpoints publics (home, login, register, demo, /health, /metrics) | FrankenPHP + Twig + collectors Doctrine de `/metrics` |
| `metrics-stress` | `/metrics` en boucle, sans think-time | Pool Doctrine + collectors PostgreSQL (`COUNT()` Player/Fight/Mob) |
| `mercure-streaming` | Abonnes SSE concurrents sur `chat/zone/<id>` | Hub Mercure integre (Caddy/FrankenPHP) + FD systeme + proxy Traefik |
| `authenticated-gameplay` | Login + dashboard + zone + inventaire | Firewall Symfony + sessions + petites API JSON + rendu Twig `/game/*` |

Les scenarios couvrent l'essentiel de la surface gameplay en lecture. Les
ecritures (POST mouvement, POST combat, POST achat HdV) restent un angle mort
documente dans la section "Prochaines etapes" du `README.md`.

---

## 2. Goulots probables par couche

### 2.1 Base de donnees (PostgreSQL + Doctrine ORM)

**Symptomes attendus** :

- `metrics-stress` : p95 `/metrics` qui derive au-dela de 500 ms quand
  `Player`/`Fight`/`Mob` grossissent.
- `authenticated-gameplay` : `authed_json_api_latency` p95 > 800 ms.
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

### Jalon F — Plan de scaling horizontal (priorite 6, effort L) — **audit fait (2026-07-26)**

**Objectif** : preparer le passage a plusieurs instances FrankenPHP derriere Traefik.

L'audit du 2026-07-26 a inspecte le depot pour chacun des points ci-dessous. Il en ressort
**cinq obstacles verifies**, dont un qui precede tous les autres.

#### F.0 — Le calendrier des taches n'etait consomme par personne ✅ **livre (2026-08-03)**

**Le constat (audit du 2026-07-26).** `symfony/scheduler` publie ses messages sur un transport
`scheduler_default` qu'il faut consommer avec `messenger:consume scheduler_default`. **Aucun
processus de ce type n'existait** : ni dans `compose.yaml`, ni dans `compose.prod.yaml`, ni dans
le `Dockerfile`, ni dans `frankenphp/docker-entrypoint.sh`. `config/packages/messenger.yaml`
declare `transports: []`, et il n'y avait pas de cron systeme dans le depot.

**La preuve** : `DefaultScheduleProvider` planifiait `api:mob:move` **toutes les minutes**
alors que la commande a ete supprimee par ZON-21. Un consommateur aurait leve
« Command not defined » toutes les 60 secondes depuis le pivot. Personne ne l'a vu.

Consequence : aucune tache recurrente ne tournait. Ni l'expiration des encheres et des commandes
— qui **rendent de l'escrow** —, ni les loyers, ni le restock des boutiques PNJ (le plancher T1
d'ECO-02), ni le respawn des filons, ni les saisons, ni le releve de masse monetaire d'ECO-15.

> Ce point n'etait pas un probleme de scaling : c'etait un probleme de production. Il est place
> ici parce que l'audit du jalon F l'a decouvert, et parce que **multiplier des instances qui
> executent zero tache planifiee ne produit rien**.

**Ce qui est livre.** Un service Docker `worker` (`compose.yaml`), a **une seule replique**,
lance par un entrypoint dedie (`frankenphp/scheduler-entrypoint.sh`) qui attend la base, attend
la fin des migrations du conteneur web, efface l'arriere de loyers, puis consomme le transport.
Le service n'a **aucune etiquette Traefik**, reste sur le reseau `internal`, et remplace la sonde
de sante HTTP heritee de l'image par une sonde de vivacite du processus.

L'activation ne demande **aucune etape manuelle** : `scripts/deploy.sh` releve le worker apres
les migrations et verifie que le calendrier est effectivement consomme, en le disant fort si ce
n'est pas le cas. Le cablage entier est garde en CI par
`tests/Unit/Scheduler/SchedulerWorkerDeploymentTest.php` (12 assertions, sans Docker).

#### Mode d'emploi de l'activation — et comment chaque piege est desamorce

L'activation n'etait pas qu'un service a ajouter : quatre pieges la rendaient dangereuse telle
quelle. Ils sont tous tenus par du code versionne, et non par une procedure a suivre.

**Piege 1 — l'arriere de loyers.** `PlayerHouse::extendRent()` et `ShopRentService::extend()`
avancent l'echeance de sept jours **a partir de l'echeance precedente**, pas a partir de
maintenant, et chaque execution ne rattrape **qu'une periode**. Comme `app:house:rent` et
`app:shop:rent` n'avaient jamais tourne, toutes les echeances etaient dans le passe : brancher le
planificateur tel quel aurait preleve **une semaine de loyer par jour** a chaque proprietaire
jusqu'a rattrapage — un mois de prelevements quotidiens pour six mois d'arriere, puis mise en
sommeil des demeures et fermeture des echoppes insolvables.

Personne n'avait contracte cette dette. L'entrypoint du worker l'efface **avant** toute
consommation, a chaque demarrage :

```sh
php bin/console app:economy:rent-backlog-reset --min-periods=2
```

Le seuil est ce qui rend l'appel automatique possible. Sans lui, un redemarrage a 00 h 10
annulerait une echeance tombee a 00 h 00 que la tache de 00 h 15 s'appretait a prelever : le
loyer ne rentrerait **jamais**. Avec `--min-periods=2`, seul un retard d'au moins quatorze jours
est efface — en regime normal le planificateur preleve tous les jours, donc la commande ne trouve
rien. Un tel retard ne peut venir que d'une interruption longue, c'est-a-dire exactement de la
dette que personne n'a contractee.

Lancee a la main sans option, la commande garde son comportement d'origine (tout l'arriere echu) :

```bash
docker compose exec php php bin/console app:economy:rent-backlog-reset --dry-run  # mesurer
docker compose exec php php bin/console app:economy:rent-backlog-reset            # effacer
```

**Piege 2 — l'entrypoint.** `frankenphp/docker-entrypoint.sh` declenche son bloc d'installation
pour tout `$1` valant `frankenphp`, `php` ou `bin/console` : migrations Doctrine, `cache:clear`,
`cache:warmup`, `tailwind:build`, `asset-map:compile`. Un worker lance par
`command: php bin/console messenger:consume …` **rejouerait donc les migrations en concurrence avec
le conteneur web**, et refarait les assets a chaque redemarrage.

Le service passe donc par `frankenphp/scheduler-entrypoint.sh`, installe dans l'image sous
`/usr/local/bin/scheduler-entrypoint` — **hors de `/app`**, parce que le stage de prod fait
`rm -Rf frankenphp/`. Il ne migre pas et ne compile aucun asset : il *attend* que le conteneur
web ait fini (`depends_on: php: service_healthy`, plus une verification de
`doctrine:migrations:up-to-date`), efface l'arriere, puis consomme.

**Piege 3 — le nombre de repliques.** Tant que F.1 (verrou de calendrier) n'est pas fait,
**exactement une** replique du worker, quel que soit le nombre de repliques web. `compose.yaml`
fige `deploy.replicas: 1` et un test le verifie. **Ne jamais lancer
`docker compose up --scale worker=N` avec N > 1** : c'est le seul chemin qui contourne encore la
contrainte, et ses degats sont economiques et irreversibles.

**Piege 4 — la sonde de sante heritee** (trouve a l'implementation). L'image porte
`HEALTHCHECK curl -f http://localhost:2019/metrics`, l'admin de Caddy. Le worker ne sert pas de
HTTP : sans remplacement il serait **toujours** declare malsain, et `docker compose up --wait`
ferait echouer **chaque deploiement du site**. La sonde est donc remplacee par une vivacite de
processus (`/proc/1/cmdline` contient `messenger:consume`), ce qui a l'avantage de verifier la
bonne chose : non pas que le conteneur tourne, mais que le calendrier est consomme.

- [x] Effacer l'arriere de loyers (piege 1) — automatique, seuil a 2 periodes
- [x] Service `worker` dans `compose.yaml` + `compose.prod.yaml`, entrypoint dedie (piege 2)
- [x] **Exactement une** replique de ce worker (piege 3)
- [x] Sonde de sante propre au worker (piege 4)
- [x] `scripts/deploy.sh` releve le worker apres migrations et verifie la consommation
- [x] Garde-fou CI : `tests/Unit/Scheduler/SchedulerWorkerDeploymentTest.php`
- [ ] Verifier apres 24 h en production : un seul releve `gils_supply_snapshot`, un seul
      prelevement de loyer, un restock horaire, les encheres echues rendues

**Ce que le premier demarrage va faire, et qu'il faut regarder.** Le monde est fige depuis des
mois : le premier tour de calendrier va expirer d'un coup toutes les encheres et commandes de
craft echues (donc **rendre l'escrow** correspondant), reapprovisionner les boutiques PNJ, et
liberer les filons. C'est voulu et sain, mais c'est un evenement economique visible par les
joueurs. La rotation hebdomadaire, elle, attendra le lundi suivant : rien n'est rejoue.

```bash
docker compose -f compose.yaml -f compose.prod.yaml logs -f worker
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console app:economy:rent-backlog-reset --dry-run   # doit rester vide
```

#### F.1 — Le calendrier n'a pas de verrou ⚠️ **desormais la seule chose qui separe du scaling**

`Schedule::lock()` n'est pas appele, et `symfony/lock` n'est meme pas une dependance. Maintenant
qu'un consommateur existe, **c'est le point qui interdit la deuxieme replique** : chaque tache se
declencherait **N fois**. Les degats sont economiques et irreversibles : recompenses de saison
versees N fois, loyers preleves N fois, et N releves de masse monetaire par jour — ce qui
fausserait la tendance d'ECO-15 elle-meme.

En attendant, la contrainte est tenue par le deploiement plutot que par convention :
`deploy.replicas: 1` dans `compose.yaml`, verifie en CI par `SchedulerWorkerDeploymentTest`. Cela
couvre le chemin nominal (`scripts/deploy.sh`, le CD) ; cela ne couvre **pas** un
`docker compose up --scale worker=2` tape a la main.

- [ ] `composer require symfony/lock`, puis `->lock($lockFactory->createLock('scheduler'))` — avec
      un store **partage** (`DoctrineDbalStore` sur la base, ou Redis une fois le jalon A fait) :
      un verrou de fichier serait local au conteneur et ne verrouillerait rien entre repliques
- [x] Contrainte de replique unique tenue par le deploiement et verifiee en CI

#### F.2 — Les sessions sont sur disque local

`config/packages/framework.yaml` declare `session: true` sans `handler_id` : Symfony retombe sur
le stockage fichier, dans le conteneur. Derriere un round-robin, un joueur perd sa session a
chaque requete servie par une autre replique.

- [ ] `session.handler` sur Redis — **prerequis du jalon A**

#### F.3 — Le cache applicatif est sur disque local

`config/packages/cache.yaml` ne declare aucun adaptateur : le pool `cache.app` est en
`filesystem`. Les collectors `/metrics` du **jalon C** mettent en cache avec un TTL de 10 s ;
avec N repliques, ce sont N caches independants, donc N fois plus de collectes qu'attendu — le
gain du jalon C se dilue exactement au rythme ou l'on scale.

- [ ] `cache.app` sur Redis — **meme prerequis que F.2**

#### F.4 — Mercure est un hub local a chaque conteneur

`frankenphp/Caddyfile` configure le transport Mercure sur `{$MERCURE_TRANSPORT_PATH:/data/mercure.db}`,
un fichier Bolt **local au conteneur**. Avec N repliques, un abonne connecte a la replique 1 ne
recevra jamais une publication faite par la replique 2.

C'est l'obstacle le plus visible pour le joueur, et il echoue **en silence** : le chat de zone,
les evenements de zone, les donjons de groupe et les barres de boss se scinderaient en N mondes
paralleles sans qu'aucune erreur ne soit levee.

- [ ] Hub Mercure externe (standalone ou `cloud.mercure.rocks`), decouple des repliques

#### Et ensuite

- [ ] Traefik : round-robin sur 2 a 4 repliques FrankenPHP
- [ ] Re-run **les 4 scenarios** a 200 VUs avec 2 repliques, compare a la baseline mono-instance

**Gain attendu** : capacite lineaire avec le nombre de repliques. Conditionne par les jalons A-E
— sinon on duplique des goulots au lieu d'augmenter la capacite. **F.0 est livre** : les taches
planifiees s'executent, ce qui rend le reste du jalon F pertinent pour la premiere fois.

**Garde-fous livres** :

- `tests/Unit/Scheduler/ScheduledCommandTest.php` — toute commande est planifiee ou declaree
  manuelle, et toute commande planifiee existe. Les sept commandes recurrentes qui n'etaient
  declarees nulle part (`app:auction:expire`, `app:craft-order:expire`, `app:harvest:respawn`,
  `app:shop:restock`, `app:house:rent`, `app:shop:rent`, `app:invasion:tick`) sont dans le
  calendrier — et desormais **actives**.
- `tests/Unit/Scheduler/SchedulerWorkerDeploymentTest.php` — le cablage de deploiement : un
  service consomme bien `scheduler_default`, l'arriere est efface avant (et seulement avec un
  seuil), l'entrypoint ne rejoue ni migrations ni assets et est installe dans l'image, replique
  unique, aucune route publique, sonde de sante remplacee, et le calendrier reste sans etat.

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
| **D — Indexes composites + refactor map** | **✅ Termine**, puis **sans objet** (ZON-24) | 3a (`idx_mob_alive_map` chevauche) + 3c (refactor `findByMapWithMonster`) + 3f (cloture analytique : `idx_player_map_coords` non actionable car coords sont une string filtree en PHP). Les endpoints `/api/map/*` qui motivaient ce jalon ont disparu avec ZON-21 ; seul `idx_mob_alive_map` reste utile. |
| E — Hardening Mercure | ⏳ A faire | — |
| **F — Scaling horizontal** | 🔍 Audit fait (2026-07-26), **F.0 livre** (2026-08-03) | 5 obstacles verifies (F.0 a F.4). **F.0 etait bloquant et anterieur au scaling** : aucun processus ne consommait le calendrier des taches, donc aucune tache recurrente ne tournait. **Resolu** : service `worker` a replique unique (`compose.yaml`), entrypoint dedie, arriere de loyers efface au demarrage, verification dans `scripts/deploy.sh`. Garde-fous `ScheduledCommandTest` + `SchedulerWorkerDeploymentTest`. Restent F.1 a F.4 pour le scaling proprement dit — **F.1 (verrou) est ce qui interdit la 2e replique**. |
| **Z — Passe de mesure sur le profil zone** | ⏳ **A faire, prerequis de l'objectif 200 joueurs** | Scenarios realignes par ZON-24 ; aucun run n'a encore ete effectue sur le profil zone. |

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
