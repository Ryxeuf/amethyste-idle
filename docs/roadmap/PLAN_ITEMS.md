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

**8 jalons** (**OBJ-01** à **OBJ-08**) en 3 pistes. **✅ Plan complet 8/8 (2026-08-02).**

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| OBJ-01 ✅ | La taxonomie alignée sur 5 types | S | ∅ |
| OBJ-02 ✅ | Le ménage : doublons et hors-périmètre | S | ← OBJ-01 |
| OBJ-03 ✅ | La grille d'équipement neutre | M | ∅ |
| OBJ-04 ✅ | Les emplacements typés et progressifs | M | ← OBJ-03 ; ‖ MAT-03 |
| OBJ-05 ✅ | L'outil de récolte | M | ∅ |
| OBJ-06 ✅ | Les paliers d'outil et les 3 métiers manquants | M | ← OBJ-05 |
| OBJ-07 ✅ | Les matières : le champignon et l'équilibre des lignes | S | ← OBJ-02 |
| OBJ-08 ✅ | Tests du plan | S | ‖ |

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

### OBJ-01 — La taxonomie alignée sur 5 types (S | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02
> Le code porte 5 constantes, les données 12 valeurs. L'onglet **Matériaux** de
> l'inventaire filtre sur `isResource()` et n'affiche que **34 matières sur 91**.
> Prérequis : ∅
- [x] Données migrées : `crafted`/`plant`/`ore`/`herb` → `resource` (57
      objets) ; `quest`/`food`/`potion` → `stuff` (11) ; `weapon` → `gear`
      (4) — plus **trois matières historiquement `stuff`** que le test de
      contrat a attrapées (`wood-log`, `leather-skin-1`, `leather-skin-2`).
      Migration idempotente pour les bases existantes
      (`Version20260802CItemTaxonomy`), consommateurs alignés
      (`ResourceCatalogController`, `ResourceCatalogListener`, formulaire
      admin qui offre enfin les 5 types)
- [x] Les objets de quête se distinguent par `BindType` (liés à l'obtention),
      pas par un type propre — les deux qui ne l'étaient pas le deviennent
- [x] **Pas de champ `family`** : le préfixe de slug porte la famille fine
      (badge du catalogue redéfini sur le préfixe, jamais sur le type)
- [x] Tests : les 5 types seulement (PHP + YAML), l'onglet Matériaux complet
      (toute matière d'une famille de récolte est `resource`), objets de
      quête liés (`ItemTaxonomyTest`)

### OBJ-02 — Le ménage (S | ★★ | HAUTE) — ✅ LIVRÉ 2026-08-02 (en deux moitiés, règle 8)
> Prérequis : ← OBJ-01
- [x] Doublons supprimés : `wood_log` (les butins et la quête du menuisier
      passent à `wood-beech`), `pickaxe` sans palier (les butins passent à
      `pickaxe_bronze`), `herb_lavender`, `herb_mint`, `leather_skin_1/2`
      (le sac de démo passe à `leather_raw`), `food_bread` — et les trois
      exclusions fantômes d'`affinities.yaml` avec eux
- [x] Les recettes hors périmètre et les 3 minerais d'extension
      (`ore-adamantite`, `ore-starmetal`, `ore-voidium`) versés à la réserve
      (`docs/EXTENSION_RESERVE.md` — un fichier, pas du contenu livré), avec
      les 3 spots legacy et les 2 nœuds de minage qui les promettaient
      (l'arbre du mineur recâblé, le capstone garde ses bonus). **Nuance
      découverte en route** : le Grand élixir ne butait que par la gemme
      prismatique — il est **réécrit en base** (gemme enchantée) plutôt que
      retiré, sans quoi le fruit du vide (ZON-35) redevenait sans débouché.
      7 recettes retirées, 1 réécrite ; les nœuds maîtres qui les citaient
      restent, en dette déclarée (`RECIPES_TO_AUTHOR`)
- [x] **Suivi ouvert** : la courbe de recettes perd ses derniers crans. Le haut
      de la chaîne d'artisanat est à re-remplir **dans le périmètre de la
      base** — chantier à instruire avec `PLAN_ZONES` et la carte des
      minerais ; `ore-orichalcum` (filon réel, débouché parti en extension)
      est l'exception nommée de `HarvestHarmonyTest` en attendant
- [x] Fixtures mortes supprimées (`fixtures/domain.yaml`,
      `fixtures/game/{skill,spell,monster}/`) — elles annonçaient 15 domaines
      quand la vraie source en a 36, et rien ne signalait qu'elles n'étaient
      plus chargées
- [x] Tests : aucun doublon de slug (344 slugs, toutes sources), les doublons
      legacy ne reviennent pas, les fixtures mortes non plus
      (`ItemCleanupTest`)
- [x] **OBJ-02b** : aucune recette infabricable — tout ingrédient résout vers
      un objet livré, et le hors-périmètre ne revient pas sans ses filons
      (`RecipeCraftabilityTest`)

### OBJ-07 — Les matières (S | ★★ | MOYENNE) — ✅ LIVRÉ 2026-08-02
> Prérequis : ← OBJ-02
- [x] **Le champignon** : `mushroom` tombe d'une vingtaine de tables de butin
      sans aucun débouché. Il devient une **matière** (`resource`, migration
      comprise), rejoint la ligne du cuisinier (la fricassée, au nœud gratuit
      du four) et entre dans la base de potion de l'alchimiste
- [x] Vérifier les matières sans débouché : l'audit du 2026-08-02 n'en trouve
      plus que 9 — les 8 produits finis (plats, flèches, nécessaire, sceau)
      sont légitimes, et `ore-orichalcum` relève de la dette déclarée
      (`recipe-orichalcum-ingot` dans `RECIPES_TO_AUTHOR`). La Piste H et
      OBJ-01→06 avaient résorbé le reste
- [x] Rééquilibrer les filons — **mesure du 2026-08-02 : pas de goulot**.
      Chaque essence a sa source (ZON-35), chaque poisson la sienne, et
      `RecipeCraftabilityTest` tient « tout ingrédient atteignable ». La
      répartition (herboristerie 43 %, bûcheronnage 9 %) reste inégale mais la
      cible était « aucun goulot, pas l'égalité » — rien à corriger tant qu'une
      ligne n'étrangle pas son métier
- [x] Tests : aucune matière première sans débouché hors consommables finis
      (`MaterialOutletContractTest` : toute ressource brute nourrit une
      recette, dettes déclarées avec leur raison, le champignon nommément)

---

## Piste B — Le vestiaire

### OBJ-03 — La grille d'équipement neutre (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02 (en deux moitiés, règle 8)
> t2 couvre air/terre/feu/eau, t3 couvre bête/ombre/lumière/métal : **aucun
> élément n'a de progression t2 → t3**. Compléter la grille demanderait
> 168 pièces — pour une variable qui n'est pas celle du build.
> Prérequis : ∅
- [x] **La pièce d'équipement ne porte plus d'élément** (GAME_ITEMS §3.2) —
      plus une seule, PHP comme YAML : les 56 élémentaires, les 2 cosmétiques,
      les 11 pièces uniques de boss et les 13 pièces YAML (pendentifs, anneaux,
      série dragon/shadowsilk) perdent le champ ; l'identité reste dans le nom
      et les effets
- [x] Les 56 pièces `t2_<élément>_*` / `t3_<élément>_*` fusionnées en **une
      pièce par forme et par palier** (14 pièces : sword/shield/helmet/chest/
      legs/boots/gloves × t2/t3), dans le prolongement de la grille d'armes
      neutre existante (t1-t3 axe/bow/dagger/lance/staff) ; 76 lignes de butin
      remappées, les 4 sets élémentaires t3 fusionnés en un **Set de l'Élite**
      neutre
- [x] **OBJ-03b** : le palier t1 était en réalité **déjà complet** — le
      constat du plan (« 5 pièces ») précédait le set de départ de
      l'onboarding : `starter_*` (6 pièces) + l'épée de bois couvrent les
      7 formes au niveau 1. Vérifié et verrouillé plutôt que re-créé
- [x] Tests : aucune pièce d'équipement avec un élément, le vestiaire
      élémentaire ne revient pas, la grille **3 paliers × 7 formes** sans
      trou (`GearNeutralityTest`)

### OBJ-04 — Les emplacements typés et progressifs (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02 (versant Spell + progression ; le versant Technique attend ARC)
> Le cœur du build. `materiaSlotType` existe, `MateriaGearSetter` le lit, et
> **9 pièces sur 178 le renseignent** — le défaut `Free` fait que 169 pièces
> acceptent tout, donc DOM-03 est inerte sur 95 % du vestiaire.
> Prérequis : ← OBJ-03 ; **à livrer avec ou après MAT-03**, jamais avant
- [x] **Progression des emplacements** : plancher 1 / 2 / 3 par bande de
      niveau (1-4 / 5-12 / 13+) sur **tout** le vestiaire, PHP et YAML
      (73 pièces relevées) — un plancher, jamais un écrêtage : les pièces
      uniques gardent leur avance. Migration idempotente
      (`Version20260802EMateriaSlotFloor`)
- [x] **Type dérivé de la famille — versant Spell** : les armes de lanceur
      au-dessus du palier d'entrée rejoignent le tissu (`t2-staff`,
      `t3-staff`, `guardian-thorn-staff` → `Spell`). **Le versant
      `Technique` (mêlée, tir, plaque, cuir mixte) était une dette sur ARC** :
      aucune matéria de technique n'existait, et le test interdisait — à
      raison — un emplacement que rien ne peut remplir. **Dette soldée par
      ARC-02b** (2026-08-03) : les gestes d'arme déclarent leur registre, la
      matéria en hérite, et les 12 armes de mêlée/tir de la grille neutre plus
      les 8 pièces de plaque au-dessus du palier d'entrée sont typées
      `Technique`. Le cuir reste `Free` : « 1 `Spell`, le reste `Technique` »
      est une règle **par emplacement**, et `materiaSlotType` est porté par la
      pièce (cf. ARC-02b)
- [x] Cohérence avec `domain_catalog.yaml` : le versant livré est exactement
      ce qu'il annonce (« Bâtons, baguettes et tissu »)
- [x] Tests : progression vérifiée sur 100 % du vestiaire, lanceurs typés,
      le plancher jour 1 et l'exception maintenus (`MateriaSlotTypingTest`)

---

## Piste C — Les outils

### OBJ-05 — L'outil de récolte (M | ★★ | MOYENNE) — ✅ LIVRÉ 2026-08-02
> `GatherService` n'exige aucun outil : pioche, faucille, canne, couteau et
> hache — **20 objets** — n'ont aucune fonction mécanique.
> Prérequis : ∅
- [x] La récolte exige un outil du bon type, comme le craft — refus **avant**
      la dépense d'énergie ; la possession suffit (équipé préféré, le sac
      travaille aussi), un outil cassé ne compte pas
- [x] **Le palier module le rendement, jamais l'accès au filon** — +0/+8/+18/
      +30 % (barème GAME_TRADES §7), appliqué en direct et **jamais via
      `gather_percent`**, qui décale aussi la bande de pureté
- [x] **La garantie anti-mur** : l'outil de palier 1 est **gratuit et livré à
      l'ouverture de l'arbre de récolte** (`grantGatherToolKit`, dérivé des
      nœuds `tool_slot.unlock` du graphe réel) ; migration de rattrapage pour
      les personnages grand-périsés par ONB-08
- [x] **Au passage** : la hache avait un type d'outil (DOM-05) mais aucun bit
      d'équipement — `GEAR_TOOL_AXE` existe, le bûcheronnage devient outillable
- [x] Tests : outil requis, rendement par palier, jamais de filon inaccessible
      (`GatherToolContractTest` : toute profession a un type, tout type a son
      palier 1 en fixtures et son emplacement)

### OBJ-06 — Les paliers d'outil et les 3 métiers manquants (M | ★★ | MOYENNE) — ✅ LIVRÉ 2026-08-02
> Sur 4 paliers déclarés, seuls bronze et fer sont atteignables : l'outillage
> s'arrête au fer.
> Prérequis : ← OBJ-05
- [x] Recettes de forgeron pour **acier et mithril** — les 12 types × 2 paliers,
      **dérivées** (`RecipeFixtures::toolRecipesData()`) plutôt qu'énumérées ;
      l'acier est un alliage (lingot de fer + cobalt, ECO-25), chaque outil
      consomme un manche de charpentier (ECO-14 vaut pour l'outillage)
- [x] Un outil requis pour **cuisinier, charpentier et tailleur** : la marmite,
      la varlope et l'aiguille (type + bit + emplacement différés par
      ECO-29/30/31), le nœud gratuit de chaque arbre livre l'emplacement et le
      palier d'entrée, bronze au plancher T1 (Marcellin), fer chez Émilie
- [x] Tests : les 4 paliers atteignables, les 7 métiers pourvus
      (`CraftToolContractTest` : bronze/fer vendus, acier/mithril au forgeron,
      chaque type câblé bit + emplacement + étiquette, dérivation vérifiée)

---

## Piste — Le contrat

### OBJ-08 — Tests du plan (S | ★★★ | HAUTE) — ✅ LIVRÉ 2026-08-02 — **plan objets complet 8/8**
> ‖ au fil des jalons. Les 9 invariants de GAME_ITEMS §6, tenus par
> `ItemsPlanContractTest` (l'index du contrat + les deux morceaux qui
> manquaient) et les tests nés des jalons.
- [x] Cinq types, pas douze (`ItemTaxonomyTest`) ; l'onglet Matériaux est
      complet (`ItemTaxonomyTest` + **tout ingrédient de recette est une
      ressource**, porté par l'index)
- [x] Aucune pièce d'équipement ne porte d'élément (`GearNeutralityTest`) ;
      les emplacements progressent ; 100 % du vestiaire est typé
      (`MateriaSlotTypingTest` — versant Spell, la dette Technique est
      déclarée sur ARC)
- [x] Aucun outil sans fonction (**tout type d'outil est exigé par une récolte
      ou un craft**, porté par l'index) ; aucun palier d'outil sans source
      (`CraftToolContractTest`, `GatherToolContractTest`,
      `DomainAccessManagerTest`)
- [x] Aucune recette infabricable (`RecipeCraftabilityTest`) ; aucun doublon
      de slug (`ItemCleanupTest`)
- [x] L'index ne pourrit pas : chaque test cité par le contrat existe
