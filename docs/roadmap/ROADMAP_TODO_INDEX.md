# Roadmap a venir — Index

> Les taches detaillees sont reparties par **sprint** dans les fichiers ci-dessous.
> Derniere mise a jour : 2026-07-29 (**ouverture du plan Onboarding ONB-01→15** — le jeu n'avait aucune porte d'entree : cf. [PLAN_ONBOARDING.md](PLAN_ONBOARDING.md)) ; 2026-07-29 (**point global doc↔code** : DOM 8/8 complet, ZON-30→36 livres, FAC ouvert 1/10, FOY 17/17 + vague 2 FOY-18→21 ouverte, RET 7/7) ; 2026-07-26 (**tache 130** montures, **Sprint 13 clos** par ZON-26b-a, **Sprints 14-15 termines**, **housing complet**) ; 2026-07-25 (**point post-pivot** : campagne ZON close, menage des sprints 11-12, ouverture des Sprints 13-14)
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
| Plan Narration (NAR) | 14/14 | ✅ Termine (2026-07-25) — vague 2 ouverte : NAR-15→20 (l'an 1 des marees) |
| Chantier Modele zone (ZON-01→21) | 21/21 | ✅ Termine (Sprints 7-10) |
| **Consolidation post-pivot (ZON-22→27)** | **8/8** | ✅ Sprint 13 termine (2026-07-26) — dette du pivot soldee |
| Plan Retention (RET-01→07) | 7/7 | ✅ Termine (2026-07-28) — vague 4 ouverte : RET-08→10 (le tableau du lundi) |
| Plan Foyers (FOY-01→17) | 17/17 | ✅ Termine (2026-07-29) — vague 2 ouverte : FOY-18→21 (logement) |
| Plan Zones (ZON-30→36) | 7/7 | ✅ Termine (2026-07-29) — +ZON-37/38/39/40 livres ; **ZON-41 ouvert** (cablage des illustrations de zone) ; restent des donnees |
| Plan Domaines (DOM-01→08) | 8/8 | ✅ Termine (2026-07-29) — DOM-09 ouvert |
| Plan Factions (FAC-01→10) | 1/10 | En cours (FAC-01 livre le 2026-07-29) |
| **Plan Onboarding (ONB-01→20)** | **0/20** | **A faire — bloquant** : `/register` leve un 404, aucun compte ne peut naitre |
| Plan Repertoire (REP-01→06) | 0/6 | A faire (l'Autel d'eveil, apres FAC-04) |
| **Plan Economie joueur (ECO)** | Pistes A→E, G, H completes | Reste ECO-28 (Piste F 3/4) et la Piste I ECO-32→35 (caravanes) |
| Sprint 11 — Monde vivant | 6/6 | ✅ Termine (2026-07-26) |
| Sprint 12 — Technique & i18n | 3 taches restantes | En cours (134 perf, 135 i18n, 136 doc admin) — **seul sprint ouvert** |

---

## Ordre de chantier (2026-07-28) — pour un developpement autonome

> Sequence de reference issue du cadrage macro (GAME_WORLD, GAME_PROGRESSION, BALANCE §22).
> Un agent qui reprend le projet **execute dans cet ordre**, sauf contrordre explicite.
> Critere d'arbitrage permanent (GAME_PROGRESSION §4) : rapprocher de la semaine 3 le moment
> ou « quelqu'un compte sur moi » vaut mieux qu'ajouter du contenu au mois 6.

| # | Chantier | Contenu | Pourquoi cet ordre |
|---|----------|---------|--------------------|
| ~~1~~ | ~~**RET-01**~~ ✅ | Rotation du WeeklyChallenge (cron + restitution) | **Livre le 2026-07-28** — point de rotation unique du lundi, reutilise par RET-02/04/05/06 |
| ~~2~~ | ~~**ECO-24b**~~ ✅ | Sources des minerais de haut palier + etain, puis cuirs du tanneur | **Livre le 2026-07-28** (a + b) — carte de GAME_ZONES §3 appliquee, tables de butin posees. Plus aucune matiere de recette sans source hors reserves d'extension. **ECO-24c livre le 2026-07-28** (gate de competence sur les filons) |
| ~~3~~ | ~~**FOY-17**~~ ✅ | Mesure de la charge (a) et facteur `W` (b) | **Livre le 2026-07-28** — `W` s'applique aux capacites de filon, `respawn_seconds` fixe. Debloque FOY-08/11, ECO-22 et le recalibrage |
| ~~4~~ | ~~**Recalibrage filons**~~ ✅ | BALANCE §22.3, W=1 a ~50 joueurs/jour | **Livre le 2026-07-28**, apres ZON-37 qui en etait le prerequis cache. La couche de rarete cesse d'etre inerte : purete (ECO-22) et Paleur (FOY-11) ont desormais un signal a lire |
| ~~5~~ | ~~**FOY-01 → 05**~~ ✅ | Socle des foyers | **Livre le 2026-07-28** — le pilier territorial existe |
| ~~6~~ | ~~**RET-02 + RET-03**~~ ✅ | Commission de la semaine + commande de guilde | **Livres le 2026-07-28** — le rendez-vous hebdomadaire personnel et le « on compte sur moi » existent |
| ~~7~~ | ~~**FOY-06, 07, 10**~~ ✅ | Services gates, bonus d'atelier, regression bornee | **Livres le 2026-07-28** — faire vivre une zone y ouvre un marche et de meilleurs ateliers. **Piste B complete** |
| ~~8~~ | ~~**RET-05**~~ ✅ | Chantier de la semaine (par foyer) | **Livre le 2026-07-28** — le foyer demande, et nomme ceux qui repondent |
| ~~9~~ | ~~**ECO-21 → 22 → 23**~~ ✅ | Purete (bandes, tirage, marche/commandes) | **Livres le 2026-07-28.** Reveille `Recipe.quality`, donne un metier au prospecteur |
| ~~10~~ | ~~**RET-06**~~ ✅ | Affleurement de la semaine | **Livre le 2026-07-28** — et personne n'en est informe, c'est le point |
| ~~11~~ | ~~**FOY-08, 09, 14 + RET-04**~~ ✅ | La Crue + assiduite | **Livres le 2026-07-28** — l'enjeu politique existe, une grande ville plafonne desormais ses voisines (**Piste C complete**), le journal de monde grave qui a bati quoi a chaque cloture de maree, et l'assiduite est en paliers. Bloc complet |
| ~~12~~ | ~~**ECO-25 → 27**~~ ✅ | Chaine de production par paliers | **Livres le 2026-07-28** — l'echelle du metal est continue du cuivre a l'orichalque (coefficient 1), la purete remonte la chaine par le maillon le plus faible, aucun craft ne detruit de la valeur. **Piste G complete** |
| ~~13~~ | ~~**FOY-11 → 16**~~ ✅ | Paleur, restauration, doctrine, marees consequence | **Livres les 2026-07-28/29** — l'extraction laisse une trace par filon (jamais par zone), la sanction devient une depense politique (**Piste D complete**), l'axe Extraire / Preserver devient un batiment (**Piste E complete**), la maree qui vient est choisie par ce que le serveur a fait, contrat transverse pose. **Plan foyers complet 17/17** |
| ~~14~~ | ~~**ZON-30 → 36**~~ ✅ | Contenu des zones ([PLAN_ZONES.md](PLAN_ZONES.md)) | **7/7 livres au 2026-07-29 — plan complet** : Vallons d'Aubepine, Dunes approfondies, signatures d'amethyste, lois de zone en contrat, ligne du bois, recoltes harmonisees, affinites elementaires. **+ZON-37/38 livres** en chemin |
| 15 | **ONB-01 → 04** | Le compte peut naitre ([PLAN_ONBOARDING.md](PLAN_ONBOARDING.md), Piste A) | **Rang 1 de fait** : `/register` leve un 404, il n'existe aucun mailer, et `isBanned` n'est lu nulle part. Tant que ce bloc n'est pas livre, le jeu n'a aucun joueur possible et perdre son mot de passe fait perdre son personnage |
| 16 | **ONB-15** | Reparer les quetes `explore` de l'arc d'intro | Trois des sept quetes d'`intro` valident un `explore` par `map_id`+coordonnees : post-ZON-21 elles ne se declenchent qu'au voyage. **L'acte I est bloque des sa premiere etape** — correctif S, a passer avant tout contenu neuf |
| 16 bis | **MAT-01 → 04** | Rendre le combat jouable ([PLAN_MATERIA.md](PLAN_MATERIA.md)) | **Rang 1 de fait, au meme titre qu'ONB-01.** La regle 10 fait de la materia la seule source d'actions de combat ; les arbres promettent 200 materia, 10 sont obtenables, et **20 arbres de combat sur 24 demarrent sans aucun sort**. A passer **avant ONB-05→20** : le tunnel enseigne la materia (ONB-11), il ne peut pas enseigner ce qui n'existe pas. MAT-01 (l'element des 65 monstres) debloque au passage la capacite raciale de l'Orc. Cout reel modere — **les 139 sorts manquants existent deja tous**, seul l'objet qui les porte manque |
| 16 ter | **OBJ-01 → 04** | Objets et ressources ([PLAN_ITEMS.md](PLAN_ITEMS.md)) | **OBJ-01 est le jalon le plus rentable du lot** : une passe de donnees repare un bug d'inventaire visible (l'onglet Materiaux cache 57 matieres sur 91). **OBJ-04** — les emplacements de materia typés et progressifs — est le **support de la decision materia** : une materia ne se sertit que dans un emplacement qui l'accepte, donc a livrer **avec ou apres MAT-03**, jamais avant. OBJ-03 referme une grille d'equipement dont les deux moities ne se rejoignaient jamais |
| 16 quater | **BES-01 → 05** | Le bestiaire ([PLAN_BESTIARY.md](PLAN_BESTIARY.md)) | **BES-01 partage la migration d'entite avec MAT-01** : les deux touchent `Monster`, a livrer ensemble et jamais sur deux branches. BES-03 et BES-05 sont independants et sans risque — a passer en premier. Le jalon ne cree quasiment aucun monstre : la faille du milieu vient de **17 especes que seul `MobFixtures` place**, invisibles au graphe declaratif |
| 16 quinquies | **DON-01 → 05** | Les donjons ([PLAN_DUNGEONS.md](PLAN_DUNGEONS.md)) | **DON-01 est un correctif narratif** : le Nexus de la Convergence est la fin de l'arc et il est injouable — modele carte post-ZON-21, et aucun monstre. **DON-03 suppose BES-01** (une rencontre puise dans la faune de son palier) et **DON-04 converge avec MAT-06** (les donjons sont le canal des materia m4-m5). Le contenu neuf se limite a **un donjon T3** : les trois autres paliers reutilisent l'existant |
| 17 | **BALANCE §24.0 + §24.3** | Ponderation du grain + seuils de foyer × W | §24.0 : 1 ligne de config `settlements.yaml`, toujours **non appliquee** au 2026-07-29. §24.3 **tranche le 2026-07-29 : la doc a raison** — `SettlementRankCalculator` doit lire des seuils × `W` (`WorldScaleService`). Meme chaine de depot, a livrer ensemble |
| 18 | **RET-08 → 10** | Le tableau du lundi ([GAME_DASHBOARD](../GAME_DASHBOARD.md), PLAN_RETENTION vague 4) | Verdict V1 du playtest : le jalon UI le plus rentable — la semaine entre sur le hub, le lundi devient un etat, le choix de commission remonte |
| 20 | **ONB-05 → 20** | Le tunnel, la boucle du jeu et le coach ([PLAN_ONBOARDING.md](PLAN_ONBOARDING.md), Pistes B a E) | NAR-20 et ZON-39 sont livrees (2026-07-30) : le tunnel neuf, le foyer d'attache et les PNJ du Fanal touchaient les memes textes. Deux points d'attention : **ONB-08** (le parchemin ouvre un arbre) **precise GAME_DOMAINS §1** — a relire avec DOM ; **ONB-10** est une exigence de **donnees de zone** a instruire avec PLAN_ZONES (le Fanal n'expose qu'une seule recolte, donc « au choix » y est aujourd'hui impossible) |
| 21 | **FAC-02 → 03** | Les gestes nourrissent la reputation, les consequences d'Hostile | La faction portee (FAC-01) doit se gagner par le jeu, pas par decret |
| 22 | **FAC-04 → 05** | La Fonderie (faction + contrats d'approvisionnement) | La cinquieme faction devient jouable ; prerequis du Repertoire |
| 23 | **ECO-28** | Commandes de service | Clot la **Piste F** ; prerequis tous livres |
| 24 | **FAC-06 → 08** | Les Ruelles jouees (receleur, contrefacon, contrebande) | La face sombre de la Concorde, sans PvP |
| 25 | **REP-01 → 06** | L'Autel d'eveil et le Repertoire ([PLAN_REPERTOIRE.md](PLAN_REPERTOIRE.md)) | Apres FAC-04 — le dernier systeme acte sans code |
| 26 | **FOY-18 → 21** | Le logement dans les foyers (vague 2) | Le housing rejoint le pilier territorial |
| 27 | **ECO-32 → 35** | Les caravanes (Piste I) | Affretement Bourg↔Bourg, escorte-astreinte, jamais d'interception joueur |
| 28 | **NAR-15 → 19** | L'an 1 des marees ([PLAN_NARRATIVE.md](PLAN_NARRATIVE.md)) | Attention : la moitie « consequences » de NAR-15 est deja livree par FOY-15. **NAR-20 est livree le 2026-07-30**, avec ZON-39 — l'acte d'intro s'est reecrit avec le tunnel, pas apres |
| 29 | **DOM-09, restes ZON-26b** | Au fil de l'eau | Bornage des nœuds partages + arbitrage Element wood/composes (DOM-09), Marais/Crete declaratifs, illustrations de zone, 3e source de cuivre |
| 30 | **WIK-02 → 03** | Le wiki joueur en ligne ([PLAN_WIKI.md](PLAN_WIKI.md)) | Contenu WIK-01 ✅ livre (docs/wiki/, 7 chapitres) ; reste le controleur public /wiki et l'acces site + jeu |

Transverse, au fil de l'eau : **FOY-16 livre** (tests du pilier territorial ; **RET-07 livre**), mise a jour de `ROADMAP_DONE.md`
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
| **Sprint 12** | Technique & i18n (134-136) | Basse | En cours — **seul sprint ouvert** (134 perf, 135 i18n, 136 doc admin) |

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

SPRINT 13 — CONSOLIDATION POST-PIVOT ✅ 8/8 (2026-07-26)
  ZON-22..27 livres (dont ZON-26a graphe, ZON-26b-a mobs declaratifs,
  ZON-26b-b PNJ declaratifs — restent des donnees, cf. rang 25 de l'ordre de chantier)

SPRINT 14 — ECONOMIE JOUEUR : SOCLE ✅ 9/9
  ECO-01/02/03/04/14/16 livres

SPRINT 11 — MONDE VIVANT ✅ 6/6 (2026-07-26)
  128 zones Acte 4, 129 housing, 130 montures, 131 events live,
  132 classement saisonnier, 133 mini-jeux — tous livres

SPRINT 12 — TECHNIQUE & I18N (seul sprint encore ouvert)
  134 Load testing & scaling              jalons restants (ZON-24 livre)
  135 Localisation i18n                   ∅ (contenu de jeu + ecrans du pivot)
  136 Documentation admin

SUITE ECONOMIE ✅ LIVREE (Sprint 15 + Pistes D/E/G/H)
  ECO-05..09 commandes de craft ✅, ECO-10..13 echoppes ✅, ECO-15 ✅, ECO-17 ✅

RESTE — pilote par les plans annexes (cf. ordre de chantier, rangs 15+) :
  ONB-01..20 (compte, personnage, arrivee — bloquant, cf. rangs 15/16/20),
  FAC-02..10, REP-01..06, ECO-28, ECO-32..35 (caravanes),
  FOY-18..21 (logement), NAR-15..19 (marees), DOM-09
```

---

## Parallelisation

Les sprints numerotes sont **soldes**, a l'exception du **Sprint 12** (134 perf, 135 i18n, 136 doc admin),
parallelisable a tout moment. Le travail est desormais pilote par les **plans annexes**
(FAC, REP, ECO Piste I, FOY vague 2, NAR vague 2) ; la sequence de reference est
l'« Ordre de chantier » ci-dessus.

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
- [Retention hebdomadaire (RET-01 a RET-07)](PLAN_RETENTION.md) ✅ — **7/7, plan complet** : l'horizon le plus fragile de la colonne de progression. La bascule du lundi est **une** — `WeekKey` en est le point de calcul unique, verrouille par `RetentionPlanContractTest`
- [Foyers, Crue et Paleur (FOY-01 a FOY-17)](PLAN_SETTLEMENTS.md) ✅ — **17/17, plan complet (2026-07-29)** ; **vague 2 ouverte : FOY-18→21** (le logement dans les foyers, decline GAME_WORLD §12.6) : le monde bati par les joueurs (socle de monde adopte, cf. [docs/GAME_WORLD.md](../GAME_WORLD.md))
- [Contenu des zones (ZON-30 a ZON-36)](PLAN_ZONES.md) ✅ — **7/7, plan complet (2026-07-29)** ; **+ZON-37/38 livres** en chemin ; **ZON-39** et **ZON-40** livres le 2026-07-30 (la loi de nommage rejoint les libelles avec NAR-20 ; l'amethyste affleure et les signatures cessent d'etre inertes) ; **ZON-41 ouvert le 2026-07-31** — `Zone::illustrationPath` existe, est rendu par l'ecran de zone et n'est lu par aucun loader : une valeur saisie en admin est perdue au prochain chargement de fixtures. Le jalon la fait entrer dans le YAML (prompts des 12 bandeaux : [docs/ZONE_IMAGE_PROMPTS.md](../ZONE_IMAGE_PROMPTS.md)). Plus les restes de donnees ZON-26b (Marais/Crete declaratifs, 3e source de cuivre) ; decline [docs/GAME_ZONES.md](../GAME_ZONES.md)
- [Factions (FAC-01 a FAC-10)](PLAN_FACTIONS.md) — **1/10, en cours** (FAC-01 livre le 2026-07-29 : la decote au-dela d'Ami, une seule faction portee, les bonus de palier appliques) : tension par paires, patronage, la Fonderie, les Ruelles, les cinq portes ; decline GAME_WORLD §6.4 et §12.2/12.4/12.5
- [Arbres de domaine (DOM-01 a DOM-08)](PLAN_DOMAINS.md) ✅ — **8/8, plan complet (2026-07-29)** ; **DOM-09 ouvert** (bornage des nœuds partages + arbitrage Element wood/composes) ; **DOM-10 ouvert** (les arbres retrouves — hors catalogue, ouverts par une rencontre que l'accomplissement declenche ; ← ONB-08. Repare le fait que **terminer un arbre ne donne rien** aujourd'hui) ; decline [docs/GAME_DOMAINS.md](../GAME_DOMAINS.md)
- [Archetypes de combat (ARC-01 a ARC-19)](PLAN_ARCHETYPES.md) — **0/19, a faire** : ce qui manque a DOM pour que deux arbres ne se ressemblent pas. Un arbre ne contient que des acces, des passifs et des accords (regles 9 et 10) — **l'archetype vit donc dans le couple (arbre, materia qu'il ouvre)**, et avec 5 statistiques plates on ne peut distinguer ni un mage d'un archer, ni les **trois arbres d'eau × sorts** entre eux. Le plan ajoute la **fonction** comme 3e axe (assaut/controle/entretien/encaisse), **une ressource par registre** (PM / temps de reprise / **munitions**), le **geste d'arme comme materia** (`Spell::register` — sans quoi un arbre de melee ne qualifie **aucune action**, l'attaque de base ne lisant pas les passifs et aucune materia de technique n'existant), **15 leviers en pourcentage** sous **budget de 50 points par arbre** (un `damage: +1` vaut aujourd'hui +50 % sur un geste a 2 degats et +8 % sur un geste a 12), des **passifs conditionnes a ce qu'on porte** (dague, bouclier, ligne d'armure — ce qui fait enfin de l'equipement un build et donne envie de panacher), une **echelle de cout** ramenee de 465 a 390 points, et **le plafond global de 500 supprime** (il contredisait « le savoir n'est jamais borne »). Plus la **loi du depot** : le donjon de groupe etant semi-synchrone, un geste qui touche le groupe **depose une duree** au lieu de reagir — c'est ce qui rend l'archetype de soin jouable a 1-2 joueurs simultanes. Et cinq nuances : **une marque par element** (le capstone d'assaut, `grip` et la palette de controle n'ont sinon aucun objet), la **munition qui porte l'element** de l'archer, un **trait par famille d'arme** et trois profils d'armure, **la fourche** (l'arbre ecrit 60 points de budget, le personnage en porte 50 — deux pyromanciens finis cessent d'etre identiques ; reutilise `other_branch`), le **pacte** (un malus rend du budget) et les **accointances** — les `DomainSynergy` livrees sont une fuite de budget (`damage +10` hors de tout arbre, hors des plafonds). **ARC-02 partage la migration d'objet avec MAT-01** ; **ARC-05 croise BES-01** (la duree d'un combat est un contrat entre les PV d'un monstre et la valeur d'un geste). Quatre arbres ecrits en entier comme patrons, plus **quatre exemples deroules** (un soldat du jour 3 au mois 3, un guerisseur seul puis en donjon, un archer sur une journee de jeu, un hydromancien mesure en tours voles) qui ont produit treize corrections — **aucune ne portant sur un pourcentage** —, puis une **comparaison croisee** (§9 sexies) qui conclut que **les arbres ne sont pas equilibres** : le guerrier tue aussi vite, survit mieux et ne paie rien ; les leviers de survie rendent 2,5x les leviers de degats a budget egal ; et **la vitesse n'a aucune valeur** puisqu'une chasse coute 5 points d'energie quel que soit le nombre de tours. Trois corrections de plus, et un arbitrage rendu (les **rencontres a fenetre**). Puis une **simulation de journee** (§9 septies) qui **renverse le classement** — sur une journee c'est le guerisseur qui domine (70 PV perdus contre 494 a 710) et le tank pur qui est le pire des six —, d'ou deux corrections : **les munitions cessent d'etre un consommable payant** (aucun archetype ne porte un cout recurrent en gils) et **les PM regenerent comme les PV** (le curseur qui decide de tout l'equilibre solo, et qui n'existe pas). Enfin (§9 octies) **une elite n'est pas un palier solo** : a frappe ~2,9x celle d'un commun du meme palier, les six builds y laissent 102 a 129 % de leur barre — uniformement mortelle seul, et normale a quatre sans regle speciale, puisque les degats ne se divisent pas par la taille du groupe mais que les depots, si. A porter dans GAME_BESTIARY et GAME_DUNGEONS. Enfin (§13) **huit formes de geste** empruntees aux autres MMO — familier, charge, transfert, riposte, ouverture, conversion, posture, differe —, **chacune reparant un defaut mesure**, et **aucune ne demandant une cinquieme fonction** : ce qui manquait n'etait pas un role mais un vocabulaire du cote de la materia. **Deux arbitrages rendus** : le familier est un **depot offensif** (pas un acteur : cout L → M, et il ne sert que sur les longues rencontres), et **l'aggro entre bornee a 50 %** une fois la riposte livree (DON-03) — avec un prerequis chiffre dans GAME_ITEMS (**la plaque doit porter ~−30 %** : la mitigation d'un tank vient de son armure, pas de son arbre) et une correction de curseur (`encounter_hp_per_member` 200 → ~110, sinon le soigneur devient obligatoire). **Armures actees** : plaque 40 %, cuir 20 %, tissu 0 % (x2,3 / x1,6 / x1 de PV effectifs). Et un cadrage de methode (§0.2) : **aucun chiffre du document n'est une valeur de jeu**, ce sont des reperes calcules a la main — la recalibration passe par **`app:balance:simulate`** (ARC-17), qui lit les vraies donnees, rejoue cinq scenarios et tient les seuils en CI — dont un defaut structurel : la munition portant l'element, une fleche ordinaire produisait une action **sans element**, donc **sans aucun passif d'arbre** : la fourche porte un accord, toute protection se depose, le multiplicateur suit la frequence mesuree, l'ancre d'echelle gagne une seconde moitie (le cout d'une rencontre rapporte au budget du jour), la duree d'un depot etale sa valeur sans l'augmenter, et les marques se portent des deux cotes ; decline [docs/GAME_ARCHETYPES.md](../GAME_ARCHETYPES.md)
- [Arbres de metier (MET-01 a MET-10)](PLAN_TRADES.md) — **0/10, a faire** : second volet du chantier des arbres, apres ARC. Le constat mesure est brutal — **175 nœuds sur 190 sont des portes** : un arbre de metier ne dit pas *comment on travaille*, il dit *a quoi on a droit*. Un seul levier existe (`gather_percent`, plafonne a +100 %), present sur 10 nœuds, **tous en recolte** — **l'artisanat n'a aucun levier**, un forgeron qui a fini son arbre fabrique exactement comme un debutant, seulement plus de choses. Et **ce levier fait deux choses a la fois** : `PurityDrawer` lit le bonus de **rendement** pour decaler la bande de purete, donc quantite et qualite sont **le meme curseur** et il n'y a rien a arbitrer. Le plan change le borneur : **en combat un arbre trop fort desequilibre un joueur, en metier il desequilibre le serveur** — d'ou la regle « la progression deplace la **composition** et l'**information**, elle n'augmente pas la **quantite** », rendue testable (la famille « quantite » sous 24 pb par arbre). **9 leviers** (`yield`, `purity`, `stride`, `sight`, `care` ; `finesse`, `tempo`, `thrift`, `batch`) sous 50 pb, grille 3/6/9/14 reprise du combat. La **fourche Extraire / Preserver** ramene l'axe politique du jeu (GAME_WORLD §6) a l'echelle d'une journee, et **ses dents existent deja** (`vitality_ceilings` : sous 0,66 plus aucun parfait ; pâleur qui monte deux fois plus vite qu'elle ne se repare ; `VeinRestoration` qui donne un prix a la trace laissee). **MET-01 est bloquant** : **la bande de purete n'a aucune valeur marchande** — `PurityChain` la propage, `CraftOrderManager` l'exige, mais aucun multiplicateur de prix n'existe, donc toute branche « qualite » est **strictement dominee** (trouble x1, clair x1,8, pur x3,5, parfait x9). Cote artisanat, **6 recettes sur 398** portent un `required_specialization` — et la correction n'est **pas** d'en fermer davantage (*une recette fermee disparait du serveur si personne ne prend la branche : en metier, un renoncement coute a tous les acheteurs*) mais de **deplacer la qualite**. **MET-09 etend `app:balance:simulate`** (ARC-17) aux metiers, ou l'unite de sortie devient le gil par jour et la composition. L'exemple deroule retrouve le constat du combat sur un terrain sans tours : **sans son accord, l'extractrice perd 11 % de sa journee** — plus que ne lui rapportent ses 22 points de pourcentages ; decline [docs/GAME_TRADES.md](../GAME_TRADES.md)
- [Compte, personnage et arrivee en jeu (ONB-01 a ONB-20)](PLAN_ONBOARDING.md) — **0/20, a faire, bloquant** : l'inscription n'existe pas (`/register` → 404), il n'y a ni mailer ni recuperation de mot de passe, le login n'a aucun garde-fou et l'arc d'intro est casse par le pivot. Decline [docs/GAME_ONBOARDING.md](../GAME_ONBOARDING.md) (e-mail differe derriere une porte economique et sociale, tunnel unique en 4 pas, coach par ecran ; **R1** : aucune decision de build dans le tunnel — le peuple n'oriente rien, le foyer d'attache **se gagne par les gestes** (amendement a GAME_WORLD §13.1) ; **R2** : le peuple porte **une capacite** qui touche ce qu'on sait et jamais ce qu'on produit, l'acces a un arbre passe par **un parchemin** — catalogue public complet, puis parchemin, puis arbre ouvert — et le combat s'enseigne sur **deux mannequins** au Fanal, ce qui evite d'avoir a lever son `safe: true` ; **R3** : le juste milieu est nomme — *le champ est infini, l'entree est un acte* : rien n'est ferme et tout se mene de front, mais rien n'est su avant d'avoir ete appris, **actions de base comprises** (avec grand-perisage des personnages existants ; marcher, voyager, explorer, parler et se battre a mains nues restent libres). Ouvre **DOM-10**, les arbres retrouves)
- [Wiki joueur (WIK-01 a WIK-03)](PLAN_WIKI.md) — **WIK-01 ✅ contenu livre le 2026-07-29** (docs/wiki/, 7 chapitres, ~20 pages joueur declinees des GAME_*) ; restent **WIK-02** (controleur public /wiki) et **WIK-03** (acces site + jeu, contrat d'entretien)
- [Donjons (DON-01 a DON-06)](PLAN_DUNGEONS.md) — **0/6, a faire** : deux modeles incompatibles et aucun ne tient. Les **2 donjons solo sont injouables** (ils reposent sur la carte navigable supprimee par ZON-21 ; leurs mobs n'ont aucune zone donc `ExploreService` ne les trouve jamais) et **le Nexus de la Convergence — la fin de l'arc narratif, qui exige les 4 fragments — n'a aucun monstre**. Les 2 donjons de groupe tournent, mais la rencontre est un sac de PV sans `Monster`, le degat d'un joueur est son `hit`, il n'y a **aucune riposte donc aucun echec possible**, `currentStep` est inerte et le butin est en gils seuls quand `lootPreview` promet de l'equipement. Un seul modele (le donjon de zone), trois etapes puisant dans la faune du palier, **DON-04 est le canal m4-m5 qu'attend MAT-06**, et un donjon par palier T1-T4 — la Cite ensevelie etant deja declaree « T4 — donjon » par GAME_ZONES §2, le cout net est **un seul donjon a ecrire**. Decline [docs/GAME_DUNGEONS.md](../GAME_DUNGEONS.md)
- [Bestiaire (BES-01 a BES-06)](PLAN_BESTIARY.md) ✅ — **6/6, plan complet (2026-08-02)** : deux axes tier × rang, stats derivees du gabarit, zones.yaml source unique de la faune, faille du milieu refermee, code mort retire, contrat tenu par des tests nommes. Historique du constat : il ne cree quasiment aucun monstre. Trois echelles cohabitent (`level` 1-40, `difficulty` 1-5, `isBoss`) et **aucune ne dit la difficulte** — le joueur n'ayant pas de niveau (regle 6), l'echelle 1-40 ne se compare a rien ; elles deviennent deux axes, `tier` (T0-T4, **repris de la zone**, que GAME_ZONES §2 declare deja) et `rank` (Common/Elite/Boss). Le monde se coupe en deux (depart 1-24, fin 26-38, saut de vie x4) parce que **17 especes du milieu de gamme n'existent que par `MobFixtures`**, invisible au graphe declaratif — la faille est un probleme de **repartition**, pas de contenu. `zones.yaml` devient la source unique de la faune. **BES-01 partage la migration d'entite avec MAT-01.** Decline [docs/GAME_BESTIARY.md](../GAME_BESTIARY.md)
- [Objets et ressources (OBJ-01 a OBJ-08)](PLAN_ITEMS.md) — **0/8, a faire** : l'artisanat n'est **pas** casse (107 des 115 recettes fabricables, outils de craft achetables), mais la taxonomie `Item::type` porte 12 valeurs pour 5 constantes de code — l'onglet **Materiaux cache 57 matieres sur 91** —, la grille d'equipement elementaire **ne boucle pas** (t2 sur 4 elements, t3 sur les 4 autres, aucune progression t2→t3), `materiaSlotType` n'est renseigne que sur **9 pieces sur 178** (DOM-03 inerte sur 95 % du vestiaire) et 5 outils sur 9 n'ont aucune fonction. **OBJ-04 est le support de MAT** : a livrer avec ou apres MAT-03, jamais avant. Decline [docs/GAME_ITEMS.md](../GAME_ITEMS.md)
- [Chaine materia (MAT-01 a MAT-08)](PLAN_MATERIA.md) — **0/8, a faire, bloquant** : les arbres promettent **200 materia**, le jeu en livre 68 et n'en rend **10 obtenables**. La regle 10 faisant de la materia la seule source d'actions de combat, **20 arbres de combat sur 24 demarrent sans aucun sort** et le restent. Les 139 sorts manquants **existent deja tous** — c'est un chantier de derivation et de placement, pas de design de sort. Prerequis bloquant : **aucun des 65 monstres ne porte d'element**. Decline [docs/GAME_MATERIA.md](../GAME_MATERIA.md), qui corrige le constat d'etat de GAME_WORLD §2.1
- [Repertoire des gestes retrouves (REP-01 a REP-06)](PLAN_REPERTOIRE.md) — **0/6, a faire** (l'Autel d'eveil ; apres FAC-04) ; decline GAME_WORLD §12.3. **Ne resout pas MAT-** : tardif, gate Metropole, « jamais necessaire » par le canon — il couronne le butin, il ne le remplace pas
- [Economie joueur (ECO-01 a ECO-17, ECO-21 a ECO-35)](PLAN_PLAYER_ECONOMY.md) — Pistes A, B, C, D, E, G et H **completes** (Piste G : la chaine de production a une verticale, la purete la remonte par le maillon le plus faible, aucun craft ne detruit de la valeur ; Piste H : cuisinier, charpentier et tailleur livres) ; reste **ECO-28** (commandes de service, Piste F 3/4) et la **Piste I ouverte : ECO-32→35** (les caravanes, decline GAME_WORLD §12.7)
- [Narration (NAR-01 a NAR-14)](PLAN_NARRATIVE.md) ✅ — plan complet (2026-07-25) ; **vague 2 ouverte : NAR-15→20** (l'an 1 des marees, decline [docs/GAME_SEASONS.md](../GAME_SEASONS.md) — attention, la moitie « consequences » de NAR-15 est deja livree par FOY-15)
- [Controle de cite par les guildes (GCC-01 a GCC-20)](PLAN_GUILD_CITY_CONTROL.md) ✅
- [Testing & qualite (TST-01 a TST-15)](PLAN_TESTING.md) ✅ — plan complet
- [Editeur de cartes integre & generateur procedural](PLAN_MAP_EDITOR.md) ✅ — termine, sans suite dans Amethyste (reutilisable pour un futur projet Zelda-like separe)
- [Systeme d'avatar modulaire (format 8x8)](PLAN_AVATAR_SYSTEM.md) — ⛔ clos par le pivot PBBG (34/38 realisees)
- [Archive du detail livre des Sprints 11-12](ARCHIVE_SPRINT_11_12.md) — historique verbatim (non agrege par l'admin)
