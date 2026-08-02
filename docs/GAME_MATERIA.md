# La chaîne matéria — cadrage acté (2026-07-31)

> **Source de vérité** de la chaîne `compétence → matéria → sort`. Décline et
> **corrige** [GAME_WORLD.md](GAME_WORLD.md) §2.1, dont le constat d'état était
> faux (cf. §1.2). En cas d'écart avec §2.1 sur un chiffre d'état, ce document
> fait foi ; sur la **doctrine**, §2.1 reste la règle.
>
> Décliné en jalons dans [roadmap/PLAN_MATERIA.md](roadmap/PLAN_MATERIA.md).
> État des lieux chiffré : [GAME_DATA_AUDIT.md](GAME_DATA_AUDIT.md) §3.

---

## 1. Pourquoi ce document existe

### 1.1 Le constat

La règle 10 fait de la matéria la **seule source d'actions de combat**. Un
personnage sans matéria n'a que l'attaque de base de son arme. La chaîne a trois
maillons, et elle est cassée sur les deux derniers :

| Maillon | Livré |
|---|---|
| Nœuds d'arbre portant un `materia.unlock` | **265** (sur 358 nœuds de combat), **200 slugs distincts** |
| Matéria livrées comme objet | 68 |
| Matéria **obtenables** dans le monde | **10** |
| Arbres de combat ayant une matéria obtenable à 0 point | **4 sur 24** |

**20 arbres de combat sur 24 démarrent sans aucun sort**, et le restent. Le
Chevalier ouvre 10 nœuds matéria pour 0 matéria livrée.

### 1.2 La correction au canon

`GAME_WORLD.md` §2.1 écrit : « Le jeu livré respecte déjà cette règle : 69
matéria tombent des créatures à 4-10 % ». **C'est faux.** Le monde livre
**9 matéria distinctes** en butin, sur 31 lignes de loot (pour 400 au total).
Le 69 est le nombre de matéria *déclarées comme objets*, confondu avec le nombre
de matéria *lootables*.

La doctrine de §2.1 (« abondante à la base, rare au sommet ») n'est pas remise en
cause — elle est **actée et figée**. Ce qui est corrigé, c'est l'affirmation
qu'elle serait déjà respectée. Elle ne l'a jamais été.

### 1.3 Ce que le chantier n'est pas

Les **139 sorts** que les nœuds réclament sans matéria **existent déjà tous** :
écrits, nommés, décrits, avec élément, dégâts, `hit` et niveau. Répartis sur
5 niveaux et 8 éléments.

> Il ne manque pas 139 sorts à concevoir. Il manque **139 objets qui portent des
> sorts écrits**, et leur distribution dans le monde.

C'est un chantier de **dérivation et de placement**, pas de game design de sort.

---

## 2. Décision 1 — Le catalogue cible : 200 matéria

**Une matéria par `unlock` distinct.** Aucun nœud d'arbre ne promet plus rien
qui n'existe. Le catalogue passe de 68 à **200**.

Répartition mécanique (dérivée du niveau et de l'élément des sorts déjà écrits) :

| Palier (= niveau du sort) | 1 | 2 | 3 | 4 | 5 | total |
|---|---:|---:|---:|---:|---:|---:|
| matéria | 30 | 67 | 52 | 31 | 20 | **200** |

| Élément | Lumière | Bête | Ombre | Air | Terre | Métal | Feu | Eau |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| matéria | 29 | 28 | 27 | 25 | 24 | 24 | 23 | 20 |

L'équilibre élémentaire est déjà bon (20 à 29) : il découle des arbres, il n'est
pas à décider.

### 2.1 La grille de dérivation

Une matéria n'est **pas écrite à la main**. Elle se dérive du sort qu'elle porte :

| Champ de la matéria | Dérivé de |
|---|---|
| `spell` | le sort dont le slug est la valeur de `materia.unlock` |
| `element` | `spell.element` — jamais redéclaré |
| `name` / `description` | `spell.name` (« Matéria : *nom du sort* ») |
| `slug` | **`m<niveau du sort>-<slug du sort>`** (cf. §2.2) |
| `rarity` | **déjà automatique** — `ItemFixtures::inferRarity()` lit le préfixe `m1-`…`m5-` du slug : m1 → Uncommon, m2 → Rare, m3 → Epic, m4/m5 → Legendary |
| `price` | grille par palier (§2.3) |
| `energy_cost` | grille par palier (§2.3) |
| `domain` | l'arbre qui porte le nœud `unlock` ; si plusieurs arbres l'ouvrent, `null` (une matéria n'appartient pas à un arbre, elle est *ouverte* par lui) |
| `type` | `'materia'` |
| `space` | 1 |

Conséquence à tenir : **`rarity` ne se déclare jamais dans les fixtures de
matéria**. Elle est une fonction du slug, et le slug une fonction du sort. Une
matéria dont la rareté est écrite en dur est un bug.

### 2.2 La convention de slug, à redresser

La convention actuelle `m<palier>-<clé>` n'est pas tenue : le suffixe est tantôt
l'élément (`m1-fire`, `m1-earth`, `m1-wind`), tantôt le nom du sort
(`m1-flame`, `m2-combustion`). Résultat, `m1-fire` et `m1-flame` sont deux
matéria de feu de palier 1 dont les slugs ne disent pas ce qui les distingue.

**Convention actée : `m<niveau du sort>-<slug du sort>`.** `fire-ball` niveau 1
donne `m1-fire-ball`. Le slug devient déductible du sort, donc vérifiable par un
test, et la collision devient impossible.

Les 68 slugs existants sont renommés. Le jeu étant en pur dev, aucune
compatibilité n'est à préserver.

### 2.3 Grilles par palier

Reprises des médianes observées sur les 68 matéria livrées — la courbe existante
est cohérente, on la fige plutôt que d'en inventer une :

| Palier | m1 | m2 | m3 | m4 | m5 |
|---|---:|---:|---:|---:|---:|
| Rareté | Uncommon | Rare | Epic | Legendary | Legendary |
| Prix (gils) | 130 | 180 | 280 | 320 | 380 |

#### 2.3 bis La ressource dépend du registre — *acté le 2026-08-01*

*(Écart n° 7 de [GAME_TREE_ANATOMY.md](GAME_TREE_ANATOMY.md) §10.)* La grille
ci-dessus ne portait qu'un `energy_cost`, c'est-à-dire **la grille des sorts**.
Or [GAME_ARCHETYPES.md](GAME_ARCHETYPES.md) §2 a tranché que chaque registre a sa
ressource : dériver une matéria de technique par la grille des sorts reviendrait à
**facturer des PM à un guerrier**, ce qui efface la seule différence structurelle
entre les trois registres.

| Ce que la matéria coûte | m1 | m2 | m3 | m4 | m5 |
|---|---:|---:|---:|---:|---:|
| **Sorts** — PM (`energyCost`) | 10 | 15 | 20 | 25 | 30 |
| **Mêlée** — reprise en tours (`cooldown`) | **0** | **1** | **2** | **3** | **4** |
| **Distance** — munitions (`ammoCost`, *à créer*) | **1** | **2** | **3** | **4** | **5** |

> **La ligne mêlée ne s'invente pas, elle se lit.** `shadow-dance` est le seul
> geste livré à porter une reprise sans être un sort de zone : niveau 5,
> `cooldown: 4` — exactement ce que la grille prescrit. La courbe existait déjà,
> comme pour les prix.

**Trois règles, et la première est celle qui fait le travail :**

1. **Un geste ne facture que la ressource de son registre.** Les deux autres
   colonnes valent zéro. C'est ce qui rend le guerrier différent du mage et non
   « un mage qui tape », et c'est vérifiable en une requête.
2. **Aucune exception par intention.** Une `protection` de mêlée part en reprise
   comme un `dégât` ; une `protection` de distance consomme sa munition. Un geste
   qui ne coûterait rien parce qu'il ne vise personne serait le seul geste gratuit
   du jeu — et donc le seul qu'on jouerait sans réfléchir.
3. **Un `cooldown` additionnel reste permis hors mêlée à partir du palier 3**, et
   seulement là : c'est un **garde-fou anti-spam** sur les gestes de pointe, jamais
   une économie. Les données livrées le font déjà (`flame-rain`, `nightmare-pulse`,
   `void-eruption`). En mêlée, la reprise **est** la ressource : elle n'a pas de
   doublon.

> **Ce que la ligne « distance » suppose du carquois.** Avec des gestes à 1-5
> munitions, une capacité de référence de **~20** au palier T1 donne 7 à 10 gestes,
> soit une rencontre de 7 à 10 tours : le tout-venant (3-5 tours) et l'élite
> (6-10) passent, **le boss (12-20) ne passe pas** sans reconstituer son carquois.
> C'est exactement la « cadence décroissante » que GAME_ARCHETYPES §2 décrit, et
> c'est ce que le levier `wind` vient étendre. La capacité elle-même vit dans
> [GAME_ITEMS.md](GAME_ITEMS.md) — le carquois est une pièce durable, pas un
> consommable.

### 2.4 Ce qu'on retire

- **`nb_usages` sur les matéria.** Les 68 matéria portent une valeur de 2 à 25.
  Le champ n'est décrémenté nulle part dans le chemin de combat — il est donc
  inerte — mais une matéria consommable contredit frontalement « la matéria est
  le build du personnage » et « la boule de feu du jour 1 reste utile au mois 6 »
  (§2.1). Le champ est **supprimé** pour `type: materia`.
- **Les 7 matéria qu'aucun nœud n'ouvre** — `flamer`, `frost-maelstrom`,
  `orichalcum-blade`, `primal-awakening`, `shadow-covenant`, `solar-burst`,
  `thunder-storm`. Soit un nœud les ouvre, soit elles disparaissent : une matéria
  sans accord est inutilisable par construction. **Défaut : les raccrocher** au
  nœud terminal de l'arbre de leur élément, puisque leurs sorts existent.

---

## 3. Décision 2 — La règle du jour 1

> **Un joueur qui se spécialise dans le feu doit avoir ses premières matéria de
> feu au premier jour, pas à la première semaine.** (§2.1, figé.)

**La structure est déjà en place** : les 24 arbres de combat portent **exactement
2 nœuds `unlock` à 0 point** chacun. Rien à réécrire dans les arbres.

Ces **48 matéria** sont le **plancher du build**. État actuel :

| | n |
|---|---:|
| Obtenables aujourd'hui | **4** (`fire-ball`, `life-heal`, `stone-throw`, `wind-lame`) |
| Matéria existante, aucune source | 13 |
| Matéria à créer | 31 |

**Invariant acté :** *les 48 matéria ouvertes à 0 point sont obtenables sans
quitter la boucle d'entrée* — c'est-à-dire au Fanal ou dans une zone à une seule
liaison de lui, par un canal qui ne dépend pas du hasard (§4).

### 3.1 Le palier de distribution suit le nœud, pas le sort

15 des 48 matéria du jour 1 portent un sort de **niveau 2** (`rage-flame`,
`steel-shield`, `prayer`, `ambush`…). Leur rareté dérivée serait *Rare*, ce qui
les exclurait du plancher.

**Arbitrage : le palier de distribution est indexé sur le nœud qui ouvre la
matéria, jamais sur le niveau de son sort.** Une matéria ouverte à 0 point est au
plancher de distribution quelle que soit sa rareté. Cela évite de retoucher
15 sorts déjà équilibrés, et garde une seule règle : *ce qu'un arbre ouvre
gratuitement, le monde le donne tôt.*

---

## 4. Décision 3 — Les quatre canaux d'obtention

Le canon en nomme quatre (« butin de créature, coffre, récompense, marché »).
Un seul est instrumenté. Les quatre sont retenus, avec un rôle distinct :

| Canal | Rôle | Paliers |
|---|---|---|
| **Boutique PNJ** | Le **plancher garanti** — jamais de hasard sur le build d'entrée | les 48 du jour 1 |
| **Récompense de quête / acte I** | La **première** matéria de l'arbre choisi, sur le modèle de `materia_soin` | 1 par arbre |
| **Butin de créature** | La voie **normale et abondante**, le tout-venant | m1 à m3, et m4 en rare |
| **Coffre d'exploration & donjon** | Le **palier moyen et haut**, et le contenu propre des donjons | m3 à m5 |

### 4.1 Boutique PNJ — le plancher

Les 48 matéria du jour 1 sont achetables. Application directe du **plancher T1
PNJ** de `GAME_PRINCIPLES.md`, étendu au build : *le marché joueur peut faire
mieux, jamais moins.*

Placement : au Fanal pour les huit éléments au palier d'entrée, et chez le PNJ de
la zone dont la ligne porte l'élément pour le reste. Le prix est celui de la
grille (§2.3) — c'est aussi le **plafond naturel** du marché joueur sur ces
matéria.

### 4.2 Butin — et son prérequis bloquant

**Aucun des 65 monstres ne porte d'élément.** 44 déclarent des
`elementalResistances`, utilisées par `DamageCalculator`, mais le champ `element`
n'existe ni sur l'entité `Monster` ni dans les fixtures.

Sans lui, aucune règle de distribution ne peut être écrite : on ne peut pas dire
« un monstre de feu lâche des matéria de feu ». **C'est le prérequis nº1 du
plan.** Il débloque au passage la capacité raciale de l'Orc (`GAME_ONBOARDING`),
aujourd'hui inerte faute d'élément à lire.

Règle une fois l'élément posé : **un monstre lâche des matéria de son élément, à
un palier borné par son niveau.** La table de butin cesse d'être écrite à la
main — elle se dérive, comme la matéria elle-même.

### 4.3 Coffre et donjon

Les coffres d'exploration (`explore.weights.chest`) ne distribuent aujourd'hui
que des gils. Ils prennent les paliers m3-m4, indexés sur la zone.

Les donjons prennent m4-m5 : c'est leur **contenu propre**, et la première raison
mécanique d'y entrer. Le sujet est repris à part (revue des donjons).

---

## 5. Ce que la décision ne change pas

- **L'Autel d'éveil** (`PLAN_REPERTOIRE`, 0/6) reste ce que le canon en dit :
  tardif, gaté Métropole, **jamais nécessaire**. Il ne résout pas ce problème et
  n'est pas sur ce chemin critique. Il couronne le butin, il ne le remplace pas.
- **La fusion** (`MateriaFusionManager`, écrit mais non branché) reste un contenu
  d'extension.
- **Les arbres.** Aucun nœud n'est réécrit, aucun `unlock` n'est déplacé. La
  structure des 24 arbres est validée telle quelle.

---

## 6. Invariants testables

Ce qui doit casser la CI si on le viole :

1. **Aucun nœud ne ment** — tout `actions.materia.unlock` a une matéria dont le
   sort porte ce slug.
2. **Aucune matéria orpheline** — toute matéria est ouverte par au moins un nœud.
3. **Le jour 1 est tenu** — les 48 matéria ouvertes à 0 point ont une source à
   distance ≤ 1 liaison du Fanal, et au moins une source **non aléatoire**.
4. **Toute matéria est obtenable** — au moins un canal du §4 la distribue.
5. **La dérivation tient** — `slug == "m{spell.level}-{spell.slug}"`,
   `element == spell.element`, `rarity == inferRarity(slug)`.
6. **Aucune matéria n'est consommable** — pas de `nb_usages` sur `type: materia`.
7. **Le catalogue est complet** — 200 matéria, une par `unlock` distinct.
8. **Chaque geste facture la ressource de son registre, et elle seule** (§2.3 bis)
   — un geste de mêlée à `energyCost > 0`, un sort à `ammoCost > 0`, ou un geste
   de mêlée dont la reprise ne suit pas la grille de son palier échouent le test.
   Seule exception admise : un `cooldown` additionnel hors mêlée, à partir du
   palier 3.
