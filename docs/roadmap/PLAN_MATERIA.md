# Plan — La chaîne matéria

> **Numérotation :** jalons préfixés **MAT-**. Pas de conflit avec les autres préfixes.

> Décline [../GAME_MATERIA.md](../GAME_MATERIA.md), qui décline et corrige
> [../GAME_WORLD.md](../GAME_WORLD.md) §2.1. Constat chiffré :
> [../GAME_DATA_AUDIT.md](../GAME_DATA_AUDIT.md) §3.
>
> **Pourquoi c'est prioritaire.** 20 arbres de combat sur 24 démarrent sans aucun
> sort, et le restent : la règle 10 fait de la matéria la seule source d'actions
> de combat, et 10 matéria seulement sont obtenables dans le monde livré. Aucun
> autre chantier de contenu ne rattrape ça — un joueur qui choisit Chevalier
> n'a pas de jeu.
>
> **Ce que ce n'est pas.** Les 139 sorts réclamés par les nœuds **existent déjà
> tous**. Ce plan crée des objets qui portent des sorts écrits, et les place
> dans le monde. C'est de la dérivation et du placement, pas du design de sort.

## Vue d'ensemble

**8 jalons** (**MAT-01** à **MAT-08**) en 3 pistes.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| MAT-01 ✅ | L'élément des monstres | S | ∅ |
| MAT-02 ✅ | La dérivation matéria ← sort | M | ∅ |
| MAT-03 ✅ | Le catalogue à 200 | M | ← MAT-02 |
| MAT-04 ✅ | Le plancher du jour 1 | M | ← MAT-03 |
| MAT-05 ✅ | Le butin dérivé | M | ← MAT-01, MAT-03 |
| MAT-06 ✅ | Coffres et donjons | S | ← MAT-03 |
| MAT-07 ✅ | Le nettoyage | S | ← MAT-03 |
| MAT-08 ✅ | Tests du plan | S | ‖ |

```
Piste A — La donnée   : MAT-02 → MAT-03 → MAT-07
Piste B — Le monde    : MAT-01 ↘
                        MAT-03 → MAT-04 → MAT-05 → MAT-06
Piste C — Le contrat  : MAT-08 ‖
```

**Ordre conseillé.** MAT-01 et MAT-02 sont indépendants et peuvent partir en
parallèle. MAT-03 est le pivot : tout le reste en découle. MAT-04 est le jalon
qui rend le jeu jouable — c'est lui qu'on vise en premier après le pivot.

---

## Piste A — La donnée

### MAT-01 — L'élément des monstres (S | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02
> Prérequis bloquant du butin dérivé, et de la capacité raciale de l'Orc.
> Aucun des 65 monstres ne portait d'élément ; 44 déclaraient des
> `elementalResistances` sans jamais dire de quel flux ils relèvent.
- [x] Champ `element` (enum `Element`) sur l'entité `Monster` + migration
      (`Version20260802AMonsterElement`, défaut `none`)
- [x] Renseigner les 65 monstres — dérivé des résistances déclarées (la plus
      haute résistance positive nomme le flux) et de la lignée de zone pour les
      21 sans résistances ; les 2 mannequins d'entraînement restent `None`
- [x] Cohérence avec `elementalResistances` : un monstre résiste à son propre
      élément — règle posée dans le loader (`OWN_ELEMENT_RESISTANCE`, +0.3
      par défaut quand rien n'est déclaré), jamais 44 valeurs à la main
- [x] Débloque `RaceCapability` de l'Orc : le bestiaire affiche l'élément dans
      le bloc que `BestiaryRevealPolicy` gate déjà (flair = première rencontre)
- [x] Tests : couverture des 65 et validité de l'enum, `none` réservé aux
      mannequins, aucun monstre faible à son propre élément
      (`MonsterElementTest`)

### MAT-02 — La dérivation matéria ← sort (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02
> Une matéria ne s'écrit pas, elle se dérive (GAME_MATERIA §2.1). Ce jalon livre
> la règle et la convention de slug, avant qu'on génère quoi que ce soit.
- [x] Service de dérivation : `MateriaDerivation::derive(Spell)` →
      `MateriaBlueprint` (`element`, `name` + traductions dérivées du sort,
      `slug`, `price`, `energy_cost`, `type`, `space`)
- [x] **Convention de slug redressée** : `m<niveau du sort>-<slug du sort>`
      (`fire-ball` niveau 1 → `m1-fire-ball`), portée par `slugFor()` —
      déductible du sort, donc vérifiable, et la collision impossible
- [x] `rarity` **jamais déclarée** : `MateriaBlueprint` n'a aucun champ pour
      elle (ni pour `nb_usages`) — un test le verrouille
- [x] Grilles par palier figées (prix 130/180/280/320/380, énergie 10/15/20/25/30),
      constantes publiques du service ; un sort hors palier 1-5 est refusé
- [x] Tests : dérivation complète, unicité des slugs, rareté non déclarée,
      grilles exactes, traductions qui suivent le sort (`MateriaDerivationTest`)

### MAT-03 — Le catalogue à 200 (M | ★★★ | HAUTE) — **le pivot** — ✅ LIVRÉ 2026-08-02
> Une matéria par `unlock` distinct. Plus aucun nœud d'arbre ne promet ce qui
> n'existe pas.
- [x] Le catalogue entier est **génératif** : `MateriaCatalogFixtures` lit les
      nœuds `actions.materia.unlock` en base, résout chaque sort et dérive
      l'objet par `MateriaDerivation` (MAT-02). Les 200 unlocks (répartition
      30/67/52/31/20 par niveau, au chiffre près du cadrage) sont couverts
      sans **aucune entrée de données** — la règle 8 est satisfaite par
      construction : zéro entrée écrite, plutôt que 139 en lots
- [x] Les 69 matéria manuelles d'`ItemFixtures` **supprimées** — renommées de
      fait à la convention `m<niveau>-<slug du sort>` par la dérivation. Un
      nœud qui citerait un sort inexistant **casse le chargement** au lieu de
      mentir en silence
- [x] `domain` = l'arbre qui porte le nœud (via `Skill::domains`) ; `null`
      dès que plusieurs arbres l'ouvrent
- [x] Les références de fixtures deviennent `materia_<slug du sort>` — la
      forme que le butin, les quêtes et les inventaires de démo citaient
      déjà ; seule `materia_soin` (clé non canonique) migre vers
      `materia_life_heal` (4 sites)
- [x] Les **7 orphelines** (MAT-07) restent au catalogue via
      `ORPHAN_SPELLS`, dérivées comme les autres — un test exige qu'une
      entrée sorte de la liste le jour où un nœud l'ouvre
- [x] Tests : source unique et nœuds qui ne mentent pas
      (`MateriaCatalogTest`) ; sur base réelle, une matéria par unlock,
      slug et élément dérivés du sort (`MateriaCatalogIntegrationTest`)

### MAT-07 — Le nettoyage (S | ★★ | MOYENNE) — ✅ LIVRÉ 2026-08-02
> Prérequis : ← MAT-03
- [x] `nb_usages` retiré de `type: materia` — le catalogue dérivé n'en fixait
      déjà jamais (l'illimité -1 de l'entité), les dix exemplaires du joueur
      démo qui portaient des charges finies reprennent celui de leur objet
      générique (GAME_MATERIA §2.4)
- [x] Les **7 matéria qu'aucun nœud n'ouvrait** raccrochées au nœud terminal
      de l'arbre de leur élément, sur le motif défenseur/prêtre (nœud à
      100 points entre le rang 4 et l'ultime) : `solar-burst` → pyromancien,
      `frost-maelstrom` → hydromancien, `thunder-storm` → foudromancien,
      `orichalcum-blade` → chevalier, `primal-awakening` → dompteur,
      `shadow-covenant` → sorcier ; `flamer` (sort de niveau 2) entre en
      milieu d'arbre du pyromancien (50 points). `ORPHAN_SPELLS` vidée —
      la liste reste le filet de sécurité du catalogue
- [x] Tests : aucune matéria consommable, aucune matéria sans accord
      (`MateriaCleanupTest`) ; le raccrochage est verrouillé par
      `MateriaCatalogTest::testOrphanListIsAccurate` et le compte du
      catalogue par `MateriaCatalogIntegrationTest`

---

## Piste B — Le monde

### MAT-04 — Le plancher du jour 1 (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02
> Le jalon qui rend le jeu jouable. Les 24 arbres de combat portaient déjà
> exactement 2 nœuds `unlock` à 0 point ; le catalogue (MAT-03) a créé les
> matéria, ce jalon leur donne des marchands.
- [x] Les matéria du jour 1 (48 nœuds, **43 matéria distinctes** — cinq
      unlocks sont partagés entre arbres) en **boutique PNJ**, aux prix de la
      grille : le plancher T1 PNJ étendu au build
- [x] Placement : **Lucine l'accordeuse au Fanal** vend les 28 m1 (les huit
      éléments au palier d'entrée) ; les 15 dont le sort est de niveau 2 se
      vendent chez le PNJ de la zone dont la ligne porte l'élément — Terre
      chez Morrigane (Forêt, la référence du monde), Feu et Métal chez Kolm
      (Mines, la forge), Bête chez la vieille Brune (Vallons, le gibier),
      Eau et Ombre chez **Ysoline, troqueuse des brumes** (premier PNJ
      déclaré du Marais), Lumière chez **Élionor, chantre du temple**
      (Quartier des Jardins). L'Air n'a pas de m2 au plancher. Toutes les
      zones vendeuses sont à **une liaison** du Fanal
- [x] **Le palier de distribution suit le nœud, pas le sort** : les 15 m2 du
      plancher se vendent au jour 1 comme les m1 — aucun sort retouché
- [x] La 1re matéria de l'arbre choisi est **donnée** par l'acte I — déjà
      tenu par `ActOneMateriaGranter` (ONB-12b), que le catalogue complet
      (MAT-03) rend effectif pour les 24 arbres
- [x] Tests : `MateriaDayOneFloorTest` — les 43 sourcées en boutique (source
      non aléatoire), à ≤ 1 liaison du Fanal ; et une boutique ne vend
      jamais une matéria qu'aucun sort ne peut dériver

### MAT-05 — Le butin dérivé (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02
> La voie normale et abondante du canon. Avant : 9 matéria distinctes sur
> 31 lignes de butin écrites à la main.
- [x] Règle de distribution portée par `MateriaLootTable` : **un monstre lâche
      des matéria de son élément, à un palier borné par son palier de monde**
      (T1→m1, T2→m2, T3/T4→m3) — les 31 lignes manuelles de
      `MonsterItemFixtures` sont supprimées, la table se dérive
- [x] m1-m3 en voie normale, **m4 en rare** (réservé au T4 hors tout-venant,
      20 % des butins réussis), **m5 jamais en butin** ; un monstre sans
      élément — les mannequins — ne lâche rien ; un butin réussi redescend
      d'un palier plutôt que de s'évaporer sur un trou de catalogue
- [x] Fourchette canonique conservée : 4 % (commun), 7 % (élite), 10 %
      (boss) — et le multiplicateur d'événement s'applique comme au reste
- [x] La vigilance du plan est levée par BES-01→04 : la courbe n'est plus
      creuse (12 cases peuplées), les porteurs T3/T4 existent — MAT-06 reste
      le canal m4-m5 propre
- [x] Tests : `MateriaLootTableTest` — bornes de palier, couverture des
      8 éléments, m4 réservé, m5 jamais, fenêtre 4-10 %, repli de palier,
      mannequins muets

### MAT-06 — Coffres et donjons (S | ★★ | MOYENNE) — ✅ LIVRÉ 2026-08-02
> Le palier moyen et haut, et le premier contenu **propre** des donjons.
- [x] Les coffres d'exploration prennent m3-m4, **indexés sur la zone**
      (`Zone::tier`, BES-01) : m3 en T1-T2, m4 en T3-T4, un coffre sur dix,
      d'un élément tiré au hasard — les neuf autres gardent leurs gils
- [x] Les donjons prennent m4-m5 — m4 garanti, m5 en rare (20 %) — sur une
      réussite **fraîche** seulement : la récompense décroissante protège
      les gils, celle-ci protège le sommet du catalogue du farm
- [x] La convergence avec la revue des donjons est notée : DON-04 dérivera
      `lootPreview` de cette table
- [x] Tests : palier de coffre par zone, donjon seul canal du m5, chance
      bornée (`MateriaLootTableTest`), gils/décroissance inchangés
      (`GroupDungeonRewardServiceTest`)

---

## Piste C — Le contrat

### MAT-08 — Tests du plan (S | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02 — **plan matéria complet 8/8**
> ‖ au fil des jalons. Les 7 invariants de GAME_MATERIA §6, en CI.
- [x] Aucun nœud ne ment : tout `materia.unlock` a sa matéria
      (`MateriaCatalogTest`, `MateriaCatalogIntegrationTest`)
- [x] Aucune matéria orpheline : toute matéria est ouverte par un nœud
      (`MateriaCleanupTest`, compte du catalogue en intégration)
- [x] Le jour 1 est tenu : 48 matéria sourcées, ≤ 1 liaison, source non
      aléatoire (`MateriaDayOneFloorTest`)
- [x] Toute matéria est obtenable par au moins un canal
      (`MateriaObtainabilityTest`, sur les espèces réellement placées) —
      l'invariant a mordu : sans monstre de feu, d'air ou de lumière au T2,
      **21 m2 n'avaient aucun canal**. Correctifs : la salamandre redescend
      au T2 et gagne les Mines (la zone de la ligne du feu), la sylphe et le
      feu follet montent au T2 ; le taiju prend le cran d'élite laissé par la
      sylphe et le troll redevient commun (cibles de couverture BES-04
      tenues) ; le m1-healing-touch, plus aucun gibier T1 de lumière pour le
      porter, rejoint l'étal d'Élionor
- [x] La dérivation tient : slug, élément et rareté déductibles du sort
      (`MateriaCatalogIntegrationTest::testDerivationHolds` +
      `MateriaObtainabilityTest::testRarityFollowsThePalier`)
- [x] Aucune matéria n'est consommable (`MateriaCleanupTest` en source,
      `MateriaObtainabilityTest::testNoMateriaIsConsumable` en base)
- [x] Le catalogue est complet : 200+, une par `unlock` distinct
      (`MateriaCatalogIntegrationTest::testCatalogMatchesTheTreesPromises`)

---

## Ce que ce plan ne fait pas

- **L'Autel d'éveil** (`PLAN_REPERTOIRE`, 0/6) reste tardif, gaté Métropole et
  « jamais nécessaire » (canon). Il couronne le butin, il ne le remplace pas, et
  il n'est pas sur ce chemin critique.
- **La fusion** (`MateriaFusionManager`, écrit mais jamais branché) reste un
  contenu d'extension.
- **Les arbres** ne sont pas retouchés : aucun nœud réécrit, aucun `unlock`
  déplacé. La structure des 24 arbres est validée telle quelle.
