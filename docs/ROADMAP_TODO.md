# Roadmap a venir — Amethyste-Idle

> Toutes les taches restantes a implementer, organisees en 6 vagues de priorite.
> Numerotation unifiee : chaque tache a un identifiant unique (01 a 103).
> Derniere mise a jour : 2026-03-23

---

## Legende

| Symbole | Signification |
|---------|---------------|
| S / M / L / XL | Complexite (Small < Medium < Large < XL) |
| ★★★ | Gain gameplay fort |
| ★★ | Gain gameplay moyen |
| ★ | Gain gameplay faible |
| ∅ | Aucun prerequis |
| ← XX | Depend de la tache XX |
| ‖ | Parallelisable avec les autres taches du meme bloc |

---

## Graphe de dependances global

```
VAGUE 1 (aucun prerequis — tout en parallele)
  ┌─ ✅ 01 De-hardcoder map IDs (FAIT) ────────────────────────────────┐
  ├─ ✅ 02 Supprimer CSS mort (FAIT)                                    │
  ├─ ✅ 03 Optimisation queries N+1 (FAIT)                              │
  ├─ ✅ 04 Rate limiting API (FAIT)                                    │
  ├─ ✅ 05 Consolidation craft (FAIT)                                  │
  ├─ ✅ 06 Materia unlock verification (FAIT)                          │
  ├─ ✅ 07 Raretes d'equipement (FAIT)                                  │
  ├─ ✅ 08 Combat log frontend (FAIT)                                  │
  ├─ ✅ 09 Icones statuts timeline combat (FAIT)                        │
  ├─ ✅ 10 Indicateur difficulte monstres (FAIT)                       │
  ├─ ✅ 11 Recompenses uniques de boss (FAIT)                           │
  ├─ ✅ 12 Recompenses de quetes completes (FAIT)                       │
  ├─ ✅ 13 Prerequis de quetes et chaines (FAIT)                          │
  ├─ ✅ 14 Respec basique (FAIT)                                       │
  ├─ ✅ 15 Consommables de base (FAIT)                                  │
  ├─ ✅ 16 Materia complement (8 nouvelles) (FAIT)                     │
  ├─ ✅ 17 Equipement tier 1 Starter (FAIT)                             │
  ├─ ✅ 18 Commandes chat slash (FAIT)                                  │
  ├─ ✅ 19 Profil joueur public (FAIT)                                          │
  ├─ ✅ 20 Horloge in-game & API temps (FAIT)                           │
  ├─ ✅ 21 GameEvent executor (FAIT)                                    │
  ├─ ✅ 22 Factions & reputation (FAIT)                               │
  ├─ ✅ 23 Tests fonctionnels controleurs (FAIT)                       │
  ├─ ✅ 24 Notifications toast in-game (FAIT)                           │
  └─ ✅ 25 Boutiques PNJ fixtures (FAIT)                               │
                                                                      │
VAGUE 2 (depend de Vague 1)                                           │
  ┌─ ✅ 26 Recettes de craft fixtures (FAIT) ← 05                      │
  ├─ ✅ 27 Tracking quetes collect/craft (FAIT)                        │
  ├─ 28 Monstres tier 1 (8 mobs) ← 15                                │
  ├─ 29 Equipement tier 2 ← 17                                        │
  ├─ ✅ 30 Teleportation entre cartes (FAIT) ← 01 ────────────────────┘
  ├─ ✅ 31 Types quetes livraison/exploration (FAIT) ← 27
  ├─ ✅ 32 Journal de quetes enrichi (FAIT) ← 13
  ├─ ✅ 33 Impact gameplay jour/nuit (FAIT) ← 20
  ├─ ✅ 34 Meteo backend & diffusion (FAIT) ← 20
  ├─ ✅ 35 Annonces Mercure evenements (FAIT) ← 21
  ├─ ✅ 36 Gains et recompenses reputation (FAIT) ← 22
  ├─ ✅ 37 Loot exclusif et rarete etendue (FAIT) ← 07
  ├─ ✅ 38 Liste d'amis (FAIT) ← 19
  ├─ ✅ 39 Limite points multi-domaine (FAIT) ← 14
  ├─ ✅ 40 Synergies cross-domaine (FAIT)
  ├─ ✅ 41 Indicateurs quetes sur PNJ (FAIT) ← 27
  ├─ ✅ 42 Tests unitaires systemes core (FAIT) ← 25, 26, 27
  ├─ ✅ 43 Tests integration events (FAIT) ← 23
  ├─ ✅ 44 Extraction services TerrainImport (FAIT) ← 01
  └─ ✅ 45 Portraits de personnages (FAIT)

VAGUE 3 (depend de Vague 2)
  ┌─ 46 Trame Acte 1 : L'Eveil ← 12, 13, 31
  ├─ 47 Monstres tier 2 (lvl 10-15) ← 28, 29
  ├─ 48 Village central hub ← 30, 25
  ├─ 49 Monstres soigneurs (multi-mobs) ← 28
  ├─ 50 Meteo effets visuels PixiJS ← 34
  ├─ ✅ 51 Meteo impact gameplay (FAIT) ← 34
  ├─ 52 Guildes fondation ← 38
  ├─ 53 Groupes de combat formation ← 38
  ├─ 54 Quetes a choix ← 13, 31
  ├─ 55 Quetes quotidiennes ← 12, 27
  ├─ 56 Presets de build ← 14
  ├─ 57 Commande terrain:sync ← 44
  ├─ 58 Parsing zones/biomes Tiled ← 44
  ├─ 59 Tests E2E Panther ← 23, 42
  ├─ 60 Minimap PixiJS (∅)
  ├─ 61 Barre d'action rapide (∅)
  ├─ 62 Particules combat/recolte (∅)
  └─ 63 Flash elementaire combat (∅)
```

---

## Vague 1 — Fondations & Quick Wins ✅

> **25 taches** initiales, **25 completees**. Vague terminee.

---

## Vague 2 — Systemes core completes

> **20 taches** initiales, **15 completees**, 5 restantes.
> Organisees en 5 pistes paralleles.
> Les pistes sont independantes entre elles. Les dependances intra-piste sont indiquees.

---

## Piste A — Donnees & fixtures (‖)

### 28 — Monstres tier 1 — 8 mobs elementaires (M | ★★★)
> 20 monstres existent, on en ajoute 8 pour couvrir chaque element. Prerequis : ← 15
- [ ] 8 monstres elementaires niveaux 1-10 :
  - Feu: Salamandre (lvl 3), Eau: Ondine (lvl 2), Air: Sylphe (lvl 4)
  - Terre: Golem d'argile (lvl 5), Metal: Automate rouille (lvl 3)
  - Bete: Loup alpha (lvl 4), Lumiere: Feu follet (lvl 2), Ombre: Ombre rampante (lvl 5)
- [ ] Stats, AI patterns, resistances elementaires pour chaque monstre
- [ ] Tables de loot (drops ressources + consommables C-1)
- [ ] Succes bestiaire (3 paliers x 8 monstres = 24 achievements)
- [ ] Placement sur la carte existante (MobFixtures)

### 29 — Equipement tier 2 Intermediaire (M | ★★)
> Set complet avec variantes elementaires. Prerequis : ← 17
- [ ] Set complet 7 pieces — 4 variantes elementaires (Feu, Eau, Terre, Air)
  - = 28 items au total (7 pieces x 4 elements)
- [ ] Bonus elementaire sur chaque piece (+10% degats element)
- [ ] Ajouter aux loot tables des monstres lvl 5-15

---

## Piste B — Systemes quetes & carte (‖)

### ~~30 — Teleportation entre cartes~~ ✅ FAIT

### ~~31 — Types quetes livraison/exploration~~ ✅ FAIT

---

## Piste C — Monde vivant & events (‖)

### ~~33 — Impact gameplay jour/nuit~~ ✅ FAIT

### ~~34 — Meteo backend & diffusion~~ ✅ FAIT

---

## Piste D — Social & progression (‖)

### ~~40 — Synergies cross-domaine~~ ✅ FAIT

---

## Piste E — Qualite & pipeline (‖)

### ~~42 — Tests unitaires systemes core~~ ✅ FAIT

### ~~43 — Tests integration events~~ ✅ FAIT

### ~~44 — Extraction services TerrainImport~~ ✅ FAIT

---

## Vague 3 — Contenu & enrichissement

> **18 taches** qui dependent de la Vague 2.
> Organisees en 5 pistes paralleles.

---

### Piste A — Narration & quetes (‖)

(contenu inchange — voir fichier complet)

### ~~51 — Meteo impact gameplay~~ ✅ FAIT

(reste de la Vague 3+ inchange)