# Anatomie d'un arbre de combat — l'Assassin, déroulé de bout en bout

> **Statut : exercice instruit, 2026-08-01.** Ce document ne décide rien que
> [GAME_ARCHETYPES.md](GAME_ARCHETYPES.md) et [GAME_DOMAINS.md](GAME_DOMAINS.md)
> n'aient déjà décidé : il les **applique jusqu'au bout** sur un seul arbre, nœud
> par nœud, palier par palier, et rapporte ce qui casse en chemin. En cas d'écart,
> le canon fait foi ; les six écarts trouvés sont listés au §10, pour arbitrage.
>
> En amont : GAME_ARCHETYPES (la grammaire), GAME_DOMAINS §5.1 (le gabarit),
> [GAME_MATERIA.md](GAME_MATERIA.md) (la chaîne accord → matéria → geste),
> [GAME_ONBOARDING.md](GAME_ONBOARDING.md) §6 (le parchemin et le catalogue).
> Jalons d'exécution : [roadmap/PLAN_ARCHETYPES.md](roadmap/PLAN_ARCHETYPES.md).

**À quoi sert ce document.** GAME_ARCHETYPES écrit quatre arbres « en entier »,
mais au niveau de la *grammaire* : les leviers, le budget, la fourche. Aucun ne
descend jusqu'à ce qu'un joueur voit à l'écran, ce qu'un nœud pèse en base, ni ce
qu'il faut écrire dans `SkillFixtures.php` pour que l'arbre existe. Celui-ci
descend, sur un arbre unique, jusqu'au dernier prérequis — pour qu'on puisse
répondre à la question : *qu'est-ce qu'un arbre peut contenir, et à quel niveau ?*

---

## 0. Pourquoi l'Assassin

Les quatre patrons du canon couvrent une fonction chacun, mais **la mêlée n'y
existe qu'en encaisse** (le Soldat). Or c'est le registre le plus mal servi par le
modèle actuel : ses passifs sont bornés à un registre dans lequel **aucune action
ne tombe** (l'attaque de base ne lit pas les passifs, et il n'existe aucune
matéria de technique — dette n° 1 du §11.1 de GAME_ARCHETYPES).

| Ce que l'Assassin fait voir | Qu'aucun des quatre patrons ne montre |
|---|---|
| La mêlée **en assaut** — la ressource est le tour, jamais un pool | Le Soldat est de l'encaisse, les trois autres ne sont pas en mêlée |
| Une famille d'arme au **trait marqué** (dague : critique haut, dégâts bas) | Le canal de sort n'a pas de trait offensif |
| Une **ligne d'armure** comme condition en escalier (le cuir) | Le tissu du Pyromancien n'apparaît que sur un nœud |
| **Trois familles de conditions** dans le même arbre : arme, armure, marque | Aucun n'en porte trois |
| Le **pacte** (§6.5), et ce qu'il coûte réellement de le prendre | Aucun patron n'en porte |
| Un arbre livré **complet** en base, donc un écart mesurable | — |

Son entrée au catalogue public (`config/game/domain_catalog.yaml`) est déjà écrite
et elle contraint tout le reste :

> **assassin** — *« À ouvrir le combat avant que l'autre ne sache qu'il a
> commencé. »* · Équipe : *« Dagues, poisons et cuir léger. »*

Sa case est fixée par `DomainFixtures` : `element: dark`, `register: melee`. Sa
**fonction** est fixée par la grille du §10 de GAME_ARCHETYPES : **assaut**.
Aucune de ces trois valeurs n'est un choix de ce document.

---

## 1. Ce qui est livré aujourd'hui (mesuré le 2026-08-01)

`SkillFixtures::getAssassinSkills()` — 16 nœuds écrits, plus l'accord dormant
généré par `getDormantHybridAccords()`.

| Nature | Nombre | Détail |
|---|---:|---|
| Accords de matéria | **10** | ambush, necrotic-touch, death-touch, life-leech, vital-drain, shadow-bolt, death-grip, soul-siphon, deadly-strike, death-nova, shadow-dance *(11 avec le rang 5)* |
| Passifs | 3 | `critical +2`, `damage +1`, `hit +2` |
| Échelons de port (dague) | 2 | T2 (`critical +1`), T3 (`critical +2`) |
| Accord dormant | 1 | 200 points, `materia.hybrid` / `dark` |

**Coût total pour tout apprendre : 515 points** (hors dormant), pour un plafond
global de 500 encore présent dans le code
(`PlayerSkillHelper::MAX_TOTAL_SKILL_POINTS`) — **l'arbre est littéralement
inapprenable en entier**, et il consommerait à lui seul la totalité du budget de
savoir d'un personnage. Le canon a tranché la suppression du plafond
(GAME_ARCHETYPES §11.2, jalon ARC-10) ; elle n'est pas faite.

Six écarts au gabarit cible, dans l'ordre où ils mordent :

1. **Onze accords pour six**, et l'arbre n'est presque que ça — 11 nœuds sur 16.
2. **Aucun accord n'est une technique.** Les onze pointent des `Spell` d'élément
   `dark` sans registre : un assassin qui sertit tout ce que son arbre lui ouvre
   lance **onze sorts**. C'est un mage avec une dague, littéralement.
3. **Aucun accord non-`dégât`** : la règle des intentions (§5.1 du canon) exige
   un plan B ; les onze sont des dégâts ou des drains.
4. **Aucun accord n'applique la marque** de son élément — Aveuglé n'existe pas
   (`StatusEffectFixtures` livre burn, poison, paralysis, freeze, silence…).
   Le capstone d'assaut n'a donc **aucun objet**.
5. **Les passifs sont plats et hors budget** : `critical +2` ne se rapporte à
   aucune échelle, et rien ne dit si l'arbre est plus fort qu'un autre.
6. **Les échelons de port portent des statistiques** — voir §10, écart n° 5 :
   c'est celui qui fuit hors de l'arbre.

> Ce n'est pas un procès de l'existant : ces seize nœuds ont été écrits avant que
> la grammaire n'existe. C'est la **mesure du chemin** entre les deux.

---

## 2. Ce qu'un nœud peut être — le vocabulaire complet

Un arbre de combat n'a que **six natures de nœud**, et pas une septième. C'est la
première réponse à « tout ce qu'un arbre peut comprendre » : la liste est courte,
et c'est ce qui la rend testable.

| Nature | Ce que le nœud donne | Ce qu'il coûte en budget | Où il a le droit d'apparaître |
|---|---|---:|---|
| **Accord** | le droit d'utiliser **une** matéria (`actions.materia.unlock`) — sort *ou* technique | **0 pb** | tous paliers, 6 par arbre (2 gratuits) |
| **Passif** | un levier en pourcentage, éventuellement conditionnel | 3 / 6 / 9 pb | paliers 1 à 3, 6 par arbre |
| **Capstone** | un passif **toujours conditionnel**, signature de la fonction | 14 pb | palier 100, exactement 1 |
| **Échelon de port** | le droit de porter une famille d'arme au palier n | **0 pb**, aucune statistique | paliers 1 et 2, échelons 2 et 3 |
| **Pacte** | un levier **majoré** payé par un malus permanent | 19 pb de bonus, 10 pb de malus | palier 3 seulement, 1 au plus, et c'est une feuille |
| **Accord dormant** | rien, aujourd'hui — l'hybride réservé (DOM-07) | hors budget | 1 par arbre de combat |

**Et ce qu'un nœud ne peut jamais être**, ce qui compte autant :

- **jamais une action** (règle absolue n° 9) — un arbre n'accorde pas un sort, il
  accorde le *droit* d'une matéria ; le chemin est fermé et commenté dans
  `CombatSkillResolver` ;
- **jamais un malus conditionnel** (« −10 % en plaque ») — c'est l'interdit de
  port réintroduit par la bande ; seul le pacte a le droit d'un malus, et il est
  permanent et inconditionnel ;
- **jamais une condition portant sur une pièce nommée ou une rareté** — sinon le
  butin devient un prérequis de build ;
- **jamais un seizième levier** — le vocabulaire est fermé (§4 du canon).

---

## 3. L'identité de l'arbre — ce qui se décide avant le premier nœud

| | |
|---|---|
| **Triplet** | ténèbres × **mêlée** × **assaut** *(le seul de la grille)* |
| **Promesse** | *J'ouvre le combat, et il est déjà à moitié fini.* |
| **Coût structurel** | Au contact, en cuir, sans seconde chance : chaque tour qui passe joue contre lui |
| **Profil temporel** | **La pointe** — énorme au premier tour, décroissant ensuite |
| **Sa faiblesse** | Ce qui a beaucoup de points de vie, et ce qui survit à l'ouverture |
| **Sa marque** | **Aveuglé** (ténèbres) — *ses gestes ratent leur cible* |
| **Sa palette** (assaut) | **`power`**, `critical`, `critical_power`, `pierce`, `tempo` |
| **Sa teinte** (hors palette, ≤ 10 pb) | **`dodge`** — le cuir ; *on ne touche pas ce qui n'est plus là* |
| **Sa famille d'arme** | **dague** — critique élevé, dégâts de base bas (§2.2 du canon) |
| **Sa ligne d'armure** | **cuir** — éviter plutôt qu'absorber |

**Pourquoi `dodge` et pas autre chose.** La teinte est le seul levier hors palette
qu'un arbre a le droit d'acheter, et elle doit dire quelque chose que la fonction
ne dit pas. L'assaut paie sa fragilité ; `dodge` ne la répare pas — il la
**transforme en pari**, ce qui est exactement le trait du cuir (§2.2 : « le
porteur de cuir a connu deux tours gratuits et un tour catastrophique »). Un
assassin teinté `guard` serait un soldat mal équipé ; teinté `dodge`, il reste
fragile *en moyenne* et imprévisible *en pratique*. Et comme la teinte ne vit que
dans une branche (§6.1 bis, règle 3), la moitié des assassins ne l'auront pas.

**Le test du voisin.** Trois arbres partagent quelque chose avec lui, aucun ne
partage tout : le **Sorcier** (ténèbres × sorts × assaut) a l'élément et la
fonction, pas le registre ; le **Berserker**, le **Chevalier** et l'**Inquisiteur**
ont le registre et la fonction, pas l'élément — et aucun n'a la dague comme
condition centrale. ✔

---

## 4. L'arbre cible, palier par palier

### 4.0 Avant le premier nœud — ce que l'arbre ne contient pas mais exige

Trois choses précèdent le palier 0, et aucune ne coûte de point de domaine :

1. **Le parchemin** — *Codes de l'assassin* (`DomainParchmentFixtures`), acheté à
   un PNJ à prix fixe, sans prérequis d'aucune sorte. C'est le coût réel de
   l'entrée dans l'arbre (GAME_ONBOARDING §6).
2. **L'échelon 1 de port de la dague** (`port_dagger`, `free: true`) — livré à
   l'ouverture de l'arbre. Il n'est **pas** dans l'arbre : il est partagé par les
   quatre arbres qui enseignent la dague (assassin, vagabond, chasseur, dompteur),
   et en ouvrir un seul suffit.
3. **Un emplacement de matéria libre**, garanti par les kits T1 (plancher jour 1,
   GAME_WORLD §2.1). Sans lui, les deux accords gratuits ne serviraient à rien.

### 4.1 Palier d'entrée — 0 point, 2 nœuds

Les deux seuls nœuds gratuits de l'arbre, et le jeu entier du jour 1.

| Nœud | Nature | Ce qu'il donne |
|---|---|---|
| **Accord : Embuscade** | accord | technique mêlée · `dégât` / `une cible` · **forme : ouverture** — elle frappe beaucoup plus fort si c'est le premier geste de la rencontre |
| **Accord : Toucher nécrotique** | accord | technique mêlée · `dégât` / `une cible` · reprise courte, et **elle applique Aveuglé** |

**Ce que ces deux-là décident.** Embuscade est la promesse en un geste : le premier
tour vaut deux. Toucher nécrotique est le **plan B** (l'ouverture ne se joue
qu'une fois par rencontre) *et* la mise en place du capstone — c'est la règle 4
des marques : sans un accord d'entrée qui marque, le sommet de l'arbre serait
inatteignable pour qui n'a que le kit du jour 1.

> **Invariant GAME_MATERIA §3 : exactement deux accords à 0 point par arbre.**
> Pas un de plus — sinon le plancher de 48 matéria distribuées le jour 1 se met à
> flotter d'un arbre à l'autre.

### 4.2 Palier 1 — 10 points le nœud, 4 nœuds, 40 points

Fin de la première semaine. Aucun passif n'est conditionnel ici : le joueur n'a
pas encore d'équipement, et un arbre dont l'entrée est conditionnée est un arbre
fermé (garde-fou 1 du §4.3).

| Nœud | Nature | Levier | pb | Effet |
|---|---|---|---:|---|
| **Coup bas** | passif | `power` | 3 | +3 % de dégâts |
| **Point vital** | passif | `critical` | 3 | +1,5 pt de critique |
| **Accord : Voile** | accord | — | — | technique mêlée · `protection` / `soi` · **forme : posture** — un dépôt court d'esquive, le tour où frapper n'est pas la réponse |
| **Port : dague, échelon 2** | port | — | **0** | le droit de porter les dagues de palier 2 |

> **Voile est le seul accord non-`dégât` de l'arbre**, et il est obligatoire :
> règle 2 des intentions. C'est aussi le seul geste qui donne à un assaut une
> raison de ne pas attaquer — le tour où l'on encaisse ce qu'on ne peut pas
> éviter.

### 4.3 Palier 2 — 25 points le nœud, 4 nœuds, 100 points

Semaines 3 à 4 : *le passage critique* (GAME_PROGRESSION). C'est ici que la
première condition d'équipement s'allume, et ce n'est pas un hasard — c'est le
moment où le joueur a enfin le choix de ce qu'il porte.

| Nœud | Nature | Levier | pb | Effet |
|---|---|---|---:|---|
| **Là où ça saigne** | passif | `critical_power` | 6 | +9 % de dégâts critiques |
| **Lame courte** | passif | `critical` | 6 | **+4,2 pt** de critique *si une dague est en main* (condition de build, ×1,4) |
| **Accord : Nova de mort** | accord | — | — | technique mêlée · `dégât` / `plusieurs cibles` — le geste de zone |
| **Port : dague, échelon 3** | port | — | **0** | le droit de porter les dagues de palier 3 |

**Lame courte est le nœud pivot de l'arbre**, et il mérite qu'on s'y arrête. Il
creuse le trait de sa famille (la dague critique beaucoup et frappe peu), il est
satisfaisable par ce que l'arbre débloque lui-même (garde-fou 3), et il ne ferme
rien : l'assassin à l'épée existe toujours, il n'a simplement pas ces 4,2 points.
On ne lit jamais un interdit — on lit ce qu'on gagnerait à porter autre chose.

### 4.4 Palier 3 — 50 points le nœud, 6 nœuds écrits, 3 apprenables, 150 points

Semaines 6 à 8 : **la fourche**. L'arbre propose deux branches ; le personnage
n'en apprend qu'une, et le retour en arrière se paie (respec de branche, motif de
refus `other_branch` — mécanisme livré par DOM-06 pour l'artisanat, jamais servi
au combat).

**Branche « l'Ombre » — celui qui frappe et qui n'est plus là**

| Nœud | Levier | pb | Effet |
|---|---|---:|---|
| **Pas de côté** | `dodge` *(teinte)* | 9 | **+4,4 pt** d'esquive *en cuir* — **en escalier : +0,88 pt par pièce portée** |
| **Deux fois plutôt qu'une** | `tempo` | 9 | **+12,6 %** d'initiative *si deux armes sont portées* (×1,4) |
| **Accord : Danse des ombres** | — | — | technique · `dégât` / `plusieurs cibles` · **forme : ouverture** — elle frappe **et rouvre** : la condition du premier tour redevient vraie |

**Branche « la Lame » — celui qui achève**

| Nœud | Levier | pb | Effet |
|---|---|---:|---|
| **Entre les côtes** | `critical_power` | 9 | +13,5 % de dégâts critiques |
| **Fil aiguisé** | `pierce` | 9 | +6,3 pt de résistance ignorée |
| **Accord : Coup mortel** | — | — | technique · `dégât` / `une cible` — le geste de pointe, cher en reprise |

**Ce que la fourche oppose vraiment.** Pas « offensif contre défensif » — la
fonction ne change pas. *L'Ombre* rallonge le combat pour le rouvrir : elle
survit plus longtemps et se paie un second premier tour. *La Lame* raccourcit :
elle ne cherche pas à durer, elle cherche à finir. Les deux branches ne partagent
**aucun levier** (règle 2), chacune ouvre **son** geste (règle 5 — sans quoi deux
branches produisent le même combat au tour près, constat mesuré au §9 bis du
canon), et la teinte `dodge` n'existe que dans l'Ombre : *la teinte est un choix,
pas une fatalité*.

### 4.5 Capstone — 100 points, 1 nœud

| Nœud | Nature | Levier | pb | Effet |
|---|---|---|---:|---|
| **Ce qui ne voit pas venir** | capstone | `power` | 14 | **+28 %** de dégâts *contre une cible Aveuglée* (14 pb × 2,0) |

Mois 3. La condition est **atteignable au tour 2 avec le seul kit d'entrée** —
Toucher nécrotique applique Aveuglé, et il est gratuit. Elle ne demande jamais un
second personnage. Et elle vaut 14 pb, pas un de plus : son amplitude ×2 est déjà
le paiement de son intermittence.

> **Le prérequis du capstone est l'accord de branche, jamais ses passifs.** Ce
> n'est pas un détail de plomberie : c'est ce qui rend les quatre passifs de
> fourche **feuilles**, donc ce qui rend le pacte possible (§5). Voir l'écart
> n° 3 du §10.

### 4.6 L'accord dormant — 150 points, hors budget

`assassin_hybrid_accord` — posé, inactif, non apprenable (`dormant: true`). Il
déclare son élément parent (`dark`) et rien d'autre : nommer l'hybride reviendrait
à décider de la fusion avant qu'elle n'existe. Il ne compte dans aucun budget tant
que la fusion n'ouvre pas.

*(En base il coûte 200 points ; le canon en dit 150 — écart n° 6 du §10.)*

### 4.7 L'arbre complet, d'un coup d'œil

| Palier | Coût | Nœud | Nature | Levier / effet | pb |
|---|---:|---|---|---|---:|
| Entrée | 0 | Accord : **Embuscade** | accord | `dégât` · `une cible` · ouverture | — |
| Entrée | 0 | Accord : **Toucher nécrotique** | accord | `dégât` · `une cible` · **applique Aveuglé** | — |
| 1 | 10 | **Coup bas** | passif | `power` +3 % | 3 |
| 1 | 10 | **Point vital** | passif | `critical` +1,5 pt | 3 |
| 1 | 10 | Accord : **Voile** | accord | `protection` · `soi` · posture | — |
| 1 | 10 | *Port* : dague, échelon 2 | port | — | 0 |
| 2 | 25 | **Là où ça saigne** | passif | `critical_power` +9 % | 6 |
| 2 | 25 | **Lame courte** | passif | `critical` **+4,2 pt** *à la dague* | 6 |
| 2 | 25 | Accord : **Nova de mort** | accord | `dégât` · `plusieurs cibles` | — |
| 2 | 25 | *Port* : dague, échelon 3 | port | — | 0 |
| 3 · Ombre | 50 | **Pas de côté** | passif | `dodge` **+4,4 pt** *en cuir* (escalier) | 9 |
| 3 · Ombre | 50 | **Deux fois plutôt qu'une** | passif | `tempo` **+12,6 %** *à deux armes* | 9 |
| 3 · Ombre | 50 | Accord : **Danse des ombres** | accord | `dégât` · `plusieurs cibles` · ouverture | — |
| 3 · Lame | 50 | **Entre les côtes** | passif | `critical_power` +13,5 % | 9 |
| 3 · Lame | 50 | **Fil aiguisé** | passif | `pierce` +6,3 pt | 9 |
| 3 · Lame | 50 | Accord : **Coup mortel** | accord | `dégât` · `une cible` | — |
| Capstone | 100 | **Ce qui ne voit pas venir** | capstone | `power` **+28 %** *contre une cible Aveuglée* | 14 |
| *Dormant* | *150* | *Accord d'hybride (ténèbres)* | *dormant* | *réservé* | — |

**18 nœuds écrits · 15 apprenables · 390 points** (`4×10 + 4×25 + 3×50 + 100`)
· **50 pb pile, par branche**.

---

## 5. La variante que le gabarit autorise — le pacte, et ce qu'il coûte

Le §6.5 du canon permet à un arbre de prendre **un** pacte : un malus permanent
qui rend du budget. L'Assassin est le candidat naturel — un assaut qui paie en
survie est sa fiction même. L'exercice montre que **ce n'est pas un nœud qu'on
ajoute : c'est un arbre qu'on réorganise.**

Le pacte visé, écrit à la lettre des six règles :

> **« Rien à perdre »** — `power` **+19 %**, `life` **−15 %**. Permanent.
> *(9 pb de nœud de palier 3 + 10 pb rendus par le malus = 19 pb de bonus.)*

Il remplace un des deux passifs d'une branche — jamais un dix-neuvième nœud. Et
là, l'arithmétique mord :

| Contrainte | Calcul | Verdict |
|---|---|---|
| `power` plafonne à **20 pb** par arbre | pacte 19 + capstone 14 + Coup bas 3 = **36** | ❌ impossible en l'état |
| Le capstone doit donc quitter `power` | seuls 8 leviers sur 15 ont un plafond ≥ 14 ; dans la palette assaut : `power` (20) et `critical_power` (15) | capstone → `critical_power` |
| `critical_power` plafonne à **15** | capstone 14 + « Là où ça saigne » 6 = **20** | ❌ le palier 2 doit changer |

**L'arbre qui en sort** — même budget, même palette, quatre nœuds déplacés :

| Palier | Nœud | Levier | pb |
|---|---|---|---:|
| 1 | Point vital | `critical` | 3 |
| 1 | Pas pressé | `tempo` | 3 |
| 2 | Fil aiguisé | `pierce` | 6 |
| 2 | Lame courte *(à la dague)* | `critical` | 6 |
| 3 · Lame | **« Rien à perdre »** — `power` +19 %, `life` −15 % | `power` / *`life`* | **19 / −10** |
| 3 · Lame | Deux fois plutôt qu'une *(à deux armes)* | `tempo` | 9 |
| Capstone | Ce qui ne voit pas venir *(cible Aveuglée)* | `critical_power` | 14 |

Vérification : bonus 60 pb − malus 10 pb = **50** ✔ · `power` 19 ≤ 20 ✔ ·
`critical` 9 ≤ 12 ✔ · `tempo` 12 ≤ 12 ✔ · `critical_power` 14 ≤ 15 ✔ ·
`pierce` 6 ≤ 12 ✔ · malus **hors palette** (`life` n'est pas dans l'assaut) ✔ ·
pacte au palier 3, feuille, un seul, visible avant d'apprendre ✔.

> **Ce que l'exercice apprend** : le pacte n'est pas une option qu'on visse sur un
> arbre fini. Il consomme à lui seul l'équivalent d'un levier entier, force le
> capstone à changer de levier, et le capstone force à son tour le palier 2. Un
> arbre à pacte est un **autre arbre**, pas une variante. C'est probablement sain
> — mais le canon ne le dit nulle part, et c'est l'écart n° 2 du §10.

---

## 6. Ce que l'arbre ne contient pas, et où ça vit

C'est la moitié de la réponse à « tout ce qu'un arbre peut comprendre » : ce qu'il
**ne** contient pas est décidé aussi fermement que ce qu'il contient.

| Ce que le joueur croit venir de l'arbre | Où ça vit réellement |
|---|---|
| **Les gestes** (Embuscade, Nova de mort…) | Des **matéria**, objets du monde : on les trouve, on les achète, on les sertit. L'arbre n'accorde que le *droit* (règle 9) |
| **La puissance des gestes** | Le **support** : emplacements de matéria, bonus de pièce. « On ne progresse pas en changeant de sort, on progresse en le portant mieux » |
| **La dague elle-même** | L'économie : forgeron, butin, marché. L'arbre ne donne que le **droit de la porter** |
| **La mitigation du cuir** (20 %) | [GAME_ITEMS.md](GAME_ITEMS.md) — l'arbre qualifie, l'armure décide. Par l'arbre seul, l'écart tank/tissu n'est que de ×1,39 |
| **Le statut Aveuglé** | `StatusEffect` — une donnée de moteur partagée, portée par les gestes **et par les monstres** (règle 5 des marques) |
| **La ressource** (le temps de reprise) | `Spell::cooldown`, au modèle depuis toujours, sans consommateur |
| **Les formes de geste** (ouverture, posture) | La matéria, jamais l'arbre — le vocabulaire du §13 du canon |
| **L'élément d'une action** | La matéria sertie. En mêlée, la technique ; en distance, le carquois la remplace |
| **Le premier échelon de port** | Partagé entre quatre arbres, gratuit, hors arbre (ONB-20b) |

> **La conséquence pratique** : un assassin qui a fini son arbre et ne sertit rien
> n'a que son attaque de base — non qualifiée, sans un seul passif. L'arbre est un
> **droit d'exprimer**, jamais une puissance en soi. C'est la doctrine des trois
> couches, prise au mot : le savoir n'est pas borné, le faire l'est par ce qu'on
> porte.

---

## 7. Les vérifications — les invariants du §12, un par un

| # | Invariant | Mesure sur cet arbre |
|---|---|---|
| 1 | Budget = 50 pb exactement | Ombre : 3+3+6+6+9+9+14 = **50** ✔ · Lame : 3+3+6+6+9+9+14 = **50** ✔ |
| 2 | Aucun levier au-dessus de son plafond | `power` 17/20 · `critical` 9/12 · `critical_power` 15/**15** · `dodge` 9/12 · `tempo` 9/12 · `pierce` 9/12 ✔ |
| 3 | Palette ≥ 40 pb, hors palette ≤ 10 sur un seul levier | Ombre : 41 en palette, **9** hors (`dodge` seul) ✔ · Lame : 50 en palette, 0 hors ✔ |
| 4 | Triplet unique dans la grille | ténèbres × mêlée × assaut : le seul ✔ |
| 5 | Gabarit tenu | 15 nœuds apprenables, échelle 0/10/25/50/100 + dormant ✔ |
| — | Exactement 2 accords à 0 point | Embuscade, Toucher nécrotique ✔ |
| — | Intentions de l'assaut : ≥ 3 `dégât` | 5 sur 6 accords ✔ |
| — | ≥ 1 accord non-`dégât` | Voile (`protection`) ✔ |
| — | ≥ 1 accord que nul autre arbre n'ouvre | Danse des ombres ✔ |
| — | Un accord d'entrée applique la marque | Toucher nécrotique → Aveuglé ✔ |
| — | Capstone atteignable au tour 2 avec le kit d'entrée | Toucher nécrotique est gratuit ✔ |
| — | ≥ 2 passifs sur 7 sans condition | Ombre : **3** (Coup bas, Point vital, Là où ça saigne) · Lame : **5** (+ Entre les côtes, Fil aiguisé) ✔ |
| — | Aucune condition au palier 1 | les deux passifs de palier 1 sont nus ✔ |
| — | Branches sans levier commun | Ombre {`dodge`, `tempo`} ∩ Lame {`critical_power`, `pierce`} = ∅ ✔ |
| — | Test de l'arbre nu | *critique, dégâts critiques, perce-résistance, esquive, initiative* → on lit un assaut furtif sans avoir vu un seul accord ✔ |

---

## 8. Le déroulé — ce que le joueur en voit, du jour 1 au mois 3

| Moment | Points | Ce qu'il a | Ce que ça change au combat |
|---|---:|---|---|
| **Jour 1** | 0 | Le parchemin, le port de la dague, deux techniques | Il ouvre au premier tour et marque au second. Aucun passif : **l'arbre ne fait rien encore, et c'est normal** — il donne des *gestes* |
| **Fin de semaine 1** | 40 | +3 % de dégâts, +1,5 pt de critique, Voile, dague T2 | Le premier tour où frapper n'est pas la réponse devient jouable. Les passifs sont invisibles individuellement — c'est assumé |
| **Semaines 3-4** | 140 | Critique conditionné à la dague, dégâts critiques, geste de zone, dague T3 | **Le premier vrai choix d'équipement** : l'épée trouvée hier est meilleure sur le papier, la dague vaut 4,2 points de critique |
| **Semaines 6-8** | 290 | Une branche sur deux | Le choix qui distingue deux assassins. Il se paie pour en changer |
| **Mois 3** | 390 | Le capstone | +28 % contre une cible Aveuglée — c'est-à-dire **après son propre tour 1**. Le joueur ne clique rien de plus : il est récompensé de jouer comme il jouait déjà |

**Le rythme.** À 16 combats par jour sur de la faune de son palier (T2 → 0,5 point
par geste réussi), soit ~8 points par jour, les 390 points tombent en ~7 semaines
de pratique soutenue — le mois 3 vise le joueur réel, qui mène deux ou trois
arbres et ne joue pas tous les jours. Et sur de la faune T1 : **0,25 point**. *On
ne monte pas un arbre en tapant des rats.*

**Ce qu'il porte au mois 3, et ce que ça vaut.** Cinq pièces de cuir (esquive
pleine), une dague en main gauche et une en main droite (les deux conditions
allumées), branche l'Ombre : +3 % de dégâts, +5,7 pt de critique, +9 % de dégâts
critiques, +4,4 pt d'esquive, +12,6 % d'initiative — et +28 % de dégâts dès que sa
cible est aveuglée. **Un assassin fini frappe environ 1,3 fois plus fort qu'un
assassin qui vient d'ouvrir son arbre**, condition remplie, à équipement égal.
C'est peu, et c'est voulu : la progression passe par le support, pas par l'arbre.

---

## 9. Ce que ça donne en données

**Un nœud aujourd'hui** — la forme livrée, dans `SkillFixtures::getAssassinSkills()` :

```php
'assassin_rang2_1' => [
    'title' => 'Lame dans l\'ombre',
    'slug' => 'assassin-rang2-1',
    'requiredPoints' => 10,
    'domain' => 'assassin',
    'critical' => 2,                       // un entier plat, sans échelle
    'requirements' => ['assassin_apprenti_1'],
],
```

**Le même nœud à la cible** — ce que la dette n° 3 du §11.1 rend nécessaire
(`Skill` porte une liste `(levier, pb, condition)` au lieu de cinq entiers) :

```php
'assassin_p2_critical' => [
    'title' => 'Lame courte',
    'slug' => 'assassin-p2-critical',
    'requiredPoints' => 25,
    'domain' => 'assassin',
    'levers' => [
        ['lever' => 'critical', 'budget' => 6, 'condition' => 'weapon_family:dagger'],
    ],
    'requirements' => ['assassin_p1_critical'],
],
```

**Les six dettes de moteur que cet arbre seul révèle**, dans l'ordre où elles
bloquent :

| # | Ce qui manque | Ce que l'arbre ne peut pas faire sans | Dette canon |
|---|---|---|---|
| 1 | `Spell::register` + des matéria de technique | **Aucun de ses six accords n'existe** — l'arbre reste un arbre de mage | §11.1 n° 1 |
| 2 | `Domain::role` (la fonction) | La palette n'est pas vérifiable ; rien ne distingue l'Assassin du Sorcier | §11.1 n° 2 |
| 3 | Les leviers sur `Skill` | Les 7 passifs restent plats, donc inéquilibrables | §11.1 n° 3 |
| 4 | Le statut **Aveuglé** | Le capstone n'a **aucun objet** — il ne se déclenche jamais | §11.1 n° 7 |
| 5 | La fourche au combat (`other_branch`) | Les 6 nœuds du palier 3 s'apprennent tous : le renoncement n'existe pas | §11.1 n° 8 |
| 6 | Le temps de reprise consommé (`Spell::cooldown`) | La mêlée coûte la même chose que les sorts : trois registres repeints | §11.1 n° 4 |

Plus deux qui ne sont pas des dettes de moteur mais de données : la suppression du
plafond de 500 points (ARC-10, sans quoi l'arbre livré à 515 points est
inapprenable), et la conversion des 16 nœuds existants vers les 18 du gabarit.

---

## 10. Ce que l'exercice a trouvé — six écarts, pour arbitrage

Aucun n'est corrigé ici : ce document applique le canon, il ne le modifie pas. Les
trois premiers sont des trous de règle, les trois derniers des écarts mesurés
entre le canon et le code.

1. **Le gabarit n'a aucune place pour le pacte.** §6.1 écrit 18 nœuds et les
   nomme tous ; §6.5 autorise un pacte sans dire où il se pose. *Proposition : le
   pacte est une **variante d'un passif de fourche**, jamais un nœud
   supplémentaire — sinon l'arbre à pacte a un nœud de plus que les autres.*

2. **Le pacte est arithmétiquement plus contraint que le canon ne le laisse
   croire.** 19 pb sur un seul levier ne tiennent que sous un plafond de 20 —
   c'est-à-dire `power`, `mending`, `grip` ou `life`, soit **le levier principal
   de trois fonctions sur quatre**. Or le capstone (14 pb) vise le même levier.
   Prendre un pacte force donc à changer de capstone, puis de palier 2 : **c'est
   un arbre différent, pas une option** (§5). *À écrire dans le canon, avec le
   calcul.*

3. **Les prérequis internes ne sont pas libres.** Pour que « le nœud de pacte est
   une feuille » (règle 4) soit tenable, le capstone doit exiger **l'accord** de
   branche et non ses passifs. Le canon ne dit rien des prérequis internes ; cet
   exercice montre qu'ils sont contraints par au moins une autre règle.

4. **Le capstone n'est achetable que sur 8 leviers sur 15.** Un plafond < 14 pb
   l'interdit mécaniquement : exit `critical`, `hit`, `pierce`, `wind`, `dodge`,
   `recovery`, `tempo`. Les quatre conditions canoniques du §7 tombent donc juste
   — mais par coïncidence, pas par règle écrite. *À énoncer : le capstone d'une
   fonction ne peut viser qu'un levier de plafond ≥ 14.*

5. **Les échelons de port portent des statistiques, et ils sont partagés.**
   Mesuré : `assassin_weapon_t2` donne `critical +1`, `t3` donne `critical +2` —
   et depuis ONB-20b, ces nœuds appartiennent aux **quatre** domaines qui
   enseignent la dague (`Skill::domains` est un ManyToMany, et
   `CombatSkillResolver` applique un passif dès qu'**un** de ses domaines occupe
   la case de l'action). Conséquence : un Vagabond (air × mêlée), un Chasseur
   (bête × distance) ou un Dompteur qui apprend la dague encaisse **+3 points de
   critique dans sa propre case**, hors budget et hors de sa palette. Le motif
   existe sur les **six** familles (`damage +1/+1` pour la hache et l'épée,
   `life +3/+5` pour la lance, `hit +1/+2` pour l'arc), et le pire est le
   **bâton** : ses échelons donnent `heal +1/+1` et sont enseignés par **dix**
   arbres, dont neuf de sorts — un pyromancien qui apprend à tenir un bâton de
   palier 3 gagne `heal +2`, un levier qui n'est même pas dans la palette de
   l'assaut. C'est une fuite de budget de la même nature que les `DomainSynergy`
   du §9.7. *Proposition : les échelons ne portent aucune statistique — ce que
   `getWeaponPortRungs()` fait déjà pour l'échelon 1, et ce que le commentaire du
   fichier annonce déjà (« zéro point de domaine, et aucune statistique »).*

6. **L'accord dormant coûte 200 points en base, 150 au canon.** Sans effet
   aujourd'hui (le nœud n'est pas apprenable), à aligner avant que la fusion
   n'ouvre.

---

## 11. Ce que ce document ne décide pas

- **Aucune valeur définitive.** Comme tous les nombres de GAME_ARCHETYPES (§0.2),
  les pourcentages ci-dessus sont des repères calculés sur une échelle
  illustrative. Ce qui survivra à la recalibration : la structure des 18 nœuds,
  les rapports entre paliers, les vérifications du §7. Ce qui sera recalculé :
  tous les effets affichés. Le juge final est `app:balance:simulate` (ARC-17), pas
  la relecture d'un tableau.
- **Les vingt-trois autres arbres.** La procédure du §10.1 du canon suffit ; cet
  exercice montre seulement à quoi ressemble son résultat quand on la suit
  jusqu'au bout.
- **Le nom des gestes.** Les six accords sont désignés par leur **rôle** dans le
  combat, jamais par leur niveau de sort. Les matéria elles-mêmes sont dérivées
  (GAME_MATERIA §2.1), pas écrites ici.
- **La forme de l'écran d'arbre** — §8 bis du canon dit ce qu'il doit *dire* ;
  comment il le montre est du design d'interface.
