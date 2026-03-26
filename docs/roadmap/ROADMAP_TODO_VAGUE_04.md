## Vague 4 — Monde & systemes avances

### Controle de cite par les guildes (plan detaille)

Competition PvE entre guildes pour le controle temporaire des villes : les guildes accumulent des points d'influence sur une saison via combat, craft, recolte et quetes dans les regions liees aux cartes.

**Prerequis roadmap globale :** **38** (liste d'amis), **48** (village central hub), **52** (guildes fondation — detail en Vague 3). La tache **52** couvre le socle minimal ; le plan dedie decrit entites fines, saisons, moteur d'influence et benefices economiques.

**Documentation :** [PLAN_GUILD_CITY_CONTROL.md](PLAN_GUILD_CITY_CONTROL.md) — *jalons internes **GCC-01** a **GCC-20** (sans collision avec les numeros de la roadmap globale). **GCC-01** prolonge la tache globale **52** (guildes fondation).*

---

### Editeur de cartes integre (plan detaille)

Editeur web complet remplacant Tiled : peinture de tiles, auto-tiling, creation d'entites, generation procedurale. Permet la creation de cartes a taille configurable directement depuis l'admin.

**Prerequis roadmap globale :** **44** (Extraction services TerrainImport), **57** (Commande terrain:sync), **58** (Parsing zones/biomes Tiled)

**Documentation :** [PLAN_MAP_EDITOR.md](PLAN_MAP_EDITOR.md) — *jalons internes **MED-01** a **MED-16** (sans collision avec les numeros de la roadmap globale). Debloque les taches **67** (Foret des murmures) et **68** (Mines profondes).*

---

> **16 taches** qui dependent des Vagues 2-3.
> Organisees en 5 pistes paralleles.

---

### Piste A — Contenu avance (‖)

### ~~64 — Equipement tier 3 + slots materia (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### ~~65 — Monstres tier 2 avances lvl 15-25 (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### ~~66 — Boss de zone (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### ~~67 — Foret des murmures (L | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### 68 — Mines profondes (L | ★★★)
> Carte de contenu lvl 10-25 avec tunnels, boss de mine et filons a exploiter. Prerequis : ← 30 (Teleportation entre cartes), 47 (Monstres tier 1), 66 (Boss de zone)
- [ ] **C-10b — Mines profondes (lvl 10-25)**
  - Design Tiled (~60x30), tunnels, salles, filons
  - Import BDD + portails vers hub
  - Placement 8-10 mobs (monstres C-7a + C-7b)
  - Boss de mine (C-7c)
  - 3-5 PNJ (mineur, ingenieur, marchand souterrain)
  - 5-8 spots de recolte (minerais) — si v0.4 recolte pret
  - Coffre tresor (si systeme implemente)

---

### Piste B — Combat avance (‖)

### ~~69 — Monstres invocateurs (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### ~~70 — Slots materia lies (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### ~~71 — World boss spawn & combat (L | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### Piste C — Donjons & events (‖)

### ~~72 — Donjons entite & entree (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### ~~79 — Evenements bonus/festivals (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### Piste D — Social avance (‖)

### ~~73 — Guildes chat (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### ~~74 — Guildes coffre partage (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### ~~75 — PNJ routines (L | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### Piste E — Progression & equilibrage (‖)

### ~~76 — Sets d'equipement (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### ~~77 — Effets ambiance par zone (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### ~~78 — Equilibrage & rapport (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---
