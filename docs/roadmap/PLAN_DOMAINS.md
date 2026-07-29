# Plan — Arbres de domaine & équipement-build

> **Numérotation :** jalons préfixés **DOM-** (Domains). Pas de conflit avec les autres
> préfixes.

> Décline [../GAME_DOMAINS.md](../GAME_DOMAINS.md) (acté le 2026-07-28) : doctrine des
> trois couches, double borne élément × registre, équipement-build, gabarits, quatre
> arbres neufs. Le moteur est livré (`SkillAcquiring`, `SkillRespecManager`,
> `BuildPresetManager`, `CombatSkillResolver`) — ce plan **type des données et branche
> des bornes**, il ne réécrit pas de moteur.

## Vue d'ensemble

**8 jalons** (**DOM-01** à **DOM-08**) en 3 pistes.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| DOM-01 ✅ | Passifs typés : élément × registre (refactor du format) | M | ∅ |
| DOM-02 ✅ | Activation par build (domaines actifs = sources portées) | M | ← DOM-01 |
| DOM-03 | Emplacements typés sur l'équipement (sort/technique/libre) | M | ∅ |
| DOM-04 | Spécialisation par arbre d'artisanat (migration) | S | ∅ |
| DOM-05 | Arbre du bûcheron | S | ← ZON-34 (le domaine) |
| DOM-06 | Arbres cuisinier, charpentier, tailleur | M → 1/arbre | ← ECO-29/30/31 (les domaines) |
| DOM-07 | Nœuds d'accord d'hybride dormants | S | ← DOM-01 |
| DOM-08 | Tests du plan | S | ‖ |

```
Piste A — Le système   : DOM-01 → DOM-02 ; DOM-03 ‖ ; DOM-04 ‖
Piste B — Les arbres   : DOM-05, DOM-06 (avec leurs jalons ZON/ECO)
Piste C — L'avenir     : DOM-07, DOM-08
```

**Quand.** Piste A est indépendante de tout chantier en cours et **précède** la Piste H
d'ECO (les arbres neufs se posent sur le format typé, pas sur l'ancien). DOM-05/06 se
livrent **avec** leurs jalons de domaine (ZON-34, ECO-29→31), pas avant.

---

### DOM-01 — Passifs typés : élément × registre ✅ (M | ★★★ | CRITIQUE)
> GAME_DOMAINS §2. Le refactor central : `damage`/`critical`/… plats deviennent des
> passifs bornés. « Critique +1 % » du pyromancien = sorts de feu uniquement.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
- [x] `CombatRegister` (sorts / mêlée / distance) + `Domain::register` : le domaine
      **est** la case élément × registre, et c'est lui qui porte la borne — les 130
      passifs livrés se typent d'un coup, sans une seule décision par nœud
- [x] `CombatScope` (élément × registre de l'action) et
      `CombatSkillResolver::getCombatBonuses($player, ?$scope)` — filtre effectif ;
      `FightSpellController` passe la portée du sort
- [x] Rétro-compat par une clause explicite : un nœud **sans domaine de combat**
      (récolte, artisanat, ou sans domaine) reste global. C'est elle qui permet de typer
      36 domaines sans relire 524 nœuds
- [x] Tests : 10 (`CombatSkillResolverScopeTest`) + 4 (`DomainRegisterTest`)

> **`life` échappe toujours à la borne, et c'est une décision.** Les points de vie maximum
> ne sont pas un geste : les borner ferait varier la barre de vie d'un tour à l'autre selon
> le sort choisi. Les quatre autres statistiques qualifient une action et se bornent avec
> elle.
>
> **Sans portée, rien n'est borné.** La fiche d'inventaire affiche un total, pas une action —
> `getCombatBonuses($player)` garde donc exactement le comportement d'avant. Montrer au
> joueur *ce qui s'applique vraiment* est le sujet de DOM-02 (l'écran de build).
>
> **Le registre de l'attaque de base n'a pas encore de consommateur, et ce n'est pas un
> oubli.** `FightAttackController::calculateDamage` vaut `3 + variance + enchantement` : il
> n'a **jamais** lu les passifs d'arbre. Leur y brancher une borne mêlée/distance aurait
> demandé d'inventer d'abord le registre de l'arme — ce que DOM-02 et DOM-03 posent. Les
> deux registres existent dans le modèle et sont testés ; leur porte d'entrée arrive avec
> celui à qui elle sert.
>
> **La grille 8 × 3 n'est pas pleine, et on ne l'a pas remplie de force.** Trois éléments
> (feu, air, bête) occupent leurs trois cases ; l'eau a trois domaines de sorts, le métal
> deux de mêlée. Étiqueter le « Guérisseur » en mêlée pour faire tenir la grille aurait
> menti sur ce qu'est le domaine. Le gain mécanique tient sans elle : un passif feu × mêlée
> ne sert plus un sort d'eau. Le remplissage est un sujet de contenu, pas de moteur.

### DOM-02 — Activation par build ✅ (M | ★★★ | CRITIQUE)
> GAME_DOMAINS §3. Un domaine n'est actif en combat que si le build porte une de ses
> sources. La borne est matérielle, jamais réglementaire.
> **Livré le 2026-07-29.** Détail dans [../ROADMAP_DONE.md](../ROADMAP_DONE.md).
- [x] `BuildDomainResolver` : **une source par famille de registre** — une matéria de son
      élément pour une école de sort, une arme de son registre pour une école d'arme.
      Le registre de l'arme est celui de **son** domaine, déjà déclaré dans les fixtures
      (l'épée est au soldat, l'arc à l'archer)
- [x] Les deux bornes se cumulent dans `CombatSkillResolver` : le domaine doit convenir à
      l'action **et** être porté
- [x] Les **accords** ne passent jamais par là — `getUnlockedMateriaSpellSlugs` est
      inchangé, et un test l'exige nommément
- [x] UI : l'écran des arbres marque « Exprimé / Non exprimé » et **dit quoi porter**
- [x] Tests : 6 (`BuildDomainResolverTest`) + 5 (`CombatSkillResolverBuildTest`)

> **Deux sources, une par famille, et ce n'est pas une commodité.** Une école de sort
> s'exprime par la matière qu'on sertit, une école d'arme par l'arme qu'on tient. Exiger les
> deux de chacune aurait rendu le pyromancien dépendant d'un bâton qui n'existe pas dans son
> arbre, et le soldat d'une matéria de métal qui n'a rien à voir avec son épée.
>
> **L'épée de bois du débutant n'ouvre aucune école, et n'en ferme aucune.** Elle n'a pas de
> domaine : elle n'appartient à rien. Personne n'y perd — l'attaque de base ne lit pas les
> passifs (DOM-01), et la première matéria se sertit toujours (plancher jour 1).
>
> **Le changement de build hors combat était déjà tenu** : la borne se calcule à chaque
> action à partir de l'équipement porté, et l'équipement ne se change pas en combat. Ajouter
> un verrou dans `BuildPresetManager` aurait dupliqué une garantie que le modèle donne
> gratuitement.
>
> **La fiche d'inventaire n'est pas bornée non plus.** Elle montre ce qu'un joueur a
> *appris*, pas ce que sa tenue du moment exprime : l'y borner ferait baisser ses chiffres
> en rangeant une arme, sans que rien ne l'explique. C'est l'écran des arbres qui porte la
> distinction, et il la porte en toutes lettres.

### DOM-03 — Emplacements typés sur l'équipement (M | ★★★ | HAUTE)
> GAME_DOMAINS §3. La robe porte des emplacements de sort, la plaque des emplacements
> de technique. Donnée, pas moteur.
> Prérequis : ∅ (‖ DOM-01)
- [ ] Type d'emplacement par pièce (`sort` / `technique` / `libre`) en fixtures
- [ ] Sertissage contrôlé par le type ; **garde-fou testé : les kits T1 portent au moins
      un emplacement libre** (le plancher jour 1)
- [ ] Jamais d'interdit de port : aucune pièce ne gate par « classe » — seuls les
      prérequis de compétence existants s'appliquent
- [ ] Passe sur l'existant : typage des 121 pièces livrées (robes absentes — la ligne
      tissu arrive avec ECO-31, qui pose ses emplacements de sort dès la création)
- [ ] Tests : sertissage refusé/accepté par type, plancher T1, aucun gate de port

### DOM-04 — Spécialisation par arbre (S | ★★ | HAUTE)
> GAME_DOMAINS §6. `Player.craftSpecialization` (singulier) → une spécialisation par
> arbre d'artisanat ; `Recipe.requiredSpecialization` la consomme déjà.
> Prérequis : ∅
- [ ] Migration : spécialisation portée par (player, domaine d'artisanat), rétro-compat
      de la colonne existante
- [ ] Exclusivité au sein de l'arbre ; **respec de spécialisation coûteux** (le seul
      respec payant — paramètre), le respec de points ordinaire reste doux
- [ ] Tests : une branche par arbre, coût du changement, recettes gatées

### DOM-05 — Arbre du bûcheron (S | ★★ | HAUTE)
> Gabarit récolte (GAME_DOMAINS §5.2). Se livre **avec** ZON-34.
- [ ] ~15 nœuds : rendement/fatigue, repérage des essences exclusives, qualité, outils
      (hache), gates T3/T4 (bois tourbé, pétrifié)
- [ ] Tests : conformité au gabarit (proportions, entrées à 0 pt)

### DOM-06 — Arbres cuisinier, charpentier, tailleur (M — 1 sous-jalon/arbre | ★★★ | HAUTE)
> Gabarit artisanat (§5.3) + spécialisations terminales (§7). Se livrent **avec**
> ECO-29/30/31.
- [ ] 06a Cuisinier : qualité/durée des effets, lots de voyage ; spé « table de fête /
      vivres de route »
- [ ] 06b Charpentier : qualité des canaux de sort, flèches en lot ; spé « armes de
      trait / mobilier »
- [ ] 06c Tailleur : emplacements de sort de qualité, doublures ; spé « robes de sort /
      tenues de travail »
- [ ] Tests : conformité au gabarit, spécialisations exclusives

### DOM-07 — Nœuds d'hybride dormants (S | ★ | BASSE)
> GAME_DOMAINS §8. Une ligne de données par arbre de combat, inactive au lancement.
> Prérequis : ← DOM-01 (l'enum `Element` doit tolérer les composés)
- [ ] Nœud « accord d'hybride » par arbre de combat, flag `dormant` (invisible ou grisé)
- [ ] Tests : inactivable, aucun effet tant que la fusion n'est pas ouverte

### DOM-08 — Tests du plan (S | ★★ | HAUTE)
> ‖ au fil des jalons.
- [ ] Invariants : aucun sort actif par compétence (garde existante étendue), aucun
      passif non borné après migration, le savoir jamais borné (acquérir un nœud n'est
      jamais bloqué par le build), plancher de sertissage T1
- [ ] Conformité des arbres neufs au gabarit (proportions vérifiées par test de données)

---

## Risques

| Risque | Parade |
|---|---|
| Le refactor DOM-01 casse l'équilibre livré | Rétro-compat « global » + migration par domaine, testée domaine par domaine |
| L'activation par build frustre (passifs « perdus ») | L'UI de build montre les domaines actifs ; les accords ne sont jamais perdus |
| Le typage des emplacements bloque un débutant | Garde-fou testé : emplacement libre sur tous les kits T1 |
| 36 arbres à mettre en conformité d'un coup | Non : gabarits opposables, mise en conformité progressive, domaines fréquentés d'abord |
