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

## Ordre de chantier (2026-07-28) — pour un developpement autonome

> Sequence de reference issue du cadrage macro (GAME_WORLD, GAME_PROGRESSION, BALANCE §22).
> Un agent qui reprend le projet **execute dans cet ordre**, sauf contrordre explicite.
> Critere d'arbitrage permanent (GAME_PROGRESSION §4) : rapprocher de la semaine 3 le moment
> ou « quelqu'un compte sur moi » vaut mieux qu'ajouter du contenu au mois 6.

| # | Chantier | Contenu | Pourquoi cet ordre |
|---|----------|---------|--------------------|
| ~~1~~ | ~~**RET-01**~~ ✅ | Rotation du WeeklyChallenge (cron + restitution) | **Livre le 2026-07-28** — point de rotation unique du lundi, reutilise par RET-02/04/05/06 |
| ~~2~~ | ~~**ECO-24b**~~ ✅ | Sources des minerais de haut palier + etain, puis cuirs du tanneur | **Livre le 2026-07-28** (a + b) — carte de GAME_ZONES §3 appliquee, tables de butin posees. Plus aucune matiere de recette sans source hors reserves d'extension. Reste **ECO-24c** (gate de competence sur les filons) |
| ~~3~~ | ~~**FOY-17**~~ ✅ | Mesure de la charge (a) et facteur `W` (b) | **Livre le 2026-07-28** — `W` s'applique aux capacites de filon, `respawn_seconds` fixe. Debloque FOY-08/11, ECO-22 et le recalibrage |
| ~~4~~ | ~~**Recalibrage filons**~~ ✅ | BALANCE §22.3, W=1 a ~50 joueurs/jour | **Livre le 2026-07-28**, apres ZON-37 qui en etait le prerequis cache. La couche de rarete cesse d'etre inerte : purete (ECO-22) et Paleur (FOY-11) ont desormais un signal a lire |
| 5 | **FOY-01 → 05** | Socle des foyers (Sprint 16) | Le pilier territorial commence a exister |
| ~~6~~ | ~~**RET-02 + RET-03**~~ ✅ | Commission de la semaine + commande de guilde | **Livres le 2026-07-28** — le rendez-vous hebdomadaire personnel et le « on compte sur moi » existent |
| ~~7~~ | ~~**FOY-06, 07, 10**~~ ✅ | Services gates, bonus d'atelier, regression bornee (Sprint 17) | **Livres le 2026-07-28** — faire vivre une zone y ouvre un marche et de meilleurs ateliers. **Piste B complete** |
| ~~8~~ | ~~**RET-05**~~ ✅ | Chantier de la semaine (par foyer) | **Livre le 2026-07-28** — le foyer demande, et nomme ceux qui repondent |
| ~~9~~ | ~~**ECO-21 → 22 → 23**~~ ✅ | Purete (bandes, tirage, marche/commandes) | **Livres le 2026-07-28.** Reveille `Recipe.quality`, donne un metier au prospecteur |
| ~~10~~ | ~~**RET-06**~~ ✅ | Affleurement de la semaine | **Livre le 2026-07-28** — et personne n'en est informe, c'est le point |
| 11 | **FOY-08 ✅, 09 ✅, 14 + RET-04** | La Crue + assiduite (Sprint 18) | **FOY-08 et FOY-09 livres le 2026-07-28** — l'enjeu politique existe, et une grande ville plafonne desormais ses voisines : la **Piste C est complete**. Reste FOY-14, RET-04 |
| 12 | **ECO-25 → 27** | Chaine de production par paliers | Le levier anti-creux-du-milieu |
| 13 | **FOY-11 → 13, 15** | Paleur, restauration, doctrine, marees consequence (Sprint 19) | La couche de consequence, en dernier — elle a besoin de tout le reste |
| 14 | **ZON-30 → 33** | Contenu des zones : Vallons d'Aubepine, Dunes approfondies, signatures d'amethyste ([PLAN_ZONES.md](PLAN_ZONES.md)) | **Debloque** : definitions actees dans [docs/GAME_ZONES.md](../GAME_ZONES.md) (2026-07-28). ZON-30/31 sont independants et peuvent s'intercaler plus tot si besoin de contenu ; ZON-32 attend ECO-21/22 |

Transverse, au fil de l'eau : FOY-16 et RET-07 (tests), mise a jour de `ROADMAP_DONE.md`
a chaque jalon livre (regle 13 de CLAUDE.md).

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
- [Retention hebdomadaire (RET-01 a RET-07)](PLAN_RETENTION.md) — **5/7** : l'horizon le plus fragile de la colonne de progression ; **RET-01 et RET-03 livres** (2026-07-28), la rotation du lundi 00h00 existe et sert de point d'entree unique aux briques suivantes
- [Foyers, Crue et Paleur (FOY-01 a FOY-17)](PLAN_SETTLEMENTS.md) — **12/17, grand chantier en cours** (socle FOY-01→05 complet, Pistes B et C completes : FOY-06, FOY-07, FOY-08, FOY-09, FOY-10, FOY-17a/b livres) : le monde bati par les joueurs (socle de monde adopte, cf. [docs/GAME_WORLD.md](../GAME_WORLD.md))
- [Economie joueur (ECO-01 a ECO-17, ECO-21 a ECO-27)](PLAN_PLAYER_ECONOMY.md) — **Pistes F (purete) et G (chaine de production par paliers) ouvertes** : la Piste G est le levier principal contre le creux du milieu. Pistes A et B **completes** (Sprint 14, 9 jalons dont ECO-18/19 nes de la campagne) ; Piste C en cours (Sprint 15)
- [Narration (NAR-01 a NAR-14)](PLAN_NARRATIVE.md) ✅ — plan complet (2026-07-25)
- [Controle de cite par les guildes (GCC-01 a GCC-20)](PLAN_GUILD_CITY_CONTROL.md) ✅
- [Testing & qualite (TST-01 a TST-15)](PLAN_TESTING.md) ✅ — plan complet
- [Editeur de cartes integre & generateur procedural](PLAN_MAP_EDITOR.md) ✅ — termine, sans suite dans Amethyste (reutilisable pour un futur projet Zelda-like separe)
- [Systeme d'avatar modulaire (format 8x8)](PLAN_AVATAR_SYSTEM.md) — ⛔ clos par le pivot PBBG (34/38 realisees)
- [Archive du detail livre des Sprints 11-12](ARCHIVE_SPRINT_11_12.md) — historique verbatim (non agrege par l'admin)
