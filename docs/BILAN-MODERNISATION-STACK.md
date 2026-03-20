# Bilan de modernisation de la stack Amethyste-Idle

> Realise le 9 mars 2026

---

## Versions avant / apres

| Composant | Avant | Apres |
|---|---|---|
| Doctrine ORM | 2.20.2 | **3.6.2** |
| Doctrine DBAL | 3.9.4 | **4.4.2** |
| Doctrine Persistence | 3.4.0 | **4.1.1** |
| Doctrine Bundle | 2.13.2 | **2.18.2** |
| Gedmo Extensions | 3.19.0 | **3.21.0** |
| Stof Bundle | 1.13.0 | **1.15.3** |
| Tailwind CSS | 3.4.17 (via Node.js) | **4.1.0** (binaire standalone) |
| Tailwind Bundle | 0.8.0 | **0.10.1** |
| Symfony (composants) | 7.2.0–7.2.4 | **7.2.9** |
| Symfony UX | 2.23–2.24 | **2.32** |
| Twig | 3.20 | **3.23** |
| Recherche de cellules | Typesense 4.9.3 | **PostgreSQL + cache Symfony** |
| Taches planifiees | cron/cron-bundle 3.1.1 | **symfony/scheduler 7.2.3** |
| Node.js | Present dans l'image Docker | **Supprime** |

---

## 1. Migration Doctrine ORM 3.6 / DBAL 4.4

**Objectif** : passer a Doctrine ORM 3 (maintenance-only pour ORM 2 a partir de mars 2026) et DBAL 4.

**Changements realises :**

- **22 entites** : `GeneratedValue(strategy: "AUTO")` remplace par `strategy: "IDENTITY"`
- **3 entites** (Map, Player, Fight) : remplacement de `PersistentCollection` par l'interface `Collection`
- **2 entites** (Map, World) : ajout d'un constructeur pour initialiser les collections avec `new ArrayCollection()`
- **`doctrine.yaml`** : retrait des options depreciees (`use_savepoints`, `report_fields_where_declared`, `validate_xml_mapping`, `identity_generation_preferences`)
- **`RespawnedHandler.php`** : `PublisherInterface` (deprecie) remplace par `HubInterface`
- Packages supprimes automatiquement : `doctrine/cache`, `doctrine/common`

**Fichiers modifies :**

- `config/packages/doctrine.yaml`
- `src/Entity/App/*.php` (17 fichiers)
- `src/Entity/Game/*.php` (7 fichiers)
- `src/GameEngine/Realtime/Map/RespawnedHandler.php`
- `composer.json`

---

## 2. Migration Tailwind CSS v3 → v4.1

**Objectif** : passer a Tailwind v4 avec sa configuration CSS-native et supprimer la dependance a Node.js.

**Changements realises :**

- **`assets/styles/app.css`** : remplacement de `@tailwind base/components/utilities` par `@import "tailwindcss"` + bloc `@theme` avec les custom properties `--color-primary-*`
- **Suppression** de `tailwind.config.js` et `postcss.config.js`
- **`symfonycasts_tailwind.yaml`** : mise a jour vers `binary_version: v4.1.0`
- **`symfonycasts/tailwind-bundle`** : upgrade de v0.8 a v0.10.1

**Fichiers supprimes :**

- `tailwind.config.js`
- `postcss.config.js`

---

## 3. Suppression de Node.js

**Objectif** : retirer Node.js de l'image Docker (present uniquement pour Tailwind).

**Changements realises :**

- **`Dockerfile`** : retrait des sections `nodejs`, `npm`, `nodesource` (lignes d'installation supprimees)
- **Suppression** de `package.json`, `package-lock.json`, `node_modules/`

**Impact** : image Docker significativement plus legere, build plus rapide.

---

## 4. Correction de la configuration Mercure

**Objectif** : activer Turbo Streams et supprimer l'URL Mercure codee en dur.

**Changements realises :**

- **`assets/controllers.json`** : `mercure-turbo-stream` passe de `enabled: false` a `enabled: true`
- **`assets/js/map/move-listener.js`** : URL Mercure lue depuis `document.body.dataset.mercureUrl` au lieu du hardcode `https://amethyste-idle.local:8243`
- **`config/packages/twig.yaml`** : ajout d'une variable globale `mercure_url` alimentee par `%env(MERCURE_PUBLIC_URL)%`
- **`templates/base.html.twig`** : ajout de `data-mercure-url="{{ mercure_url }}"` sur le `<body>`

---

## 5. Controller Stimulus Mercure

**Objectif** : remplacer le script brut `move-listener.js` par un controller Stimulus propre.

**Fichier cree :**

- **`assets/controllers/map_mercure_controller.js`** : controller Stimulus avec :
  - Connexion SSE Mercure (topics `map/move` et `map/respawn`)
  - Animation du deplacement case par case cote client (200ms/case via CSS transition)
  - Declenchement du re-render Live Component apres l'animation
  - Reconnexion automatique via EventSource

**Fichier modifie :**

- **`assets/app.js`** : retrait de l'import `./js/map/move-listener.js`

---

## 6. Refactoring du systeme de deplacement

**Objectif** : eliminer le `usleep(250ms)` dans le Messenger handler, supprimer le polling Live Component, et animer le deplacement cote client.

### Avant (anti-patterns)

```
Clic → Live Component move() → Dispatch PlayerMoveMessage
→ Handler: traite 1 case → usleep(250ms) → re-dispatch pour la case suivante
→ Mercure: publie 1 event par case
→ JS: recoit SSE → clique un bouton cache → Live Component updateCoordinates → re-render HTTP complet
→ Repeter pour chaque case du chemin
```

- Le worker Messenger bloque pendant 250ms par case (20 cases = 5 secondes de blocage)
- Chaque case genere 1 SSE + 1 requete HTTP complete
- Polling du Live Component toutes les 200ms

### Apres (architecture propre)

```
Clic → Live Component move() → Dispatch PlayerMoveMessage
→ Handler: traite TOUT le chemin d'un coup, sauvegarde la position finale
→ Mercure: publie 1 SEUL event avec le chemin complet (array de cells)
→ Stimulus: recoit SSE → anime le deplacement case par case cote client (CSS transition)
→ Apres animation: Live Component updateCoordinates → re-render pour les nouvelles cellules visibles
```

- Le worker Messenger ne bloque plus du tout
- 1 seul event Mercure par deplacement (au lieu de N)
- Zero polling cote Live Component
- Animation fluide cote client sans requetes serveur
- Detection de mob sur le chemin : le handler s'arrete a la case du mob et le chemin publie est tronque

**Fichiers modifies :**

- `src/GameEngine/Movement/Handler/PlayerMoveMessageHandler.php` : traitement du chemin complet, suppression `usleep` et re-dispatch
- `src/GameEngine/Realtime/Map/MovedHandler.php` : ajout de `movePath()` pour publier un chemin complet
- `src/GameEngine/Realtime/Map/MovedPlayerHandler.php` : ajout de `movePlayerPath()`
- `src/Twig/Components/Map.php` : retrait du polling (`moveQueue`, `isMoving`, `lastMoveTime`, `processNextMove`, `checkAndProcessNextMove`)
- `templates/components/Map.html.twig` : integration du controller Stimulus, retrait du polling `data-poll`

---

## 7. Remplacement de Typesense par PostgreSQL

**Objectif** : supprimer le service Docker Typesense (sur-ingenierie pour une requete spatiale simple).

**Changements realises :**

- **`src/SearchEngine/CellSearchEngine.php`** : nouveau moteur base sur Doctrine/PostgreSQL + cache Symfony (`cache.app`, TTL 1h). Charge les cellules depuis le champ `fullData` des entites `Area`, filtre par rayon
- **Suppression** de `src/SearchEngine/TypeSenseClient.php`, `SearchEngine.php`, `SearchEngineInterface.php`
- **Suppression** des commandes CLI `IndexCellCommand.php` et `ResetTypeSenseCommand.php`
- **`composer.json`** : retrait de `typesense/typesense-php` (+ 12 dependances transitives retirees automatiquement)
- **`compose.yaml`** : suppression du service `typesense` et du volume `typesense_data`
- **`compose.override.yaml`** : suppression de la section typesense (port 8109)
- **`compose.prod.yaml`** : suppression des variables `TYPESENSE_*`
- **`.env`** : variables `TYPESENSE_*` commentees

---

## 8. Remplacement de cron-bundle par Symfony Scheduler

**Objectif** : utiliser le composant natif Symfony au lieu d'un bundle tiers.

**Changements realises :**

- **`src/Scheduler/DefaultScheduleProvider.php`** (cree) : `ScheduleProviderInterface` avec `#[AsSchedule]`, tache `api:mob:move` toutes les minutes via `RecurringMessage::cron()`
- **`config/bundles.php`** : retrait de `CronCronBundle`
- **`fixtures/cron_job.yaml`** (supprime) : ancienne fixture pour le CronBundle
- **`composer.json`** : remplacement de `cron/cron-bundle` par `symfony/scheduler: 7.2.*`

---

## 9. Dependances evaluees et conservees

| Package | Decision | Raison |
|---|---|---|
| `symfony/css-selector` | **A réévaluer** | `TmxCssGeneratorCommand` supprimée — vérifier s'il reste un autre usage |
| `symfony/dom-crawler` | **Conserve** | Utilise dans `TerrainImportCommand` (import de cartes TMX) |
| `stof/doctrine-extensions-bundle` | **Conserve** | Uniquement `TimestampableEntity` (20 entites), compatible ORM 3, stof v1.15.3 |

---

## 10. Infrastructure Docker

### Avant : 4 services

- `php` (FrankenPHP + Node.js)
- `database` (PostgreSQL 16)
- `typesense`
- `watcher_async_move_consumer` (worker Messenger dedie aux deplacements)

### Apres : 2 services + Traefik

- `php` (FrankenPHP, zero Node.js)
- `database` (PostgreSQL 16)

Les services `typesense` et `watcher_async_move_consumer` ont ete supprimes. Les deplacements sont traites de facon synchrone par `PlayerMoveProcessor` dans la requete HTTP (1 SELECT mobs + 1 UPDATE joueur).

---

## 11. Stack finale

**Backend :**

- PHP 8.3.30
- Symfony 7.2.9
- FrankenPHP (Caddy) avec Mercure integre
- PostgreSQL 16
- Doctrine ORM **3.6.2** / DBAL **4.4.2** / Persistence **4.1.1**
- Symfony Scheduler 7.2.3
- Gedmo Doctrine Extensions 3.21.0

**Frontend :**

- Twig 3.23
- Tailwind CSS **v4.1.0** (binaire standalone, config CSS-native)
- Stimulus 2.32 (controllers JS)
- Turbo Streams via Mercure (active)
- Symfony UX Live Component 2.32
- AssetMapper (importmap, zero bundler)

**Infrastructure :**

- Docker : 2 services (FrankenPHP + PostgreSQL) + Traefik externe
- Zero Node.js dans l'image
- Zero service de recherche externe
- Image Docker allegee

**Temps reel :**

- Mercure SSE integre dans Caddy (server-to-client broadcast)
- Turbo Streams pour les mises a jour DOM automatiques
- Controller Stimulus dedie pour la carte (animation cote client)
- HTTP standard pour les actions joueur (client-to-server)

---

## 12. Points d'attention pour la suite

- **Advisories Symfony** : 3 advisories sur `symfony/http-foundation` ignorees en dev (`PKSA-365x-2zjk-pt47`, `PKSA-b35n-565h-rs4q`, `PKSA-t4rz-hp2g-57t1`). A resoudre lors de la migration vers Symfony 7.3 ou 8.0
- **Worker Messenger** : le service `watcher_async_move_consumer` a ete supprime. Les deplacements sont traites de facon synchrone par `PlayerMoveProcessor`.
- **Commande `api:mob:move`** : referencee dans le Scheduler mais non trouvee dans le code source. A verifier / creer
- **Bind mount Docker** : en dev, les fichiers edites sur l'host ne se propagent pas toujours dans le container. Un rebuild de l'image (`docker compose build --no-cache`) resoudra ce probleme
- **Rebuild necessaire** : le Dockerfile a ete modifie (retrait Node.js). Lancer `docker compose build` pour appliquer
