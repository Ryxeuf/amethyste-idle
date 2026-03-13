# Phase 1 — Fondations techniques — Changelog

> Implémenté le 2026-03-13

---

## 1.1 Pipeline Tiled amélioré

### Import Object Layers depuis TMX + `--sync-entities`

**Fichier** : `src/Command/TerrainImportCommand.php`

- L'option `--sync-entities` crée automatiquement les entités en base depuis les object layers TMX :
  - `portal` → `ObjectLayer` (type portal, avec destination map/coordonnées)
  - `mob_spawn` → `Mob` (lié au `Monster` via `monster_slug`)
  - `harvest_spot` / `spot` → `ObjectLayer` (type spot, avec items et actions)
  - `chest` → `ObjectLayer` (type chest, avec items)
- Export des objects dans un fichier JSON séparé (`*-objects.json`) pour accès facile
- Injection de `EntityManagerInterface` pour la persistance

### Validation enrichie (`--validate`)

**Fichier** : `src/Command/TerrainImportCommand.php`

- Vérifie que les `mob_spawn`, `npc_spawn`, `harvest_spot` sont sur des cases walkable
- Vérifie que les `portal` ont les propriétés `target_map`, `target_x`, `target_y`
- Vérifie que les `mob_spawn` ont la propriété `monster_slug`
- Conserve les validations existantes (tilesets, collisions, dimensions)

---

## 1.2 Système de portails

### Backend

| Fichier | Changement |
|---------|-----------|
| `src/Entity/App/ObjectLayer.php` | Nouveaux types `TYPE_PORTAL`, `TYPE_MOB_SPAWN`, `TYPE_NPC_SPAWN`, `TYPE_HARVEST_SPOT` + champs `destinationMapId` (int, nullable), `destinationCoordinates` (string, nullable) + méthode `isPortal()` |
| `src/GameEngine/Map/PortalDetector.php` | **Nouveau** — Service qui détecte un portail aux coordonnées du joueur sur sa map courante |
| `src/GameEngine/Movement/PlayerMoveProcessor.php` | Détecte les portails en fin de déplacement via `PortalDetector`, expose `getTriggeredPortal()` |
| `src/Controller/Api/MapApiController.php` | `POST /api/map/move` retourne `portal` (destinationMapId + destinationCoordinates) si un portail est déclenché |
| `src/Controller/Api/MapApiController.php` | **Nouveau endpoint** `POST /api/map/teleport` — vérifie que le joueur est sur un portail, change sa map et ses coordonnées |
| `src/Controller/Api/MapApiController.php` | `GET /api/map/entities` retourne les portails de la map courante |

### Frontend

**Fichier** : `assets/controllers/map_pixi_controller.js`

- **Détection** : après un `move`, si la réponse contient `portal`, stocke les données dans `_pendingPortal`
- **Transition** : après l'animation de marche, si un portail est en attente :
  1. Fondu noir (300ms) via `_fadeTransition(true)`
  2. Appel `POST /api/map/teleport`
  3. Nettoyage complet (entités, tuiles, cache)
  4. Rechargement config + cellules + entités de la nouvelle map
  5. Fondu blanc (300ms) via `_fadeTransition(false)`
- **Rendu visuel** : les portails sont affichés comme des cercles violets lumineux (`_createPortalMarker`)

---

## 1.2 Documentation Tiled

**Fichier** : `docs/TILED_GUIDE.md`

Guide complet couvrant :
- Convention de nommage des fichiers TMX
- Paramètres de carte (60×60, 32×32)
- Tilesets requis (forest, Terrain, Collisions, BaseChip_pipo)
- Layers obligatoires (background, background_overlay, objects, objects_overlay, collision)
- Object Layers : propriétés pour `mob_spawn`, `npc_spawn`, `portal`, `harvest_spot`, `chest`
- Référence des tuiles de collision (0-16)
- Workflow d'import pas-à-pas (validate → import → sync-entities → generate-css)
- Bonnes pratiques

---

## 1.3 Optimisation performance

### Filtrage spatial des entités (API)

**Fichier** : `src/Controller/Api/MapApiController.php`

- `GET /api/map/entities?radius=25` — paramètre optionnel `radius` pour ne retourner que les entités dans un rayon autour du joueur
- Appliqué à players, mobs, pnjs et portals
- Si `radius=0` (défaut), retourne toutes les entités (comportement legacy)

### Preload mid-path (frontend)

**Fichier** : `assets/controllers/map_pixi_controller.js`

- Pendant l'animation de marche, preload des cellules toutes les 10 cases (`_preloadCells` mid-path)
- Utilisation du paramètre `radius` pour le chargement des entités

---

## 1.4 Organisation des assets

### SpriteConfigProvider centralisé

**Fichier** : `src/GameEngine/Map/SpriteConfigProvider.php` (**nouveau**)

Service qui centralise toute la configuration des sprites :
- **7 sprites joueur** : `player_default`, `player_male_01/02/03`, `player_female_01/02`, `player_soldier_01`
- **12 sprites monstres** : un par type (`mob_zombie`, `mob_goblin`, `mob_troll`, `mob_dragon`, etc.)
- **10 sprites PNJ** : un par classe (`pnj_villager`, `pnj_merchant`, `pnj_guard`, `pnj_noble`, `pnj_warrior`, `pnj_mage`, `pnj_healer`, `pnj_blacksmith`, `pnj_farmer`, `pnj_hunter`)

### Sprites PNJ par classe

**Fichier** : `src/Controller/Api/MapApiController.php`

- Les PNJ utilisent un spriteKey basé sur leur `classType` (ex: un PNJ `merchant` utilise le sprite `pnj_merchant`)
- Fallback vers `pnj_default` si le spriteKey n'existe pas dans la config
- Suppression de l'ancien `getSpriteConfig()` privé, remplacé par `SpriteConfigProvider`

---

## Fichiers créés

| Fichier | Description |
|---------|-------------|
| `src/GameEngine/Map/PortalDetector.php` | Détection de portails aux coordonnées du joueur |
| `src/GameEngine/Map/SpriteConfigProvider.php` | Configuration centralisée des sprites |
| `docs/TILED_GUIDE.md` | Guide de création de cartes Tiled |
| `docs/PHASE1_CHANGELOG.md` | Ce fichier |

## Fichiers modifiés

| Fichier | Nature des changements |
|---------|----------------------|
| `src/Entity/App/ObjectLayer.php` | Nouveaux types + champs destination portail |
| `src/Command/TerrainImportCommand.php` | `--sync-entities`, export objects JSON, validation enrichie |
| `src/GameEngine/Movement/PlayerMoveProcessor.php` | Détection portails post-déplacement |
| `src/Controller/Api/MapApiController.php` | Endpoint teleport, portails dans entities, radius filter, SpriteConfigProvider |
| `assets/controllers/map_pixi_controller.js` | Transition portail (fade + reload), rendu portails, preload mid-path, radius entities |

## Schema BDD

3 colonnes ajoutées à la table `object_layer` :
- `destination_map_id` (integer, nullable)
- `destination_coordinates` (varchar, nullable)
