# Roadmap a venir — Index

> Les taches detaillees sont reparties par vague (priorite) dans les fichiers ci-dessous.
> Numerotation unifiee : chaque tache a un identifiant unique (01 a 103).
> Derniere mise a jour : 2026-03-26
> **Repriorisation** : l'editeur de cartes integre (MED-01 a MED-16) est desormais la **priorite absolue** de la Vague 4.

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
VAGUES 1-3 : TERMINEES ✅ (63 taches completees)
  Prerequis editeur de carte : ✅ 44 (TerrainImport), ✅ 57 (terrain:sync), ✅ 58 (zones/biomes)

VAGUE 4 — PRIORITE ABSOLUE : EDITEUR DE CARTES (MED-01 a MED-16)
  ┌─ Phase 1 (fondation)
  │   ✅ MED-01 TilesetRegistry ← ✅ 44, ✅ 58 ──────────────────────┐
  │   ✅ MED-02 MapFactory ← MED-01 ────────────────────────────────┤
  │                                                                   │
  ├─ Phase 2 (peinture)                                               │
  │   MED-03 Tileset Picker ← MED-01                                 │
  │   MED-04 Stamp Brush & Eyedropper ← MED-03 ─────────────────────┤
  │   MED-05 Eraser ← MED-04                                         │
  │   MED-06 Bucket Fill ← MED-04                                    │
  │                                                                   │
  ├─ Phase 3 (UX editeur) ‖                                          │
  │   MED-07 Gestion layers ← MED-03                                 │
  │   MED-08 Undo / Redo ← MED-04                                    │
  │                                                                   │
  ├─ Phase 4 (entites)                                                │
  │   MED-09 Creation entites ← MED-04                               │
  │   MED-10 Edition entites ← MED-09                                │
  │                                                                   │
  ├─ Phase 5 (auto-tiling)                                            │
  │   MED-11 WangTileResolver backend ← MED-01                       │
  │   MED-12 Auto-tiling frontend ← MED-11, MED-04                   │
  │                                                                   │
  ├─ Phase 6 (generateur procedural)                                  │
  │   MED-13 Moteur Perlin ← MED-01, MED-02                          │
  │   MED-14 Biomes ← MED-13                                         │
  │   MED-15 Objets & connectivite ← MED-14                          │
  │                                                                   │
  └─ Phase 7 (export/qualite)                                         │
      MED-16 Export TMX & tests E2E ← MED-04, MED-09                 │
                                                                      │
  PRIORITE 2 — Contenu (debloque par editeur)                         │
  ┌─ ✅ 68 Mines profondes (FAIT) ← 30, 47, 66 ─────────────────────┘
  │
  PRIORITE 3 — Guildes
  └─ GCC-01..20 Controle cite ← 38, 48, 52

VAGUE 5 (depend de Vague 4)
  ┌─ 80 Trame Acte 2 ← 46, 67, 68
  ├─ 81 Combat cooperatif ← 53, 49
  └─ 84 Donjons mecaniques & loot ← 72, 37

VAGUE 6 (long terme)
  ┌─ 92 Classement guildes ← 52
  ├─ 93 Quetes de guilde ← 52, 92
  ├─ 94 Trame Acte 3 ← 80, 72
  ├─ 95 Saisonnalite & festivals ← 20, 85
  ├─ 98 Rendu tiles animees ← 97
  ├─ 99 Transitions de zone ← 30
  ├─ 100 Sons basiques
  ├─ 101 Monitoring basique
  ├─ 102 Index DB composites
  └─ 103 Achievements caches
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
**Plan annexe :** [Editeur de cartes integre & generateur procedural](PLAN_MAP_EDITOR.md)
