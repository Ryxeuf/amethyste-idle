# Roadmap a venir — Index

> Les taches detaillees sont reparties par **sprint** dans les fichiers ci-dessous.
> Derniere mise a jour : 2026-07-25 (**point post-pivot** : campagne ZON close, menage des sprints 11-12, ouverture des Sprints 13-14)
> **PIVOT PBBG (juillet 2026)** : le jeu a abandonne la carte en tuiles au profit d'un monde en graphe de zones (energie, time-gating reel). Decision et equivalences : [docs/PIVOT_PBBG.md](../PIVOT_PBBG.md) ; bilan de campagne : [docs/ZON_CAMPAIGN_RECAP.md](../ZON_CAMPAIGN_RECAP.md).

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

| Chantier | Taches | Statut |
|----------|--------|--------|
| Vagues 1 a 6 — Fondations → polish | 100/100 | ✅ Terminees |
| Editeur de cartes (MED) | 16/16 | ✅ Termine — **sans suite** (pivot) |
| Controle de cite par les guildes (GCC) | 20/20 | ✅ Termine |
| Sprints 1-6 — Coeur de jeu | 20/20 | ✅ Termines |
| Plan Testing (TST) | 15/15 | ✅ Termine |
| Plan Avatar (AVT) | 34/38 | ⛔ Clos par le pivot (realise trace dans ROADMAP_DONE, reliquat abandonne) |
| Plan Narration (NAR) | 14/14 | ✅ Termine (2026-07-25) |
| Chantier Modele zone (ZON-01→21) | 21/21 | ✅ Termine (Sprints 7-10) |
| **Consolidation post-pivot (ZON-22→26)** | **0/5** | 🔴 **A faire — Sprint 13 (prioritaire)** |
| **Plan Economie joueur (ECO)** | **0/17** | 🟠 **A faire — Sprint 14 puis suite** |
| Sprint 11 — Monde vivant | ~8 items | En cours (montures/events/classement quasi finis) |
| Sprint 12 — Technique & i18n | ~9 items | En cours (jalons A/B/E/F + i18n contenu) |

---

## Sprints

| Sprint | Theme | Priorite | Statut |
|--------|-------|----------|--------|
| **Sprint 1** | Stabilite & Onboarding | Critique | ✅ Termine |
| **Sprint 2** | Bestiaire & PNJ | Haute | ✅ Termine |
| **Sprint 3** | Arsenal & Magie | Haute | ✅ Termine |
| **Sprint 4** | Progression & Narration | Haute | ✅ Termine |
| **Sprint 5** | Hotel des ventes | Moyenne | ✅ Termine |
| **Sprint 6** | Social & Economie | Moyenne | ✅ Termine |
| **Sprint 7** | Modele zone : Fondations (ZON-01..06) | Critique | ✅ Termine (2026-07-24) |
| **Sprint 8** | Energie & actions de zone (ZON-07..12) | Haute | ✅ Termine (2026-07-25) |
| **Sprint 9** | Time-gating, presence & evenements (ZON-13..17) | Haute | ✅ Termine (2026-07-25) |
| **Sprint 10** | Contenu de groupe & decommission carte (ZON-18..21) | Moyenne | ✅ Termine (2026-07-25) |
| **Sprint 13** | **Consolidation post-pivot (ZON-22..26)** | **Critique** | 🔴 **Prochain** |
| **Sprint 14** | **Economie joueur — socle (ECO-01..04, 14, 16)** | **Haute** | 🟠 A suivre |
| **Sprint 11** | Monde vivant (128-133) | Basse | En cours |
| **Sprint 12** | Technique & i18n (134-135) | Basse | En cours (← bloque par ZON-24) |

> **Numerotation** : les Sprints 7-10 reutilisent les numeros de l'ancien chantier Avatar (clos par
> le pivot). Les Sprints 13-14 sont ouverts apres les Sprints 11-12 dans la numerotation, mais
> **passent devant en priorite** : ils portent la dette du pivot et le pilier economique.

---

## Prochain chantier : pourquoi le Sprint 13 d'abord

La suppression du code carte (ZON-21) a retire le **dispatcher** de `PlayerMovedEvent` sans
rebrancher ses **6 abonnes**. Sont donc inertes en production : progression des quetes
d'exploration et d'escorte, declencheurs de quetes cachees sur deplacement, etape de deplacement du
tutoriel, decouverte de region. S'y ajoutent la perte de couverture E2E de la boucle de jeu
principale et des scenarios de charge pointant sur des routes supprimees.

Le Sprint 13 referme cette dette **avant** d'ajouter du contenu ou des systemes par-dessus.

---

## Graphe de dependances

```
CAMPAGNE ZON — MODELE ZONE ✅ TERMINEE (Sprints 7-10, ZON-01..21)

SPRINT 13 — CONSOLIDATION POST-PIVOT (← ZON-21)     🔴 PRIORITAIRE
  ZON-22 Rebranchement PlayerMovedEvent   ∅   ← CRITIQUE (regression)
  ZON-23 Couverture E2E zone              ← ZON-22
  ZON-24 Realignement scenarios k6        ∅        → debloque 134
  ZON-25 Nettoyage residus carte          ← ZON-22
  ZON-26 Densification graphe de zones    ∅        → debloque 128

SPRINT 14 — ECONOMIE JOUEUR : SOCLE (‖ Sprint 13)
  ECO-01 BindType                         ∅   ← CRITIQUE (bloque ECO-02/05/10)
  ECO-02 Plancher T1 & onboarding         ← ECO-01
  ECO-03 HV regional                      ← modele zone ✅
  ECO-04 Taxe HV → guilde controlante     ← ECO-03, GCC ✅
  ECO-14 Interdependance des metiers      ∅ ‖
  ECO-16 Moderation economique            ← ECO-03

SPRINT 11 — MONDE VIVANT (← Sprints 7-10)
  128 Nouvelles zones Acte 4              ← ZON-26
  129 Housing joueur                      ← 116 ✅        → debloque ECO-10
  130 Montures (temps de voyage)          ← ZON-06 ✅ (reste la transposition)
  131 Events live                         ← ZON-15 ✅ (reste l'UI admin)
  132 Classement saisonnier               quasi termine
  133 Mini-jeux                           ∅ (peche ✅, defis chrono a faire)

SPRINT 12 — TECHNIQUE & I18N
  134 Load testing & scaling              ← ZON-24 (jalons A/B/E/F restants)
  135 Localisation i18n                   ∅ (contenu de jeu + ecrans du pivot)

SUITE ECONOMIE (apres Sprint 14)
  ECO-05..09 Commandes de craft (pilier endgame)
  ECO-10..13 Echoppes joueur              ← 129 housing
  ECO-15 Gold sinks, ECO-17 Tests
```

---

## Parallelisation

```
Sprint 13 (dette du pivot) ────┬──── Sprint 11 (contenu monde)
                               │
Sprint 14 (economie, ‖) ───────┴──── Suite economie (ECO-05..17)

Sprint 12 (technique) : 134 attend ZON-24 ; 135 parallelisable a tout moment
```

---

## Fichiers par sprint

1. [Sprint 1 — Stabilite & Onboarding](SPRINT_01.md) ✅
2. [Sprint 2 — Bestiaire & PNJ](SPRINT_02.md) ✅
3. [Sprint 3 — Arsenal & Magie](SPRINT_03.md) ✅
4. [Sprint 4 — Progression & Narration](SPRINT_04.md) ✅
5. [Sprint 5 — Hotel des ventes](SPRINT_05.md) ✅
6. [Sprint 6 — Social & Economie](SPRINT_06.md) ✅
7. [Sprint 7 — Modele zone : Fondations](SPRINT_07.md) ✅
8. [Sprint 8 — Energie & actions de zone](SPRINT_08.md) ✅
9. [Sprint 9 — Time-gating, presence & evenements](SPRINT_09.md) ✅
10. [Sprint 10 — Contenu de groupe & decommission carte](SPRINT_10.md) ✅
11. [Sprint 11 — Monde vivant](SPRINT_11.md)
12. [Sprint 12 — Technique & i18n](SPRINT_12.md)
13. **[Sprint 13 — Consolidation post-pivot](SPRINT_13.md)** ← **Prochain**
14. **[Sprint 14 — Economie joueur (socle)](SPRINT_14.md)**

**Plans annexes :**
- [Pivot PBBG — decision et equivalences](../PIVOT_PBBG.md) — **source de verite du pivot**
- [Recapitulatif de la campagne ZON](../ZON_CAMPAIGN_RECAP.md) — bilan ZON-12→21 + suivis identifies
- [Principes de jeu (design)](../GAME_PRINCIPLES.md) — **source de verite du game design**
- [Economie joueur (ECO-01 a ECO-17)](PLAN_PLAYER_ECONOMY.md) — **0/17, prochain grand chantier** (Sprint 14 en couvre 6)
- [Narration (NAR-01 a NAR-14)](PLAN_NARRATIVE.md) ✅ — plan complet (2026-07-25)
- [Controle de cite par les guildes (GCC-01 a GCC-20)](PLAN_GUILD_CITY_CONTROL.md) ✅
- [Testing & qualite (TST-01 a TST-15)](PLAN_TESTING.md) ✅ — plan complet
- [Editeur de cartes integre & generateur procedural](PLAN_MAP_EDITOR.md) ✅ — termine, sans suite dans Amethyste (reutilisable pour un futur projet Zelda-like separe)
- [Systeme d'avatar modulaire (format 8x8)](PLAN_AVATAR_SYSTEM.md) — ⛔ clos par le pivot PBBG (34/38 realisees)
- [Archive du detail livre des Sprints 11-12](ARCHIVE_SPRINT_11_12.md) — historique verbatim (non agrege par l'admin)
