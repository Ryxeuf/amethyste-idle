# Plan — Editeur de cartes integre & generateur procedural

> **Numerotation :** les jalons de **ce** document sont prefixes **MED-** (Map EDitor). Ils n'entrent **pas** en conflit avec les numeros de la roadmap globale (`SPRINT_*.md`). Les prerequis **roadmap globale** sont indiques en clair (ex. **44**, **57**, **58**).

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

### MED-01 — TilesetRegistry — registre PHP des tilesets (S | ★★★ | CRITIQUE) ✅
> Registre centralise remplacant la lecture des fichiers .tsx de Tiled. Fondation de tout l'editeur. Prerequis roadmap globale : **44**, **58**
- [x] Classe `TilesetRegistry` (328 lignes) : getTilesets(), getTilesetForGid(), getLocalTileId(), getNextAvailableFirstGid()
- [x] 4 tilesets built-in + support tilesets custom en DB
- [x] Tests unitaires

### MED-02 — MapFactory — creation de carte vierge (S | ★★★ | CRITIQUE) ✅
> Permet de creer des cartes de taille configurable depuis l'admin. Prerequis : ← MED-01
- [x] Classe `MapFactory` (82 lignes) : createBlankMap(), buildBlankFullData()
- [x] Routes admin /admin/maps/create (GET + POST)
- [x] Template + lien dans la liste admin

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

### MED-06 — Bucket Fill — flood fill (S | ★★ | HAUTE) ✅
> Remplissage par inondation. Prerequis : ← MED-04
- [x] BFS flood fill dans le controller Stimulus, raccourci G, reutilise paint-tiles pour la sauvegarde
- [x] Outil Bucket Fill dans la toolbar (icone seau, raccourci G)
- [x] Clic gauche declenche le fill sur le layer actif
- [x] Execution cote client (preview immediat) puis sauvegarde via batch paint-tiles

---

## Piste C — Gestion layers & historique (parallelisable)

### MED-07 — Gestion des layers (S | ★★ | HAUTE) ✅
> Controle de visibilite et selection des layers. Prerequis : ← MED-03
- [x] Panneau layers (visibilite, layer actif, opacite)
- [x] Raccourcis 1/2/3/4 pour selectionner le layer actif

### MED-08 — Undo / Redo (S | ★★ | HAUTE) ✅
> Historique des modifications. Prerequis : ← MED-04
- [x] Module `MapEditorHistory.js` (stack undo/redo, 50 ops)
- [x] Raccourcis Ctrl+Z / Ctrl+Y

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

### MED-12 — Auto-tiling frontend (M | ★★ | HAUTE) ✅
> Preview temps reel des transitions cote client. Prerequis : ← MED-11, MED-04
- [x] Module `assets/lib/WangTileResolverJs.js` :
  - Meme logique que le PHP (table de lookup 4-corners → GID)
  - Export des definitions wangsets comme constantes JS
- [x] Mode auto-tile dans la toolbar (toggle on/off, raccourci T) :
  - Quand actif : le stamp brush applique le terrain type choisi
  - Apres peinture d'une tile, recalcul automatique des transitions sur les 8 voisins
  - Preview immediat sur le canvas
- [x] Synchronisation serveur : lors du "Sauvegarder", envoi des tiles + zones auto-tiled
- [x] 25 terrains supportes (tous les types du WangTileResolver PHP)
- [x] Tester : activer auto-tile, peindre de l'eau → transitions bordures correctes

---

## Piste F — Generateur procedural (sequentiel)

### MED-13 — Generateur procedural — moteur (M | ★★★ | HAUTE) ✅
> Genere du terrain naturel via bruit de Perlin. Prerequis : ← MED-01, MED-02
- [x] Classe `PerlinNoise` dans `src/GameEngine/Terrain/Generator/PerlinNoise.php` :
  - Bruit 2D deterministe (seed configurable)
  - Methode `noise(float $x, float $y): float` — retourne [-1, 1]
  - Support octaves pour detail (lacunarite, persistence)
- [x] Classe `MapGenerator` dans `src/GameEngine/Terrain/Generator/MapGenerator.php` :
  - Methode `generate(Map $map, BiomeDefinition $biome, int $difficulty, ?int $seed): void`
  - Pipeline : heightmap → layers background/ground → collisions → arbres
  - Layer background : remplissage variantes herbe du biome (selection aleatoire ponderee)
  - Layer ground : eau (height < 0.25), sable (0.25-0.35), terrain biome (0.35+)
  - Collisions auto-derivees : eau = -1, bords de carte optionnels
  - Ecrit directement dans `Area.fullData`
- [x] Interface `BiomeDefinition` dans `src/GameEngine/Terrain/Generator/BiomeDefinition.php` :
  - `getBackgroundGids(): array` — variantes de sol
  - `getWaterThreshold(): float`
  - `getTreeDensity(): float`
  - `getTreeGids(): array`
  - `getAvailableMobs(): array` — slugs de monstres par difficulte
  - `getHarvestItems(): array` — items recoltables
  - `getWeather(): ?string`, `getMusic(): ?string`
- [x] Tester : tests unitaires PerlinNoise + MapGenerator

### MED-14 — Generateur procedural — biomes (M | ★★ | HAUTE) ✅
> Definitions de biomes pour le generateur. Prerequis : ← MED-13
- [x] `ForestBiome` dans `src/GameEngine/Terrain/Generator/Biome/ForestBiome.php` :
  - Herbe dark_grass + variantes classiques
  - Densite arbres : 40%, clustering via automate cellulaire (3 iterations)
  - Tiles arbres : forest tileset (feuillus)
  - Mobs : slime (diff 1-3), goblin (4-6), spider (5-7), skeleton (7-10)
  - Items : healing-herb, sage, rosemary, wood
- [x] `PlainsBiome` dans `src/GameEngine/Terrain/Generator/Biome/PlainsBiome.php` :
  - Herbe variantes, densite arbres : 10%
  - Mobs : slime (1-3), giant_rat (2-4), bat (3-5), venom_snake (5-7)
  - Items : dandelion, mint, lavender
- [x] `SwampBiome` dans `src/GameEngine/Terrain/Generator/Biome/SwampBiome.php` :
  - Herbe long_grass/dark_grass + zones eau etendues (water threshold 0.35)
  - Densite arbres morts : 20%, terrain sewer_water/earth
  - Mobs : zombie (5-8), banshee (7-10), spider (5-7), ochu (8-12)
  - Items : poisonous-mushroom, swamp-root
  - Weather : fog
- [x] Layer decoration : placement arbres via automate cellulaire (3 iterations de lissage)
- [x] Tester : tests unitaires ForestBiome, SwampBiome, MapGenerator avec les 3 biomes

### MED-15 — Generateur procedural — objets & connectivite (M | ★★★ | HAUTE) ✅
> Placement automatique d'entites et verification de jouabilite. Prerequis : ← MED-14
- [x] `ObjectPlacer` (524 lignes) : placeMobSpawns, placeHarvestSpots, placePortals, placeZones
- [x] Verification connectivite flood fill
- [x] Bouton "Generer" dans l'editeur admin

---

## Piste G — Export & qualite (parallelisable)

### MED-16 — Export TMX & tests E2E (M | ★★ | MOYENNE) ✅
> Export optionnel pour validation dans Tiled + tests de bout en bout. Prerequis : ← MED-04, MED-09
- [x] Classe `TmxExporter` dans `src/GameEngine/Terrain/TmxExporter.php` :
  - Methode `export(Map $map): string` — retourne le XML TMX valide
  - Genere les 5 layers (background, ground, decoration, overlay, collision) en CSV
  - Genere l'objectgroup avec les entites (portals, mob_spawn, harvest_spot, npc_spawn)
  - Ordre tilesets conforme a `TilesetRegistry`
  - Coordonnees objets en pixels (x*32, y*32)
- [x] Route `GET /admin/maps/{id}/export-tmx` :
  - Retourne le fichier TMX en telechargement (Content-Disposition: attachment)
  - Nom fichier : `world-{worldId}-map-{mapName}.tmx`
- [x] Bouton "Exporter TMX" dans l'editeur
- [x] Tests unitaires TmxExporter (10 tests, 27 assertions)

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
