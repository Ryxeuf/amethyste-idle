# Objets et ressources — cadrage acté (2026-07-31)

> **Source de vérité** de la taxonomie des objets, de la grille d'équipement, de
> la ligne d'outils et de la chaîne de matières. Constat chiffré :
> [GAME_DATA_AUDIT.md](GAME_DATA_AUDIT.md) §4-5.
>
> Adossé à [GAME_DOMAINS.md](GAME_DOMAINS.md) (« l'équipement est le build »),
> [GAME_WORLD.md](GAME_WORLD.md) §2.1 (« on ne progresse pas en changeant de
> sort, on progresse en le portant mieux ») et [GAME_ZONES.md](GAME_ZONES.md) §3
> (carte des minerais). La matéria est cadrée à part :
> [GAME_MATERIA.md](GAME_MATERIA.md).
>
> Décliné en jalons dans [roadmap/PLAN_ITEMS.md](roadmap/PLAN_ITEMS.md).

---

## 1. Constat

**419 objets** livrés (→ ~550 après le passage du catalogue matéria à 200).

Ce qui va bien, et qu'il ne faut pas casser : **107 des 115 recettes sont
fabricables**, la chaîne de production a une verticale (ECO Piste G), les outils
de craft en bronze et fer sont achetables — **rien n'est bloqué côté artisanat**.

Les cinq écarts traités ici :

| # | Écart | Portée |
|---|---|---|
| 1 | `Item::type` : 5 constantes dans le code, **12 valeurs en données** — l'onglet Matériaux n'affiche que 34 matières sur 91 | **bug visible** |
| 2 | La grille d'équipement élémentaire **ne boucle pas** : t2 couvre 4 éléments, t3 les 4 autres | fort |
| 3 | `materiaSlotType` renseigné sur **9 pièces sur 178** ; les emplacements **ne progressent pas** (t1=1, t2=1, t3=1-2) | fort |
| 4 | 5 outils sur 9 sans fonction ; acier et mithril sans source | moyen |
| 5 | `mushroom` (16 tables de butin) sans débouché ; doublons legacy ; 8 recettes d'extension livrées dans la base | moyen |

---

## 2. Décision 1 — La taxonomie s'aligne sur le code

Le code porte **5 constantes** (`Item::TYPE_STUFF`, `TYPE_GEAR_PIECE`,
`TYPE_MATERIA`, `TYPE_RESOURCE`, `TYPE_TOOL`) et les prédicats qui vont avec
(`isStuff()`, `isMateria()`, `isGearPiece()`, `isResource()`, `isTool()`). Les
données en utilisent 12, dont 8 ne correspondent à aucune constante.

**Conséquence concrète, pas théorique :** `MaterialsController` et
`InventoryPayloadBuilder` filtrent sur `isResource()`. Les **24 plantes**
(`plant`) et les **28 intermédiaires de craft** (`crafted` — lingots, planches,
tissus, gemmes taillées) n'étant pas typées `resource`, l'onglet **Matériaux de
l'inventaire n'affiche que 34 matières sur 91**.

**Les données s'alignent sur les 5 constantes** — jamais l'inverse :

| type actuel | n | devient |
|---|---:|---|
| `gear` | 178 | — |
| `materia` | 68 | — |
| `tool` | 36 | — |
| `resource` | 34 | — |
| `stuff` | 31 | — |
| `crafted` | 28 | **`resource`** |
| `plant` | 24 | **`resource`** |
| `quest` | 6 | **`stuff`** |
| `weapon` | 4 | **`gear`** |
| `ore` | 3 | **`resource`** |
| `food` | 3 | **`stuff`** |
| `potion` | 2 | **`stuff`** |
| `herb` | 2 | **`resource`** |

Après alignement : `gear` 182, `resource` **91**, `materia` 68, `stuff` 42,
`tool` 36.

**La famille fine ne se perd pas** : elle est déjà portée par le **préfixe de
slug** (`ore-`, `plant-`, `wood-`, `leather-`, `fish-`, `crafted-`), qui sert
déjà de clé à `affinities.yaml` (ZON-36) et à `purity.yaml`. On ne duplique pas
une information qui existe — et un champ qu'on n'ajoute pas ne peut pas diverger.

**Les objets de quête** perdent leur type propre et se distinguent par
`BindType` : c'est la liaison qui doit les rendre non échangeables, pas le type.

---

## 3. Décision 2 — L'équipement redevient neutre, le build passe aux emplacements

### 3.1 Le défaut

| tier | éléments couverts | pièces |
|---|---|---:|
| t1 | aucun (hors grille) | 5 |
| t2 | air, terre, feu, eau | 28 |
| t3 | bête, ombre, lumière, métal | 28 |

**Aucun élément n'a de progression t2 → t3.** Un pyromancien plafonne au t2 ; un
paladin n'a pas de t2. Compléter la grille demanderait 8 × 3 × 7 = **168 pièces**
— un vestiaire ingérable, pour une variable qui n'est pas celle du build.

### 3.2 La décision

> **La pièce d'équipement ne porte plus d'élément. Ce qui distingue une pièce,
> ce sont ses emplacements de matéria : leur nombre et ce qu'ils acceptent.**

C'est la lettre de GAME_DOMAINS (« l'équipement est le build ») et de
GAME_WORLD §2.1 (« la rareté porte sur les emplacements de sertissage… on ne
progresse pas en changeant de sort, on progresse en le portant mieux »). La
grille passe de 168 pièces attendues à **~21 par palier**.

### 3.3 Les emplacements progressent — ce que le canon promettait

GAME_WORLD §2.1 promet que « l'équipement de haut niveau offre **plus
d'emplacements** ». Le jeu livré ne le tient pas : t1 = 1, t2 = 1, t3 = 1 à 2.
Passer de t1 à t2 ne donne rien.

| palier | emplacements par pièce |
|---|---|
| t1 | 1 |
| t2 | 2 |
| t3 | 3 |

### 3.4 Ce qu'un emplacement accepte — la vraie décision de build

`materiaSlotType` (`Spell` / `Technique` / `Free`) existe, `MateriaGearSetter`
le lit, et **9 pièces sur 178 le renseignent**. Le défaut de l'entité étant
`MateriaSlotType::Free`, **169 pièces acceptent tout** : le levier de DOM-03
(« la pièce décide de ce que ses emplacements acceptent ») est inerte sur 95 %
du vestiaire.

La règle actée — dérivée de la **famille d'équipement**, jamais posée pièce par
pièce, et déjà annoncée aux joueurs par `domain_catalog.yaml` (« Bâtons,
baguettes et tissu » / « Haches, épées lourdes et cuir ») :

| Famille | Emplacements |
|---|---|
| Armes de lanceur (bâton, baguette, grimoire, trident) | `Spell` |
| Armes de mêlée et de tir (épée, hache, dague, lance, arc, arbalète) | `Technique` |
| Armure de tissu | `Spell` |
| Armure de plaque | `Technique` |
| Armure de cuir | 1 `Spell`, le reste `Technique` |
| Accessoires (anneau, amulette, ceinture) | `Free` |

> **Le renoncement se joue là.** Une robe de tissu ne sertit pas de technique,
> une plaque ne sertit pas de sort, et le cuir paie sa polyvalence par un
> emplacement de moins de chaque côté. C'est ce qui rend un choix d'armure
> signifiant sans jamais interdire de la porter — la borne est ce qu'on porte,
> jamais un interdit (DOM-02).

### 3.5 Ce qui devient de la donnée morte

Les 56 pièces `t2_<élément>_*` et `t3_<élément>_*` perdent leur élément et
fusionnent en une grille neutre de ~21 pièces par palier. Le jeu étant en pur
dev, les doublons sont supprimés plutôt que renommés.

---

## 4. Décision 3 — Les outils deviennent une ligne de progression

### 4.1 Le défaut

`GatherService` **n'exige aucun outil**. Pioche, faucille, canne à pêche,
couteau de dépeçage et hache — **5 types sur 9, soit 20 objets** — n'ont aucune
fonction mécanique. Et `CRAFT_TOOL_TYPES` ne couvre que 4 métiers sur 7 :
cuisinier, charpentier et tailleur n'ont pas d'outil requis.

Par ailleurs, **acier et mithril n'ont aucune source** : sur 4 paliers déclarés,
seuls bronze et fer sont atteignables (boutique). L'outillage s'arrête au fer.

### 4.2 La décision

1. **La récolte exige un outil du bon type**, comme le craft.
2. **Le palier de l'outil module le rendement**, il ne conditionne jamais
   l'accès à un filon.
3. **Acier et mithril reçoivent une source de craft** (forgeron), ce qui donne
   au forgeron un débouché récurrent — l'outil s'use déjà (`durability`,
   `wearCraftTool`).
4. **Les 3 métiers sans outil en reçoivent un** : cuisinier, charpentier,
   tailleur.

### 4.3 La garantie qui empêche que ce soit un mur

`GAME_ZONE_ACTIONS` pose qu'**une récolte n'échoue jamais** : vitalité et fatigue
modulent le rendement, jamais l'accès. Exiger un outil entre en tension avec ce
principe. La conciliation, reprise telle quelle du mécanisme déjà en place pour
les armes (`equipment_ports.yaml`, `rung1.free`) :

> **L'outil de palier 1 est gratuit et livré à l'ouverture de l'arbre de
> récolte.** Le coût réel est le parchemin, jamais l'outil.

Un joueur n'est donc jamais devant un filon sans pouvoir l'ouvrir : il a l'outil
de bronze du jour où il a l'arbre. Ce qui se gagne ensuite, c'est le rendement.

---

## 5. Décision 4 — Les matières

### 5.1 Le champignon

`mushroom` tombe de **16 tables de butin** — le loot le plus fréquent du jeu — et
**aucune recette ne le consomme**. Le cuisinier a 8 recettes et ne cuisine pas le
champignon. Il rejoint la ligne du cuisinier, et devient une matière d'entrée
d'alchimie.

### 5.2 Les doublons legacy — supprimés

| Doublon | Remplacé par |
|---|---|
| `wood_log` | la ligne du bois (`wood-beech`, `wood-peat`, `wood-petrified`, `wood-whisperoak`) |
| `pickaxe` (sans palier) | `pickaxe_bronze` |
| `herb_lavender`, `herb_mint` | `plant-lavender`, `plant-mint` (récoltables) |
| `leather_skin_1`, `leather_skin_2` | `leather-raw`, `leather-thick` (déjà diagnostiqué par ECO-02, jamais supprimés) |
| `food_bread` | `bread` |

### 5.3 Les 8 recettes d'extension

Les 8 recettes infabricables butent toutes sur `ore-adamantite` et
`ore-starmetal`. **Ce n'est pas un oubli** : `GAME_ZONES` §3 les réserve
explicitement à l'**Extension 1** (`ore-voidium` à l'**Extension 2**).

Ce sont donc des **recettes hors périmètre livrées dans le jeu de base**. Elles
sont **retirées** et versées à la réserve d'extension, avec les 3 minerais et
`ore_voidium` (qu'aucune recette n'utilise). Une recette visible et éternellement
infabricable est un mensonge d'interface ; la réserve d'extension est un fichier,
pas un contenu livré.

Conséquence à assumer : la courbe de recettes, déjà creuse au-delà du niveau 5
(26 recettes sur 115, dont 11 aux paliers 8-10), perd ses 8 derniers crans. **Le
haut de la chaîne d'artisanat est à re-remplir dans le périmètre de la base** —
c'est un chantier à part, dépendant de la carte des minerais de `GAME_ZONES`.

### 5.4 L'équilibre des filons

| profession | filons | part |
|---|---:|---:|
| herbalism | 24 | 44 % |
| mining | 19 | 35 % |
| fishing | 7 | 13 % |
| woodcutting | 5 | 9 % |

Le bûcheronnage et la pêche sont sous-servis au regard de leurs débouchés
(charpentier 10 recettes, cuisinier 8). Rééquilibrage à instruire avec
`PLAN_ZONES` — la cible n'est pas l'égalité, c'est que chaque ligne alimente son
métier sans goulot.

---

## 6. Invariants testables

1. **Cinq types, pas douze** — tout `Item::type` est l'une des 5 constantes.
2. **L'onglet Matériaux est complet** — toute matière première ou intermédiaire
   de craft est `isResource()`.
3. **Aucune pièce d'équipement ne porte d'élément.**
4. **Les emplacements progressent** — 1 / 2 / 3 emplacements en t1 / t2 / t3.
5. **Tout emplacement est typé** — `materiaSlotType` renseigné sur 100 % du
   vestiaire, dérivé de la famille (§3.4), jamais posé pièce par pièce.
6. **Aucun outil sans fonction** — tout `toolType` est requis par une récolte ou
   un craft, et tout métier d'artisanat a son outil.
7. **Aucun palier d'outil sans source** — les 4 paliers × 9 types sont
   atteignables ; le palier 1 est gratuit à l'ouverture de l'arbre.
8. **Aucune recette infabricable** — tout ingrédient est atteignable depuis les
   sources du monde de base ; le hors-périmètre n'est pas livré.
9. **Aucun doublon** — une matière a un slug et un seul.
