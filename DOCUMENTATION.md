# Amethyste-Idle — Documentation Technique

> MMORPG en navigateur web d'inspiration rétro, construit avec Symfony 7.4, PHP 8.4, FrankenPHP, PostgreSQL 17, Mercure et Tailwind CSS.

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Stack technique](#2-stack-technique)
3. [Architecture du projet](#3-architecture-du-projet)
4. [Infrastructure & Déploiement](#4-infrastructure--déploiement)
5. [Domaines & Routage Traefik](#5-domaines--routage-traefik)
6. [Modèle de données](#6-modèle-de-données)
7. [Moteur de jeu (GameEngine)](#7-moteur-de-jeu-gameengine)
8. [Système de combat](#8-système-de-combat)
9. [Système de carte](#9-système-de-carte)
10. [Système d'inventaire & équipement](#10-système-dinventaire--équipement)
11. [Système de compétences](#11-système-de-compétences)
12. [Quêtes & PNJ](#12-quêtes--pnj)
13. [Temps réel (Mercure)](#13-temps-réel-mercure)
14. [Moteur de recherche (Typesense)](#14-moteur-de-recherche-typesense)
15. [Authentification & Sécurité](#15-authentification--sécurité)
16. [Frontend (Twig, Tailwind, Stimulus)](#16-frontend-twig-tailwind-stimulus)
17. [Commandes CLI](#17-commandes-cli)
18. [Fixtures & Données de test](#18-fixtures--données-de-test)
19. [Internationalisation](#19-internationalisation)
20. [Terrain & Tiled Map Editor](#20-terrain--tiled-map-editor)

---

## 1. Vue d'ensemble

**Amethyste-Idle** est un **MMORPG en navigateur web** d'inspiration rétro. Le jeu puise ses influences dans les classiques du jeu de rôle et de l'aventure :

- **The Legend of Zelda** (NES, SNES, Game Boy) — exploration, donjons, interactions avec le monde
- **Final Fantasy** — combats au tour par tour, système de sorts élémentaires, materia
- **Warcraft** et autres références — univers fantasy, récolte de ressources, artisanat

### Principes fondamentaux

- **Vue isométrique** en 2D avec des tiles 32×32 pixels
- **Déplacements sur cases carrées** avec pathfinding Dijkstra
- **Progression par arbres de talent** : pas de niveaux d'expérience propres au joueur ; la progression repose sur l'acquisition de compétences dans des arbres de talent organisés par domaine (combat, récolte, artisanat)
- **Cartes éditées via Tiled Map Editor** : les cartes sont conçues dans des fichiers TMX, puis importées dans l'application

### Fonctionnalités principales

- **Carte du monde** interactive avec pathfinding Dijkstra et déplacements synchrones
- **Combat tour par tour** avec système de timeline basé sur la vitesse, sorts élémentaires, critiques
- **Inventaire complet** : sac, banque, materia, équipement avec slots et bitmask
- **Arbres de talent** par domaine avec XP de domaine, pré-requis et bonus de stats
- **Système de quêtes** avec tracking de progression (monstres tués, etc.)
- **PNJ** avec dialogues à branches et conditions
- **Récolte et jobs** (mineur, herboriste, pêcheur, etc.)
- **Multijoueur en temps réel** via Mercure SSE (mouvements des joueurs, respawn de mobs, récolte)
- **Bilingue** FR/EN

---

## 2. Stack technique

| Couche | Technologie | Version |
|--------|------------|---------|
| **Langage** | PHP | 8.4 |
| **Framework** | Symfony | 7.4 (LTS) |
| **Serveur web** | FrankenPHP (Caddy) | 1.x |
| **Base de données** | PostgreSQL | 17 (Alpine) |
| **ORM** | Doctrine | 3.x |
| **Temps réel (SSE)** | Mercure | Intégré dans Caddy |
| **Frontend** | Twig + Tailwind CSS | 4.1 |
| **Composants dynamiques** | Symfony UX Live Component + Turbo | |
| **Asset bundling** | Symfony AssetMapper (importmap) | |
| **Stimulus** | @symfony/stimulus-bundle | |
| **Containerisation** | Docker (multi-stage) | |
| **Reverse proxy** | Traefik | 2.11 |
| **TLS** | Let's Encrypt (TLS-ALPN-01) | |

---

## 3. Architecture du projet

```
amethyste-idle/
├── assets/                  # Frontend (JS, CSS, images tilesets)
│   ├── app.js               # Point d'entrée Stimulus
│   ├── controllers/          # Controllers Stimulus (CSRF, hello)
│   ├── js/map/               # Client Mercure pour la carte
│   └── styles/               # Tailwind + CSS carte + sprites
├── config/                  # Configuration Symfony
│   ├── packages/             # Bundles (doctrine, mercure, security, etc.)
│   ├── routes/               # Routes YAML
│   └── services.yaml        # Container DI
├── fixtures/                # Données de seed (YAML)
│   └── game/                 # Items, skills, spells, monsters
├── frankenphp/              # Configuration FrankenPHP/Caddy
│   ├── Caddyfile             # Config serveur web
│   ├── worker.Caddyfile      # Mode worker (prod)
│   └── docker-entrypoint.sh  # Script d'initialisation
├── public/                  # Document root
├── scripts/                 # Scripts utilitaires
├── src/                     # Code source PHP
│   ├── Command/              # 7 commandes CLI
│   ├── Controller/           # 27 controllers (home, game, fight, etc.)
│   ├── DataFixtures/         # Fixtures Doctrine
│   ├── Entity/               # 28 entités (User, Player, Mob, Item, etc.)
│   ├── Event/                # 21 événements (combat, carte)
│   ├── GameEngine/           # Cœur du moteur de jeu (52 fichiers)
│   ├── Helper/               # 11 services utilitaires
│   ├── SearchEngine/         # Client Typesense
│   ├── Security/             # Authenticator
│   ├── Transformer/          # Transformateurs de données
│   └── Twig/Components/      # 5 Live Components
├── templates/               # 84 templates Twig
├── terrain/                 # Fichiers Tiled (TMX, tilesets)
├── translations/            # i18n (fr, en)
├── compose.yaml             # Docker Compose principal
├── compose.prod.yaml        # Override production
├── compose.override.yaml    # Override développement
└── Dockerfile               # Image multi-stage FrankenPHP
```

### Pattern architectural

L'application suit une **architecture événementielle** (Event-Driven) :

1. Les controllers déclenchent des actions (attaque, déplacement, récolte)
2. Les actions produisent des **événements** (`PlayerAttackHitEvent`, `MobDeadEvent`, `PlayerMovedEvent`, etc.)
3. Des **EventSubscribers** réagissent : génération de loot, respawn, XP, mise à jour de quêtes, publications Mercure

---

## 4. Infrastructure & Déploiement

### Services Docker

| Service | Image | Rôle | Port interne |
|---------|-------|------|-------------|
| `php` | `app-php` (FrankenPHP) | Serveur web + Mercure | 80 |
| `database` | `postgres:17-alpine` | Base de données | 5432 |

### Réseaux Docker

- **`traefik-network`** (externe) : Connecte le service `php` au reverse proxy Traefik
- **`internal`** (bridge) : Communication entre les services internes (php ↔ database)

### Démarrage production

```bash
docker compose -f compose.yaml -f compose.prod.yaml build
docker compose -f compose.yaml -f compose.prod.yaml up -d

# Initialisation
docker compose exec php php bin/console tailwind:build
docker compose exec php php bin/console asset-map:compile
docker compose exec php php bin/console doctrine:schema:update --force
docker compose exec php php bin/console cache:clear
```

### Script de déploiement

Le script `scripts/deploy.sh` enchaîne les étapes nécessaires au déploiement. **Toutes les commandes applicables sont exécutées dans le conteneur `php`** (Symfony, maintenance, cache).

```bash
# Production (compose.yaml + compose.prod.yaml)
./scripts/deploy.sh --prod

# Développement (compose.yaml seul)
./scripts/deploy.sh --dev

# Mettre à jour composer.lock depuis le conteneur avant le build (optionnel)
./scripts/deploy.sh --prod --composer-update
```

**Étapes exécutées :**

1. **Construction et démarrage** : `docker compose up -d --build --wait` (attend que les services soient healthy).
2. **Page de maintenance** : activation dans le conteneur (`touch var/maintenance.flag`), désactivation automatique en fin de script (ou en cas d’interruption).
3. **Compilation des assets** : Tailwind + AssetMapper.
4. **Cache Symfony** (conteneur php) : `cache:clear` puis `cache:warmup`.
5. **État des services** : affichage de `docker compose ps`.

Pour exécuter une commande dans le conteneur php :  
`docker compose -f compose.yaml -f compose.prod.yaml exec php <commande>` (ex. `php bin/console cache:clear`, `composer update`).

**Prérequis :** `.env` configuré, Docker et Docker Compose disponibles.

### Démarrage développement

```bash
docker compose up --build -d
# compose.override.yaml est chargé automatiquement (ports locaux, xdebug)
```

### Environnement de développement et cache

Pour éviter les problèmes de cache (ancien JS, ancien PHP) :

1. **Forcer l’environnement dev** : dans `.env`, garder `APP_ENV=dev`. En dev, le cache applicatif est moins agressif et les assets sont servis avec des URLs versionnées.
2. **Vider le cache après modification du code** :
   ```bash
   php bin/console cache:clear
   # ou dans Docker :
   docker compose exec php php bin/console cache:clear
   ```
3. **Navigateur** : rechargement forcé (Ctrl+F5 / Cmd+Shift+R) ou onglet en navigation privée pour éviter le cache des scripts.

---

## 5. Domaines & Routage Traefik

| Domaine | Router Traefik | Usage |
|---------|---------------|-------|
| **`amethyste.best`** | `amethyste-site` | Site public (page d'accueil, inscription, connexion) |
| **`game.amethyste.best`** | `amethyste-game` | Espace de jeu (mode connecté) |
| **`api.amethyste.best`** | `amethyste-api` | API |
| **`amethyste.ryxeuf.fr`** | `amethyste-site` | Alias du site public |

Tous les domaines sont servis par le même service FrankenPHP. Le routage Symfony gère la distinction entre les sections (page d'accueil vs jeu vs API) via les routes PHP.

### Certificats TLS

Chaque router Traefik utilise le resolver `letsencrypt` (challenge TLS-ALPN-01 sur le port 443). Les certificats sont générés automatiquement et stockés dans `acme.json` sur le conteneur Traefik.

---

## 6. Modèle de données

### Diagramme des entités principales

```
User (1) ──── (N) Player (1) ──── (N) Inventory (1) ──── (N) PlayerItem
  │                 │                                          │
  │                 ├──── (N) DomainExperience                 ├── genericItem → Item
  │                 │           └── domain → Domain             ├── slots (N) → Slot
  │                 │                   └── skills (N) → Skill  └── gear (bitmask)
  │                 │
  │                 ├──── (N→N) Skill
  │                 ├──── (N) PlayerQuest → Quest
  │                 ├──── fight → Fight
  │                 └──── map → Map
  │
  │          Fight (1) ──── (N) Player
  │                ├──── (N) FightStatusEffect (effets actifs)
  │                └──── (N) Mob
  │                          ├── monster → Monster
  │                          │       └── monsterItems (N) → MonsterItem → Item
  │                          └── items (N) → PlayerItem (loot)
  │
  │          Map (1) ──── (N) Area (fullData JSON)
  │               ├──── (N) ObjectLayer (spots, coffres)
  │               ├──── (N) Pnj (dialogues JSON)
  │               └──── world → World
  │
  └── Item ──── spell → Spell
           └── requirements (N→N) → Skill
           └── domain → Domain
```

### Entités principales

| Entité | Description | Champs clés |
|--------|-------------|-------------|
| **User** | Compte utilisateur | email, username, roles, password |
| **Player** | Personnage jouable | name, life, maxLife, energy, hit (précision 0-100), speed, classType, coordinates, isMoving |
| **Mob** | Instance de monstre | monster (template), level, coordinates, life |
| **Fight** | Combat en cours | step, inProgress, players[], mobs[], statusEffects[] |
| **FightStatusEffect** | Effet de statut actif | fight, targetType, targetId, type, remainingTurns, power |
| **Inventory** | Conteneur d'objets | type (bag=1/materia=2/bank=3), size, gold |
| **PlayerItem** | Instance d'objet possédé | genericItem, gear (bitmask), nbUsages, slots[] |
| **Slot** | Emplacement materia | element, item_set (materia), item (équipement) |
| **Map** | Carte du monde | name, areaWidth, areaHeight, areas[], objectLayers[], mobs[] |
| **Area** | Zone de la carte | fullData (JSON des cellules), coordinates |
| **ObjectLayer** | Objet interactif | type (spot/chest/other), items, actions, movement |
| **Item** | Définition d'objet | name, type (stuff/gear/materia), gearLocation, effect, spell |
| **Monster** | Template de monstre | name, life, speed, attack (Spell), monsterItems[] |
| **MonsterItem** | Table de loot | monster, item, probability (float) |
| **Domain** | Domaine de compétence | title, skills[] |
| **Skill** | Compétence | title, requiredPoints, damage/heal/hit/critical/life bonuses, requirements[] |
| **Spell** | Sort | name, damage, heal, hit (précision 0-100), critical, element |
| **Pnj** | Personnage non-joueur | name, dialog (JSON), coordinates |
| **Quest** | Quête | (entité référencée, détails dans fixtures) |
| **DomainExperience** | XP par domaine | totalExperience, usedExperience, damage/heal/hit (bonus précision)/critical |
| **QueueRespawnMob** | File de respawn | monster, delay, coordinates, map |

### Traits partagés

- **`CharacterStatsTrait`** : `life` (int), `diedAt` (DateTime), `isDead()` — utilisé par Player et Mob
- **`CoordinatesTrait`** : `coordinates` (string "x.y"), `getX()`, `getY()` — utilisé par Player, Mob, Area, ObjectLayer, QueueRespawnMob
- **`TimestampableEntity`** : `createdAt`, `updatedAt` (Stof Doctrine Extensions)

---

## 7. Moteur de jeu (GameEngine)

Le moteur est organisé en sous-domaines dans `src/GameEngine/` :

```
GameEngine/
├── Fight/              # Système de combat
│   ├── Handler/        # Handlers d'actions (attaque, sort, objet)
│   ├── MobActionHandler     # IA des monstres (patterns, phases de boss)
│   ├── SpellApplicator      # Application sorts (résistances, statuts, berserk)
│   ├── StatusEffectManager  # Gestion effets de statut (DOT/HOT)
│   ├── CombatSkillResolver  # Résolution sorts disponibles par compétences
│   ├── ElementalSynergyCalculator  # Synergies élémentaires
│   ├── MateriaXpGranter    # XP materia à la mort d'un monstre
│   └── MateriaFusionManager # Fusion de materias
├── Gear/               # Équipement & materia
├── Generator/          # Génération d'items
├── Item/               # Résolution d'effets d'items
├── Job/                # Récolte et jobs
├── Map/                # Pathfinding Dijkstra
├── Mob/                # Génération de mobs
├── Movement/           # Traitement des déplacements
├── Player/             # Actions joueur, respawn, dialogues PNJ
├── Progression/        # XP et acquisition de compétences
├── Quest/              # Tracking de quêtes
└── Realtime/Map/       # Publications Mercure (temps réel)
```

### Événements du moteur

Le moteur repose sur **21 événements** organisés en deux catégories :

**Combat** : `ActionEvent`, `PlayerAttackHit/MissEvent`, `PlayerSpellHit/MissEvent`, `MobActionHit/MissEvent`, `ItemUsedEvent`, `MobDeadEvent`, `PlayerDeadEvent`, `FightLootedEvent`

**Carte** : `PlayerMovedEvent`, `MobMovedEvent`, `PlayerRespawnedEvent`, `MobRespawnedEvent`, `SpotHarvestEvent`, `SpotAvailableEvent`

---

## 8. Système de combat

### Flux de combat

1. Le joueur rencontre un mob sur la carte → `FightHandler` crée un `Fight`
2. La **timeline** est calculée par `FightTimelineHelper` : chaque combattant a un nombre de tours proportionnel à sa vitesse
3. Le joueur choisit une action : **Attaquer**, Sort, Objet, Fuir
4. `PlayerActionHandler` délègue au handler approprié (`PlayerAttackHandler`, `PlayerSpellHandler`, `PlayerItemHandler`)
5. `FightCalculator::hasAttackHit()` détermine si l'attaque touche (random vs hit chances)
6. `SpellApplicator` applique les dégâts/soins avec bonus de domaine, critiques (×1.5), résistances élémentaires et effets de statut
7. Si un mob meurt → `MobDeadEvent` → `LootGenerator` (items selon probabilités) + `MobDeathQueuing` (respawn en 10s, boss 1h) + `MateriaXpGranter` (XP materia)
8. Si le joueur meurt → `PlayerDeadEvent` → `PlayerRespawnHandler` (respawn à 50% HP)
9. Combat terminé → loot → `FightLootedEvent` → `FightCleaner` nettoie

### Chances de toucher (hit)

La stat `hit` représente la **précision** (chances de toucher), et non les dégâts. Elle est exprimée en pourcentage (0-100).

- **Attaque basique** : `hitChances = player.hit` (défaut : 50)
- **Sort** : `hitChances = spell.hit + bonusSkills.hit` (défaut sort : 75)
- **Mob** : `hitChances = monster.hit` (défaut : 20)

`FightCalculator::hasAttackHit(hitChances)` tire un nombre aléatoire entre 0 et 99. Si ce nombre est inférieur à `hitChances`, l'attaque touche. Sinon, elle rate.

Les bonus de `hit` proviennent des compétences débloquées (`Skill.hit`) et sont cumulés dans `DomainExperience.hit` via `SkillAcquiring`.

### Calcul des dégâts

Les dégâts sont calculés **uniquement si l'attaque touche** :

```
# 1. Vérification de précision (hit)
hitChances = spell.hit + domainExperience.hit   # (ou player.hit pour attaque basique)
hit = random(0-99) < hitChances                  # true = touche, false = rate

# 2. Calcul des dégâts (seulement si hit = true)
isCritical = random(0-99) < (spell.critical + domainExperience.critical)
damage = spell.damage + domainExperience.damage
if critical: damage *= 1.5
if elementalResistance: damage *= (1 - resistance)  # ex: 0.5 = 50% résistance
if berserk: damage *= 1.5  # bonus dégâts sous berserk
if burn: damage *= 0.75    # réduction dégâts sous brûlure
if shield: damage -= shieldAbsorb  # absorption du bouclier
targetLife = clamp(targetLife - damage, 0, maxLife)
```

> **Important** : `hit` = précision (chances de toucher). Les dégâts proviennent de `spell.damage`, des bonus de domaine, et des modificateurs (critiques, élémentaires, statuts).

### Éléments de sorts

`NONE`, `FIRE`, `WATER`, `EARTH`, `AIR`, `LIGHT`, `DARK`

### Synergies élémentaires

`ElementalSynergyCalculator` calcule des bonus de dégâts quand certaines combinaisons d'éléments sont présentes :

| Combo | Éléments | Nom | Effet |
|-------|----------|-----|-------|
| Feu + Eau | `FIRE` + `WATER` | Steam (Vapeur) | Bonus de dégâts |
| Terre + Air | `EARTH` + `AIR` | Tornado (Tornade) | Bonus de dégâts |
| Lumière + Ténèbres | `LIGHT` + `DARK` | Eclipse | Bonus de dégâts |
| Feu + Terre | `FIRE` + `EARTH` | Magma | Bonus de dégâts |

### Effets de statut

`StatusEffectManager` gère 8 types d'effets de statut appliqués pendant le combat. L'entité `FightStatusEffect` stocke les effets actifs par combat.

| Type | Catégorie | Effet |
|------|-----------|-------|
| `poison` | DOT | Dégâts sur la durée à chaque tour |
| `burn` | DOT | Dégâts de brûlure à chaque tour |
| `regeneration` | HOT | Soin sur la durée à chaque tour |
| `paralysis` | Contrôle | Empêche d'agir, empêche de fuir |
| `freeze` | Contrôle | Gèle la cible |
| `silence` | Contrôle | Empêche le lancement de sorts |
| `shield` | Défensif | Réduit les dégâts reçus (×0.5) |
| `berserk` | Offensif | Augmente les dégâts (×1.5), empêche de fuir |

Les fixtures (`StatusEffectFixtures`) définissent 11 effets incluant des variantes fortes (ex : poison fort, brûlure forte).

### Compétences de combat actives

`CombatSkillResolver` détermine les sorts de combat disponibles pour un joueur en fonction de ses compétences débloquées. Les compétences sont liées aux sorts via le champ `actions.combat.spell_slug` dans `SkillFixtures`.

**7 archétypes de combat** avec 3-4 compétences chacun formant une chaîne de progression :

| Archétype | Domaine | Description |
|-----------|---------|-------------|
| Pyromancien | Combat | Sorts de feu offensifs |
| Soldat | Combat | Attaques physiques puissantes |
| Soigneur | Combat | Sorts de soin |
| Défenseur | Combat | Capacités défensives et bouclier |
| Nécromancien | Combat | Sorts d'ombre et drain de vie |
| Druide | Combat | Sorts de nature et régénération |
| Mage blanc | Combat | Sorts de lumière et purification |

### Materia — XP et Fusion

- **`MateriaXpGranter`** : accorde de l'XP aux materias serties quand un monstre meurt (base 10 XP × niveau du monstre, boss ×5)
- **`MateriaFusionManager`** : système de fusion pour combiner deux materias en une materia supérieure

### Intelligence artificielle des monstres

`MobActionHandler` gère les actions des monstres avec un système d'IA configurable via des patterns JSON stockés dans `Monster::aiPattern`.

#### Format du pattern IA (`aiPattern`)

```json
{
  "sequence": ["attack", "spell", "attack"],
  "spell_chance": 0.3,
  "low_hp_heal": true,
  "danger_alert": true,
  "danger_message": "Le dragon prépare son souffle de feu !"
}
```

| Clé | Type | Description |
|-----|------|-------------|
| `sequence` | `string[]` | Séquence d'actions répétée en boucle |
| `spell_chance` | `float` | Probabilité (0-1) de lancer un sort plutôt qu'une attaque de base |
| `low_hp_heal` | `bool` | Si `true`, le monstre se soigne quand ses PV sont bas |
| `danger_alert` | `bool` | Si `true`, affiche une alerte de danger dans l'UI |
| `danger_message` | `string` | Message d'alerte affiché au joueur (boss uniquement) |

#### Sélection de sort

`MobActionHandler::resolveSpell()` choisit un sort parmi le pool de sorts du monstre (`Monster::spells`). Les boss utilisent une sélection basée sur les phases.

### Mécanique de boss

Les boss sont des monstres spéciaux avec des mécaniques avancées :

| Propriété | Boss | Monstre normal |
|-----------|------|----------------|
| Respawn | 1 heure (`MobDeathQueuing`) | 10 secondes |
| Fuite | Impossible | Possible |
| Phases | Oui (changement de pattern selon HP) | Non |
| Alerte danger | Oui (bannière dans l'UI) | Non |
| Indicateur difficulté | Étoiles (★) dans le template | Non |
| XP materia | ×5 | ×1 |

#### Boss : Dragon (exemple)

- **HP** : 80
- **Phases** : 3 (changement de comportement selon le % de vie restant)
- **Sorts** : 4 sorts de feu
- **Résistances élémentaires** : résistance au feu
- **Pattern** : séquences d'attaques variables par phase, alerte de danger

### Services de combat (Phase 3)

| Service | Rôle |
|---------|------|
| `SpellApplicator` | Application des dégâts/soins avec résistances élémentaires, berserk, burn, shield, effets de statut |
| `StatusEffectManager` | Gestion DOT/HOT, vérification de statut, application des effets |
| `CombatSkillResolver` | Résolution des sorts disponibles selon les compétences du joueur |
| `ElementalSynergyCalculator` | Calcul des bonus de synergies élémentaires (4 combos) |
| `MateriaXpGranter` | Attribution d'XP aux materias serties à la mort d'un monstre |
| `MateriaFusionManager` | Fusion de materias |
| `MobActionHandler` | IA des monstres : patterns séquentiels, soin bas HP, phases de boss |
| `MobDeathQueuing` | File de respawn avec délai spécial boss (1h) |

---

## 9. Système de carte

### Architecture

1. **Tiled Map Editor** : les cartes sont éditées dans `.tmx` (60×60 tuiles de 32×32 px)
2. **Import** : `app:terrain:import` parse les TMX → JSON avec couches, tilesets, collisions, object layers. Options : `--validate`, `--sync-entities`, `--dry-run`, `--stats`
3. **Indexation** : `app:index:cell` insère les cellules dans Typesense
4. **Rendu PixiJS** : `map_pixi_controller.js` charge les cellules visibles (rayon 25) via `/api/map/cells` avec rendu GPU
5. **CSS** : `app:tmx:generate-css` génère les classes CSS pour les sprites (`world-1.css`)

### Rendu PixiJS (map_pixi_controller.js)

Le rendu de la carte utilise PixiJS v8 avec les optimisations suivantes :
- **Tile sprite pool** : réutilisation des sprites via `_acquireSprite()` / `_releaseSprite()`
- **Entity container pool** : réutilisation des conteneurs d'entités
- **Texture cache** : cache par GID, par marker, par sprite sheet
- **Spatial hash** : lookup O(1) des entités par position (interactions PNJ)
- **Lazy loading** : chargement incrémental des cellules et entités
- **Pruning** : suppression automatique des cellules distantes (3× radius)
- **Camera lerp** : suivi fluide du joueur avec facteur 0.15
- **Camera shake** : tremblement paramétrable (portails, combats)
- **Cycle jour/nuit** : overlay ambiant selon l'heure réelle (jour/aube/crépuscule/nuit)
- **Particules** : effets visuels pour les portails et interactions

### Système de sprites (SpriteAnimator)

- **Format** : RPG Maker VX (3 colonnes × 4 lignes par personnage)
- **Multi-sheet** : support 12×8 avec `charIndex` (0-7) pour 8 personnages par sheet
- **Idle breathing** : oscillation subtile en Y quand le personnage est immobile
- **Émotes** : bulles au-dessus des sprites (!, ?, ♥, ✦, ~, ♪, …, ★)
- **États** : idle, walk, interact avec vitesse adaptative
- **Registre** : `SpriteConfigProvider` centralise 30+ sprites avec métadonnées (catégorie, animations, taille)

### Pathfinding

L'algorithme de **Dijkstra** calcule le chemin le plus court :
- Graphe : chaque cellule est un nœud, les arêtes sont les mouvements (N/S/E/W) avec coût
- Obstacles : murs (`movement = -1`), collisions directionnelles (bitmask N/S/E/W)
- Abilities : certaines cases nécessitent escalade ou natation (bitmask)

### Déplacement synchrone

1. Clic sur une cellule → `Map::move()` → calcul Dijkstra → `PlayerMoveProcessor::processMove()`
2. Le processeur charge les mobs du chemin (1 requête), détecte le premier mob, tronque le chemin si nécessaire
3. Mise à jour de la position finale du joueur en base (1 seul flush)
4. Si mob détecté : déclenchement de combat
5. Publication Mercure (`map/move`) du chemin complet → animation côté client

### Cartes disponibles (World 1)

| Zone | Fichier TMX | Description |
|------|-------------|-------------|
| (0,0) à (2,2) | `world-1-map-X-Y.tmx` | 9 zones de carte ouvertes |
| Intérieur | `world-1-house-1.tmx` | Maison |
| Souterrain | `world-1-cave-1.tmx` | Grotte |

---

## 10. Système d'inventaire & équipement

### Types d'inventaire

| Type | Constante | Taille par défaut | Description |
|------|-----------|-------------------|-------------|
| Sac | `TYPE_BAG = 1` | 100 | Inventaire principal |
| Materia | `TYPE_MATERIA = 2` | 50 | Stockage de materias |
| Banque | `TYPE_BANK = 3` | 1000 | Stockage longue durée |

### Types d'items

| Type | Description | Exemples |
|------|-------------|---------|
| `stuff` | Consommable | Potions, champignons, matériaux de craft |
| `gear` | Équipement | Épées, armures, bottes, chapeaux |
| `materia` | Materia sertissable | Boule de feu, Soin, Lame d'air |

### Système d'équipement (bitmask)

Chaque emplacement est un bit dans le champ `gear` de `PlayerItem` :

| Emplacement | Constante | Valeur |
|-------------|-----------|--------|
| Tête | `GEAR_HEAD` | `0b1` |
| Cou | `GEAR_NECK` | `0b10` |
| Torse | `GEAR_CHEST` | `0b100` |
| Mains | `GEAR_HAND` | `0b1000` |
| Arme principale | `GEAR_MAIN_WEAPON` | `0b10000` |
| Arme secondaire | `GEAR_SIDE_WEAPON` | `0b100000` |
| Ceinture | `GEAR_BELT` | `0b1000000` |
| Jambes | `GEAR_LEG` | `0b10000000` |
| Pieds | `GEAR_FOOT` | `0b100000000` |
| Anneau 1 | `GEAR_RING_1` | `0b1000000000` |
| Anneau 2 | `GEAR_RING_2` | `0b10000000000` |
| Épaules | `GEAR_SHOULDER` | `0b100000000000` |

### Materia & Slots

- Chaque équipement peut avoir des **Slots** pour sertir des materias
- Un `Slot` a un `element` et référence le `PlayerItem` materia serti (`item_set`)
- Le sertissage est géré par `MateriaGearSetter` (vérification des pré-requis, équipement actif)

---

## 11. Système de compétences (Arbres de talent)

> **Philosophie de progression** : Amethyste-Idle n'utilise **pas** de système de niveaux d'expérience propres au joueur (pas de "level up" global). La progression repose entièrement sur des **arbres de talent** organisés par domaine. Le joueur accumule de l'XP spécifique à chaque domaine en pratiquant les activités associées, puis investit cette XP pour débloquer des compétences dans l'arbre de talent correspondant.

### Éléments du jeu

8 éléments + physique (none) :

| Élément | Slug | Description |
|---------|------|-------------|
| Physique | `none` | Attaques non-élémentaires |
| Feu | `fire` | Destruction, rage, pièges |
| Eau | `water` | Soins, contrôle, potions |
| Air | `air` | Foudre, précision, vitesse |
| Terre | `earth` | Défense, géomancie, minéraux |
| Métal | `metal` | Arts martiaux, forge, ingénierie |
| Bête | `beast` | Nature, animaux, cuirs |
| Lumière | `light` | Sacré, soins, jugement |
| Ombre | `dark` | Nécromancie, assassinat, malédictions |

### Domaines disponibles (32 domaines)

| Catégorie | Élément | Domaines | Archétypes |
|-----------|---------|----------|------------|
| **Combat** | Feu | Pyromancien, Berserker, Artificier | DPS magique, DPS CaC, Support offensif |
| | Eau | Hydromancien, Guérisseur, Marémancien | DPS magique, Healer, Support défensif |
| | Air | Foudromancien, Archer, Vagabond | DPS magique, DPS distance, Support/Évasion |
| | Terre | Géomancien, Défenseur, Gardien | DPS magique, Tank, Tank/Support |
| | Métal | Soldat, Chevalier, Ingénieur | DPS CaC, Tank lourd, Support technique |
| | Bête | Chasseur, Dompteur, Druide | DPS distance, Tank/Invocateur, Healer/Support |
| | Lumière | Paladin, Prêtre, Inquisiteur | Tank/Healer, Healer pur, DPS magique |
| | Ombre | Assassin, Nécromancien, Sorcier | DPS CaC, DPS magique/Invocateur, Support/Debuff |
| **Récolte** | Terre | Mineur | — |
| | Eau | Pêcheur | — |
| | Bête | Herboriste, Dépeceur | — |
| **Artisanat** | Métal | Forgeron | — |
| | Bête | Tanneur | — |
| | Eau | Alchimiste | — |
| | Terre | Joaillier | — |

### Compétences multi-domaines

- Une même compétence peut appartenir à **plusieurs domaines** (relation ManyToMany)
- Si un joueur débloque une compétence dans un domaine et qu'elle existe dans un second domaine déjà débloqué → **auto-apprentissage gratuit**
- Utiliser une compétence partagée fait gagner **100% de l'XP dans chaque domaine** associé
- Chaque domaine possède au minimum **15 compétences** organisées en arbre avec prérequis

### Race de personnage

- Entité `Race` avec `slug`, `name`, `description`, `statModifiers` (JSON), `spriteSheet`, `availableAtCreation`
- Actuellement : seule la race **Humain** est disponible (stats neutres)
- Les races futures pourront modifier les stats de base du personnage

### Mécanique d'acquisition (arbre de talent)

1. Le joueur gagne de l'XP de domaine en utilisant des items/sorts/récoltes liés à ce domaine
2. `DomainExperienceEvolver` (EventSubscriber) incrémente l'XP à chaque `ItemUsedEvent` ou `SpotHarvestEvent`
3. L'XP investissable = XP totale du domaine − XP déjà dépensée dans l'arbre
4. Le joueur choisit un talent à débloquer dans l'arbre si : assez d'XP + tous les pré-requis (talents parents) remplis
5. `SkillAcquiring` applique les bonus de stats : `damage`, `heal`, `hit`, `critical`, `life`
6. Il n'existe pas de "niveau du joueur" global : la puissance du personnage est la somme des talents débloqués dans tous ses arbres

### Arbre de compétences (exemple : Pyromancie)

```
Apprenti pyromancien (gratuit)
├── Points faibles (+1% critique, 10 pts)
│   └── Materia niv 2 (20 pts)
│       └── Materia niv 3 (30 pts)
├── Efficacité du feu (+1 dégât, 10 pts)
└── Chaude précision (+1% toucher, 20 pts)
```

### Arbre de compétences (exemple : Mineur)

```
Ruby débutant (gratuit)
├── Ruby apprenti (10 pts)
│   ├── Ruby avancé (50 pts)
│   │   └── Ruby avancé+ (100 pts)
│   │       └── Ruby expert (200 pts)
│   └── Fer débutant (10 pts, req: Ruby apprenti)
│       └── Fer apprenti (50 pts)
```

---

## 12. Quêtes & PNJ

### Système de quêtes

- `PlayerQuest` : quête en cours avec `tracking` (JSON de compteurs)
- `PlayerQuestCompleted` : quête terminée
- `QuestTrackingFormater` : génère la structure de tracking depuis les requirements
- `QuestMonsterTrackingListener` : EventSubscriber sur `MobDeadEvent` → met à jour les compteurs
- `PlayerQuestHelper` : calcule la progression (%)
- `PlayerQuestUpdater` : incrémente le compteur de monstres

### Dialogues PNJ

Les PNJ ont des dialogues à branches (JSON) avec :
- **Étapes** (`steps`) : texte + réponses possibles
- **Choix** avec `conditional_next` : conditions `quest`, `quest_not`, `quest_active`, `has_item`, `domain_xp_min`
- **Actions** : `close`, `quest_offer`, `open_shop`, `next` (branching)
- **Variables** : `{{player_name}}` et `{{pnj_name}}` substitués dynamiquement
- **Parser** : `PnjDialogParser` résout les conditions côté serveur avant envoi au client

**Frontend (dialog_controller.js) :**
- Typewriter intelligent avec pauses sur ponctuation (×6 pour `.!?`, ×3 pour `,`)
- Navigation clavier : Espace/Entrée pour avancer, Échap pour fermer
- Animations : slide-up ouverture, slide-down fermeture, choix avec fade-in échelonné
- Accessibilité : ARIA `role="dialog"`, `aria-live="polite"`, backdrop-blur

Exemple (PNJ "Hello World") :
```
Salutation → Présentation → Offre de quête "Tuer les zombies"
                                ├── Oui → Acceptation (quest_offer)
                                └── Non → Refus (close)
```

---

## 13. Temps réel (Mercure)

### Mercure SSE

| Topic | Événement | Données publiées |
|-------|-----------|-----------------|
| `map/move` | `PlayerMovedEvent` | type, objectId, coordinates |
| `map/move` | `MobMovedEvent` | type, objectId, coordinates |
| `map/respawn` | `MobRespawnedEvent` | type, objectId, coordinates |
| `map/spot` | `SpotHarvestEvent` | objectLayer (spot récolté) |
| `map/spot` | `SpotAvailableEvent` | objectLayer (spot disponible) |

Le client JavaScript (`assets/js/map/move-listener.js`) écoute les événements Mercure et déclenche la mise à jour du Live Component.

Les déplacements sont traités de manière synchrone par `PlayerMoveProcessor` dans la requête HTTP, puis le chemin complet est publié via Mercure pour les autres clients.

---

## 14. Moteur de recherche (Typesense)

### Collection `cells`

Indexe les cellules de la carte pour recherche rapide par position.

- **Client** : `TypeSenseClient` (configuré via variables d'environnement)
- **Recherche** : `CellSearchEngine::getMapCells(x, y, mapId)` — rayon de 10 cases, pagination 250
- **Transformation** : `CellTransformer` — calcule les positions de sprite, coûts de mouvement, couches graphiques
- **Indexation** : commande `app:index:cell` — parcourt World → Map → Area → cellules
- **Reset** : commande `app:reset-ts` — vide la collection

---

## 15. Authentification & Sécurité

### Configuration

- **Provider** : entité `User` (login par email)
- **Authenticator** : `LoginFormAuthenticator` (formulaire avec CSRF)
- **Remember me** : 1 semaine

### Hiérarchie des rôles

```
ROLE_USER (base)
├── ROLE_PLAYER (accès au jeu)
├── ROLE_ADMIN
│   └── ROLE_SUPER_ADMIN (+ switch user)
```

### Contrôle d'accès

| Pattern | Rôle requis |
|---------|------------|
| `/game/*` | `ROLE_USER` |
| `/(profiler\|wdt\|css\|images\|js)/` | Aucun (firewall dev) |

### Comptes de test (fixtures)

| Email | Username | Mot de passe | Rôles |
|-------|----------|-------------|-------|
| demo@amethyste.fr | demo | test | ADMIN, SUPER_ADMIN, PLAYER |
| demo2@amethyste.fr | demo2 | test | ADMIN, SUPER_ADMIN, PLAYER |

---

## 16. Frontend (Twig, Tailwind, Stimulus)

### Layouts

- **`base.html.twig`** : Layout racine — navbar publique, fond `bg-gray-900`, sélecteur de langue
- **`game/game.html.twig`** : Layout jeu — navbar de jeu avec navigation Dashboard/Inventaire/Quêtes/Carte/Skills/Settings

### Pages principales

| Route | Template | Description |
|-------|----------|-------------|
| `/` | `home/index.html.twig` | Page d'accueil |
| `/login` | `security/login.html.twig` | Connexion |
| `/register` | `registration/register.html.twig` | Inscription |
| `/game` | `game/index.html.twig` | Dashboard (récap joueur, mini-carte, stats) |
| `/game/map` | `game/map/index.html.twig` | Carte du monde (Live Component) |
| `/game/fight` | `game/fight/index.html.twig` | Combat (joueurs vs mobs, timeline, actions) |
| `/game/fight/loot` | `game/fight/loot.html.twig` | Écran de butin |
| `/game/inventory` | `game/inventory/index.html.twig` | Inventaire (onglets Turbo Frame) |
| `/game/skills` | `game/skills/index.html.twig` | Arbre de compétences |
| `/game/settings` | `game/settings.html.twig` | Paramètres |

### Live Components (Symfony UX)

| Composant | Description |
|-----------|-------------|
| `Map` | Carte interactive : affiche les cellules, gère le clic pour déplacement, poll 200ms pendant mouvement |
| `FightTimeline` | Timeline de combat : affiche l'ordre des actions |
| `FightNotification` | Notifications de combat : dégâts infligés/reçus |
| `DashboardPlayerRecap` | Récapitulatif du joueur : vie, carte, position |
| `Counter` | Compteur de démo |

### Design

- **Tailwind CSS** avec palette custom `primary` en violet (`#6D28D9`)
- Fond sombre (`bg-gray-900`), texte blanc
- Système de raretés colorées : common (gris), uncommon (vert), rare (bleu), epic (violet), legendary (orange)
- Tilesets RPG : Pipoya (32/40/48px), Shikashi Fantasy Icons

---

## 17. Commandes CLI

| Commande | Description |
|----------|-------------|
| `app:terrain:import` | Importe les fichiers TMX → JSON. Options : `--all`, `--validate`, `--sync-entities`, `--dry-run`, `--stats` |
| `app:tmx:generate-css` | Génère les classes CSS pour chaque tile (sprites). Options : `--single-file`, `--with-sprites`, `--by-layer`, `--filter` |
| `app:index:cell` | Parcourt World→Map→Area, indexe les cellules dans Typesense |
| `app:reset-ts` | Vide la collection Typesense `cells` |
| `app:map:dump` | Dump JSON d'une map : modèle statique (`-m`) ou tag Dijkstra |
| `app:debug-move` | Debug de pathfinding : teste Dijkstra avec coordonnées, génère `debug.txt` visuel |
| `app:debug` | Debug de recherche Typesense : affiche les cellules autour du joueur |

---

## 18. Fixtures & Données de test

### Entités seedées

| Catégorie | Nombre | Détail |
|-----------|--------|--------|
| **Utilisateurs** | 2 | demo, demo2 |
| **Joueurs** | 2 | Player demo (4,7), Player demo 2 (85,36) |
| **Monde** | 1 | "Demo world" |
| **Carte** | 1 | 60×60 cases |
| **Inventaires** | 6 | 3 par joueur (sac, materia, banque) |
| **Domaines** | 15 | Combat (7) + Récolte (4) + Artisanat (4) |
| **Skills** | ~20 | Pyramides de compétences avec pré-requis |
| **Sorts** | 14+ | Feu (4+), Vie (1), Mort (1), Nature (1), Vent (1), Terre (1), Métal (2), None (3) — avec coûts en énergie, cooldowns et effets de statut |
| **Effets de statut** | 11 | poison, paralysie, brûlure, gel, silence, régénération, bouclier, berserk + variantes fortes |
| **Items** | ~25 | Materias (11), Gear (5), Stuff/Consommables (~10), Minerais (2), Plantes (2) |
| **Monstres** | 5 | Zombie, Taiju, Ochu, Squelette, Dragon (boss avec 3 phases, résistances élémentaires, pattern IA) |
| **Mobs** | 5 | Instances sur la carte |
| **PNJ** | 1 | "Hello World" avec dialogue à branches |
| **Table de loot** | 3 | Zombie → Champignon (75%), Peau (90%), Pioche (10%) |

---

## 19. Internationalisation

Deux langues supportées : **Français** (défaut) et **Anglais**.

### Fichiers de traduction

- `translations/messages.fr.json` (~291 lignes)
- `translations/messages.en.json` (~298 lignes)

### Catégories de clés

| Préfixe | Contenu |
|---------|---------|
| `base.*` | Navigation, titre, langue |
| `home.*` | Page d'accueil |
| `registration.*` | Inscription |
| `security.*` | Connexion |
| `game.nav.*` | Navigation jeu |
| `game.inventory.*` | Inventaire, slots, actions |
| `game.map.*` | Carte, régions, ressources, ennemis |
| `game.stats.*` | Statistiques (attaque, défense, magie, etc.) |
| `game.settings.*` | Paramètres (4 onglets) |
| `game.fight.loot.*` | Butin de combat |
| `game.skills.*` | Compétences |
| `game.domains.*` | Domaines d'XP |
| `common.*` | Actions génériques (Annuler, Confirmer, etc.) |

### Changement de langue

Route `GET /change-locale/{locale}` → `LocaleController` → stocke la locale en session → `LocaleListener` l'applique à chaque requête.

### Dépannage : les traductions affichent les clés (ex. `game.skills.description`)

1. **Vider le cache** : `php bin/console cache:clear` (ou via Docker : `docker compose exec php php bin/console cache:clear`). Le catalogue de traductions est mis en cache ; après modification des fichiers JSON, il faut vider le cache.
2. **Conteneur** : en prod, reconstruire l’image après modification des fichiers dans `translations/` pour que le cache soit régénéré avec les bons fichiers.
3. **Locale** : sans session (première visite), la locale par défaut (`fr`) est bien appliquée grâce au `LocaleListener`. Vérifier que le sélecteur de langue enregistre bien la locale en session (`/change-locale/fr` ou `/change-locale/en`).

---

## 20. Terrain & Tiled Map Editor

### Workflow de création de cartes

1. **Édition** : Tiled Map Editor (`terrain/*.tmx`, `terrain/*.tiled-project`)
2. **Import** : `app:terrain:import` → parse XML TMX → JSON avec object layers
   - `--all` : importer tous les fichiers TMX
   - `--validate` : vérifier la cohérence sans importer
   - `--sync-entities` : créer/mettre à jour les entités en base (Mob, ObjectLayer)
   - `--dry-run` : analyse complète sans écriture de fichiers
   - `--stats` : afficher les statistiques détaillées (cells, layers, tilesets)
3. **Génération CSS** : `app:tmx:generate-css` → classes CSS sprites
4. **Seed Areas** : `php scripts/concat_area_fixtures.php` → `area_data.json`
5. **Indexation** : `app:index:cell` → Typesense

### Format TMX

- **Orientation** : orthogonale, rendu left-up
- **Dimensions** : 60×60 tuiles, 32×32 pixels/tuile
- **Tilesets** : `Terrain.tsx`, `forest.tsx`, `BaseChip_pipo.tsx`, `Collisions.tsx`
- **Couches** : tile layers (background, objects) + object layers (mob_spawn, portal, harvest_spot, chest)
- **Encodage** : CSV
- **Object Layers** : types supportés — `portal`, `mob_spawn`, `harvest_spot`/`spot`, `chest`

### Système de collision

Le bitmask de collision gère les directions :
- Mur complet : `movement = -1`
- Collision directionnelle : bits pour N, S, E, W
- Capacités spéciales : escalade, natation (bitmask `abilityMask` dans Dijkstra)

### Fichiers de règles

| Fichier | Rôle |
|---------|------|
| `borders.tmx` | Transitions entre biomes |
| `collisions.tmx` | Zones bloquantes |
| `uncollisions.tmx` | Zones accessibles (override) |
| `post_collisions.tmx` | Ajustements post-traitement |
| `teleports.tmx` | Portails entre zones |

---

## Routes complètes

| Méthode | URL | Nom | Controller |
|---------|-----|-----|-----------|
| GET | `/` | `app_home` | `HomeController` |
| GET | `/change-locale/{locale}` | `app_change_locale` | `LocaleController` |
| GET | `/demo` | `app_demo` | `DemoController` |
| GET | `/login` | `app_login` | `LoginController` |
| GET\|POST | `/register` | `app_register` | `RegistrationController` |
| GET | `/logout` | `app_logout` | `LogoutController` |
| GET | `/game` | `app_game` | `Game\IndexController` |
| GET | `/game/settings` | `app_game_settings` | `Game\SettingsController` |
| GET | `/game/map` | `app_game_map` | `Game\Map\IndexController` |
| GET | `/game/skills` | `app_game_skills` | `Game\Skill\IndexController` |
| GET | `/game/skills/domain/{id}` | `app_game_domain_info` | `Game\Skill\DomainInfoController` |
| POST | `/game/skills/acquire` | `app_game_skill_acquire` | `Game\Skill\AcquireController` |
| GET | `/game/inventory` | `app_game_inventory_index` | `Game\Inventory\IndexController` |
| GET | `/game/inventory/items` | `app_game_inventory_items_list` | `Game\Inventory\ItemsController` |
| GET | `/game/inventory/materials` | `app_game_inventory_materials_list` | `Game\Inventory\MaterialsController` |
| GET | `/game/inventory/bank` | `app_game_inventory_bank_list` | `Game\Inventory\BankController` |
| GET | `/game/inventory/equipment` | `app_game_inventory_equipment_list` | `Game\Inventory\EquipmentController` |
| POST | `/game/inventory/equipment/equip/{id}` | `app_game_inventory_equipment_equip` | `Game\Inventory\EquipItemController` |
| GET | `/game/inventory/materia` | `app_game_inventory_materia_list` | `Game\Inventory\MateriaController` |
| GET | `/game/fight` | `app_game_fight` | `Game\Fight\FightIndexController` |
| POST | `/game/fight/attack` | `app_game_fight_attack` | `Game\Fight\FightAttackController` |
| POST | `/game/fight/spell` | `app_game_fight_spell` | `Game\Fight\FightSpellController` |
| POST | `/game/fight/item` | `app_game_fight_item` | `Game\Fight\FightItemController` |
| POST | `/game/fight/flee` | `app_game_fight_flee` | `Game\Fight\FightFleeController` |
| GET | `/game/fight/loot` | `app_game_fight_loot` | `Game\Fight\FightLootIndexController` |
| POST | `/game/fight/loot/proceed` | `app_game_fight_loot_proceed` | `Game\Fight\FightLootProceedController` |
| GET | `/game/fight/timeline` | `app_game_fight_timeline` | `Game\Fight\FightTimelineController` |
