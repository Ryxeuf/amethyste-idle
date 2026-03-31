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

8. **Taille des phases** : si une phase ou feature semble trop volumineuse (taille L ou XL, ex: 400+ lignes de fixtures, 10+ fichiers a modifier), la decouper en sous-phases realisables en une seule session. Chaque sous-phase doit etre commitable et testable independamment. Ne jamais tenter d'ecrire plus de ~200 lignes de donnees/fixtures en une seule passe.

9. **Competences = PASSIVES UNIQUEMENT** : les skills des arbres de talent ne donnent JAMAIS de sort actif directement. Les skills servent a :
   - **Debloquer l'utilisation d'une materia** (`actions.materia.unlock`) — prerequis pour equiper/utiliser une materia specifique
   - **Accorder des bonus passifs** — stats (damage, heal, hit, critical, life)
   - **Permettre d'equiper certains objets** — prerequis d'equipement
   Ne JAMAIS utiliser `actions.combat.spell_slug` dans un skill. Utiliser `actions.materia.unlock` a la place.

10. **Sorts actifs = UNIQUEMENT via materia** : pour lancer un sort en combat, le joueur doit (1) posseder la materia, (2) avoir appris la competence materia correspondante dans un arbre, (3) avoir sockette la materia dans un slot d'equipement. L'attaque de base de l'arme est toujours disponible gratuitement.

11. **Roadmap** : realise dans `docs/ROADMAP_DONE.md` ; a venir reparti par vague dans `docs/roadmap/` :
   - `docs/roadmap/ROADMAP_TODO_INDEX.md` — Legende, graphe de dependances, liens vers les vagues
   - `docs/roadmap/ROADMAP_TODO_VAGUE_01.md` … `ROADMAP_TODO_VAGUE_06.md` — Taches par priorite (vague 1 = la plus urgente)
   - `docs/ROADMAP_TODO.md` — Point d'entree (liens vers l'index et les vagues) ; l'admin `/admin/roadmap` **agrege** index + 6 vagues par defaut, avec **sous-onglets** (`?part=index`, `vague_1` … `vague_6`, ou `all`) pour n'afficher qu'un fichier
   - Quand une tache est **implementee**, elle est **retiree** du fichier de vague concerne et **ajoutee** a `ROADMAP_DONE.md`
   - Plan annexe controle de cite : `docs/roadmap/PLAN_GUILD_CITY_CONTROL.md` — jalons **GCC-01** a **GCC-20** (ancre Vague 4 dans `ROADMAP_TODO_VAGUE_04.md`)

12. **Versioning automatique** : la version affichee dans le jeu (`app_version`) est geree dynamiquement par `VersionExtension` :
   - **Dev** : resolue via `git describe --tags --always --dirty` (ex: `0.6.0-alpha-12-gabcdef`)
   - **Prod** : lue depuis l'env var `APP_VERSION` injectee au build Docker par Semantic Release
   - **Fallback** : parametre statique `app.version` dans `config/services.yaml`
   - Le parametre `app.version` dans `services.yaml` sert de version de reference (bump manuel pour les jalons majeurs)
   - **Commits conventionnels obligatoires** : utiliser les prefixes `feat:`, `fix:`, `perf:`, `refactor:` pour que Semantic Release genere automatiquement les versions. Les types `ci:`, `docs:`, `style:`, `test:`, `chore:` ne declenchent PAS de release.

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

# Fixtures
docker compose exec php php bin/console doctrine:fixtures:load

# Debug
docker compose exec php php bin/console app:map:dump
docker compose exec php php bin/console app:audit:entity-placement

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
    Fight/              #   Combat tour par tour (SpellApplicator, MobActionHandler, StatusEffectManager, CombatSkillResolver, ElementalSynergyCalculator, MateriaXpGranter)
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
- **Migrations PostgreSQL** : `ADD CONSTRAINT IF NOT EXISTS` n'existe PAS en PostgreSQL. Pour une contrainte idempotente, utiliser un bloc `DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'nom') THEN ALTER TABLE ... ADD CONSTRAINT ...; END IF; END $$`. En revanche, `ADD COLUMN IF NOT EXISTS`, `CREATE TABLE IF NOT EXISTS` et `CREATE INDEX IF NOT EXISTS` sont valides.

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

- [DOCUMENTATION.md](DOCUMENTATION.md) — Documentation technique complete (22 sections, modele de donnees, combat, carte, inventaire, quetes, bestiaire, succes, etc.)
- [docs/ROADMAP_DONE.md](docs/ROADMAP_DONE.md) — Roadmap realisee (historique des phases completees)
- [docs/roadmap/ROADMAP_TODO_INDEX.md](docs/roadmap/ROADMAP_TODO_INDEX.md) — Roadmap a venir (index + liens par vague) ; [docs/ROADMAP_TODO.md](docs/ROADMAP_TODO.md) — Point d'entree
- [AGENTS.md](AGENTS.md) — Conventions du projet (identite jeu, stack, rendu PixiJS, UI, progression)
- [ASSETS.md](ASSETS.md) — Guide des assets graphiques (format sprites, tilesets, ajout de nouveaux sprites)
- [docs/CICD.md](docs/CICD.md) — Documentation CI/CD (pipelines, secrets, rollback)

## Routes principales

| Route | Description |
|-------|------------|
| `/game/map` | Carte PixiJS |
| `/game/fight` | Combat tour par tour |
| `/game/fight/spell` | POST lancer un sort de combat |
| `/game/fight/attack` | POST attaque basique |
| `/game/fight/item` | POST utiliser un objet |
| `/game/fight/flee` | POST fuir le combat |
| `/game/fight/loot` | Ecran de butin apres victoire |
| `/game/inventory` | Inventaire (items, equipement, materia, banque) |
| `/game/skills` | Arbres de talent |
| `/api/map/config` | Config tilesets + sprites |
| `/api/map/cells` | Donnees tuiles (x, y, radius, mapId) |
| `/api/map/entities` | Positions joueurs/mobs/PNJ |
| `/api/map/move` | POST mouvement joueur |
| `/api/map/pnj/{id}/dialog` | Dialogue PNJ |
| `/game/bestiary` | Bestiaire joueur (paliers 10/50/100 kills) |
| `/game/achievements` | Succes (combat, exploration, quetes) |

## Commandes slash (Claude Code)

### Workflow de developpement

| Commande | Description |
|----------|------------|
| `/feature <besoin>` | Structuration + implementation d'une feature (2 phases obligatoires) |
| `/plan <besoin>` | Planification detaillee avant implementation (pas de code) |
| `/tdd <specification>` | Developpement TDD : RED → GREEN → REFACTOR |
| `/review` | Revue de code complete (securite + qualite) des changements |
| `/quality-gate [--fix]` | Pipeline qualite locale (lint + PHPStan + tests) |
| `/verify [quick\|full\|pre-commit\|pre-pr]` | Verification pre-commit/PR complete |
| `/build-fix` | Diagnostic et correction incrementale des erreurs de build |
| `/refactor [path]` | Detection et suppression securisee du code mort |

### Agents specialises domaine jeu

| Commande | Description |
|----------|------------|
| `/combat-agent` | Combat, equilibrage, IA monstres, materia |
| `/quest-agent` | Quetes, dialogues PNJ, narration |
| `/content-agent` | Items, monstres, sorts, recettes |
| `/economy-agent` | Boutiques, hotel des ventes, equilibre economique |
| `/craft-gather-agent` | Recolte et artisanat |
| `/gameplay-agent` | Flux d'etats joueur, game design |
| `/multiplayer-agent` | Mercure SSE, chat, guildes, temps reel |

### Agents specialises techniques

| Commande | Description |
|----------|------------|
| `/architect` | Architecture systeme, patterns, compromis techniques |
| `/code-reviewer` | Revue de code (securite, qualite, patterns Symfony) |
| `/security-reviewer` | Audit securite OWASP, secrets, injections |
| `/performance-optimizer` | Performance (Doctrine, PixiJS, FrankenPHP) |
| `/database-reviewer` | PostgreSQL, index, migrations, requetes |
| `/refactor-cleaner` | Nettoyage code mort, doublons, consolidation |
| `/planner` | Planification d'implementation structuree |
| `/test-agent` | Tests PHPUnit, PHPStan, PHP-CS-Fixer |
| `/migration-agent` | Migrations Doctrine, schema PostgreSQL |
| `/pixijs-agent` | Rendu 2D, sprites, animations, effets visuels |
| `/level-design-agent` | Zones, biomes, placement d'entites |
| `/map-editor-agent` | Editeur de cartes web |
| `/map-generator-agent` | Generation procedurale de cartes |

### Utilitaires

| Commande | Description |
|----------|------------|
| `/build` | Compiler Tailwind + AssetMapper |
| `/cache-clear` | Vider le cache Symfony |
| `/db-query` | Requete SQL directe |
| `/deploy` | Deploiement (--prod / --dev) |
| `/fixtures` | Reset DB + fixtures (DESTRUCTIF) |
| `/import-terrain` | Importer les cartes Tiled |
| `/roadmap` | Afficher la roadmap |

## Hooks actifs

- **PreToolUse (Bash)** : bloque les commandes PHP/Composer/Node.js hors Docker
- **PostToolUse (Edit/Write)** : detecte les debug statements (`dd()`, `dump()`, `var_dump()`) dans les fichiers PHP modifies

## Domaines web

- `amethyste.best` / `amethyste.ryxeuf.fr` -> Site public
- `game.amethyste.best` -> Jeu (mode connecte)
- `api.amethyste.best` -> API
