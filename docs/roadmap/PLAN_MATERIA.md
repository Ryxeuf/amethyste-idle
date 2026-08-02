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
| MAT-03 | Le catalogue à 200 | M | ← MAT-02 |
| MAT-04 | Le plancher du jour 1 | M | ← MAT-03 |
| MAT-05 | Le butin dérivé | M | ← MAT-01, MAT-03 |
| MAT-06 | Coffres et donjons | S | ← MAT-03 |
| MAT-07 | Le nettoyage | S | ← MAT-03 |
| MAT-08 | Tests du plan | S | ‖ |

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

### MAT-03 — Le catalogue à 200 (M | ★★★ | HAUTE) — **le pivot**
> Une matéria par `unlock` distinct. Plus aucun nœud d'arbre ne promet ce qui
> n'existe pas.
> Prérequis : ← MAT-02
- [ ] Générer les **139 matéria manquantes** par dérivation des sorts existants
      (30 en m1, 67 en m2, 52 en m3, 31 en m4, 20 en m5)
- [ ] **Renommer les 68 matéria existantes** à la nouvelle convention — le jeu
      est en pur dev, aucune compatibilité à préserver
- [ ] `domain` = l'arbre qui porte le nœud ; `null` si plusieurs arbres l'ouvrent
      (une matéria n'appartient pas à un arbre, elle est *ouverte* par lui)
- [ ] Découpage : par élément (8 lots), jamais 139 entrées en une passe
      (règle 8 du CLAUDE.md)
- [ ] Tests : 200 matéria, une par `unlock`, aucun nœud orphelin

### MAT-07 — Le nettoyage (S | ★★ | MOYENNE)
> Prérequis : ← MAT-03
- [ ] Retirer `nb_usages` de `type: materia` — inerte en combat, mais contredit
      « la matéria est le build du personnage » (GAME_MATERIA §2.4)
- [ ] Raccrocher les **7 matéria qu'aucun nœud n'ouvre** (`flamer`,
      `frost-maelstrom`, `orichalcum-blade`, `primal-awakening`,
      `shadow-covenant`, `solar-burst`, `thunder-storm`) au nœud terminal de
      l'arbre de leur élément
- [ ] Tests : aucune matéria consommable, aucune matéria sans accord

---

## Piste B — Le monde

### MAT-04 — Le plancher du jour 1 (M | ★★★ | HAUTE)
> Le jalon qui rend le jeu jouable. Les 24 arbres de combat portent **déjà**
> exactement 2 nœuds `unlock` à 0 point : la structure est en place, seules les
> 48 matéria correspondantes manquent (4 obtenables, 13 sans source, 31 à créer).
> Prérequis : ← MAT-03
- [ ] Les 48 matéria du jour 1 en **boutique PNJ** — plancher T1 étendu au build
      (`GAME_PRINCIPLES`) : le marché joueur peut faire mieux, jamais moins
- [ ] Placement : les huit éléments au palier d'entrée **au Fanal** ; le reste
      chez le PNJ de la zone dont la ligne porte l'élément
- [ ] **Le palier de distribution suit le nœud, pas le sort** : une matéria
      ouverte à 0 point est au plancher même si son sort est de niveau 2
      (15 des 48 sont dans ce cas — on ne retouche pas 15 sorts équilibrés)
- [ ] La 1re matéria de l'arbre choisi est **donnée** par l'acte I, sur le modèle
      de `materia_soin` (le seul canal de quête livré aujourd'hui)
- [ ] Tests : les 48 sourcées, à ≤ 1 liaison du Fanal, au moins une source
      **non aléatoire** par matéria

### MAT-05 — Le butin dérivé (M | ★★★ | HAUTE)
> La voie normale et abondante du canon. Aujourd'hui : 9 matéria distinctes sur
> 31 lignes de butin — le canon affirmait 69, l'écart est l'objet de ce plan.
> Prérequis : ← MAT-01 (l'élément), MAT-03 (le catalogue)
- [ ] Règle de distribution : **un monstre lâche des matéria de son élément, à un
      palier borné par son niveau** — la table cesse d'être écrite à la main
- [ ] Paliers m1 à m3 en voie normale, m4 en rare, m5 jamais en butin
- [ ] Conserver la fourchette de probabilité canonique (4-10 %)
- [ ] **Vigilance** : la courbe de monstres est creuse au-delà du niveau 5
      (42 des 65 monstres tiennent dans les niveaux 1-5, cf. audit §6.1). Les
      paliers m4-m5 n'auront pas de porteurs suffisants — d'où MAT-06, et une
      dépendance à la revue des monstres
- [ ] Tests : dérivation, bornes de palier, couverture des 8 éléments

### MAT-06 — Coffres et donjons (S | ★★ | MOYENNE)
> Le palier moyen et haut, et le premier contenu **propre** des donjons.
> Prérequis : ← MAT-03
- [ ] Les coffres d'exploration (`explore.weights.chest`) ne donnent aujourd'hui
      que des gils : ils prennent m3-m4, indexés sur la zone
- [ ] Les donjons prennent m4-m5 — leur première raison mécanique d'exister
- [ ] Converge avec la revue des donjons (2 donjons de groupe pour 12 zones)
- [ ] Tests : distribution par zone, bornes de palier

---

## Piste C — Le contrat

### MAT-08 — Tests du plan (S | ★★★ | HAUTE)
> ‖ au fil des jalons. Les 7 invariants de GAME_MATERIA §6, en CI.
- [ ] Aucun nœud ne ment : tout `materia.unlock` a sa matéria
- [ ] Aucune matéria orpheline : toute matéria est ouverte par un nœud
- [ ] Le jour 1 est tenu : 48 matéria sourcées, ≤ 1 liaison, source non aléatoire
- [ ] Toute matéria est obtenable par au moins un canal
- [ ] La dérivation tient : slug, élément et rareté déductibles du sort
- [ ] Aucune matéria n'est consommable
- [ ] Le catalogue est complet : 200, une par `unlock` distinct

---

## Ce que ce plan ne fait pas

- **L'Autel d'éveil** (`PLAN_REPERTOIRE`, 0/6) reste tardif, gaté Métropole et
  « jamais nécessaire » (canon). Il couronne le butin, il ne le remplace pas, et
  il n'est pas sur ce chemin critique.
- **La fusion** (`MateriaFusionManager`, écrit mais jamais branché) reste un
  contenu d'extension.
- **Les arbres** ne sont pas retouchés : aucun nœud réécrit, aucun `unlock`
  déplacé. La structure des 24 arbres est validée telle quelle.
