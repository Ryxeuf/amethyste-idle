# CLAUDE.md — Guide de développement Amethyste-Idle

## Qu'est-ce que ce projet ?

MMORPG navigateur web rétro-style. Inspirations : Zelda (exploration), Final Fantasy 7/8/9 (combat, materia, univers medieval-fantastique-futuriste), stein.world (MMO navigateur isométrique). Vue 2D top-down avec tiles 32x32. Sprites personnage 24x32 (format RPG Maker VX).

## Stack technique

- **Backend** : PHP 8.4 + Symfony 7.4 + Doctrine ORM 3.x + PostgreSQL 17
- **Serveur** : FrankenPHP (Caddy) avec Mercure SSE intégré
- **Frontend** : Twig + Tailwind CSS 4.1 + Stimulus.js + Symfony UX Live Components + Turbo
- **Rendu carte** : PixiJS (WebGL/Canvas 2D) via `map_pixi_controller.js`
- **Assets** : Symfony AssetMapper (importmap) — PAS de Node.js, PAS de webpack/vite
- **Conteneurs** : Docker multi-stage + Traefik reverse proxy
- **Temps réel** : Mercure SSE (topics: `map/move`, `map/respawn`)

## Structure des dossiers clés

```
src/
  Controller/           # HTTP controllers (Game/, Api/, Security/)
  Entity/
    App/                # Entités applicatives (Player, Map, Area, Mob, Fight, Inventory, Pnj...)
    Game/               # Entités de définition (Item, Monster, Spell, Skill, Domain, Quest)
  GameEngine/           # Logique métier du jeu, organisée par domaine :
    Fight/              # Combat tour par tour (handlers, SpellApplicator, LootGenerator)
    Map/                # Pathfinding Dijkstra, MovementCalculator
    Movement/           # PlayerMoveProcessor
    Player/             # Actions joueur (dialogue, respawn)
    Mob/                # Spawn et comportement mobs
    Progression/        # XP et acquisition de compétences
    Quest/              # Suivi des quêtes
    Gear/               # Équipement et materia
    Item/               # Résolution d'effets d'items
    Job/                # Récolte (mining, fishing, herbalism)
    Generator/          # Génération d'items
    Realtime/Map/       # Publishers Mercure
  Event/                # 21 événements de domaine
  EventListener/        # Subscribers
  Twig/Components/      # 5 Live Components (Map, FightTimeline, FightNotification, DashboardPlayerRecap, Counter)

assets/
  controllers/          # Stimulus controllers JS
    map_pixi_controller.js    # Rendu PixiJS de la carte (~670 lignes)
    map_mercure_controller.js # Sync temps réel Mercure
  lib/                  # Modules JS réutilisables (SpriteAnimator)
  styles/
    images/             # Tilesets et sprites (terrain/, character/, demons.png...)
    map/                # CSS sprites générés (world-1.css)

terrain/                # Fichiers Tiled Map Editor (.tmx, .tsx, .world)
data/                   # JSON exportés des maps
fixtures/               # Données de seed (items, skills, spells, monsters en YAML)
templates/              # 84 templates Twig
translations/           # i18n (messages.fr.json, messages.en.json)
```

## Commandes CLI importantes

```bash
# Import des cartes Tiled (.tmx → JSON → DB)
php bin/console app:terrain:import

# Génération des classes CSS sprites depuis les tilesets
php bin/console app:tmx:generate-css

# Fixtures (seed de données de jeu)
php bin/console doctrine:fixtures:load

# Migrations
php bin/console doctrine:migrations:migrate

# Debug API map data
php bin/console app:api:map:dump
php bin/console app:debug:move
```

## Pipeline des assets terrain

```
1. Tiled Map Editor → .tmx (terrain/)
2. app:terrain:import → JSON (data/map/)
3. app:tmx:generate-css → CSS sprites (assets/styles/map/world-1.css)
4. Fixtures → Areas en DB avec fullData JSON
5. API /api/map/cells → Client PixiJS
```

## Architecture événementielle

Le jeu utilise un pattern Event-Driven :
- Les controllers appellent les processors du GameEngine
- Les processors émettent des Events (21 types : PlayerMovedEvent, MobDeadEvent, etc.)
- Les EventSubscribers réagissent (mise à jour état, publication Mercure, logs)

## Conventions de code

- **PHP** : PSR-12, attributs PHP 8 pour les routes (`#[Route(...)]`)
- **JS** : ESM (import/export), classes Stimulus avec `static values` et `static targets`
- **Entités** : traits partagés (`CharacterStatsTrait`, `CoordinatesTrait`, `TimestampableEntity`)
- **Coordonnées** : format string `"x.y"` (ex: `"10.5"`)
- **Nommage** : PascalCase entités, camelCase méthodes, snake_case routes Symfony

## Points d'attention

- **Pas de Node.js** : utiliser `importmap:require` pour ajouter des dépendances JS
- **Pixi.js** : bundlé manuellement dans `assets/vendor/pixi-bundle.js`
- **Mercure** : intégré dans Caddy/FrankenPHP, pas de serveur séparé
- **Coordonnées** : toujours au format string `"x.y"`, utiliser `getX()`/`getY()` pour extraire
- **Collision** : bitmask directionnel (N/S/E/W), -1 = mur impassable
- **Progression** : PAS de niveau global joueur, XP par domaine uniquement
- **Sprites** : format RPG Maker VX (3 colonnes × 4 lignes, 24x32px par frame)

## Routes principales

```
/game                → Dashboard (IndexController)
/game/map            → Carte PixiJS (Map/IndexController)
/game/inventory      → Inventaire (tabs: items, equipment, materia, materials, bank)
/game/skills         → Arbres de talent par domaine
/game/fight          → Combat tour par tour
/game/settings       → Paramètres
/api/map/config      → Config tilesets + viewport
/api/map/cells       → Données des tuiles (x, y, radius, mapId)
/api/map/entities    → Positions joueurs/mobs/PNJ
/api/map/move        → POST mouvement joueur
```

## Domaines

- amethyste.best / amethyste.ryxeuf.fr → Site public
- game.amethyste.best → Jeu (mode connecté)
- api.amethyste.best → API

## Tests

```bash
# Pas de framework de test configuré actuellement
# Tester manuellement via le navigateur sur /game/map
# Vérifier les FPS : ouvrir la console JS → app.ticker.FPS
```
