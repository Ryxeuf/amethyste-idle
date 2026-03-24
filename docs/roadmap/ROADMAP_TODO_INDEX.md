# Roadmap a venir — Index

> Les taches detaillees sont reparties par vague (priorite) dans les fichiers ci-dessous.
> Numerotation unifiee : chaque tache a un identifiant unique (01 a 103).
> Derniere mise a jour : 2026-03-24

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
  ├─ ✅ 28 Monstres tier 1 (8 mobs) (FAIT) ← 15                       │
  ├─ ✅ 29 Equipement tier 2 (FAIT) ← 17                                │
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
  ├─ ✅ 54 Quetes a choix (FAIT) ← 13, 31
  ├─ 55 Quetes quotidiennes ← 12, 27
  ├─ ✅ 56 Presets de build (FAIT) ← 14
  ├─ 57 Commande terrain:sync ← 44
  ├─ 58 Parsing zones/biomes Tiled ← 44
  ├─ 59 Tests E2E Panther ← 23, 42
  ├─ ✅ 60 Minimap PixiJS (FAIT)
  ├─ ✅ 61 Barre d'action rapide (FAIT)
  ├─ ✅ 62 Particules combat/recolte (FAIT)
  └─ ✅ 63 Flash elementaire combat (FAIT)
```

---

## Fichiers par vague (ordre de priorite)

1. [Vague 1 — Fondations & Quick Wins](ROADMAP_TODO_VAGUE_01.md)
2. [Vague 2 — Systemes core completes (restant)](ROADMAP_TODO_VAGUE_02.md)
3. [Vague 3 — Contenu & enrichissement](ROADMAP_TODO_VAGUE_03.md)
4. [Vague 4 — Monde & systemes avances](ROADMAP_TODO_VAGUE_04.md)
5. [Vague 5 — Endgame & contenu avance](ROADMAP_TODO_VAGUE_05.md)
6. [Vague 6 — Long terme & polish final](ROADMAP_TODO_VAGUE_06.md)

**Plan annexe :** [Controle de cite par les guildes](PLAN_GUILD_CITY_CONTROL.md)
