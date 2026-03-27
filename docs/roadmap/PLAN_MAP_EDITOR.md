# Plan — Editeur de cartes integre & generateur procedural

> **Numerotation :** les jalons de **ce** document sont prefixes **MED-** (Map EDitor). Ils n'entrent **pas** en conflit avec les numeros de la roadmap globale (`ROADMAP_TODO_VAGUE_*.md`). Les prerequis **roadmap globale** sont indiques en clair (ex. **44**, **57**, **58**).

> Editeur web complet remplacant le logiciel Tiled pour la creation et l'edition de cartes.
> Format JSON natif stocke en DB (`Area.fullData`), export TMX optionnel pour validation.
> Generateur procedural de terrain pour creer des cartes jouables automatiquement.

## Vue d'ensemble

**16 jalons** (**MED-01** a **MED-16**) organises en 7 pistes.
Prerequis roadmap globale : **44** (extraction services TerrainImport), **57** (commande terrain:sync), **58** (parsing zones/biomes Tiled).

| Code | Sujet (resume) |
|------|----------------|
| MED-01 | TilesetRegistry — registre des tilesets |
| MED-02 | MapFactory — creation carte vierge (taille au choix) |
| MED-03 | Tileset Picker — palette de tiles cliquable |
| MED-04 | Stamp Brush & Eyedropper |
| MED-05 | Eraser |
| MED-06 | Bucket Fill (flood fill) |
| MED-07 | Gestion des layers |
| MED-08 | Undo / Redo |
| MED-09 | Creation d'entites via menu contextuel |
| MED-10 | Edition d'entites inline |
| MED-11 | WangTileResolver backend |
| MED-12 | Auto-tiling frontend |
| MED-13 | Generateur procedural — moteur |
| MED-14 | Generateur procedural — biomes |
| MED-15 | Generateur procedural — objets & connectivite |
| MED-16 | Export TMX & tests E2E |

```
Piste A — Fondations & infrastructure     : MED-01 → MED-02
Piste B — Outils de peinture              : MED-03 → MED-04 → MED-05 → MED-06
Piste C — Gestion layers & historique     : MED-07, MED-08
Piste D — Entites                         : MED-09 → MED-10
Piste E — Auto-tiling                     : MED-11 → MED-12
Piste F — Generateur procedural           : MED-13 → MED-14 → MED-15
Piste G — Export & qualite                : MED-16
```

---

## Piste A — Fondations & infrastructure (sequentiel)

### MED-01 — TilesetRegistry — registre PHP des tilesets (S | ★★★ | CRITIQUE)
> Registre centralise remplacant la lecture des fichiers .tsx de Tiled. Fondation de tout l'editeur. Prerequis roadmap globale : **44**, **58**
- [ ] Classe `TilesetRegistry` dans `src/GameEngine/Terrain/TilesetRegistry.php`
- [ ] Methode `getTilesets(): array` — retourne les 4 tilesets avec : name, firstGid, columns, tileCount, tileWidth, tileHeight, imagePath
- [ ] Methode `getTilesetForGid(int $gid): ?array` — resout le tileset d'un GID
- [ ] Methode `getLocalTileId(int $gid): int` — convertit GID global en ID local tileset
- [ ] Ordre fixe : Terrain (firstGid=1), forest (1025), BaseChip_pipo (4097), Collisions (5161)
- [ ] Constantes publiques pour les GID cles (herbe variantes, eau, collision mur, etc.)
- [ ] Injecter dans `MapEditorController` et `MapApiController` en remplacement des lectures .tsx
- [ ] Tests unitaires : resolution GID, conversion local/global

### MED-02 — MapFactory — creation de carte vierge (S | ★★★ | CRITIQUE)
> Permet de creer des cartes de taille configurable depuis l'admin. Prerequis : ← MED-01
- [ ] Classe `MapFactory` dans `src/GameEngine/Terrain/MapFactory.php`
- [ ] Methode `createBlankMap(string $name, int $width, int $height, World $world): Map`
  - Cree l'entite `Map` avec areaWidth/areaHeight = width/height
  - Cree une `Area` avec `fullData` initialise (toutes les cells a GID 0, mouvement 0)
  - Format `fullData` : `{"width": W, "height": H, "tileWidth": 32, "tileHeight": 32, "cells": {"x.y": {"layers": [0,0,0,0], "mouvement": 0, "borders": [0,0,0,0]}}}`
- [ ] Validation : width/height entre 10 et 200, nom unique
- [ ] Route `GET /admin/maps/create` — formulaire (nom, largeur, hauteur, world)
- [ ] Route `POST /admin/maps/create` — appelle `MapFactory`, redirige vers l'editeur
- [ ] Template `templates/admin/map/create.html.twig`
- [ ] Lien "Nouvelle carte" dans la liste admin des cartes
- [ ] Tester : creer une carte 40x30, verifier Area.fullData en DB, ouvrir dans l'editeur

---

## Piste B — Outils de peinture (sequentiel)

### MED-03 — Tileset Picker — palette de tiles (M | ★★★ | CRITIQUE) ✅
> Panneau lateral avec les tilesets en grille cliquable. Prerequis : ← MED-01
- [x] Controller Stimulus `admin_tileset_picker_controller.js` dans `assets/controllers/`
- [x] Chargement des images tileset via les URLs de `TilesetRegistry` (route `/editor/tilesets`)
- [x] Rendu canvas de chaque tileset en grille 32x32 avec scroll
- [x] Onglets par tileset : Terrain, Forest, BaseChip (pas Collisions — gere automatiquement)
- [x] Clic sur une tile = selection d'un stamp 1x1, affichage du GID selectionne
- [x] Drag rectangle = selection multi-tiles (stamp NxM), apercu dans un mini-canvas
- [x] Selecteur de layer actif : background / ground / decoration / overlay (radio buttons)
- [x] Highlight visuel de la tile/zone selectionnee
- [x] Integration dans le template editeur existant (remplacement du panneau lateral droit)
- [x] Tester : ouvrir l'editeur, naviguer entre tilesets, selectionner des tiles et stamps

### MED-04 — Stamp Brush & Eyedropper (M | ★★★ | CRITIQUE) ✅
> Outil principal de peinture. Prerequis : ← MED-03
- [x] Outil Stamp Brush dans `admin_map_editor_controller.js` :
  - Clic gauche sur la carte peint le stamp selectionne sur le layer actif
  - Drag = peinture continue (trail de tiles)
  - Preview fantome du stamp sous le curseur avant le clic
  - Accumulation des changements en memoire (pas d'envoi a chaque clic)
- [x] Outil Eyedropper :
  - Alt+clic gauche capture le GID de la tile sous le curseur (layer actif)
  - Met a jour la selection dans le Tileset Picker
  - Bascule automatiquement en mode peinture
- [x] Route `POST /{id}/editor/paint-tiles` dans `MapEditorController` :
  - Body : `{cells: [{x, y, layer, gid}, ...]}` (batch)
  - Met a jour `Area.fullData.cells[x.y].layers[layer]` pour chaque cell
  - Retourne 200 + count des cells modifiees
- [x] Bouton "Sauvegarder" pour envoyer le batch accumule (reutiliser le mecanisme existant)
- [x] Compteur de modifications en attente (badge existant)
- [x] Rendu immediat sur le canvas (optimistic rendering)
- [x] Tester : peindre des tiles, sauvegarder, recharger → les tiles persistent

### MED-05 — Eraser (S | ★★ | HAUTE) ✅
> Outil de gomme. Prerequis : ← MED-04
- [x] Outil Eraser dans la toolbar (bouton Gomme, raccourci E)
- [x] Clic gauche met le GID a 0 sur le layer actif pour la cell ciblee
- [x] Drag = gomme continue
- [x] Reutilise la meme route `paint-tiles` (avec gid: 0)
- [x] Rendu immediat (preview rouge avec X sous le curseur)
- [x] Raccourcis clavier outils : V (selection), P (peindre), E (gomme), B (bloquer), U (debloquer), W (mur)
- [x] Raccourcis layers : 1/2/3/4, Ctrl+S (sauvegarder)

### MED-06 — Bucket Fill — flood fill (S | ★★ | HAUTE)
> Remplissage par inondation. Prerequis : ← MED-04
- [ ] Module `assets/lib/BucketFill.js` :
  - Algorithme BFS : remplit les cells connectees ayant le meme GID cible
  - Parametre : startX, startY, layerIndex, newGid, cells (reference au fullData local)
  - Limite de securite : max `width × height` cells (evite les boucles infinies)
  - Retourne la liste des cells modifiees
- [ ] Outil Bucket Fill dans la toolbar (icone seau, raccourci F)
- [ ] Clic gauche declenche le fill sur le layer actif
- [ ] Route `POST /{id}/editor/fill` dans `MapEditorController` :
  - Body : `{x, y, layer, gid}` — le serveur execute le flood fill cote backend aussi
  - Retourne la liste des cells modifiees
- [ ] Execution cote client d'abord (preview immediat) puis confirmation serveur
- [ ] Tester : remplir une carte vierge en herbe, remplir une zone fermee

---

## Piste C — Gestion layers & historique (parallelisable)

### MED-07 — Gestion des layers (S | ★★ | HAUTE)
> Controle de visibilite et selection des layers. Prerequis : ← MED-03
- [ ] Panneau layers dans le template editeur (sous le tileset picker) :
  - 4 layers : Background, Ground, Decoration, Overlay
  - Checkbox visibilite par layer (oeil)
  - Radio button layer actif (celui qui recoit la peinture)
  - Opacite reduite pour les layers non-actifs (aide visuelle)
- [ ] Rendu canvas : masquer/afficher les layers selon les toggles
- [ ] Layer collision toujours visible via l'overlay existant (rouge/bleu/vert)
- [ ] Raccourcis clavier : 1/2/3/4 pour selectionner le layer actif
- [ ] Tester : masquer/afficher des layers, peindre sur un layer specifique

### MED-08 — Undo / Redo (S | ★★ | HAUTE)
> Historique des modifications. Prerequis : ← MED-04
- [ ] Module `assets/lib/MapEditorHistory.js` :
  - Classe `EditorHistory` avec stack undo et stack redo
  - Chaque operation : `{type: 'paint'|'fill'|'erase'|'entity', cells: [{x, y, layer, oldGid, newGid}]}`
  - `push(operation)` : ajoute a la stack undo, vide la stack redo
  - `undo()` : depile undo, applique les oldGid, empile dans redo
  - `redo()` : depile redo, applique les newGid, empile dans undo
  - Limite : 50 operations en memoire
- [ ] Raccourcis : Ctrl+Z (undo), Ctrl+Y ou Ctrl+Shift+Z (redo)
- [ ] Boutons Undo/Redo dans la toolbar (grises si stack vide)
- [ ] L'undo/redo modifie l'etat local — le "Sauvegarder" envoie l'etat final
- [ ] Tester : peindre, undo, redo, sauvegarder → etat correct

---

## Piste D — Entites (sequentiel)

### MED-09 — Creation d'entites via menu contextuel (M | ★★★ | HAUTE) ✅
> Remplace la creation d'entites via formulaires separes. Prerequis roadmap globale : **57**
- [x] Menu contextuel HTML (clic droit sur une case walkable) :
  - "Ajouter un mob" → formulaire inline panneau lateral
  - "Ajouter un portail" → formulaire inline panneau lateral
  - "Ajouter un spot de recolte" → formulaire inline panneau lateral
  - "Ajouter un PNJ" → formulaire inline panneau lateral
  - Si entite deja presente : "Supprimer"
- [x] Formulaires inline dans le panneau lateral (section dynamique) :
  - Mob : select monster (depuis DB), input level
  - Portail : input nom, select map destination, input coordonnees destination
  - Harvest spot : input nom, input slug, select outil requis, input delai respawn
  - PNJ : input nom, input classe
- [x] Route `POST /{id}/editor/create-entity` dans `MapEditorController` :
  - Body : `{type, x, y, properties: {...}}`
  - Cree l'`ObjectLayer` (ou `Mob`/`Pnj`) en DB avec les coordonnees `"x.y"`
  - Validation : cell walkable (mouvement != -1)
  - Retourne l'entite creee en JSON
- [x] Route `GET /{id}/editor/entity-options` : liste monstres, cartes, PNJ existants
- [x] L'entite apparait immediatement sur le canvas (marqueur colore existant)
- [x] Listes de choix alimentees par l'API (monsters, maps existantes)
- [x] Tester : clic droit → creer mob → visible sur canvas → verifier en DB

### MED-10 — Edition d'entites inline (S | ★★ | MOYENNE) ✅
> Modifier les proprietes des entites directement depuis l'editeur. Prerequis : ← MED-09
- [x] Clic sur une entite existante → panneau lateral affiche ses proprietes editables
- [x] Route `POST /{id}/editor/update-entity` dans `MapEditorController` :
  - Body : `{entityId, entityType, properties: {...}}`
  - Met a jour l'entite en DB
  - Retourne l'entite modifiee en JSON
- [x] Modification du level, monstre, nom, coordonnees destination (pour portails), outil requis (pour harvest spots), classe (pour PNJ)
- [x] Bouton ✎ dans le panneau de cellule + option "Editer" dans le menu contextuel
- [x] Tester : modifier un mob, sauvegarder, verifier en DB

---

## Piste E — Auto-tiling (sequentiel)

### MED-11 — WangTileResolver — backend (M | ★★★ | HAUTE) ✅
> Moteur d'auto-tiling pour les transitions de terrain. Prerequis : ← MED-01
- [x] Classe `WangTileResolver` dans `src/GameEngine/Terrain/WangTileResolver.php`
- [x] Definitions de wangsets (extraits de `Terrain.tsx`) en PHP :
  - 25 terrain types supportes (eau, sable, terre, chemin, neige, etc.)
  - Table de lookup : configuration 4-corners → GID correspondant
  - Corners : chaque cell a 4 coins, chaque coin touche 4 cells voisines
- [x] Methode `resolve(array $cells, int $x, int $y, int $layer, string $terrainSlug): int`
- [x] Methode `resolveZone(array &$cells, ...): array`
- [x] Route `POST /{id}/editor/auto-tile` dans `MapEditorController`
- [x] Route `GET /{id}/editor/wangsets` — export des definitions pour le frontend
- [x] Tests unitaires : 21 tests (detection terrain, ile, voisins, bord, peninsule, zone, idempotence)

### MED-12 — Auto-tiling frontend (M | ★★ | HAUTE)
> Preview temps reel des transitions cote client. Prerequis : ← MED-11, MED-04
- [ ] Module `assets/lib/WangTileResolverJs.js` :
  - Meme logique que le PHP (table de lookup 4-corners → GID)
  - Export des definitions wangsets comme constantes JS
- [ ] Mode auto-tile dans la toolbar (toggle on/off, raccourci T) :
  - Quand actif : le stamp brush applique le terrain type choisi
  - Apres peinture d'une tile, recalcul automatique des transitions sur les 8 voisins
  - Preview immediat sur le canvas
- [ ] Synchronisation serveur : lors du "Sauvegarder", envoi des tiles + zones auto-tiled
- [ ] Terrains supportes en v1 : herbe/eau, herbe/sable, herbe/terre, herbe/chemin
- [ ] Tester : activer auto-tile, peindre de l'eau → transitions bordures correctes

---

## Piste F — Generateur procedural (sequentiel)

### MED-13 — Generateur procedural — moteur (M | ★★★ | HAUTE)
> Genere du terrain naturel via bruit de Perlin. Prerequis : ← MED-01, MED-02
- [ ] Classe `PerlinNoise` dans `src/GameEngine/Terrain/Generator/PerlinNoise.php` :
  - Bruit 2D deterministe (seed configurable)
  - Methode `noise(float $x, float $y): float` — retourne [-1, 1]
  - Support octaves pour detail (lacunarite, persistence)
- [ ] Classe `MapGenerator` dans `src/GameEngine/Terrain/Generator/MapGenerator.php` :
  - Methode `generate(Map $map, BiomeDefinition $biome, int $difficulty, ?int $seed): void`
  - Pipeline : heightmap → layers background/ground → collisions
  - Layer background : remplissage variantes herbe du biome (selection aleatoire ponderee)
  - Layer ground : eau (height < 0.25), sable (0.25-0.35), terrain biome (0.35+)
  - Collisions auto-derivees : eau = -1, bords de carte optionnels
  - Ecrit directement dans `Area.fullData`
- [ ] Interface `BiomeDefinition` dans `src/GameEngine/Terrain/Generator/BiomeDefinition.php` :
  - `getBackgroundGids(): array` — variantes de sol
  - `getWaterThreshold(): float`
  - `getTreeDensity(): float`
  - `getTreeGids(): array`
  - `getAvailableMobs(): array` — slugs de monstres par difficulte
  - `getHarvestItems(): array` — items recoltables
  - `getWeather(): ?string`, `getMusic(): ?string`
- [ ] Tester : generer une carte 60x60 avec heightmap, verifier visuellement

### MED-14 — Generateur procedural — biomes (M | ★★ | HAUTE)
> Definitions de biomes pour le generateur. Prerequis : ← MED-13
- [ ] `ForestBiome` dans `src/GameEngine/Terrain/Generator/Biome/ForestBiome.php` :
  - Herbe variantes (GID 293, 353, 354, 355)
  - Densite arbres : 30-50%, clustering via automate cellulaire
  - Tiles arbres : forest.tsx (selection de GID representatifs pour troncs et feuillage)
  - Mobs : slime (diff 1-3), goblin (4-6), spider (5-7), skeleton (7-10)
  - Items : healing-herb, sage, rosemary, wood
  - Musique : foret, weather : clear/rain
- [ ] `PlainsBiome` dans `src/GameEngine/Terrain/Generator/Biome/PlainsBiome.php` :
  - Herbe variantes, densite arbres : 5-15%
  - Mobs : slime (1-3), giant_rat (2-4), bat (3-5), venom_snake (5-7)
  - Items : dandelion, mint, lavender
  - Musique : plaines, weather : clear/wind
- [ ] `SwampBiome` dans `src/GameEngine/Terrain/Generator/Biome/SwampBiome.php` :
  - Herbe sombre + zones eau etendues (water threshold plus haut : 0.35)
  - Densite arbres morts : 15-25%
  - Mobs : zombie (5-8), banshee (7-10), spider (5-7), ochu (8-12)
  - Items : poisonous-mushroom, swamp-root
  - Musique : marecage, weather : fog
- [ ] Layer decoration : placement arbres via automate cellulaire (3-4 iterations de lissage)
- [ ] Tester : generer 3 cartes (foret, plaines, marecage), comparer visuellement

### MED-15 — Generateur procedural — objets & connectivite (M | ★★★ | HAUTE)
> Placement automatique d'entites et verification de jouabilite. Prerequis : ← MED-14
- [ ] Classe `ObjectPlacer` dans `src/GameEngine/Terrain/Generator/ObjectPlacer.php` :
  - `placeMobSpawns(Map, BiomeDefinition, int $difficulty)` :
    - 8-15 spawns par carte, repartis uniformement sur cells walkables
    - Slug et level selectionnes selon biome + difficulty
  - `placeHarvestSpots(Map, BiomeDefinition)` :
    - 5-10 spots, proches des arbres (foret) ou des rivages (plaines)
    - Item slug selon biome
  - `placePortals(Map, array $adjacentMaps)` :
    - Portails aux bords (nord, sud, est, ouest) sur cells walkables
    - Coordonnees cible calculees (bord oppose de la carte adjacente)
  - `placeZones(Map, BiomeDefinition)` :
    - 1-3 rectangles zone avec biome, weather, music, light_level
- [ ] Verification connectivite (flood fill) :
  - Apres generation, verifier que toutes les cells walkables forment un graphe connexe
  - Si ilots isoles : creuser des passages pour connecter
  - Tous les portails doivent etre atteignables
- [ ] Bouton "Generer" dans l'editeur admin :
  - Formulaire modal : biome (select), difficulte (1-10), seed (optionnel)
  - Avertissement : "Ecrase le contenu existant"
  - Route `POST /admin/maps/{id}/generate` → appelle MapGenerator + ObjectPlacer
- [ ] Tester : generer une carte complete, verifier entites en DB, naviguer en jeu

---

## Piste G — Export & qualite (parallelisable)

### MED-16 — Export TMX & tests E2E (M | ★★ | MOYENNE)
> Export optionnel pour validation dans Tiled + tests de bout en bout. Prerequis : ← MED-04, MED-09
- [ ] Classe `TmxExporter` dans `src/GameEngine/Terrain/TmxExporter.php` :
  - Methode `export(Map $map): string` — retourne le XML TMX valide
  - Genere les 5 layers (background, ground, decoration, overlay, collision) en CSV
  - Genere l'objectgroup avec les entites (portals, mob_spawn, harvest_spot, npc_spawn)
  - Ordre tilesets conforme a `TilesetRegistry`
  - Coordonnees objets en pixels (x*32, y*32)
- [ ] Route `GET /admin/maps/{id}/export-tmx` :
  - Retourne le fichier TMX en telechargement (Content-Disposition: attachment)
  - Nom fichier : `world-{worldId}-map-{mapName}.tmx`
- [ ] Bouton "Exporter TMX" dans l'editeur
- [ ] Tests E2E :
  - Creer carte vierge → peindre tiles → sauvegarder → recharger → tiles correctes
  - Creer entite → verifier en DB → verifier sur canvas apres rechargement
  - Generer procedurallement → naviguer en jeu → carte jouable
  - Exporter TMX → reimporter via `app:terrain:import` → donnees identiques
- [ ] Tests unitaires TilesetRegistry, MapFactory, WangTileResolver, TmxExporter

---

## Ordre d'implementation recommande

```
Phase 1 (fondation)     : MED-01 → MED-02                          (2 sessions)
Phase 2 (peinture)      : MED-03 → MED-04 → MED-05 → MED-06      (3 sessions)
Phase 3 (UX editeur)    : MED-07, MED-08                           (1 session, parallelisable)
Phase 4 (entites)       : MED-09 → MED-10                          (1 session)
Phase 5 (auto-tiling)   : MED-11 → MED-12                          (1-2 sessions)
Phase 6 (generateur)    : MED-13 → MED-14 → MED-15                 (2-3 sessions)
Phase 7 (export/tests)  : MED-16                                    (1 session)
```

Total estime : **~12 sessions**

### Deblocages

- **MED-02** debloque les taches globales **67** (Foret des murmures) et **68** (Mines profondes) — plus besoin de Tiled pour concevoir ces cartes
- **MED-13 a MED-15** permettent de generer rapidement du contenu pour les cartes de la Vague 4+
