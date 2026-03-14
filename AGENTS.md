# AGENTS.md — Conventions du projet Amethyste-Idle

## Identité du jeu

- **Type** : MMORPG en navigateur web
- **Inspirations principales** : The Legend of Zelda (exploration), Final Fantasy 7/8/9 (combat tour par tour, materia, univers medieval-fantastique-futuriste), stein.world (MMO navigateur), Warcraft (ressources, crafting)
- **Vue** : 2D top-down avec tiles 32×32 px
- **Sprites personnage** : format RPG Maker VX, 24×32 px par frame (3 colonnes × 4 lignes)
- **Déplacements** : pathfinding Dijkstra, animation de marche avec sprites directionnels
- **Progression** : arbres de talent par domaine, PAS de niveaux globaux
- **Cartes** : fichiers TMX créés avec Tiled Map Editor, importés via `app:terrain:import`
- **Univers** : medieval-fantastique-futuriste (comme Final Fantasy 7/8/9)

## Stack technique

- PHP 8.4, Symfony 7.4, FrankenPHP (Caddy), PostgreSQL 17
- Doctrine ORM 3.x
- Mercure SSE pour le temps réel (intégré dans Caddy)
- Frontend : Twig + Tailwind CSS 4.1 + Symfony UX Live Component + Turbo + Stimulus
- Assets : Symfony AssetMapper (importmap, sans bundler Node.js)
- PixiJS pour le rendu canvas de la carte (WebGL avec fallback Canvas 2D)
- Containerisation Docker multi-stage + Traefik reverse proxy

## Conventions de développement

- Architecture événementielle (Event-Driven) : actions → événements → EventSubscribers
- Les déplacements sont traités de façon synchrone par `PlayerMoveProcessor` dans la requête HTTP
- Les entités de jeu sont dans `src/Entity/Game/`, les entités applicatives dans `src/Entity/App/`
- Le moteur de jeu est dans `src/GameEngine/` organisé par sous-domaine (Fight, Map, Movement, etc.)
- Les Live Components Symfony UX sont dans `src/Twig/Components/`
- Les traductions sont en FR (défaut) et EN dans `translations/messages.{fr,en}.json`
- La documentation technique principale est dans `DOCUMENTATION.md`
- Commits atomiques : un commit par changement fonctionnel testable
- Tester après chaque modification avant de passer à la suivante

## Conventions de carte / terrain

- Les cartes sont éditées dans Tiled Map Editor et exportées en TMX (orientation orthogonale, 60×60 tuiles)
- Tilesets : `Terrain.tsx`, `forest.tsx`, `BaseChip_pipo.tsx`, `Collisions.tsx` dans `terrain/tileset/`
- Les règles (collisions, téléportations, bordures) sont dans `terrain/rules/`
- Le workflow complet : Tiled → TMX → `app:terrain:import` → JSON → `app:tmx:generate-css` → CSS sprites → fixtures areas
- Collisions : bitmask directionnel (N/S/E/W), -1 = mur impassable

## Conventions de rendu (PixiJS)

- Rendu carte via `assets/controllers/map_pixi_controller.js` (Stimulus controller)
- Containers PixiJS : _tileContainer (z:0), _entityContainer (z:10), _playerContainer (z:20)
- Données tuiles API : `{ x, y, l: [gid1, gid2...], w: boolean }` (l = layers, w = walkable)
- Caméra : interpolation fluide 15%/frame, centrée sur le joueur
- Modules JS réutilisables dans `assets/lib/` (ex: SpriteAnimator)
- Sync temps réel via Mercure SSE (topics: `map/move`, `map/respawn`)

## Conventions d'interface

- Seuls les déplacements et dialogues PNJ restent sur l'écran de la carte
- Inventaire, combat, compétences, trading : vues séparées (routes `/game/*`)
- Mobile : tap-to-move uniquement (pas de D-pad virtuel)
- Thème sombre : bg-gray-900, couleur primaire purple (#6D28D9)
- Raretés : common (gray), uncommon (green), rare (blue), epic (purple), legendary (orange)

## Système de combat

### Effets de statut
- 8 types : `poison`, `paralysis`, `burn`, `freeze`, `silence`, `regeneration`, `shield`, `berserk`
- `StatusEffectManager` gère les DOT/HOT par tour, les vérifications de statut et l'application
- `FightStatusEffect` : entité qui stocke les effets actifs par combat (cible, type, tours restants, puissance)
- Les sorts peuvent appliquer des effets de statut via `SpellApplicator`

### Résistances élémentaires
- Les monstres peuvent avoir des résistances élémentaires (ex : dragon résiste au feu)
- `SpellApplicator` applique le coefficient de résistance aux dégâts
- `ElementalSynergyCalculator` : 4 combos (Fire+Water=Steam, Earth+Air=Tornado, Light+Dark=Eclipse, Fire+Earth=Magma)

### IA des monstres (`MobActionHandler`)
- Pattern IA configurable via `Monster::aiPattern` (JSON)
- Clés : `sequence` (actions en boucle), `spell_chance` (probabilité de sort), `low_hp_heal` (soin automatique bas HP)
- Boss : phases basées sur le % de vie, `danger_alert` + `danger_message` pour alertes UI
- `resolveSpell()` : sélection aléatoire dans le pool de sorts du monstre

### Mécanique de boss
- Respawn 1h (vs 10s pour les mobs normaux) — géré par `MobDeathQueuing`
- Impossible de fuir un boss (+ berserk empêche aussi la fuite)
- Indicateur de difficulté (étoiles ★) dans le template de combat
- Bannière d'alerte danger dans l'UI de combat
- XP materia ×5 à la mort d'un boss

### Compétences de combat actives
- 7 archétypes : Pyromancien, Soldat, Soigneur, Défenseur, Nécromancien, Druide, Mage blanc
- Chaque archétype a 3-4 compétences formant une chaîne de progression
- Liaison compétence → sort via `actions.combat.spell_slug` dans `SkillFixtures`
- `CombatSkillResolver` résout les sorts disponibles selon les compétences débloquées

### Materia
- `MateriaXpGranter` : XP materia = 10 × niveau monstre (boss ×5)
- `MateriaFusionManager` : fusion de deux materias en une materia supérieure

## Système de progression (arbres de talent)

- Pas de "niveau du joueur" global
- L'XP est gagnée par domaine (combat, récolte, artisanat) en pratiquant les activités liées
- L'XP de domaine est investie dans un arbre de talent pour débloquer des compétences
- Chaque compétence a un coût en points et des pré-requis (compétences parentes)
- Les compétences débloquées donnent des bonus de stats (dégâts, soin, toucher, critique, vie)
- La puissance du personnage = somme des talents débloqués dans tous ses arbres
