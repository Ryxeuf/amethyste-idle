# Plan — Objets et ressources

> **Numérotation :** jalons préfixés **OBJ-**. Pas de conflit avec les autres préfixes.

> Décline [../GAME_ITEMS.md](../GAME_ITEMS.md). Constat chiffré :
> [../GAME_DATA_AUDIT.md](../GAME_DATA_AUDIT.md) §4-5.
>
> **Ce que ce plan n'est pas.** L'artisanat n'est **pas** cassé : 107 des
> 115 recettes sont fabricables et les outils de craft en bronze et fer sont
> achetables. Ce plan répare un bug d'inventaire visible, referme une grille
> d'équipement qui ne bouclait pas, et rend utile une ligne d'outils déjà codée.
>
> **Dépendance croisée** : OBJ-04 (les emplacements typés) est le support de la
> décision matéria — une matéria ne se sertit que dans un emplacement qui
> l'accepte. À livrer **avec ou après MAT-03**, jamais avant.

## Vue d'ensemble

**8 jalons** (**OBJ-01** à **OBJ-08**) en 3 pistes.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| OBJ-01 | La taxonomie alignée sur 5 types | S | ∅ |
| OBJ-02 | Le ménage : doublons et hors-périmètre | S | ← OBJ-01 |
| OBJ-03 | La grille d'équipement neutre | M | ∅ |
| OBJ-04 | Les emplacements typés et progressifs | M | ← OBJ-03 ; ‖ MAT-03 |
| OBJ-05 | L'outil de récolte | M | ∅ |
| OBJ-06 | Les paliers d'outil et les 3 métiers manquants | M | ← OBJ-05 |
| OBJ-07 | Les matières : le champignon et l'équilibre des lignes | S | ← OBJ-02 |
| OBJ-08 | Tests du plan | S | ‖ |

```
Piste A — La donnée      : OBJ-01 → OBJ-02 → OBJ-07
Piste B — Le vestiaire   : OBJ-03 → OBJ-04   (‖ MAT-03)
Piste C — Les outils     : OBJ-05 → OBJ-06 ; OBJ-08 ‖
```

**Ordre conseillé.** OBJ-01 est le plus rentable et le moins risqué : une passe
de données répare un bug d'inventaire visible. OBJ-03/04 est le morceau de fond,
à synchroniser avec MAT-03.

---

## Piste A — La donnée

### OBJ-01 — La taxonomie alignée sur 5 types (S | ★★★ | HAUTE)
> Le code porte 5 constantes, les données 12 valeurs. L'onglet **Matériaux** de
> l'inventaire filtre sur `isResource()` et n'affiche que **34 matières sur 91**.
> Prérequis : ∅
- [ ] Migrer les données : `crafted`/`plant`/`ore`/`herb` → `resource` (57
      objets) ; `quest`/`food`/`potion` → `stuff` (11) ; `weapon` → `gear` (4)
- [ ] Les objets de quête se distinguent par `BindType`, pas par un type propre
- [ ] **Ne pas ajouter de champ `family`** : le préfixe de slug porte déjà la
      famille fine et sert de clé à `affinities.yaml` et `purity.yaml`
- [ ] Tests : les 5 types seulement, l'onglet Matériaux complet

### OBJ-02 — Le ménage (S | ★★ | HAUTE)
> Prérequis : ← OBJ-01
- [ ] Supprimer les doublons : `wood_log`, `pickaxe` (sans palier),
      `herb_lavender`, `herb_mint`, `leather_skin_1/2`, `food_bread`
- [ ] Retirer les **8 recettes hors périmètre** et les 3 minerais d'extension
      (`ore-adamantite`, `ore-starmetal`, `ore-voidium`) vers la réserve
      d'extension — `GAME_ZONES` §3 les réserve explicitement aux Extensions 1
      et 2. Une recette éternellement infabricable est un mensonge d'interface
- [ ] **Suivi ouvert** : la courbe de recettes perd ses 8 derniers crans. Le haut
      de la chaîne d'artisanat est à re-remplir **dans le périmètre de la base**
      — chantier à instruire avec `PLAN_ZONES` et la carte des minerais
- [ ] Supprimer les fixtures mortes lisibles (`fixtures/domain.yaml`,
      `fixtures/game/{skill,spell,monster}/`) — elles annoncent 15 domaines quand
      la vraie source en a 36, et rien ne signale qu'elles ne sont plus chargées
- [ ] Tests : aucun doublon de slug, aucune recette infabricable

### OBJ-07 — Les matières (S | ★★ | MOYENNE)
> Prérequis : ← OBJ-02
- [ ] **Le champignon** : `mushroom` tombe de 16 tables de butin et n'a aucun
      débouché. Il rejoint la ligne du cuisinier et devient une matière d'entrée
      d'alchimie
- [ ] Vérifier les 15 matières sans débouché : les 11 consommables finis sont
      légitimes, les autres rejoignent une ligne ou disparaissent
- [ ] Rééquilibrer les filons — herboristerie 44 %, bûcheronnage 9 % — au regard
      des débouchés (charpentier 10 recettes, cuisinier 8). **Cible : aucun
      goulot, pas l'égalité.** À instruire avec `PLAN_ZONES`
- [ ] Tests : aucune matière première sans débouché hors consommables finis

---

## Piste B — Le vestiaire

### OBJ-03 — La grille d'équipement neutre (M | ★★★ | HAUTE)
> t2 couvre air/terre/feu/eau, t3 couvre bête/ombre/lumière/métal : **aucun
> élément n'a de progression t2 → t3**. Compléter la grille demanderait
> 168 pièces — pour une variable qui n'est pas celle du build.
> Prérequis : ∅
- [ ] **La pièce d'équipement ne porte plus d'élément** (GAME_ITEMS §3.2)
- [ ] Fusionner les 56 pièces `t2_<élément>_*` / `t3_<élément>_*` en une grille
      neutre de ~21 pièces par palier ; supprimer les doublons plutôt que les
      renommer (pur dev)
- [ ] Compléter le palier t1, qui ne compte que 5 pièces
- [ ] Tests : aucune pièce d'équipement avec un élément, 3 paliers complets

### OBJ-04 — Les emplacements typés et progressifs (M | ★★★ | HAUTE)
> Le cœur du build. `materiaSlotType` existe, `MateriaGearSetter` le lit, et
> **9 pièces sur 178 le renseignent** — le défaut `Free` fait que 169 pièces
> acceptent tout, donc DOM-03 est inerte sur 95 % du vestiaire.
> Prérequis : ← OBJ-03 ; **à livrer avec ou après MAT-03**, jamais avant
- [ ] **Progression des emplacements** : 1 / 2 / 3 en t1 / t2 / t3 — ce que
      GAME_WORLD §2.1 promet et que le jeu ne tient pas (t1=1, t2=1, t3=1-2)
- [ ] **Type d'emplacement dérivé de la famille**, jamais posé pièce par pièce :
      lanceur et tissu → `Spell` ; mêlée, tir et plaque → `Technique` ; cuir →
      1 `Spell` + le reste `Technique` ; accessoires → `Free` (GAME_ITEMS §3.4)
- [ ] Cohérence avec `domain_catalog.yaml`, qui annonce déjà ces familles aux
      joueurs (« Bâtons, baguettes et tissu », « Haches, épées lourdes et cuir »)
- [ ] Tests : 100 % du vestiaire typé, dérivation depuis la famille, progression

---

## Piste C — Les outils

### OBJ-05 — L'outil de récolte (M | ★★ | MOYENNE)
> `GatherService` n'exige aucun outil : pioche, faucille, canne, couteau et
> hache — **20 objets** — n'ont aucune fonction mécanique.
> Prérequis : ∅
- [ ] La récolte exige un outil du bon type, comme le craft
- [ ] **Le palier module le rendement, jamais l'accès au filon**
- [ ] **La garantie anti-mur** : l'outil de palier 1 est **gratuit et livré à
      l'ouverture de l'arbre de récolte**, sur le modèle de `rung1.free`
      (`equipment_ports.yaml`). Sans elle, le jalon contredit
      `GAME_ZONE_ACTIONS` (« une récolte n'échoue jamais »)
- [ ] Tests : outil requis, rendement par palier, jamais de filon inaccessible

### OBJ-06 — Les paliers d'outil et les 3 métiers manquants (M | ★★ | MOYENNE)
> Sur 4 paliers déclarés, seuls bronze et fer sont atteignables : l'outillage
> s'arrête au fer.
> Prérequis : ← OBJ-05
- [ ] Recettes de forgeron pour **acier et mithril** (9 types × 2 paliers) — un
      débouché récurrent, puisque l'outil s'use déjà (`durability`)
- [ ] Un outil requis pour **cuisinier, charpentier et tailleur** :
      `CRAFT_TOOL_TYPES` n'en couvre que 4 sur 7
- [ ] Tests : les 4 paliers atteignables, les 7 métiers pourvus

---

## Piste — Le contrat

### OBJ-08 — Tests du plan (S | ★★★ | HAUTE)
> ‖ au fil des jalons. Les 9 invariants de GAME_ITEMS §6.
- [ ] Cinq types, pas douze ; l'onglet Matériaux est complet
- [ ] Aucune pièce d'équipement ne porte d'élément ; les emplacements
      progressent ; 100 % du vestiaire est typé
- [ ] Aucun outil sans fonction ; aucun palier d'outil sans source
- [ ] Aucune recette infabricable ; aucun doublon de slug
