## Vague 3 — Contenu & enrichissement

> **18 taches** initiales, **15 completees**, 3 restantes.
> Organisees en 5 pistes paralleles.

---

### Piste A — Narration & quetes (‖)
### 46 — Trame Acte 1 : L'Eveil (M | ★★★)
> Tutoriel narratif. Chaine de 4-5 quetes guidant le joueur dans ses premieres actions. Utilise les systemes existants (kill, collect, deliver, explore) — pas de nouvelle mecanique. Prerequis : ← 12 (QN-3 prerequis quetes), 13 (QN-4 journal enrichi), 31 (QN-1 recompenses quetes)
- [ ] Quete 1.1 "Reveil" : dialogue d'introduction avec un PNJ guide, explorer le village
- [ ] Quete 1.2 "Premiers pas" : aller voir le forgeron, recevoir une arme de base
- [ ] Quete 1.3 "Bapteme du feu" : tuer 2 monstres faibles dans la zone de depart
- [ ] Quete 1.4 "Recolte" : collecter des ressources de base (herbes ou minerai)
- [ ] Quete 1.5 "Le cristal d'amethyste" : explorer un lieu specifique, dialogue revelateur
- [ ] Dialogues narratifs pour chaque PNJ implique (guide, forgeron, ancien du village)
- [ ] Recompenses progressives (equipement starter, gils, XP, premiere materia)

### ~~54 — Quetes a choix (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~55 — Quetes quotidiennes (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### Piste B — Contenu monde (‖)

### ~~47 — Monstres tier 2 lvl 10-15 (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### 48 — Village central hub (L | ★★★)
> Nouvelle carte "Village central" servant de hub principal entre les zones. Prerequis : ← 30 (C-8 teleportation entre cartes), 25 (v0.4-A boutiques PNJ)
- [ ] Design carte Tiled (~40x40, interieur/exterieur) avec places, batiments
- [ ] Import BDD via app:terrain:import
- [ ] PNJ hub : forgeron, alchimiste, marchand, maitre des quetes, banquier (5-8 PNJ)
- [ ] Dialogues PNJ (JSON conditions)
- [ ] Portail bidirectionnel vers carte existante
- [ ] Aucun monstre (zone safe)

### ~~49 — Monstres soigneurs / multi-mobs (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### Piste C — Meteo & visuels (‖)

### ~~50 — Meteo effets visuels PixiJS (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~62 — Particules combat et recolte (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~63 — Flash elementaire et animations combat (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### Piste D — Social & builds (‖)

### ~~52 — Guildes fondation (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~53 — Groupes de combat formation (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~56 — Presets de build (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---

### Piste E — Infra & qualite (‖)

### ~~57 — Commande terrain:sync (M | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~58 — Parsing zones/biomes Tiled (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### 59 — Tests E2E Panther (M | ★★)
> Tests de parcours complets multi-pages valides via Panther. Prerequis : ← 23 (P6-3 tests fonctionnels controleurs), 42 (P6-5 tests integration evenements)
- [ ] Parcours combat : carte → engagement mob → combat → victoire → loot → retour carte
- [ ] Parcours quete : PNJ dialogue → accepter quete → tuer mob → rendre quete → recompense
- [ ] Parcours craft : inventaire → atelier → crafter → verifier item cree

### ~~60 — Minimap PixiJS (M | ★★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

### ~~61 — Barre d'action rapide (S | ★★)~~ ✅
> Deplace dans `ROADMAP_DONE.md`.

---
