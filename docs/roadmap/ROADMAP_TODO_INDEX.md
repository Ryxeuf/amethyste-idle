# Roadmap a venir — Index

> Les taches detaillees sont reparties par **sprint** dans les fichiers ci-dessous.
> Derniere mise a jour : 2026-07-24 (adoption du pivot PBBG)
> **PIVOT PBBG (juillet 2026)** : le jeu abandonne la carte en tuiles au profit d'un monde en graphe de zones (energie, time-gating reel). Decision et equivalences : [docs/PIVOT_PBBG.md](../PIVOT_PBBG.md).
> **Bilan** : Vagues 1-6 terminees (103 taches), editeur de cartes termine (16 MED), guildes termine (20 GCC), Sprints 1-6 termines (coeur de jeu).
> **Avatar (ex-Sprints 7-10)** : chantier clos par le pivot. Sprints 7 (12/12), 8 (10/10) et 9 (8/8) avatar termines, Sprint 10 avatar interrompu a 4/8 (AVT-31, AVT-32, AVT-36, AVT-37 + AVT-35 sous-phases 1-2 livrees). Tout le realise est trace dans `ROADMAP_DONE.md` ; le reliquat (AVT-33, AVT-34, AVT-35 sous-phase 3, AVT-38) est abandonne — voir [PLAN_AVATAR_SYSTEM.md](PLAN_AVATAR_SYSTEM.md).
> **Numerotation** : les numeros de sprint 7-10 sont reutilises pour le chantier « Modele zone » (taches ZON-01 a ZON-21).

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

## Etat d'avancement global

| Phase | Taches | Statut |
|-------|--------|--------|
| Vague 1 — Fondations & Quick Wins | 25/25 | ✅ Terminee |
| Vague 2 — Systemes core | 20/20 | ✅ Terminee |
| Vague 3 — Contenu & enrichissement | 18/18 | ✅ Terminee |
| Vague 4 — Monde & systemes avances | 16/16 + 16 MED + 20 GCC | ✅ Terminee |
| Vague 5 — Endgame & contenu avance | 10/10 | ✅ Terminee |
| Vague 6 — Long terme & polish | 11/11 | ✅ Terminee |
| Sprints 1-6 — Coeur de jeu | 20/20 | ✅ Termines |
| Plan Testing (TST) | 11/15 | ✅ Quasi-termine (integre en Sprint 1) |
| Plan Avatar (AVT) | 34/38 | ⛔ Clos par le pivot PBBG (realise trace dans ROADMAP_DONE, reliquat abandonne) |
| Chantier Modele zone (ZON) | 4/21 | En cours (Sprints 7-10 ; ZON-02 a ZON-05 livrees 2026-07-24) |

---

## Sprints a venir

| Sprint | Theme | Taches | Priorite | Statut |
|--------|-------|--------|----------|--------|
| **Sprint 1** | Stabilite & Onboarding | 3 | Critique | ✅ **Termine** |
| **Sprint 2** | Bestiaire & PNJ | 2 | Haute | ✅ **Termine** |
| **Sprint 3** | Arsenal & Magie | 3 | Haute | ✅ **Termine** |
| **Sprint 4** | Progression & Narration | 3 | Haute | ✅ **Termine** |
| **Sprint 5** | Hotel des ventes | 3 | Moyenne | ✅ **Termine** |
| **Sprint 6** | Social & Economie | 6/6 | Moyenne | ✅ **Termine** |
| **Sprint 7** | Modele zone : Fondations (ex-Avatar: Fondations ✅ 12/12) | 4/6 | Critique | En cours (ZON-02 a ZON-05 ✅) |
| **Sprint 8** | Energie & actions de zone (ex-Avatar: Backend & Carte ✅ 10/10) | 6 | Haute | A venir |
| **Sprint 9** | Time-gating, presence & evenements (ex-Avatar: Personnage & Equipement ✅ 8/8) | 5 | Haute | A venir |
| **Sprint 10** | Contenu de groupe & decommission carte (ex-Avatar: Polish, interrompu a 4/8) | 4 | Moyenne | A venir |
| **Sprint 11** | Monde vivant | 6 | Basse | En cours (130 sous-phases 1 + 2a + 2b.shop + 2b.quest + 2b.loot + 4a + 5 + 6 + 6b, 131 sous-phases 2a + 2b + 3 + 4, 132 sous-phases 1 + 2a + 2b + 3 + 4a + 4b.1 + 4b.1b + 4b.1c + 4b.2, 133 sous-phases 1 + 1b + 1c ; adaptations pivot : montures → temps de voyage, events → evenements de zone) |
| **Sprint 12** | Technique & i18n | 2 | Basse | En cours (134 sous-phases 1 + 2a + 2b + 2c + 2d + 3a + 3b + 3c + 3d, 135 sous-phases 1 + 2a + 2b + 3a + 3b + 3c + 3c.b + 3c.c + 3c.d + 3c.e + 3e.a + 3e.b.a + 3e.b.b + 3e.b.b.suite + 3e.c.d.quest + 3e.c.d.quest.b + 3e.c.d.quest.c + 3e.c.d.quest.d + 3e.c.d.quest.e + 3e.c.d.quest.f + 3e.c.d.quest.g + 3e.c.d.quest.h + 3e.c.d.quest.i + 3e.c.d.quest.j + 3e.c.domain + 3e.c.domain.b + 3e.c.domain.c + 3e.c.achievement + 3e.c.achievement.b + 3e.c.achievement.c + 3e.c.skill + 3e.d + 3e.f + 3e.f.b + 3e.i + 3e.j + 3e.j.b + 3e.j.c + 3e.j.d + 3e.j.e + 3e.l + 3e.m + 3e.n + 3e.o + 3e.p + 3e.v + 3e.w + 3e.x + 3e.y + 3e.z + 3e.aa + 3e.cc + 3e.dd) |

**Total restant : 29 taches** (21 ZON + 6 monde vivant + 2 technique — dont plusieurs taches des Sprints 11-12 deja bien avancees en sous-phases)
**Avancement historique** : Sprints 1 (3), 2 (2), 3 (3), 4 (3), 5 (3), 6 (6), 7-avatar (12), 8-avatar (10) et 9-avatar (8) termines + Sprint 10-avatar interrompu (4/8) = 54 taches completes avant pivot

---

## Graphe de dependances (sprints)

```
SPRINT 7 — MODELE ZONE : FONDATIONS (priorite absolue post-pivot)
  ZON-01 Gel de la carte           ∅
  ZON-02 Entites Zone/Connexion    ✅ 2026-07-24
  ZON-03 Position joueur → zone    ✅ 2026-07-24
  ZON-04 Donnees monde → zones     ✅ 2026-07-24
  ZON-05 Ecran de zone             ✅ 2026-07-24
  ZON-06 Voyage entre zones        ← ZON-05

SPRINT 8 — ENERGIE & ACTIONS DE ZONE (← Sprint 7)
  ZON-07 Ressource energie         ∅
  ZON-08 Action Explorer           ← ZON-07
  ZON-09 Action Chasser            ← ZON-07
  ZON-10 Recolte & filons partages ← ZON-07
  ZON-11 Config declarative        ← ZON-08..10
  ZON-12 Regulation par les PV     ← ZON-08

SPRINT 9 — TIME-GATING & PRESENCE (← Sprint 8)
  ZON-13 Expeditions time-gated    ← ZON-11
  ZON-14 Presence & chat de zone   ← ZON-05
  ZON-15 Evenements de zone        ← ZON-14
  ZON-16 Carte du monde illustree  ← ZON-06
  ZON-17 Cycle jour/nuit mecanique ← ZON-11

SPRINT 10 — GROUPE & DECOMMISSION (← Sprint 9)
  ZON-18 Boss de zone asynchrone   ← ZON-15
  ZON-19 Donjon semi-synchrone     ← ZON-14
  ZON-20 Lockouts & decroissance   ← ZON-19
  ZON-21 Suppression code carte    ← ZON-16

SPRINT 11 — MONDE VIVANT (← Sprints 7-9)
  128 Nouvelles zones Acte 4       ← 94 ✅, 141 ✅, ZON-11
  129 Housing joueur               ← 116 ✅
  130 Montures (temps de voyage)   ← ZON-06 (obtention/catalogue/activation ✅)
  131 Events live                  ← 79 ✅, ZON-15 (buff global/historique/annonce ✅)
  132 Classement saisonnier        ← 92 ✅ (quasi-termine)
  133 Mini-jeux                    ∅ (peche ✅, defis chrono a faire)

SPRINT 12 — TECHNIQUE & I18N (‖ tout)
  134 Load testing & scaling       ∅ (en cours)
  135 Localisation i18n            ∅ (en cours)
```

---

## Parallelisation des sprints

```
Pivot ──── Sprint 7 ──── Sprint 8 ──── Sprint 9 ──── Sprint 10
                                          │
                                          └── Sprint 11 (monde vivant)

Independant ─ Sprint 12 (technique, parallelisable a tout moment)
```

---

## Fichiers par sprint

1. [Sprint 1 — Stabilite & Onboarding](SPRINT_01.md) ✅
2. [Sprint 2 — Bestiaire & PNJ](SPRINT_02.md) ✅
3. [Sprint 3 — Arsenal & Magie](SPRINT_03.md) ✅
4. [Sprint 4 — Progression & Narration](SPRINT_04.md) ✅
5. [Sprint 5 — Hotel des ventes](SPRINT_05.md) ✅
6. [Sprint 6 — Social & Economie](SPRINT_06.md) ✅
7. **[Sprint 7 — Modele zone : Fondations](SPRINT_07.md)** ← Prochain
8. [Sprint 8 — Energie & actions de zone](SPRINT_08.md)
9. [Sprint 9 — Time-gating, presence & evenements](SPRINT_09.md)
10. [Sprint 10 — Contenu de groupe & decommission carte](SPRINT_10.md)
11. [Sprint 11 — Monde vivant](SPRINT_11.md)
12. [Sprint 12 — Technique & i18n](SPRINT_12.md)

**Plans annexes :**
- [Pivot PBBG — decision et equivalences](../PIVOT_PBBG.md) — **source de verite du pivot**
- [Controle de cite par les guildes](PLAN_GUILD_CITY_CONTROL.md) ✅
- [Editeur de cartes integre & generateur procedural](PLAN_MAP_EDITOR.md) ✅ — termine, sans suite dans Amethyste (reutilisable pour un futur projet Zelda-like separe)
- [Testing & qualite](PLAN_TESTING.md) — quasi-termine (taches restantes integrees en Sprint 1)
- [Systeme d'avatar modulaire (format 8x8)](PLAN_AVATAR_SYSTEM.md) — ⛔ clos par le pivot PBBG (34/38 realisees)
