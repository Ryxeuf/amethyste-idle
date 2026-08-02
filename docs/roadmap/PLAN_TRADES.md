# Plan — Les arbres de métier (récolte et artisanat)

> **Numérotation :** jalons préfixés **MET-** (Métiers). Pas de conflit avec les
> autres préfixes.

> Décline [../GAME_TRADES.md](../GAME_TRADES.md) (proposition instruite du
> 2026-08-01), second volet du chantier des arbres annoncé par
> [GAME_ARCHETYPES §14](../GAME_ARCHETYPES.md).
>
> **Ce plan ne réécrit pas la doctrine** — GAME_DOMAINS reste la loi (trois
> couches, le savoir jamais borné, le renoncement dans l'arbre). Il donne aux
> douze arbres de métier ce qui leur manque pour être des arbres : des leviers,
> une fourche, et un budget borné par le **marché** plutôt que par le combat.

## Ce que le constat a mesuré

Les 12 arbres comptent **190 nœuds**, dont **175 sont des portes**. Un seul
levier existe (`gather_percent`, +100 %), présent sur 10 nœuds, **tous en
récolte** — l'artisanat n'en a aucun. Et ce levier fait deux choses à la fois :
`PurityDrawer` lit le bonus de **rendement** pour décaler la bande de pureté,
donc quantité et qualité sont le **même curseur** et il n'y a rien à arbitrer.

Trois conséquences qui structurent le plan :

1. **La bande de pureté n'a aucune valeur marchande** — aucun multiplicateur de
   prix nulle part. Toute branche « qualité » est donc **strictement dominée**.
   C'est le blocage racine (**MET-01**).
2. **`purity` doit se séparer de `yield`** avant qu'un arbitrage existe
   (**MET-02**).
3. **6 recettes sur 398** portent un `required_specialization` : le renoncement
   de DOM-04/06 n'a presque aucun contenu (**MET-07**).

## Vue d'ensemble

**11 jalons** (**MET-01** à **MET-11**) en 3 pistes.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| MET-01 | **La bande de pureté porte un prix** (×1 / ×1,8 / ×3,5 / ×9) | M | ∅ — **bloquant** |
| MET-02 | `yield` et `purity` deviennent deux leviers distincts | S | ∅ |
| MET-03 | Le vocabulaire fermé des 9 leviers de métier, avec plafonds | **L** | ← MET-02 |
| MET-04 | La porte de palier : 175 portes deviennent ~72 | M | ← OBJ, ZON |
| MET-05 | L'outil fusionne avec la porte de palier (45 nœuds absorbés) | S | ← MET-04, OBJ-05 |
| MET-06 | **La fourche de récolte** — Extraire / Préserver, et ses 10 accords | M | ← MET-01, MET-03 |
| MET-07 | Les branches d'artisanat déplacent la qualité au lieu de fermer l'accès | M | ← MET-03 |
| MET-08 | Les 12 arbres réécrits au gabarit (~15 nœuds, 50 pb) | **L** | ← MET-03→07 |
| MET-09 | Le simulateur étendu aux métiers (`app:balance:simulate --trades`) | M | ← ARC-17, MET-08 |
| MET-10 | Contrat de test transverse (les 12 invariants) | S | ‖ |
| MET-11 | **L'outil porte un chiffre**, et la derniere porte est un monopole | S | ← MET-04, OBJ-05 |

**Ordre de chantier recommandé :** MET-01 → MET-02 → MET-03 → (MET-04, MET-05,
MET-11) → MET-06 → MET-07 → MET-08 → MET-09 → MET-10.

MET-01 et MET-02 sont indépendants et petits ; les livrer ensemble est
raisonnable. **Rien d'autre ne vaut la peine avant eux** : sans prix de bande et
sans séparation des curseurs, chaque jalon suivant équilibrerait un choix qui
n'existe pas.

---

## Piste A — Débloquer l'arbitrage

### MET-01 — La bande de pureté porte un prix — **bloquant** (M)

**Le constat.** `PurityChain` propage la bande, `CraftOrderManager` l'exige sur
les commandes, `PurityDrawer` la tire : la bande a une valeur **d'usage** et
aucune valeur **d'échange**. Un joueur qui investit dans la qualité produit
moins d'objets pour le même argent.

**Le livrable.**

- Un multiplicateur de prix par bande, déclaré dans `purity.yaml` (jamais en
  dur) : trouble **×1**, clair **×1,8**, pur **×3,5**, parfait **×9**.
- Application partout où un prix se calcule : rachat PNJ, prix de référence de
  l'hôtel des ventes, estimation d'inventaire, valeur d'une commande de craft.
- L'affichage dit le prix **de la bande**, pas le prix générique.

**Test.** Deux lots de la même matière en trouble et en parfait ont un rapport
de prix de 9, à tous les endroits où un prix s'affiche. Le rapport est identique
pour toutes les matières du périmètre.

### MET-02 — `yield` et `purity` deviennent deux leviers (S)

**Le constat.** `PurityDrawer::draw()` appelle
`weightsFor(..., $this->yieldResolver->getBonusPercent($player, CATEGORY_GATHER), ...)`.
Le bonus de **rendement** décale la bande. Investir dans la quantité offre la
qualité.

**Le livrable.** Une catégorie `CATEGORY_PURITY` dans `ActionYieldResolver` ;
`PurityDrawer` la lit à la place de `CATEGORY_GATHER`. Le plafond de décalage
(`skill_weight_cap: 25`) reste, la source change.

**Test.** Un joueur avec +12 % de rendement et 0 en pureté tire exactement les
poids de base. Un joueur avec 0 en rendement et 15 points de pureté tire
45/30/24/1.

---

## Piste B — Écrire les arbres

### MET-03 — Le vocabulaire fermé des 9 leviers (L)

Cinq en récolte (`yield`, `purity`, `stride`, `sight`, `care`), quatre en
artisanat (`finesse`, `tempo`, `thrift`, `batch`), avec leur taux de change et
leur **plafond par arbre**, calés sur le **risque économique** et non sur la
puissance (GAME_TRADES §2).

**Ce qui est nouveau côté code** : `stride` (coût en énergie du geste), `care`
(vitalité consommée), `sight` (repérage et lecture de vitalité), `thrift`
(intrant non consommé), `batch` (taille de lot). `finesse` et `tempo` se
branchent sur `QualityCalculator` et `craftingTime`, qui existent.

**Garde-fou testable.** La somme des leviers de la famille « quantité »
(`yield`, `stride`, `tempo`, `thrift`) ne dépasse **jamais 24 pb** dans un
arbre — moins de la moitié du budget. C'est la règle §1.3 rendue vérifiable :
*la progression déplace la composition, elle n'augmente pas la quantité.*

### MET-04 — La porte de palier (M)

Aujourd'hui : 73 nœuds `craft` pour 398 recettes, **56 nœuds `harvest` pour
55 filons**. Un nœud par ressource, c'est un catalogue.

**Le livrable.** Une porte ouvre **un palier du métier** — « le travail du
fer » ouvre tout ce qui est en fer. Six portes par arbre couvrent T0→T4 plus
l'exclusivité de zone. **175 portes deviennent ~72.**

Dépend de la carte des paliers : [GAME_ZONES](../GAME_ZONES.md) §2 pour les
zones, [GAME_ITEMS](../GAME_ITEMS.md) pour les objets.

### MET-05 — L'outil fusionne avec la porte (S)

45 nœuds (24 % de l'arbre) ouvrent des outils que **personne n'arbitre** — on
prend toujours le supérieur. Ils disparaissent dans les portes de palier :
*ouvrir le palier du fer, c'est pouvoir forger la pioche de fer*. Le palier 1
reste gratuit à l'ouverture de l'arbre (OBJ, modèle `rung1.free`).

### MET-06 — La fourche de récolte (M)

**Extraire / Préserver** sur les 5 arbres de récolte. C'est l'axe politique du
jeu ([GAME_WORLD](../GAME_WORLD.md) §6) ramené à l'échelle d'une personne et
d'une journée.

- **Extraire** — `yield`, `stride`, accord **la Percée** : le filon rend jusqu'à
  **1,5× son débit du jour**, pris sur celui de demain, et la pâleur monte
  d'autant. Écrit 32 pb.

  **Attention, c'est la correction qui sauve la fourche.** Un accord qui donnerait
  du *rendement* ne donne rien : le débit du filon est déjà la contrainte
  (GAME_TRADES §7.3). Mesuré sans la Percée, Extraire perd **13 % à portes
  égales et 17 % toutes portes ouvertes** — l'invariant 10 tombe aux deux
  échelles. Avec +50 % de débit : −0 % et +6 %.
- **Préserver** — `purity`, `care`, accord **le Repos** (un filon au-dessus de
  0,66 rend une bande de plus). Écrit 32 pb.
- **`sight` reste dans le tronc** : l'information est la famille la plus sûre et
  les deux branches doivent en disposer.

**Les dents existent déjà** : `vitality_ceilings` (sous 0,66 plus de parfait),
`paleness.rise_per_pressure` 0,08 contre `daily_recovery` 0,04,
`dulls_purity_from`, et `VeinRestoration` qui donne un prix à la trace laissée.

**Le seuil à tenir** (GAME_TRADES §6) : les deux branches gagnent **autant** —
moins de 10 % d'écart de chiffre d'affaires journalier — en vendant **autre
chose** — plus de 2× d'écart sur la part de bande haute. **À vérifier aux deux
échelles** (portes T0-T1 et toutes portes), sinon l'écart passe inaperçu.

**Ce que ça renvoie à FOY.** Le coût de restauration d'un filon doit se comparer
à ce qu'une journée d'Extraire rapporte. Trop bas, personne n'arbitre ; trop
haut, le foyer laisse pâlir et la fourche meurt d'un côté.

### MET-07 — Les branches d'artisanat déplacent au lieu de fermer (M)

**Le constat.** 7 métiers × 2 branches écrits avec soin dans
`craft_branches.yaml`, respec à 2 500 gils — et **6 recettes sur 398** portent un
`required_specialization`.

**Ce qu'on ne fait pas.** Ajouter des recettes fermées en masse. Une recette
fermée par branche **disparaît du serveur** si personne ne prend la branche : en
combat un renoncement ne coûte qu'à celui qui renonce, en métier il coûte à tous
les acheteurs.

**Le livrable.** La branche déplace la qualité : `finesse` **+8 pt** sur sa
moitié et **−4 pt** sur l'autre, `tempo` **−8 %** / **+4 %**. Les **6 pièces de
signature** existantes restent fermées — c'est exactement ce qu'une branche
exclusive doit produire : peu nombreuses, identifiables, jamais sur le chemin
critique d'un débutant.

Un forgeron d'armures fabrique donc encore des épées : moins bien, plus
lentement, et jamais la pièce de signature.

### MET-08 — Les 12 arbres réécrits au gabarit (L)

~15 nœuds : 1 ouverture gratuite (outil de palier 1 + première porte), 6 portes,
3 leviers portés (1 tronc à 18 pb, 2 branche à 22-25 pb), 1 fourche, 1 capstone
conditionnel. **Tronc + une branche = exactement 50 pb ; l'arbre en écrit ~82.**

Le capstone ne peut prendre que trois formes, aucune n'augmentant une quantité :
**le second regard** (meilleure de deux bandes sur un filon reposé), **la main
sûre** (un intrant pur ou mieux interdit la descente d'un palier de qualité),
**le relevé** (vitalité et pâleur de tous les filons connus, lues à l'entrée
dans la zone).

À découper par arbre (règle 8 du projet) : 12 sous-phases commitables, jamais
plus de deux arbres par passe.

---

## Piste C — Tenir

### MET-09 — Le simulateur étendu aux métiers (M)

`app:balance:simulate` (ARC-17) apprend les métiers. Mêmes scénarios, autre
unité de sortie : **le gil par jour** et **la composition de la production**, pas
le tour de combat.

Trois mesures à sortir : l'écart de chiffre d'affaires entre les deux branches
d'un même métier, l'écart de composition entre elles, et — premier scénario à
écrire — **l'écart entre métiers** (un mineur et un pêcheur gagnent-ils autant
par jour ? le document le suppose, l'ancre commune étant le point d'énergie,
mais ne le vérifie pas).

### MET-11 — L'outil porte un chiffre, et la dernière porte est un monopole (S)

**Deux manques mesurés le 2026-08-01** (GAME_TRADES §7.5 et §7.6), petits en
code et structurants en jeu.

**L'outil.** GAME_ITEMS promet que *« le palier module le rendement, jamais
l'accès »* sans jamais dire de combien. Les quatre paliers existent et coûtent
déjà 50 / 150 / 400 / 1 000 gils. Le chiffre : **+0 / +8 / +18 / +30 %**. Mesuré
à **×1,11** sur la journée d'un expert — peu, parce que c'est de la quantité,
donc soumis à la borne de débit. Sa vraie fonction est d'être un **gouffre à
gils** qui donne au palier 4 une raison d'exister.

**La dernière porte.** `requires_skill` existe déjà sur **11 filons sur 56**
(`miner-mithril-xs`, `miner-orichalcum-xs`, `fisher-kraken`…) et c'est le levier
le plus puissant du jeu : l'expert n'est pas le meilleur fournisseur de
sombracier, il est **le seul**. Il doit être la récompense de **fin d'arbre**,
jamais un palier intermédiaire.

**Plus le capstone qui manque** : *le filon signé* — une fois par jour, travailler
un filon comme s'il était à pleine vitalité. Sur un T4 pressé, le plafond passe
de `clair` à `parfait` : la seule façon régulière d'obtenir du parfait, donc
d'éveiller une matéria. Borné en fréquence, énorme en valeur, **zéro unité de
plus**.

### MET-10 — Le contrat de test transverse (S)

Les **12 invariants** de GAME_TRADES §5, en CI. Les trois qui comptent le plus :

- **Invariant 5** — la famille « quantité » sous 24 pb par arbre. C'est le seul
  garde-fou contre l'inflation.
- **Invariant 10** — deux branches gagnent autant en vendant autre chose. Si
  celui-là tombe, la fourche n'est qu'un choix cosmétique.
- **Invariant 11** — l'invariant 10 se vérifie **à deux échelles** (portes T0-T1
  et toutes portes). C'est ce qui a fait apparaître que la fourche ne tenait pas
  au sommet : une mesure à une seule échelle ne l'aurait pas vu.

---

## Ce que ce plan ne couvre pas

- **Les valeurs.** Illustratives (GAME_TRADES §0.2). La recalibration passe par
  MET-09.
- **La liste des 6 portes par arbre** — se dérive de GAME_ZONES §2 et de
  GAME_ITEMS, donc après eux.
- **Le prix de restauration d'un filon** — GAME_TRADES §6.4 pose la contrainte,
  FOY tranche.
- **Les 22 accords restants** (le plan en écrit 2 comme patrons).
- **La formule exacte de composition de `finesse` avec `QualityCalculator`** —
  décision d'implémentation ; la règle est que `finesse` **s'ajoute** à la
  pratique, il ne la remplace pas.
