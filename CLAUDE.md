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

7. **Position = zone (pivot PBBG)** : la reference de position d'un joueur est `Player::currentZone` (FK vers `Zone`, maintenue par `ZoneTravelService`). Toute logique de position s'appuie sur la zone, JAMAIS sur les coordonnees. Le code carte navigable (rendu PixiJS, pathfinding, mouvement, editeur admin, terrain) a ete **supprime avec ZON-21** ; les coordonnees `"x.y"` (`getX()`/`getY()`) subsistent comme champ herite sur `Player` sans logique de deplacement.

8. **Taille des phases** : si une phase ou feature semble trop volumineuse (taille L ou XL, ex: 400+ lignes de fixtures, 10+ fichiers a modifier), la decouper en sous-phases realisables en une seule session. Chaque sous-phase doit etre commitable et testable independamment. Ne jamais tenter d'ecrire plus de ~200 lignes de donnees/fixtures en une seule passe.

9. **Competences = PASSIVES UNIQUEMENT** : les skills des arbres de talent ne donnent JAMAIS de sort actif directement. Les skills servent a :
   - **Debloquer l'utilisation d'une materia** (`actions.materia.unlock`) — prerequis pour equiper/utiliser une materia specifique
   - **Accorder des bonus passifs** — stats (damage, heal, hit, critical, life)
   - **Permettre d'equiper certains objets** — prerequis d'equipement
   Ne JAMAIS utiliser `actions.combat.spell_slug` dans un skill. Utiliser `actions.materia.unlock` a la place.

10. **Sorts actifs = UNIQUEMENT via materia** : pour lancer un sort en combat, le joueur doit (1) posseder la materia, (2) avoir appris la competence materia correspondante dans un arbre, (3) avoir sockette la materia dans un slot d'equipement. L'attaque de base de l'arme est toujours disponible gratuitement.

11. **Pas de PvP** : le jeu est exclusivement PvE cooperatif. Ne jamais introduire de combat joueur contre joueur (arenes, duels, zones PvP, RvR). Les interactions entre joueurs sont limitees a la cooperation (guildes, groupes, commerce, chat).

12. **Multi-personnages** : un compte utilisateur (User) peut posseder plusieurs personnages (Player). La limite par defaut est de **1 personnage par compte** (configurable, cas particuliers possibles). Le systeme de creation de personnage (choix du nom, de la race, de l'apparence) est distinct de l'inscription du compte.

13. **Roadmap** : realise dans `docs/ROADMAP_DONE.md` ; a venir reparti par **sprint** dans `docs/roadmap/` :
   - `docs/roadmap/ROADMAP_TODO_INDEX.md` — Legende, graphe de dependances, liens vers les sprints
   - `docs/roadmap/SPRINT_01.md` … `SPRINT_15.md` — Taches par sprint (15 sprints focuses, priorite Critique a Basse)
   - `docs/ROADMAP_TODO.md` — Point d'entree (liens vers l'index et les sprints) ; l'admin `/admin/roadmap` **agrege** index + 15 sprints par defaut, avec **sous-onglets** (`?part=index`, `sprint_1` … `sprint_15`, ou `all`) pour n'afficher qu'un fichier. Ajouter un sprint = ajouter le fichier **et** l'entree dans `RoadmapController` (3 constantes)
   - Quand une tache est **implementee**, elle est **retiree** du fichier de sprint concerne et **ajoutee** a `ROADMAP_DONE.md`. Ne jamais laisser s'accumuler le detail des sous-phases livrees dans un fichier de sprint (cf. le menage du 2026-07-25 : `SPRINT_12.md` pesait 181 Ko pour 2 taches)
   - Sprints **actifs** : **Sprint 15** (commandes de craft, ECO-05→09 — Piste C, pilier endgame). Sprint 13 clos a 6/7 (reste ZON-26b), **Sprint 14 complet 9/9**. Sprints 11-12 en cours, priorite basse
   - **Ordre de chantier global** : section « Ordre de chantier » de `ROADMAP_TODO_INDEX.md` — la sequence de reference pour un developpement autonome (RET-01 → ECO-24b → FOY-17 → recalibrage → foyers/retention/purete/Crue/chaine → zones)
   - Plans annexes : `PLAN_RETENTION.md` (**RET-01→07, 0/7** — retention hebdomadaire), `PLAN_SETTLEMENTS.md` (**FOY-01→16, 0/16 — prochain grand chantier** : foyers, Crue, Paleur ; adosse a `docs/GAME_WORLD.md`), `PLAN_GUILD_CITY_CONTROL.md` (GCC-01→20 ✅), `PLAN_NARRATIVE.md` (NAR-01→14 ✅), `PLAN_TESTING.md` (TST-01→15 ✅), `PLAN_PLAYER_ECONOMY.md` (Pistes A/B **completes** au Sprint 14 ; Piste C en cours au Sprint 15 ; **Pistes F/G ouvertes** — purete des ressources et chaine de production par paliers, ECO-21→27)
   - `docs/roadmap/ARCHIVE_SPRINT_11_12.md` — archive verbatim du detail livre des Sprints 11-12 (non agregee par l'admin)

14. **Versioning automatique** : la version affichee dans le jeu (`app_version`) est geree dynamiquement par `VersionExtension` :
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
| Frontend | Twig + Tailwind CSS 4.1 + Stimulus.js + Turbo (vues server-rendered, pivot PBBG) |
| Assets | Symfony AssetMapper (importmap, SANS bundler) |
| Conteneurs | Docker multi-stage + Traefik reverse proxy |
| Temps reel | Mercure SSE (topics: `chat/zone/<id>`, `zone/<id>/event`, `dungeon/run/<id>`, annonces) |

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

# Fixtures
docker compose exec php php bin/console doctrine:fixtures:load

# Zones (modele PBBG — seedees depuis config/game/zones/*.yaml)
docker compose exec php php bin/console app:zone:import

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
    Zone/               #   Modele zone PBBG (voyage, presence, evenements, expeditions, boss)
    Dungeon/            #   Donjons de groupe semi-synchrones (formation, combat, recompenses)
    Progression/        #   XP et talents
    Realtime/           #   Publishers Mercure (zone, combat, guilde, donjon)
  Event/                # Evenements domaine
  EventListener/        # Subscribers
assets/
  controllers/          # Stimulus controllers JS
  lib/                  # Modules JS
  styles/images/        # Sprites
scripts/                # Scripts deploy, fixtures, etc.
```

> **Note ZON-21** : le code carte navigable (rendu PixiJS, pathfinding Dijkstra,
> `PlayerMoveProcessor`, endpoints `/api/map/*`, editeur de carte admin, moteur
> `GameEngine/Terrain`, dossier `terrain/`) a ete **supprime** avec le pivot PBBG.
> La position d'un joueur est sa **zone** (voir regle #7). Les entites `Map`/`Area`
> subsistent comme support de donnees des zones.

## Conventions de code

- **PHP** : PSR-12, attributs PHP 8 (`#[Route(...)]`), readonly constructor promotion
- **JS** : ESM (import/export), Stimulus controllers avec `static values`/`static targets`
- **Entites** : traits partages (`CharacterStatsTrait`, `CoordinatesTrait`, `TimestampableEntity`)
- **Evenements** : architecture Event-Driven (actions -> Events -> EventSubscribers)
- **Collisions** : bitmask directionnel N/S/E/W, -1 = mur impassable
- **Nommage** : PascalCase entites, camelCase methodes, snake_case routes
- **Systeme de design** : `assets/styles/design-system.css` porte les tokens (`@theme`) et les
  composants `.ds-*` (boutons, cartes, sceaux de rarete, jauges, lignes d'objet, etats vides).
  Un ecran repris utilise ces composants ; il ne redeclare pas de couleur. Les rampes Tailwind
  heritees (`gray-*`, `purple-*`, `text-white`…) sont **reindexees** vers le parchemin pour que
  les ecrans pas encore repris restent coherents : c'est un pont, pas une API. Trois regles
  tenues par les composants : une seule action primaire par ecran, tout chiffre en monospace,
  un etat vide qui dit quoi faire. Reference visuelle : `design/Amethyste - Design System.dc.html`.
- **Tailwind 4 uniquement** : plus aucun CDN Tailwind 2 n'est charge (il gagnait sur la feuille
  compilee, les regles hors calque l'emportant sur celles d'un `@layer`). Les noms d'avant la v4
  sont refuses par `LegacyTailwindScanner` : utilitaires d'opacite (`bg-opacity-50` ->
  `bg-noir/50`), renommages v3 (`flex-shrink-0` -> `shrink-0`), `bg-gradient-to-*` ->
  `bg-linear-to-*`, et `outline-none` -> `outline-hidden` (en v4, `outline-none` supprime le
  contour au lieu de le rendre transparent, et le repere de focus disparait en contraste force).
  Attention aux echelles decalees d'un cran : le `shadow-sm` de la v4 vaut l'ancien `shadow`,
  son `blur-sm` vaut l'ancien `blur`. Les anciennes valeurs s'ecrivent `shadow-xs` et `blur-xs`.

## Pieges courants

- `public/assets/` compile ecrase l'AssetMapper dev -> `rm -rf public/assets/` si comportement JS inattendu
- Mercure integre dans Caddy, pas de serveur separe a demarrer
- En dev, le volume Docker monte `.:/app` -> les fichiers sont partages entre hote et conteneur
- `tailwind:build` doit tourner avant `asset-map:compile` ou `debug:asset`
- **Migrations PostgreSQL** : `ADD CONSTRAINT IF NOT EXISTS` n'existe PAS en PostgreSQL. Pour une contrainte idempotente, utiliser un bloc `DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'nom') THEN ALTER TABLE ... ADD CONSTRAINT ...; END IF; END $$`. En revanche, `ADD COLUMN IF NOT EXISTS`, `CREATE TABLE IF NOT EXISTS` et `CREATE INDEX IF NOT EXISTS` sont valides.
- **Ordre des migrations le meme jour** : Doctrine trie les migrations par ordre alphabetique du nom de version (`VersionYYYYMMDD<Suffixe>`), PAS par heure de creation. Deux migrations du meme jour sont donc executees dans l'ordre alphabetique de leur suffixe, pas dans l'ordre logique/chronologique voulu. Si une migration `B` (ex: `Version20260726GardenPlot`) reference une table creee par une migration `C` du meme jour (ex: `Version20260726PlayerHouse`), et que `B` < `C` alphabetiquement, le deploiement casse (`relation "x" does not exist`) — vu en prod le 2026-07-27 (`ZoneGraph` vs `PlayerCurrentZone`/`WorldEntitiesZone`/`ZoneGatherVeins`, puis `PlayerHouse` vs `GardenPlot`/`HouseFurnishing`/`HouseRent`). Fix : renommer (fichier + classe) la migration qui cree la table en prefixant son suffixe par une lettre qui la trie avant ses dependantes (convention deja utilisee pour `Version20260326ZGuildInfluence`). Avant d'ajouter plusieurs migrations le meme jour, verifier que celle qui cree une table/colonne est bien triee alphabetiquement avant celles qui la referencent.

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
- [docs/GAME_PRINCIPLES.md](docs/GAME_PRINCIPLES.md) — **Principes de game design** (source de verite) : controle de cite, narration (trame large + acte d'intro + narration saisonniere, monde hybride, credits narratifs de cite, Codex), economie joueur (craft joueur, plancher T1 PNJ, HV regional taxe par la cite, commandes de craft, echoppes, objets lies). Decline en jalons dans [docs/roadmap/PLAN_PLAYER_ECONOMY.md](docs/roadmap/PLAN_PLAYER_ECONOMY.md) (economie) et [docs/roadmap/PLAN_NARRATIVE.md](docs/roadmap/PLAN_NARRATIVE.md) (narration)
- [docs/GAME_PROGRESSION.md](docs/GAME_PROGRESSION.md) — **Colonne vertebrale de progression** : ce qu'un joueur fait au jour 1, au jour 40, au mois 6. Cinq horizons emboites (session 10-15 min, jour, semaine, maree de 28 j, an) avec la regle « a chaque horizon, quelque chose qui finit et quelque chose qui reste » ; quatre actes (je comprends → je me specialise → **on compte sur moi** → j'ai une place) ; le **passage critique des semaines 3 a 6** ou se decide la retention ; le budget d'energie (240/jour, ~80 actions) et ce qu'il autorise. Le §6 enonce ce que la colonne impose aux zones et aux ressources — a lire **avant** de definir du contenu de zone
- [docs/GAME_WORLD.md](docs/GAME_WORLD.md) — **Socle de monde adopte** (decisions A→F actees le 2026-07-27) : geographie, civilisation, politique, trame globale. Postulat unique (le temps vecu se depose en amethyste) d'ou decoulent les biomes (un biome = une facon dont le temps s'est depose), les **foyers** (nœuds a la Ashes of Creation, sans siege : l'activite des joueurs fait monter Campement → Metropole, l'inactivite fait redescendre ; integration **incrementale**, rien n'est retro-gate) et la **Crue** (quota de grandes cites indexe sur la population active = enjeu de territoire sans PvP), l'economie territoriale a la Albion (une ligne de production par biome, bonus d'atelier par foyer, caravanes PvE) avec la **purete** en 4 bandes sur la ligne du cristal (seul le Parfait eveille une materia), la **Paleur** (etat de zone reversible, repare contre paiement au tresor) distincte de **l'Etale** (lieu ancien, permanent, source exclusive de plans et de materia), la politique (la Concorde, le Serment = raison interne du « pas de PvP », axe Extraire/Preserver, faction **la Fonderie** a creer — jouable, sans mechancete, avec le levier **fondre ou lire** une materia) et le scenario (acte d'intro / marees / meta-arc du Reflux). A lire **avant** d'ajouter une zone, une faction ou un theme de saison
- [docs/GAME_INSPIRATIONS.md](docs/GAME_INSPIRATIONS.md) — **Revue des jeux de reference** : chaque jeu ramene a une idee precise et a l'endroit ou elle se branche. Premier cercle (A Tale in the Desert, Wakfu, Eco, EVE, Ryzom, Star Wars Galaxies), second cercle (FFXIV/Ishgard, Black Desert, GW2, Wurm, Travian, PoE, Foxhole), **ce qu'on refuse explicitement** et pourquoi. Cinq idees retenues, dont la **qualite variable des ressources** (SWG) et les **indices d'activite decroissants** (EVE)
- [docs/GAME_ZONE_ACTIONS.md](docs/GAME_ZONE_ACTIONS.md) — **Cadrage des actions et de l'information de zone** (source de verite de l'ecran de zone) : les trois registres d'action (tenter / engager / frequenter), le principe « la zone montre ce que le personnage sait » (rumeur → reperee → cartographiee), decouverte des filons et des monstres par passifs d'arbre, information exclusive du prospecteur, partage et concurrence sur les filons (**une recolte n'echoue jamais** : vitalite partagee et fatigue personnelle modulent le rendement, pas l'acces), reperage cumulatif, cartes de zone (pas de domaine « Cartographe »). A lire **avant** toute reprise design de `/game/zone`
- [docs/ROADMAP_DONE.md](docs/ROADMAP_DONE.md) — Roadmap realisee (historique des phases completees)
- [docs/ZON_CAMPAIGN_RECAP.md](docs/ZON_CAMPAIGN_RECAP.md) — Synthese versionnee de la campagne ZON-12→21 (pivot PBBG) : jalons, decisions de conception, suivis
- [docs/roadmap/ROADMAP_TODO_INDEX.md](docs/roadmap/ROADMAP_TODO_INDEX.md) — Roadmap a venir (index + liens par sprint) ; [docs/ROADMAP_TODO.md](docs/ROADMAP_TODO.md) — Point d'entree
- [AGENTS.md](AGENTS.md) — Conventions du projet (identite jeu, stack, rendu PixiJS, UI, progression)
- [design/Amethyste - Design System.dc.html](design/Amethyste%20-%20Design%20System.dc.html) — Document du systeme de design « Parchemin » (tokens, composants, six ecrans mobiles, refonte de l'ecran de zone). Traduit en code dans `assets/styles/design-system.css`
- [ASSETS.md](ASSETS.md) — Guide des assets graphiques (format sprites, tilesets, ajout de nouveaux sprites)
- [docs/CICD.md](docs/CICD.md) — Documentation CI/CD (pipelines, secrets, rollback)
- [docs/LOAD_TESTING_BOTTLENECKS.md](docs/LOAD_TESTING_BOTTLENECKS.md) — Synthese des goulots probables identifies par les scenarios k6 (`scripts/load-test/`) + plan d'optimisation priorise en 6 jalons (Redis cache, PgBouncer, indexes & cache des collectors `/metrics`, indexes composites map, hardening Mercure, scaling horizontal). Etat des jalons mis a jour au fur et a mesure des sous-phases livrees (cf. tache 134 du Sprint 12).
- [scripts/load-test/README.md](scripts/load-test/README.md) — Scenarios k6 (guest-browsing, metrics-stress, mercure-streaming, authenticated-gameplay) + variables d'environnement + thresholds + cas d'usage. Objectif Sprint 12 : 200 joueurs simultanes sans degradation.

## Routes principales

| Route | Description |
|-------|------------|
| `/game/zone` | Ecran de zone (pivot PBBG — vue principale) |
| `/game/zone/travel/{id}` | POST voyager via une connexion du graphe |
| `/game/zone/dungeon/*` | Donjon de groupe (launch/act/abandon) |
| `/game/world-map` | Carte du monde illustree (graphe de zones) |
| `/game/fight` | Combat tour par tour |
| `/game/fight/spell` | POST lancer un sort de combat |
| `/game/fight/attack` | POST attaque basique |
| `/game/fight/item` | POST utiliser un objet |
| `/game/fight/flee` | POST fuir le combat |
| `/game/fight/loot` | Ecran de butin apres victoire |
| `/game/inventory` | Inventaire (items, equipement, materia, banque) |
| `/game/skills` | Arbres de talent |
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
