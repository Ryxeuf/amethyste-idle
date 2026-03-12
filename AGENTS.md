# AGENTS.md — Conventions du projet Amethyste-Idle

## Identité du jeu

- **Type** : MMORPG en navigateur web
- **Inspirations principales** : The Legend of Zelda (NES, SNES, Game Boy), Final Fantasy (combats au tour par tour, materia), Warcraft (univers fantasy, ressources)
- **Vue** : isométrique 2D
- **Déplacements** : sur des cases carrées (tiles 32×32 px), pathfinding Dijkstra
- **Progression** : arbres de talent par domaine, PAS de système de niveaux d'expérience propres au joueur
- **Cartes** : fichiers TMX créés avec Tiled Map Editor, importés via la commande `app:terrain:import`

## Stack technique

- PHP 8.4, Symfony 7.4, FrankenPHP (Caddy), PostgreSQL 17
- Doctrine ORM 3.x
- Mercure SSE pour le temps réel (intégré dans Caddy)
- Frontend : Twig + Tailwind CSS 4.x + Symfony UX Live Component + Turbo + Stimulus
- Assets : Symfony AssetMapper (importmap, sans bundler Node.js)
- Pixi.js pour le rendu canvas de la carte (optionnel)
- Containerisation Docker multi-stage

## Conventions de développement

- Architecture événementielle (Event-Driven) : actions → événements → EventSubscribers
- Les déplacements sont traités de façon synchrone par `PlayerMoveProcessor` dans la requête HTTP
- Les entités de jeu sont dans `src/Entity/Game/`, les entités applicatives dans `src/Entity/App/`
- Le moteur de jeu est dans `src/GameEngine/` organisé par sous-domaine (Fight, Map, Movement, etc.)
- Les Live Components Symfony UX sont dans `src/Twig/Components/`
- Les traductions sont en FR (défaut) et EN dans `translations/messages.{fr,en}.json`
- La documentation technique principale est dans `DOCUMENTATION.md`

## Conventions de carte / terrain

- Les cartes sont éditées dans Tiled Map Editor et exportées en TMX (orientation orthogonale, 60×60 tuiles)
- Tilesets : `Terrain.tsx`, `forest.tsx`, `BaseChip_pipo.tsx`, `Collisions.tsx` dans `terrain/tileset/`
- Les règles (collisions, téléportations, bordures) sont dans `terrain/rules/`
- Le workflow complet : Tiled → TMX → `app:terrain:import` → JSON → `app:tmx:generate-css` → CSS sprites → fixtures areas

## Système de progression (arbres de talent)

- Pas de "niveau du joueur" global
- L'XP est gagnée par domaine (combat, récolte, artisanat) en pratiquant les activités liées
- L'XP de domaine est investie dans un arbre de talent pour débloquer des compétences
- Chaque compétence a un coût en points et des pré-requis (compétences parentes)
- Les compétences débloquées donnent des bonus de stats (dégâts, soin, toucher, critique, vie)
- La puissance du personnage = somme des talents débloqués dans tous ses arbres
