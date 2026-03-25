## Vague 4 — Monde & systemes avances

### Controle de cite par les guildes (plan detaille)

Competition PvE entre guildes pour le controle temporaire des villes : les guildes accumulent des points d'influence sur une saison via combat, craft, recolte et quetes dans les regions liees aux cartes.

**Prerequis roadmap globale :** **38** (liste d'amis), **48** (village central hub), **52** (guildes fondation — detail en Vague 3). La tache **52** couvre le socle minimal ; le plan dedie decrit entites fines, saisons, moteur d'influence et benefices economiques.

**Documentation :** [PLAN_GUILD_CITY_CONTROL.md](PLAN_GUILD_CITY_CONTROL.md) — *jalons internes **GCC-01** a **GCC-20** (sans collision avec les numeros de la roadmap globale). **GCC-01** prolonge la tache globale **52** (guildes fondation).*

---

> **16 taches** qui dependent des Vagues 2-3.
> Organisees en 5 pistes paralleles.

---

### Piste A — Contenu avance (‖)

### 64 — Equipement tier 3 + slots materia (M | ★★)
> Set avance avec slots materia integres pour les builds endgame. Prerequis : ← 29 (Equipement tier 2), 06 (Systeme materia fonctionnel)
- [ ] Set complet 7 pieces — 4 variantes elementaires (Metal, Bete, Lumiere, Ombre)
  - = 28 items au total
- [ ] 1-2 slots materia sur chaque piece avancee
- [ ] Ajouter aux loot tables des monstres lvl 15-25 et boss

---

### ~~65 — Monstres tier 2 avances lvl 15-25 (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### 66 — Boss de zone (M | ★★★)
> Deux boss avec mecaniques de phases, loot unique et succes associes. Prerequis : ← 65 (Monstres tier 2 avances)
- [ ] **Sous-partie C** : 2 boss de zone avec mecaniques de phases
  - Boss Foret (element Bete/Terre, 2 phases)
  - Boss Mine (element Metal/Ombre, 3 phases)
  - Loot unique (equipement tier 3, materia rare)
  - Succes boss (2 achievements)

---

### 67 — Foret des murmures (L | ★★★)
> Carte de contenu lvl 5-15 avec monstres, PNJ, spots de recolte et quetes de zone. Prerequis : ← 30 (Teleportation entre cartes), 28 (Village central hub), 47 (Monstres tier 1)
- [ ] **C-10a — Foret des murmures (lvl 5-15)**
  - Design Tiled (~60x60), arbres, clairiere, riviere
  - Import BDD + portails vers hub
  - Placement 8-10 mobs (monstres C-3 + C-7a)
  - 3-5 PNJ (garde forestier, herboriste, ermite)
  - 5-8 spots de recolte (herbes, bois) — si v0.4 recolte pret
  - 2-3 quetes zone (si v0.4 quetes pret)

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

### 71 — World boss spawn & combat (L | ★★★)
> Boss mondial spawn via evenements, visible sur la carte, combat multi-joueurs avec loot a contribution. Prerequis : ← 21 (Executeur GameEvent), 35 (Annonces Mercure evenements)
- [ ] **Sous-phase A — Spawn** : GameEventExecutor traite `boss_spawn` → creer un Mob boss sur une map donnee (params JSON)
- [ ] **Sous-phase A** : Afficher le world boss sur la carte avec un sprite/aura distinctif
- [ ] **Sous-phase A** : Despawn automatique quand l'event expire (si non vaincu)
- [ ] **Sous-phase B — Combat multi-joueurs** : Permettre a plusieurs joueurs d'engager le meme Mob (Fight partage)
- [ ] **Sous-phase B** : `ContributionTracker` : tracker les degats infliges par chaque joueur pendant le combat
- [ ] **Sous-phase B** : Loot base sur la contribution (top 3 = loot garanti, autres = loot probabiliste)
- [ ] Tester : spawn world boss via event admin, 2+ joueurs l'engagent, loot distribue

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

### 75 — PNJ routines (L | ★★)
> Les PNJ se deplacent selon un horaire in-game, animes sur la carte via Mercure. Prerequis : ← 20 (Horloge in-game & API temps)
- [ ] Entite `PnjSchedule` (pnj, hour, coordinates, map) — table horaire du PNJ
- [ ] Migration SQL
- [ ] `PnjRoutineService` : deplace les PNJ selon l'heure in-game courante
- [ ] Commande Scheduler `app:pnj:routine` (toutes les 5 min)
- [ ] Topic Mercure `map/pnj-move` pour animer le deplacement cote client
- [ ] Animation de marche du PNJ dans le renderer PixiJS (reutiliser SpriteAnimator)
- [ ] Fixtures : 3-5 PNJ avec routines simples (maison ↔ travail ↔ taverne)
- [ ] Gerer le cas ou un joueur parle a un PNJ qui se deplace

---

### Piste E — Progression & equilibrage (‖)

### ~~76 — Sets d'equipement (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### ~~77 — Effets ambiance par zone (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### 78 — Equilibrage & rapport (M | ★★)
> Commande CLI de rapport d'equilibrage et document de reference pour ajuster les stats du jeu. Prerequis : ← 15 (Consommables de base), 17 (Raretes d'equipement), 28 (Monstres tier 2 sous-partie A), 29 (Equipement tier 2)
- [ ] Commande CLI `app:balance:report` :
  - Courbe XP par domaine (actuel vs theorique)
  - Stats monstres par palier (HP, degats, XP donne)
  - Table de drop rates par tier
  - Alertes si desequilibre detecte (monstre trop fort/faible, drop rate aberrant)
- [ ] Document de reference `docs/BALANCE.md` :
  - Courbe de progression XP cible par domaine
  - Bareme des prix boutique (ratio achat/vente 30-50%)
  - Degats/HP attendus par palier de monstre
  - Temps de recolte et rendement par skill level
- [ ] Ajustement des stats monstres/items si desequilibre constate


---
