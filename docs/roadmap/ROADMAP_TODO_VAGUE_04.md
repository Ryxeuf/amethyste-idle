## Vague 4 — Monde & systemes avances

> **Priorite absolue : Editeur de cartes integre (MED-01 a MED-16)**
> Tous les prerequis globaux (44, 57, 58) sont termines. L'editeur debloque la creation rapide de contenu pour les vagues suivantes.

---

### PRIORITE 1 — Editeur de cartes integre

Editeur web complet remplacant Tiled : peinture de tiles, auto-tiling, creation d'entites, generation procedurale. Permet la creation de cartes a taille configurable directement depuis l'admin.

**Prerequis roadmap globale :** ✅ **44** (Extraction services TerrainImport), ✅ **57** (Commande terrain:sync), ✅ **58** (Parsing zones/biomes Tiled) — **tous termines**

**Documentation detaillee :** [PLAN_MAP_EDITOR.md](PLAN_MAP_EDITOR.md) — jalons **MED-01** a **MED-16**

#### Phase 1 — Fondations & infrastructure (MED-01, MED-02)

**MED-01 — TilesetRegistry — registre PHP des tilesets (S | ★★★ | CRITIQUE)**
> Registre centralise remplacant la lecture des fichiers .tsx de Tiled. Fondation de tout l'editeur.
- [ ] Classe `TilesetRegistry` dans `src/GameEngine/Terrain/TilesetRegistry.php`
- [ ] Methodes `getTilesets()`, `getTilesetForGid()`, `getLocalTileId()`
- [ ] Constantes publiques pour les GID cles
- [ ] Tests unitaires

**MED-02 — MapFactory — creation de carte vierge (S | ★★★ | CRITIQUE)**
> Permet de creer des cartes de taille configurable depuis l'admin. Prerequis : ← MED-01
- [ ] Classe `MapFactory` dans `src/GameEngine/Terrain/MapFactory.php`
- [ ] Routes admin `/admin/maps/create` (GET + POST)
- [ ] Template + lien dans la liste admin
- [ ] Tester creation carte + verification Area.fullData

#### Phase 2 — Outils de peinture (MED-03 a MED-06)

**MED-03 — Tileset Picker — palette de tiles (M | ★★★ | CRITIQUE)**
> Panneau lateral avec les tilesets en grille cliquable. Prerequis : ← MED-01
- [ ] Controller Stimulus `admin_tileset_picker_controller.js`
- [ ] Onglets par tileset, selection stamp 1x1 et NxM
- [ ] Selecteur de layer actif

**MED-04 — Stamp Brush & Eyedropper (M | ★★★ | CRITIQUE)**
> Outil principal de peinture. Prerequis : ← MED-03
- [ ] Stamp Brush (clic/drag, preview fantome, batch)
- [ ] Eyedropper (clic droit / Alt+clic)
- [ ] Route `POST /{id}/editor/paint-tiles`
- [ ] Rendu immediat + sauvegarde batch

**MED-05 — Eraser (S | ★★ | HAUTE)**
> Outil de gomme. Prerequis : ← MED-04
- [ ] Gomme (GID a 0), raccourci E, reutilise paint-tiles

**MED-06 — Bucket Fill (S | ★★ | HAUTE)**
> Remplissage par inondation. Prerequis : ← MED-04
- [ ] Module `BucketFill.js` (BFS), route `POST /{id}/editor/fill`

#### Phase 3 — UX editeur (MED-07, MED-08) — parallelisable

**MED-07 — Gestion des layers (S | ★★ | HAUTE)**
> Controle de visibilite et selection des layers. Prerequis : ← MED-03
- [ ] Panneau layers (visibilite, layer actif, opacite)
- [ ] Raccourcis 1/2/3/4

**MED-08 — Undo / Redo (S | ★★ | HAUTE)**
> Historique des modifications. Prerequis : ← MED-04
- [ ] Module `MapEditorHistory.js` (stack undo/redo, 50 ops)
- [ ] Raccourcis Ctrl+Z / Ctrl+Y

#### Phase 4 — Entites (MED-09, MED-10)

**MED-09 — Creation d'entites via menu contextuel (M | ★★★ | HAUTE)**
> Prerequis : ← MED-04
- [ ] Menu contextuel clic droit (mob, portail, spot recolte, PNJ)
- [ ] Formulaires inline panneau lateral
- [ ] Route `POST /{id}/editor/create-entity`

**MED-10 — Edition d'entites inline (S | ★★ | MOYENNE)**
> Prerequis : ← MED-09
- [ ] Clic entite → panneau edition
- [ ] Route `POST /{id}/editor/update-entity`

#### Phase 5 — Auto-tiling (MED-11, MED-12)

**MED-11 — WangTileResolver backend (M | ★★★ | HAUTE)**
> Moteur d'auto-tiling pour les transitions de terrain. Prerequis : ← MED-01
- [ ] Classe `WangTileResolver`, table lookup 4-corners
- [ ] Route `POST /{id}/editor/auto-tile`
- [ ] Tests unitaires

**MED-12 — Auto-tiling frontend (M | ★★ | HAUTE)**
> Preview temps reel. Prerequis : ← MED-11, MED-04
- [ ] Module `WangTileResolverJs.js`, mode auto-tile toggle (raccourci T)

#### Phase 6 — Generateur procedural (MED-13 a MED-15)

**MED-13 — Generateur procedural — moteur (M | ★★★ | HAUTE)**
> Bruit de Perlin + pipeline generation. Prerequis : ← MED-01, MED-02
- [ ] Classes `PerlinNoise`, `MapGenerator`, `BiomeDefinition`

**MED-14 — Generateur procedural — biomes (M | ★★ | HAUTE)**
> Foret, plaines, marecage. Prerequis : ← MED-13
- [ ] `ForestBiome`, `PlainsBiome`, `SwampBiome`

**MED-15 — Generateur procedural — objets & connectivite (M | ★★★ | HAUTE)**
> Placement entites + verification jouabilite. Prerequis : ← MED-14
- [ ] `ObjectPlacer`, verification connectivite flood fill
- [ ] Bouton "Generer" dans l'editeur admin

#### Phase 7 — Export & qualite (MED-16)

**MED-16 — Export TMX & tests E2E (M | ★★ | MOYENNE)**
> Prerequis : ← MED-04, MED-09
- [ ] Classe `TmxExporter`, route export, tests E2E complets

---

### PRIORITE 2 — Contenu (debloque par l'editeur)

### 68 — Mines profondes (L | ★★★)
> Carte de contenu lvl 10-25 avec tunnels, boss de mine et filons a exploiter. Prerequis : ← 30 (Teleportation entre cartes), 47 (Monstres tier 1), 66 (Boss de zone), **MED-02** (MapFactory)
> **Note :** a realiser avec l'editeur integre (MED-02+) au lieu de Tiled.
- [ ] Design carte ~60x30 via l'editeur integre (tunnels, salles, filons)
- [ ] Import BDD + portails vers hub
- [ ] Placement 8-10 mobs (monstres C-7a + C-7b)
- [ ] Boss de mine (C-7c)
- [ ] 3-5 PNJ (mineur, ingenieur, marchand souterrain)
- [ ] 5-8 spots de recolte (minerais) — si v0.4 recolte pret
- [ ] Coffre tresor (si systeme implemente)

---

### PRIORITE 3 — Controle de cite par les guildes (plan detaille)

Competition PvE entre guildes pour le controle temporaire des villes : les guildes accumulent des points d'influence sur une saison via combat, craft, recolte et quetes dans les regions liees aux cartes.

**Prerequis roadmap globale :** **38** (liste d'amis), **48** (village central hub), **52** (guildes fondation — detail en Vague 3). La tache **52** couvre le socle minimal ; le plan dedie decrit entites fines, saisons, moteur d'influence et benefices economiques.

**Documentation :** [PLAN_GUILD_CITY_CONTROL.md](PLAN_GUILD_CITY_CONTROL.md) — *jalons internes **GCC-01** a **GCC-20** (sans collision avec les numeros de la roadmap globale). **GCC-01** prolonge la tache globale **52** (guildes fondation).*

---

### Taches completees (Vague 4)

### ~~64 — Equipement tier 3 + slots materia (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~65 — Monstres tier 2 avances lvl 15-25 (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~66 — Boss de zone (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~67 — Foret des murmures (L | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~69 — Monstres invocateurs (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~70 — Slots materia lies (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~71 — World boss spawn & combat (L | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~72 — Donjons entite & entree (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~73 — Guildes chat (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~74 — Guildes coffre partage (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~75 — PNJ routines (L | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~76 — Sets d'equipement (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~77 — Effets ambiance par zone (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~78 — Equilibrage & rapport (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~79 — Evenements bonus/festivals (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---
