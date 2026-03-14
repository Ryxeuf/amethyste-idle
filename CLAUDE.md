# CLAUDE.md — Amethyste-Idle

MMORPG navigateur web retro (Zelda + FF7/8/9 + stein.world). Vue 2D top-down, tiles 32x32, sprites RPG Maker VX.

## Regles absolues

1. **Docker obligatoire** : TOUTES les commandes PHP/Symfony/Composer s'executent dans le conteneur Docker :
   ```bash
   docker compose exec php php bin/console <commande>
   docker compose exec php composer <commande>
   ```
   Ne JAMAIS executer `php`, `composer`, `symfony` directement sur la machine hote.

2. **Pas de Node.js** : le projet utilise Symfony AssetMapper (importmap). Pour ajouter un package JS :
   ```bash
   docker compose exec php php bin/console importmap:require <package>
   ```
   Ne JAMAIS utiliser npm, yarn, webpack, vite.

3. **Commits atomiques** : un seul commit par changement fonctionnel testable.

4. **Tester avant de continuer** : verifier chaque modification avant de passer a la suivante.

5. **Langue** : communiquer en francais avec l'utilisateur.

6. **Pas de niveau global** : la progression est par arbres de talent/domaine uniquement. Ne jamais introduire un systeme de "level up" global.

7. **Coordonnees** : toujours au format string `"x.y"` en base. Utiliser `getX()`/`getY()` pour extraire les composantes entieres.

## Stack technique

| Couche | Technologie |
|--------|------------|
| Backend | PHP 8.4 + Symfony 7.4 + Doctrine ORM 3.x |
| BDD | PostgreSQL 17 (port dev: `localhost:32768`) |
| Serveur | FrankenPHP (Caddy) + Mercure SSE integre |
| Frontend | Twig + Tailwind CSS 4.1 + Stimulus.js + Turbo |
| Rendu carte | PixiJS v8 (bundle dans `assets/vendor/pixi-bundle.js`) |
| Assets | Symfony AssetMapper (importmap, SANS bundler) |
| Conteneurs | Docker multi-stage + Traefik reverse proxy |
| Temps reel | Mercure SSE (topics: `map/move`, `map/respawn`) |

## Commandes courantes

```bash
# Cache
docker compose exec php php bin/console cache:clear

# Assets
docker compose exec php php bin/console tailwind:build
docker compose exec php php bin/console asset-map:compile

# Base de donnees
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console doctrine:schema:update --force

# Terrain (cartes Tiled)
docker compose exec php php bin/console app:terrain:import
docker compose exec php php bin/console app:tmx:generate-css

# Fixtures
docker compose exec php php bin/console doctrine:fixtures:load

# Debug
docker compose exec php php bin/console app:map:dump
docker compose exec php php bin/console app:debug-move

# PostgreSQL direct
docker compose exec database psql -U app -d amethyste
```

## Scripts

```bash
./scripts/deploy.sh --prod          # Deploiement production
./scripts/deploy.sh --dev           # Deploiement developpement
./scripts/reload-fixtures.sh --dev  # Reset DB + fixtures (DESTRUCTIF)
```

## Architecture

```
src/
  Controller/           # HTTP controllers (Game/, Api/, Security/)
  Entity/App/           # Entites applicatives (Player, Map, Mob, Fight, Pnj...)
  Entity/Game/          # Definitions (Item, Monster, Spell, Skill, Domain)
  GameEngine/           # Logique metier par domaine :
    Fight/              #   Combat tour par tour
    Map/                #   Pathfinding Dijkstra
    Movement/           #   PlayerMoveProcessor
    Progression/        #   XP et talents
    Realtime/Map/       #   Publishers Mercure
  Event/                # 21 evenements domaine
  EventListener/        # Subscribers
assets/
  controllers/          # Stimulus controllers JS
  lib/                  # Modules JS (SpriteAnimator)
  vendor/               # PixiJS bundle
  styles/images/        # Sprites et tilesets
terrain/                # Fichiers Tiled (.tmx, .tsx, .world)
scripts/                # Scripts deploy, fixtures, etc.
```

## Conventions de code

- **PHP** : PSR-12, attributs PHP 8 (`#[Route(...)]`), readonly constructor promotion
- **JS** : ESM (import/export), Stimulus controllers avec `static values`/`static targets`
- **Entites** : traits partages (`CharacterStatsTrait`, `CoordinatesTrait`, `TimestampableEntity`)
- **Evenements** : architecture Event-Driven (actions -> Events -> EventSubscribers)
- **Collisions** : bitmask directionnel N/S/E/W, -1 = mur impassable
- **Nommage** : PascalCase entites, camelCase methodes, snake_case routes

## Pieges courants

- `public/assets/` compile ecrase l'AssetMapper dev -> `rm -rf public/assets/` si comportement JS inattendu
- Mercure integre dans Caddy, pas de serveur separe a demarrer
- Les sprites sont au format RPG Maker VX (3 col x 4 lignes), le `SpriteAnimator` detecte la taille automatiquement depuis la texture
- En dev, le volume Docker monte `.:/app` -> les fichiers sont partages entre hote et conteneur
- `tailwind:build` doit tourner avant `asset-map:compile` ou `debug:asset`

## Qualite de code

```bash
# Lint (PHP-CS-Fixer)
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff
docker compose exec php vendor/bin/php-cs-fixer fix  # correction auto

# Analyse statique (PHPStan niveau 5)
docker compose exec php vendor/bin/phpstan analyse

# Tests (PHPUnit)
docker compose exec php vendor/bin/phpunit
docker compose exec php vendor/bin/phpunit --testsuite Unit
docker compose exec php vendor/bin/phpunit --filter NomDuTest
```

## CI/CD

- **CI** : GitHub Actions sur chaque push/PR — lint, PHPStan, PHPUnit, build Docker
- **CD** : Deploiement automatique sur le serveur quand un push arrive sur `main`
- Voir [docs/CICD.md](docs/CICD.md) pour la documentation complete

## Documentation approfondie

- [DOCUMENTATION.md](DOCUMENTATION.md) — Documentation technique complete (20 sections, modele de donnees, combat, carte, inventaire, quetes, etc.)
- [AGENTS.md](AGENTS.md) — Conventions du projet (identite jeu, stack, rendu PixiJS, UI, progression)
- [ASSETS.md](ASSETS.md) — Guide des assets graphiques (format sprites, tilesets, ajout de nouveaux sprites)
- [docs/CICD.md](docs/CICD.md) — Documentation CI/CD (pipelines, secrets, rollback)

## Routes principales

| Route | Description |
|-------|------------|
| `/game/map` | Carte PixiJS |
| `/game/fight` | Combat tour par tour |
| `/game/inventory` | Inventaire (items, equipement, materia, banque) |
| `/game/skills` | Arbres de talent |
| `/api/map/config` | Config tilesets + sprites |
| `/api/map/cells` | Donnees tuiles (x, y, radius, mapId) |
| `/api/map/entities` | Positions joueurs/mobs/PNJ |
| `/api/map/move` | POST mouvement joueur |
| `/api/map/pnj/{id}/dialog` | Dialogue PNJ |

## Domaines web

- `amethyste.best` / `amethyste.ryxeuf.fr` -> Site public
- `game.amethyste.best` -> Jeu (mode connecte)
- `api.amethyste.best` -> API
