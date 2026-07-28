# Roadmap a venir — Index

> Les taches detaillees sont reparties par **sprint** dans les fichiers ci-dessous.
> Derniere mise a jour : 2026-07-26 (**tache 130** montures, **Sprint 13 clos** par ZON-26b-a, **Sprints 14-15 termines**, **housing complet**) ; 2026-07-25 (**point post-pivot** : campagne ZON close, menage des sprints 11-12, ouverture des Sprints 13-14)
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
| **Consolidation post-pivot (ZON-22→27)** | **8/8** | ✅ Sprint 13 termine (2026-07-26) — dette du pivot soldee |
| **Plan Economie joueur (ECO)** | **Pistes A/B/C completes** | ✅ Sprints 14-15 — reste Piste D (echoppes, ← housing) |
| Sprint 11 — Monde vivant | 6/6 | ✅ Termine (2026-07-26) |
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
| **Sprint 13** | **Consolidation post-pivot (ZON-22..27)** | **Critique** | ✅ Termine 8/8 (2026-07-26) |
| **Sprint 14** | **Economie joueur — socle (ECO-01..04, 14, 16)** | **Haute** | ✅ Termine 9/9 |
| **Sprint 15** | **Commandes de craft — Piste C (ECO-05..09, ECO-20)** | **Haute** | ✅ Termine 8/8 (2026-07-26) |
| **Sprint 11** | Monde vivant (128-133) | Basse | ✅ Termine 6/6 (2026-07-26) |
| **Sprint 12** | Technique & i18n (134-135) | Basse | En cours (← bloque par ZON-24) |

> **Numerotation** : les Sprints 7-10 reutilisent les numeros de l'ancien chantier Avatar (clos par
> le pivot). Les Sprints 13-14 sont ouverts apres les Sprints 11-12 dans la numerotation, mais
> **passent devant en priorite** : ils portent la dette du pivot et le pilier economique.

---

## Bilan du Sprint 13 (2026-07-25)

Le pivot avait coupe plus de fils qu'il n'y paraissait. Etaient **inertes en production**, et
refonctionnent : quetes d'exploration et d'escorte, quetes cachees, etapes « deplacement » et
« inventaire » du tutoriel, decouverte de region, quetes `talk_to`, acces aux boutiques PNJ.

Deux defauts trouves au passage, invisibles jusque-la : la victoire suivie du butin etait le **seul
chemin de sortie de combat** qui n'ancrait pas la regeneration des PV (annulant le second regulateur
du pivot), et les fixtures laissaient les joueurs sans zone, sur la « Carte de test » heritee.

Le garde-fou `DomainEventDispatchGuardTest` verrouille la recidive : **plus aucun evenement de
domaine sans emetteur**, liste d'exceptions vide.

**Cloture le 2026-07-26 par ZON-26b-a** : les rencontres sont desormais **declaratives**. Un `Mob`
n'atteignait sa zone que par une carte (`WorldEntityZoneListener` derive `Mob.zone` de `Mob.map`),
si bien qu'une zone nouvelle — donc sans carte, le moteur ayant ete supprime par ZON-21 — ne pouvait
avoir aucune rencontre. Le bloc `mobs:` leve le verrou, et les **Dunes d'Ambre** sont la premiere
zone livree sans `source_map`. L'Acte 4 (tache 128) est debloque.

**Clos definitivement par ZON-26b-b** : les PNJ aussi sont declaratifs. `Pnj::slug` sert de cle
d'idempotence, et les **Dunes d'Ambre** — zone sans carte d'origine — ont desormais deux habitants.
Les 7 fixtures historiques ne sont pas migrees : elles fonctionnent, et les reecrire serait du
risque pur pour aucun gain.

---

## Graphe de dependances

```
CAMPAGNE ZON — MODELE ZONE ✅ TERMINEE (Sprints 7-10, ZON-01..21)

SPRINT 13 — CONSOLIDATION POST-PIVOT (← ZON-21)     ✅ 7/7
  ZON-22 Rebranchement PlayerMovedEvent   ✅
  ZON-23 Couverture E2E zone              ✅
  ZON-24 Realignement scenarios k6        ✅        → debloque 134
  ZON-25 Evenements orphelins & residus   ✅
  ZON-27 Couche PNJ (boutiques, dialogue) ✅
  ZON-26a Densification du graphe         ✅
  ZON-26b-a Population declarative (mobs)  ✅ → debloque 128
  ZON-26b-b PNJ declaratifs                ✅

SPRINT 14 — ECONOMIE JOUEUR : SOCLE (‖ Sprint 13)
  ECO-01 BindType                         ∅   ← CRITIQUE (bloque ECO-02/05/10)
  ECO-02 Plancher T1 & onboarding         ← ECO-01
  ECO-03 HV regional                      ← modele zone ✅
  ECO-04 Taxe HV → guilde controlante     ← ECO-03, GCC ✅
  ECO-14 Interdependance des metiers      ∅ ‖
  ECO-16 Moderation economique            ← ECO-03

SPRINT 11 — MONDE VIVANT (← Sprints 7-10)
  128 Nouvelles zones Acte 4              ✅ complet (128a→d)
  129 Housing joueur                      ✅ complet      → debloque ECO-10
  130 Montures (temps de voyage)          ✅ transposee au modele zone
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
11. [Sprint 11 — Monde vivant](SPRINT_11.md) ✅ 6/6
12. [Sprint 12 — Technique & i18n](SPRINT_12.md)
13. [Sprint 13 — Consolidation post-pivot](SPRINT_13.md) ✅ 8/8
14. [Sprint 14 — Economie joueur (socle)](SPRINT_14.md) ✅ 9/9
15. **[Sprint 15 — Commandes de craft (Piste C)](SPRINT_15.md)** ✅ **8/8 — termine** (ECO-07, ECO-08 et ECO-20 scindees en sous-jalons ; ECO-20 nee des audits ECO-06/07)

**Plans annexes :**
- [Pivot PBBG — decision et equivalences](../PIVOT_PBBG.md) — **source de verite du pivot**
- [Recapitulatif de la campagne ZON](../ZON_CAMPAIGN_RECAP.md) — bilan ZON-12→21 + suivis identifies
- [Principes de jeu (design)](../GAME_PRINCIPLES.md) — **source de verite du game design**
- [Foyers, Crue et Paleur (FOY-01 a FOY-16)](PLAN_SETTLEMENTS.md) — **0/16, prochain grand chantier** : le monde bati par les joueurs (socle de monde adopte, cf. [docs/GAME_WORLD.md](../GAME_WORLD.md))
- [Economie joueur (ECO-01 a ECO-17)](PLAN_PLAYER_ECONOMY.md) — Pistes A et B **completes** (Sprint 14, 9 jalons dont ECO-18/19 nes de la campagne) ; Piste C en cours (Sprint 15)
- [Narration (NAR-01 a NAR-14)](PLAN_NARRATIVE.md) ✅ — plan complet (2026-07-25)
- [Controle de cite par les guildes (GCC-01 a GCC-20)](PLAN_GUILD_CITY_CONTROL.md) ✅
- [Testing & qualite (TST-01 a TST-15)](PLAN_TESTING.md) ✅ — plan complet
- [Editeur de cartes integre & generateur procedural](PLAN_MAP_EDITOR.md) ✅ — termine, sans suite dans Amethyste (reutilisable pour un futur projet Zelda-like separe)
- [Systeme d'avatar modulaire (format 8x8)](PLAN_AVATAR_SYSTEM.md) — ⛔ clos par le pivot PBBG (34/38 realisees)
- [Archive du detail livre des Sprints 11-12](ARCHIVE_SPRINT_11_12.md) — historique verbatim (non agrege par l'admin)
