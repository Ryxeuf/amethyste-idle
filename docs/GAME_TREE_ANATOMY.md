# Anatomie d'un arbre de combat — cinq arbres déroulés de bout en bout

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
descend jusqu'au dernier prérequis — pour qu'on puisse répondre à la question :
*qu'est-ce qu'un arbre peut contenir, et à quel niveau ?*

**Cinq arbres : les trois registres, les quatre fonctions, et aucune redite.**

| § | Arbre | Case | Ce qu'il met à l'épreuve |
|---|---|---|---|
| 0-11 | **Assassin** | ténèbres × **mêlée** × assaut | la méthode : natures de nœud, budget, matéria, invariants |
| 12 | **Nécromancien** | ténèbres × **sorts** × contrôle | *même élément, même marque, rien en commun* — le test du voisin |
| 13 | **Artificier** | feu × **distance** × contrôle | le carquois, `wind` converti, la munition élémentaire |
| 14 | **Défenseur** + **Gardien** | terre × mêlée × **encaisse** / **entretien** | *même case, seule la fonction diffère* — l'aggro et le dépôt |

**Chacun a trouvé ce que les précédents ne pouvaient pas voir** : l'Assassin les
sept premiers écarts, le Nécromancien le corollaire du capstone (n° 8),
l'Artificier une contradiction du canon avec lui-même (n° 11), et la paire de la
terre deux palettes amputées d'un levier (n° 13 et 14).

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
| Accord dormant | 1 | 150 points, `materia.hybrid` / `dark` *(200 avant la correction de l'écart n° 6)* |

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
| **Ce qui ne voit pas venir** | capstone | `power` | 14 | **+19,6 %** de dégâts *contre une cible Aveuglée* (14 pb × 1,4 — décision 23) |

Mois 3. La condition est **atteignable au tour 2 avec le seul kit d'entrée** —
Toucher nécrotique applique Aveuglé, et il est gratuit. Elle ne demande jamais un
second personnage. Et elle vaut 14 pb, pas un de plus : son amplitude ×2 est déjà
le paiement de son intermittence.

> **Le prérequis du capstone est l'accord de branche, jamais ses passifs.** Ce
> n'est pas un détail de plomberie : c'est ce qui rend les quatre passifs de
> fourche **feuilles**, donc ce qui rend le pacte possible (§5). *Tranché le
> 2026-08-01 — c'est désormais la règle 3 du §6.6 du canon.*

### 4.6 L'accord dormant — 150 points, hors budget

`assassin_hybrid_accord` — posé, inactif, non apprenable (`dormant: true`). Il
déclare son élément parent (`dark`) et rien d'autre : nommer l'hybride reviendrait
à décider de la fusion avant qu'elle n'existe. Il ne compte dans aucun budget tant
que la fusion n'ouvre pas.

*(Il coûtait 200 points en base contre 150 au canon — écart n° 6, corrigé le 2026-08-01.)*

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
| Capstone | 100 | **Ce qui ne voit pas venir** | capstone | `power` **+19,6 %** *contre une cible Aveuglée* | 14 |
| *Dormant* | *150* | *Accord d'hybride (ténèbres)* | *dormant* | *réservé* | — |

**18 nœuds écrits · 15 apprenables · 390 points** (`4×10 + 4×25 + 3×50 + 100`)
· **50 pb pile, par branche**.

---

## 5. La variante que le gabarit autorise — le pacte, et ce qu'il coûte

Le §6.5 du canon permet à un arbre de prendre **un** pacte : un malus permanent
qui rend du budget. L'Assassin est le candidat naturel — un assaut qui paie en
survie est sa fiction même. L'exercice montre que **ce n'est pas un nœud qu'on
ajoute : c'est un arbre qu'on réorganise.**

> *Ce calcul est ce qui a produit les décisions du §6.5 du canon (tranchées le
> 2026-08-01) : le pacte **remplace** un passif de fourche, il vit dans une seule
> branche, et sa version majeure force le capstone à changer de levier.*

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
| **Mois 3** | 390 | Le capstone | +19,6 % contre une cible Aveuglée — c'est-à-dire **après son propre tour 1**. Le joueur ne clique rien de plus : il est récompensé de jouer comme il jouait déjà |

**Le rythme.** À 16 combats par jour sur de la faune de son palier (T2 → 0,5 point
par geste réussi), soit ~8 points par jour, les 390 points tombent en ~7 semaines
de pratique soutenue — le mois 3 vise le joueur réel, qui mène deux ou trois
arbres et ne joue pas tous les jours. Et sur de la faune T1 : **0,25 point**. *On
ne monte pas un arbre en tapant des rats.*

**Ce qu'il porte au mois 3, et ce que ça vaut.** Cinq pièces de cuir (esquive
pleine), une dague en main gauche et une en main droite (les deux conditions
allumées), branche l'Ombre : +3 % de dégâts, +5,7 pt de critique, +9 % de dégâts
critiques, +4,4 pt d'esquive, +12,6 % d'initiative — et +19,6 % de dégâts dès que sa
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

## 9 bis. Les six matéria de l'arbre — ce qu'il faut créer

Un arbre ne contient aucun geste : il contient six **accords**, c'est-à-dire six
droits d'utiliser six matéria. Ces matéria sont l'autre moitié de l'archétype
(« l'archétype vit dans le couple *(arbre, matéria)* »), et aucune n'existe
aujourd'hui sous la forme dont l'Assassin a besoin — non parce que les gestes
manquent, mais parce qu'ils sont tous déclarés **sorts**.

**Le constat, gestes en main** : sur les six, **quatre sont déjà écrits** et n'ont
qu'à changer de registre — leurs descriptions livrées sont déjà des gestes de lame
(« surgit de l'ombre », « un coup visant les organes vitaux ») —, et **deux
n'existent pas** : la posture et le geste de zone au contact. Aucun sort de
ténèbres livré n'est une `protection`, et aucun n'est un vrai multi-cible
(`death-nova` s'appelle « nova » et frappe **une** cible — `aoeTargets` vaut 1 par
défaut).

> **Ratio à généraliser aux sept autres arbres de mêlée** : ~2 gestes neufs sur 6.
> Le chantier des matéria de technique n'est pas un chantier de dérivation pure,
> contrairement à celui des sorts (GAME_MATERIA §1.3) — mais il n'est pas non plus
> un chantier de création : **les deux tiers sont un changement de champ**.

### 9 bis.1 La dérivation, appliquée

Rien n'est écrit à la main : chaque matéria se dérive de son geste (grille de
GAME_MATERIA §2.1), et le palier de **distribution** suit le nœud, pas le niveau
du geste (§3.1).

| # | Rôle dans le combat | Geste | Niv. | Slug matéria | Rareté *(dérivée)* | Prix | Reprise | État |
|---|---|---|---:|---|---|---:|---:|---|
| 1 | **Le geste** — ouverture | Embuscade (`ambush`) | 2 | `m2-ambush` | Rare | 180 | 1 | à **reclasser** |
| 2 | **Le plan B** — et la marque | Toucher nécrotique (`necrotic-touch`) | 1 | `m1-necrotic-touch` | Uncommon | 130 | 0 | à **reclasser** + statut |
| 3 | **La réponse** — posture | Voile d'ombre (`shadow-veil`) | 2 | `m2-shadow-veil` | Rare | 180 | 3 | à **créer** |
| 4 | **La zone** | Moulinet d'ombre (`shadow-whirl`) | 3 | `m3-shadow-whirl` | Epic | 280 | 2 | à **créer** |
| 5 | **Branche l'Ombre** — rouvrir | Danse des ombres (`shadow-dance`) | 5 | `m5-shadow-dance` | Legendary | 380 | 4 | à **reclasser** |
| 6 | **Branche la Lame** — la pointe | Coup mortel (`deadly-strike`) | 4 | `m4-deadly-strike` | Legendary | 320 | 3 | à **reclasser** |

Les deux premières sont ouvertes à **0 point** : elles sont donc au **plancher de
distribution** quelle que soit leur rareté (§3.1), achetables chez un PNJ au prix
de la grille. Les quatre autres suivent les canaux normaux — butin d'une créature
de ténèbres pour les paliers 2-3, coffre et donjon pour les 4-5.

### 9 bis.2 La grille de reprise — ce que la mêlée paie à la place des PM

GAME_MATERIA §2.3 donne un `energy_cost` par palier. C'est la grille des **sorts**.
Une technique ne coûte pas de PM : elle coûte **le tour**, et le canon a déjà dit
où ça vit (`Spell::cooldown`, au modèle, sans consommateur). Il manque la grille.

| Palier | m1 | m2 | m3 | m4 | m5 |
|---|---:|---:|---:|---:|---:|
| **Sorts** — PM | 10 | 15 | 20 | 25 | 30 |
| **Mêlée** — reprise (tours) | **0** | **1** | **2** | **3** | **4** |

> **Le contrôle est déjà dans les données** : `shadow-dance`, unique geste livré à
> porter une reprise, porte `cooldown: 4` — exactement ce que la grille prescrit
> pour un niveau 5. La grille ne s'invente pas, elle se lit.

Ce que ça produit au combat, et qui est le vrai visage du registre mêlée : un
assassin fini a **six gestes dont deux sont disponibles à chaque tour** (le
Toucher nécrotique et l'Embuscade), et quatre qui reviennent par cycles. Il ne
tombe jamais en panne — il joue une **rotation**, là où le pyromancien joue un
pic puis se tait. C'est la différence structurelle du §2, obtenue sans une seule
règle supplémentaire.

### 9 bis.3 Les six matéria, en détail

**1 · Matéria : Embuscade** — `m2-ambush` · ténèbres · technique mêlée ·
`dégât` / `une cible` · **forme : ouverture**

> Livré : dégâts 3, précision 95, critique **20**. Reprise 1.
> À changer : `register: melee` ; le bonus d'ouverture (le geste frappe beaucoup
> plus fort s'il est le premier de la rencontre) est la **condition de combat**
> « le premier tour de la rencontre », qui est déjà au vocabulaire fermé (§4.3).

C'est la promesse de l'arbre en un objet. Son critique de base à 20 % est ce que
les passifs `critical` et `critical_power` viennent multiplier : **la matéria
donne l'amplitude, l'arbre donne la fréquence.** Un assassin qui la sertit sans
avoir monté son arbre la joue déjà ; l'arbre la rend fiable.

**2 · Matéria : Toucher nécrotique** — `m1-necrotic-touch` · ténèbres ·
technique mêlée · `dégât` / `une cible` · applique **Aveuglé**

> Livré : dégâts 2, précision **100**, aucune reprise, aucun statut.
> À changer : `register: melee` et `statusEffectSlug: blind` — **le statut est à
> créer** (`StatusEffectFixtures` n'a ni Aveuglé ni aucune des huit marques).

C'est le nœud de voûte de tout l'arbre, et il est gratuit : sans lui le capstone
ne se déclenche jamais, `grip` n'a rien à allonger, et le joueur du jour 1 n'a pas
de geste sûr (précision 100). **Si une seule chose devait être créée pour que
l'Assassin existe, c'est le statut Aveuglé** — pas une matéria.

**3 · Matéria : Voile d'ombre** — `m2-shadow-veil` · ténèbres · technique mêlée ·
`protection` / `soi` · **forme : posture** · **à créer**

> À écrire : aucun dégât, reprise 3, dépose **2 tours** d'esquive accrue sur son
> lanceur. Portée `soi`, donc pas de dépôt de groupe — mais **une durée**, parce
> que toute `protection` en porte une (invariant 19).

Le seul accord non-`dégât` de l'arbre, et il est obligatoire : c'est le plan B du
test du jour 1. C'est aussi ce qui donne un sens à la branche l'Ombre — un
assassin qui a `dodge` + le Voile a un vrai tour de survie, celui qu'un assaut n'a
jamais.

**4 · Matéria : Moulinet d'ombre** — `m3-shadow-whirl` · ténèbres ·
technique mêlée · `dégât` / `plusieurs cibles` · **à créer**

> À écrire : dégâts modérés, `aoeTargets: 3`, reprise 2, précision basse (75) —
> le geste large est le geste imprécis, c'est le trait de sa famille.

`death-nova` aurait pu servir, mais c'est un sort d'explosion, et il ne frappe
qu'une cible dans les données. Un assassin qui fait le tour de trois adversaires
avec deux dagues est un geste, pas une déflagration. **Coût : un geste neuf, et
c'est le seul qui soit vraiment neuf** — le Voile ayant des dizaines d'équivalents
à copier ailleurs.

**5 · Matéria : Danse des ombres** — `m5-shadow-dance` · ténèbres ·
technique mêlée · `dégât` / `plusieurs cibles` · **forme : ouverture**

> Livré : dégâts 8, précision 85, critique 20, **`cooldown: 4`**.
> À changer : `register: melee`, et l'effet qui la distingue — **elle rouvre** :
> après elle, la condition « premier tour de la rencontre » redevient vraie une
> fois.

**C'est l'accord que nul autre arbre n'ouvre** (invariant du §5.1), et il n'est
accessible qu'à la branche l'Ombre. C'est aussi ce qui rend cette branche
cohérente de bout en bout : elle survit plus longtemps *pour* pouvoir se payer un
second premier tour.

**6 · Matéria : Coup mortel** — `m4-deadly-strike` · ténèbres · technique mêlée ·
`dégât` / `une cible`

> Livré : dégâts 6, précision 80, critique **25**, reprise 3.
> À changer : `register: melee`.

L'accord de la branche la Lame. Le plus gros critique du catalogue de ténèbres,
sur l'arbre qui achète `critical_power` deux fois : c'est le couple *(arbre,
matéria)* dans sa forme la plus littérale — la branche et le geste ne valent
rien l'un sans l'autre.

### 9 bis.4 Ce que ces six matéria supposent, et qui n'existe pas

| Ce qu'il faut | Portée | Sans quoi |
|---|---|---|
| **`Spell::register`** + `intent` + `scope` | 3 champs, tous les gestes | Les six matéria sont des sorts : l'arbre ne qualifie aucune de ses propres actions |
| **Le statut `blind` (Aveuglé)** | 1 statut, plus les 7 autres marques | Le capstone ne se déclenche jamais |
| **La grille de reprise** (§9 bis.2) | 1 table dans GAME_MATERIA | La mêlée paie en PM, donc c'est un mage |
| **`Spell::cooldown` consommé en combat** | moteur | La reprise est décorative, la rotation n'existe pas |
| **2 gestes à écrire** (`shadow-veil`, `shadow-whirl`) | contenu | L'arbre n'a ni plan B ni geste de zone — deux invariants échouent |
| **L'élément sur les monstres** | 65 monstres | Aucune des 4 matéria non gratuites n'a de source de butin |

---

## 10. Ce que l'exercice a trouvé — sept écarts

**Douze des quatorze sont réglés** au 2026-08-01 : les écarts **1 à 4**, **8**,
**11**, **13** et **14** sont tranchés et portés dans
[GAME_ARCHETYPES.md](GAME_ARCHETYPES.md) ; les écarts **5**, **6** et **10** sont
corrigés dans le code. Reste le **7** — un trou de grille dans GAME_MATERIA,
ouvert par l'exercice des matéria du §9 bis — plus les observations **12** et
**15**, qui ne demandent pas d'arbitrage mais une mesure du simulateur et un
jalon de donjon.

Les écarts 8 à 12 sont détaillés là où ils ont été trouvés — §12.7 pour le second
arbre, §13.8 pour le troisième.

1. **Le gabarit n'avait aucune place pour le pacte.** §6.1 écrit 18 nœuds et les
   nomme tous ; §6.5 autorisait un pacte sans dire où il se pose.
   → **Tranché** (§6.5, *Où il se pose*) : **le pacte est une variante d'un passif
   de fourche, jamais un nœud de plus** ; il vit au palier 3, dans une seule
   branche. Gain non prévu : la fourche gagne son opposition la plus lisible —
   *la voie sûre et la voie qui parie*.

2. **Le pacte était arithmétiquement plus contraint que le canon ne le disait.**
   19 pb sur un seul levier ne tiennent que sous un plafond de 20 — `power`,
   `mending`, `grip`, `life` — c'est-à-dire le levier principal de trois fonctions
   sur quatre, celui-là même que vise le capstone.
   → **Tranché** (§6.5, règle 7 + grille) : la contrainte est **assumée et
   écrite** — *un arbre à pacte est un autre arbre* —, avec la table de ce qui
   reste possible par fonction, et un **pacte mineur à 5 pb de malus** (nœud de
   14 pb) pour qui ne veut pas réorganiser son arbre.

3. **Les prérequis internes n'étaient pas spécifiés, et ils ne sont pas libres.**
   → **Tranché** (§6.6, *la loi des prérequis internes*, six règles) : au plus un
   parent par nœud, pris au palier précédent ; **le capstone exige l'accord de
   branche, jamais ses passifs** ; aucun prérequis ne traverse la fourche. Gain
   non prévu : le chemin vers le sommet devient lisible — **les cinq accords, et
   rien d'autre**, soit 185 des 390 points.

4. **Le capstone n'est achetable que sur 8 leviers sur 15.**
   → **Tranché** (§7.1) : *le capstone vise un levier de plafond ≥ 14 pb*. Sept
   leviers sont exclus d'office, et la table par fonction donne le capstone
   canonique **et** son second, qui est celui de l'arbre à pacte. Les quatre
   conditions canoniques du §7 cessent d'être une coïncidence.

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
   du §9.7.
   → **Corrigé le 2026-08-01** : les douze échelons perdent leur statistique.
   La loi est tenue par `rewireWeaponPortLadders()` — la passe qui recâble déjà
   le domaine et le prérequis efface désormais les cinq statistiques — plutôt
   qu'au point de déclaration, pour qu'un échelon ajouté plus tard ne puisse pas
   la contourner en silence. `EquipmentPortLadderTest::testNoPortRungCarriesCombatStats()`
   la vérifie **après recâblage**. *Un échelon est une porte, jamais une
   récompense* — ce que `getWeaponPortRungs()` faisait déjà pour l'échelon 1.

6. **L'accord dormant coûtait 200 points en base, 150 au canon.** La valeur de
   200 était héritée de l'échelle d'avant le gabarit, où le rang 5 culminait à
   150 ; le canon pose le dormant **au-dessus du sommet** (capstone 100, dormant
   150).
   → **Corrigé le 2026-08-01** : les 24 accords générés coûtent 150.
   `DomainPlanContractTest::testTheDormantAccordCostsWhatTheCanonSays()` lit la
   valeur dans la table génératrice et la compare au canon. *L'écart n'avait
   aucun effet — le nœud n'est pas apprenable — et c'est exactement ce qui le
   rendait dangereux : personne ne l'aurait vu avant l'ouverture de la fusion, et
   il aurait alors été lu comme une décision.*

7. **La grille par palier de GAME_MATERIA §2.3 ne connaît que les PM.** Elle
   donne un `energy_cost` de 10 à 30 selon le palier — ce qui est la grille des
   **sorts**. Une matéria de technique ne coûte pas de PM : elle coûte une
   **reprise** (§2 du canon : « le registre mêlée est le seul dont la ressource
   ne se reporte pas d'un combat au suivant »). Sans seconde ligne, une matéria de
   mêlée dérivée par la grille facturera des PM à un guerrier. *Proposition :
   la grille du §9 bis.2 — reprise 0/1/2/3/4 par palier, qui tombe exactement sur
   le `cooldown: 4` du seul geste livré qui en porte un.*

---

## 11. Ce que ce document ne décide pas

- **Aucune valeur définitive.** Comme tous les nombres de GAME_ARCHETYPES (§0.2),
  les pourcentages ci-dessus sont des repères calculés sur une échelle
  illustrative. Ce qui survivra à la recalibration : la structure des 18 nœuds,
  les rapports entre paliers, les vérifications du §7. Ce qui sera recalculé :
  tous les effets affichés. Le juge final est `app:balance:simulate` (ARC-17), pas
  la relecture d'un tableau.
- **Les dix-neuf autres arbres.** La procédure du §10.1 du canon suffit ; cet
  exercice montre seulement à quoi ressemble son résultat quand on la suit
  jusqu'au bout. **Les trois registres et les quatre fonctions y sont passés** —
  ce qui reste est de la déclinaison, pas de la découverte.
- **Le nom des gestes.** Les six accords sont désignés par leur **rôle** dans le
  combat, jamais par leur niveau de sort. Les matéria elles-mêmes sont dérivées
  (GAME_MATERIA §2.1), pas écrites ici.
- **La forme de l'écran d'arbre** — §8 bis du canon dit ce qu'il doit *dire* ;
  comment il le montre est du design d'interface.

---

## 12. Le second arbre — le Nécromancien

### 12.0 Pourquoi celui-là

Un second arbre ne sert à rien s'il ne met à l'épreuve que ce que le premier a
déjà montré. Le Nécromancien est choisi pour être **le voisin le plus proche
possible** de l'Assassin — même élément, donc **la même marque** — et pour ne
partager avec lui absolument rien d'autre :

| | Assassin | Nécromancien |
|---|---|---|
| Élément | ténèbres | ténèbres |
| Marque | **Aveuglé** | **Aveuglé** |
| Registre | mêlée | **sorts** |
| Fonction | assaut | **contrôle** |
| Ressource | le tour (reprise) | **les PM** |
| Levier principal | `power` | **`grip`** |
| Teinte | `dodge` | **`mending`** |
| Profil temporel | la pointe | **la montée** |

**C'est le test du voisin dans sa forme la plus dure** : si deux arbres qui
partagent l'élément *et* la marque restent discernables, la grammaire tient. Il
apporte en plus trois choses qu'aucun exercice du canon n'avait écrites dans un
arbre concret : la fonction **contrôle** en entier, le **familier** (la forme de
geste la plus discutée du §13), et une fourche qui oppose **le solo au donjon**.

Son entrée au catalogue public est déjà écrite : *« À faire servir ce qui est
mort, et à payer ce que cela coûte. »* · Équipe : *« Bâtons, grimoires et
tissu. »*

### 12.1 L'identité

| | |
|---|---|
| **Triplet** | ténèbres × **sorts** × **contrôle** |
| **Promesse** | *Je décide de qui joue et quand — et ce que je pose continue sans moi.* |
| **Coût structurel** | La mise en place : ses trois premiers tours ne tuent rien, et il n'a pas de plan de secours offensif |
| **Profil temporel** | **La montée** — inoffensif au tour 1, il possède le combat au tour 6 |
| **Sa faiblesse** | Ce qui meurt vite (le contrôle n'a pas le temps d'exister) et ce qui résiste aux statuts |
| **Sa marque** | **Aveuglé**, comme l'Assassin — mais il la **prolonge** là où l'autre la **consomme** |
| **Sa palette** (contrôle) | **`grip`**, `hit`, `thrift`, `tempo`, `pierce` |
| **Sa teinte** | **`mending`** — *ce qu'il prend, il le garde* : ses drains rendent des PV, et c'est la seule façon dont il survit |
| **Sa famille d'arme** | **bâton** — canalise, frappe mal |
| **Sa ligne d'armure** | **tissu** — il ne survit pas, il fait en sorte que rien ne l'atteigne |

### 12.2 Les dix-huit nœuds

| Palier | Coût | Nœud | Nature | Effet | pb | Exige |
|---|---:|---|---|---|---:|---|
| Entrée | 0 | Accord : **Voile de cendre** | accord | `entrave`·`une cible`· **applique Aveuglé, 2 tours** | — | — |
| Entrée | 0 | Accord : **Drain de vie** | accord | `dégât`+`soin`·`une cible` | — | — |
| 1 | 10 | **Œil mort** | passif | `hit` +1,5 pt | 3 | Voile de cendre |
| 1 | 10 | **Souffle court** | passif | `tempo` +3 % | 3 | Drain de vie |
| 1 | 10 | Accord : **Malédiction** | accord | `entrave`·`une cible`· dégâts sur la durée | — | Voile de cendre |
| 1 | 10 | *Port* : bâton, échelon 2 | port | — | 0 | échelon 1 |
| 2 | 25 | **Ce qui s'accroche** | passif | `grip` **+7,2 %** | 6 | Œil mort |
| 2 | 25 | **Économie du geste** | passif | `thrift` **−5 %** *en tissu* — escalier, −1 %/pièce | 6 | Souffle court |
| 2 | 25 | Accord : **Pulsation cauchemardesque** | accord | `entrave`·`plusieurs cibles` | — | Malédiction |
| 2 | 25 | *Port* : bâton, échelon 3 | port | — | 0 | échelon 2 |
| 3 · **Linceul** | 50 | **Rien ne passe** | passif | `pierce` +6,3 pt | 9 | Ce qui s'accroche |
| 3 · **Linceul** | 50 | **Devancer** | passif | `tempo` +9 % | 9 | Économie du geste |
| 3 · **Linceul** | 50 | Accord : **Linceul** | accord | `entrave`·`une cible`· **longue durée** | — | Pulsation |
| 3 · **Veillée** | 50 | **Ce qu'il prend** | passif | `mending` **+12,6 %** *main gauche libre* *(teinte)* | 9 | Ce qui s'accroche |
| 3 · **Veillée** | 50 | **Longue patience** | passif | `thrift` **−5,4 %** | 9 | Économie du geste |
| 3 · **Veillée** | 50 | Accord : **Serviteur d'ossements** | accord | `dégât`·`une cible`· **forme : familier** *(dépôt offensif)* | — | Pulsation |
| **Capstone** | 100 | **Ce qui ne lâche pas** | capstone | `grip` **+19,6 %** *contre une cible qui subit un de vos statuts* | 14 | **l'accord de branche** |
| *Dormant* | *150* | *Accord d'hybride (ténèbres)* | dormant | — | — | rien |

**18 nœuds écrits · 15 apprenables · 390 points · 50 pb par branche.**

### 12.3 Ce que la fourche oppose — le solo contre le donjon

C'est la forme forte de la règle 6 du §6.1 bis, et elle tombe naturellement ici.

***Le Linceul*** tient **l'ennemi**, et il tient seul. Il perce les résistances,
il joue avant l'autre, et son accord immobilise une cible longtemps. C'est
l'archétype de contrôle classique : *rien ne me touche parce que rien ne joue.*
En duel, sa valeur est exactement celle qu'a mesurée le §9 quinquies — une
entrave ne vole un tour que si elle en vole **deux**, d'où sa durée.

***La Veillée*** tient **la durée**, et elle est faite pour le donjon
semi-synchrone. Son accord est un **familier** : un dépôt offensif qui frappe à
chaque tour de la rencontre pendant N tours, **y compris les tours où son
invocateur n'est pas connecté**. C'est la réponse littérale au défaut mesuré au
§13.1 du canon — *le tour d'un absent ne produit qu'une attaque de base*.

> **Le familier est traité comme un dépôt, jamais comme un acteur** (arbitrage du
> 2026-08-01) : retirez-lui le ciblage et il ne reste qu'une chose qui frappe
> chaque tour pendant une durée. On économise un acteur, une IA et une cible, et
> on garde tout ce qui comptait. **Sa valeur totale vaut ~1 tour d'attaque** — la
> correction 21 : *un dépôt offensif ne dépasse jamais un tour d'attaque par tour
> investi*, quand un dépôt défensif peut valoir davantage parce que la barre de
> vie de sa cible l'écrête toute seule.

Les deux branches ne partagent aucun levier (`pierce`/`tempo` contre
`mending`/`thrift`), chacune ouvre son geste, et la teinte `mending` n'existe que
dans la Veillée : *le nécromancien qui joue seul ne se soigne pas, il empêche.*

### 12.4 Les vérifications

| Invariant | Mesure |
|---|---|
| Budget = 50 pb | Linceul : 3+3+6+6+9+9+14 = **50** ✔ · Veillée : idem **50** ✔ |
| Plafonds par levier | `grip` 6+14 = **20/20** · `hit` 3/10 · `tempo` 12/12 *(Linceul)* · `thrift` 6+9 = **15/15** *(Veillée)* · `pierce` 9/12 · `mending` 9/20 ✔ |
| Palette ≥ 40, hors palette ≤ 10 | Linceul : **50** en palette ✔ · Veillée : 41 en palette, **9** hors (`mending` seul) ✔ |
| Triplet unique | ténèbres × sorts × contrôle : le seul ✔ |
| Intentions du contrôle : ≥ 2 `entrave`, ≥ 1 `dégât` | **4** entraves (Voile, Malédiction, Pulsation, Linceul), **2** dégâts ✔ |
| ≥ 1 accord non-`dégât` · ≥ 1 accord exclusif | 4 entraves ✔ · le Serviteur d'ossements ✔ |
| Un accord d'entrée applique la marque | Voile de cendre → Aveuglé ✔ |
| Capstone atteignable au tour 2 avec le kit gratuit | Voile de cendre est gratuit ✔ |
| ≥ 2 passifs sans condition | Linceul : **4** · Veillée : **3** ✔ |
| Branches sans levier commun | {`pierce`,`tempo`} ∩ {`mending`,`thrift`} = ∅ ✔ |
| Aucune entrave d'un seul tour | Voile 2 tours · Malédiction sur la durée · Linceul longue durée ✔ *(§5.1 rectifié)* |
| Test de l'arbre nu | *précision, durée des statuts, perce-résistance, initiative, économie de PM* → on lit un contrôleur sans avoir vu un accord ✔ |

### 12.5 Les six matéria

Le contraste avec l'Assassin est net : **quatre gestes sur six existent déjà et
n'ont rien à changer** — ce sont des sorts, et le Nécromancien est un arbre de
sorts. C'est exactement ce que GAME_MATERIA annonçait (« il ne manque que l'objet
qui les porte ») ; l'exception était le registre mêlée, pas la règle.

| # | Rôle | Geste | Niv. | Slug | Rareté | Prix | PM | État |
|---|---|---|---:|---|---|---:|---:|---|
| 1 | l'entrave d'entrée · la marque | Voile de cendre (`ash-veil`) | 2 | `m2-ash-veil` | Rare | 180 | 15 | **créer** |
| 2 | le plan B offensif | Drain de vie (`soul-drain`) | 2 | `m2-soul-drain` | Rare | 180 | 15 | existe ✔ |
| 3 | l'entrave sur la durée | Malédiction (`plague-strike`) | 3 | `m3-plague-strike` | Epic | 280 | 20 | existe ✔ |
| 4 | l'entrave de zone | Pulsation (`nightmare-pulse`) | 3 | `m3-nightmare-pulse` | Epic | 280 | 20 | existe ✔ |
| 5 | branche Linceul | Linceul (`shroud`) | 4 | `m4-shroud` | Legendary | 320 | 25 | **créer** |
| 6 | branche Veillée · **familier** | Serviteur d'ossements (`bone-servant`) | 3 | `m3-bone-servant` | Epic | 280 | 20 | **créer** |

Trois remarques qui comptent :

- **`nightmare-pulse` applique `paralysis`, pas Aveuglé** — et c'est légitime :
  une marque est ce que l'**élément** applique, pas ce que tout geste applique.
  Un arbre de contrôle pose des statuts variés ; sa marque reste sa signature.
- **`m2-ash-veil` est à créer, et c'est le même besoin que chez l'Assassin** :
  il faut un geste d'entrée qui applique la marque de l'élément, et **aucun des
  deux arbres de ténèbres n'en a un**. Le statut `blind` est le prérequis commun.
- **Le Serviteur d'ossements est le seul geste du jeu dont la valeur dépend de
  la présence du joueur** (≈ 1 tour d'attaque s'il joue, +56 % sur six tours
  d'absence). C'est le premier accord dont l'équilibrage se mesure en **taux de
  connexion**, pas en tours.

### 12.6 Le test du voisin, chiffré

Deux arbres, même élément, même marque. Ce qu'un joueur voit :

| | Assassin | Nécromancien |
|---|---|---|
| Tour 1 | Embuscade — **le pic de dégâts de la rencontre** | Voile de cendre — **zéro dégât** |
| Ce qu'il fait d'Aveuglé | la **consomme** : son capstone donne +19,6 % de dégâts contre une cible aveuglée | la **prolonge** : `grip` +19,6 % de durée, et son capstone récompense la cible *encore* sous statut |
| Ce qu'il craint | le combat qui dure | le combat qui finit trop vite |
| Ce qu'il paie | des PV — il est au contact | des PM — 4 gestes sur 6 en coûtent |
| En donjon | il frappe, et son tour d'absence ne produit rien | il **dépose** — le familier joue les tours où il n'est pas là |
| Ses six gestes | 5 `dégât`, 1 `protection` | 4 `entrave`, 2 `dégât` |

**Ils ne se marchent pas dessus, et ils se complètent sans se ressembler** : la
marque que l'un pose sert à l'autre. Un assassin qui entre dans un combat déjà
tenu par un nécromancien trouve une cible aveuglée, donc son capstone allumé dès
son premier tour. Aucune règle spéciale n'a été écrite pour ça — c'est la
troisième règle des marques (« deux marques différentes coexistent », et *a
fortiori* la même, qui se rafraîchit).

### 12.7 Ce que le second arbre a trouvé

**Écart n° 8 — le levier du capstone ne peut jamais apparaître au palier 3.**
Découvert en écrivant l'arbre : `grip` est le levier principal du contrôle, donc
le candidat naturel de sa fourche *et* de son capstone. Or le capstone en consomme
14, un nœud de palier 3 en vaut 9, et le plafond le plus haut du jeu est 20 :
**14 + 9 = 23 est impossible**. Le levier du capstone ne peut donc apparaître
qu'une fois, au palier 1 (3 pb) ou au palier 2 (6 pb).

> **Conséquence de design, contre-intuitive et vraie pour les 24 arbres : le
> levier principal d'un arbre est presque absent de sa propre fourche.** La
> fourche est faite des leviers *secondaires* de la palette — ce qui est une
> bonne nouvelle déguisée : c'est ce qui empêche les deux branches d'être « le
> même arbre en plus fort », et ce qui les force à différer par leur **nature**
> plutôt que par leur dosage. *Porté au canon en §7.1, corollaire 2.*

**Observation n° 9 — les leviers à plafond bas sont des leviers de bas d'arbre.**
`hit` (10 pb) ne supporte pas deux nœuds si l'un est au palier 3 (3 + 9 = 12 > 10) ;
`critical`, `pierce`, `tempo`, `dodge`, `wind`, `recovery` (12 pb) n'en supportent
deux que dans la combinaison 3 + 9. **Un levier ne se place donc pas librement :
son plafond dit à quels paliers il a le droit d'exister.** Ce n'est pas un défaut
— c'est la grille qui fait son travail — mais c'est une contrainte que l'auteur
d'un arbre doit connaître avant de poser ses sept passifs, et le §10.1 (étape 3)
ne la mentionne pas.

**Ce que le second arbre a confirmé** : la procédure du §10.1 produit bien un
arbre lisible en cinq étapes, les plafonds attrapent les erreurs avant qu'on les
écrive, et deux arbres du même élément restent discernables **par leurs gestes
avant leurs pourcentages** — ce que le canon affirme depuis le §9 bis et qui se
vérifie ici pour la seconde fois.

---

## 13. Le troisième arbre — l'Artificier

### 13.0 Pourquoi celui-là

Les deux premiers arbres couvrent la mêlée et les sorts. Il reste **le registre
distance**, et avec lui les seules mécaniques du canon qu'aucun arbre n'a encore
posées : le **carquois** (une ressource qui se vide dans la rencontre et se
ramasse après), le levier **`wind` converti** (récupérer la munition tirée) et la
**munition qui remplace l'élément**.

L'Artificier (feu × distance × contrôle) est choisi parce qu'il est **voisin de
trois arbres à la fois**, ce qui rend le test du voisin enfin croisé :

- du **Pyromancien** (patron du canon) par l'élément et la marque ;
- du **Nécromancien** (§12) par la fonction ;
- de l'**Archer** (patron du canon) par le registre.

Il porte en plus une tension qu'aucun autre archétype n'a : **sa fonction allonge
les combats, et sa ressource se vide avec leur longueur.** C'est le seul cas du
jeu où la fonction attaque sa propre ressource — et c'est ce qui décide de tout
son arbre.

Catalogue public : *« À lancer le feu par la mécanique plutôt que par le geste. »*
· Équipe : *« Arbalètes, bombes et cuir. »*

### 13.1 L'identité

| | |
|---|---|
| **Triplet** | feu × **distance** × **contrôle** |
| **Promesse** | *Je prépare le terrain, et le terrain se bat pour moi.* |
| **Coût structurel** | **Le carquois** : ce qu'il allonge, il le paie en munitions — et il n'a rien de gratuit à tirer |
| **Profil temporel** | **Le palier décroissant** — très bon jusqu'au tour 8, puis il compte ses carreaux |
| **Sa faiblesse** | Ce qui arrive au contact avant que le piège ne soit posé |
| **Sa marque** | **Brûlure** — dégâts par tour, et `grip` l'allonge |
| **Sa palette** (contrôle) | **`grip`**, `hit`, `thrift`, `tempo`, `pierce` |
| **Sa teinte** | **`wind`** — la munition récupérée ; *le seul levier qui répare ce que sa fonction lui coûte* |
| **Sa famille d'arme** | **arbalète** — un gros coup, une longue reprise · **elle n'existe pas** (§13.8, écart n° 10) |
| **Sa ligne d'armure** | **cuir** — il n'encaisse pas, il n'est pas là où ça tape |

### 13.2 Les dix-huit nœuds

| Palier | Coût | Nœud | Nature | Effet | pb | Exige |
|---|---:|---|---|---|---:|---|
| Entrée | 0 | Accord : **Piège incendiaire** | accord | `dégât`·`une cible`· **applique Brûlure** · forme : différé | — | — |
| Entrée | 0 | Accord : **Bouclier d'étincelles** | accord | `protection`·`soi`· dépôt court | — | — |
| 1 | 10 | **Mèche calibrée** | passif | `grip` +3,6 % | 3 | Piège incendiaire |
| 1 | 10 | **Lunette** | passif | `hit` +1,5 pt | 3 | Bouclier d'étincelles |
| 1 | 10 | Accord : **Mur de feu** | accord | `entrave`·`plusieurs cibles` — *partagé avec le Pyromancien* | — | Piège incendiaire |
| 1 | 10 | *Port* : arbalète, échelon 2 | port | — | 0 | échelon 1 |
| 2 | 25 | **Ce qui couve** | passif | `grip` **+7,2 %** | 6 | Mèche calibrée |
| 2 | 25 | **Ligne de tir** | passif | `hit` +3 pt | 6 | Lunette |
| 2 | 25 | Accord : **Nappe de poix** | accord | `entrave`·`plusieurs cibles`· **dépôt** : le terrain reste, qui y agit brûle | — | Mur de feu |
| 2 | 25 | *Port* : arbalète, échelon 3 | port | — | 0 | échelon 2 |
| 3 · **Mèche courte** | 50 | **Tout de suite** | passif | `grip` +10,8 % | 9 | Ce qui couve |
| 3 · **Mèche courte** | 50 | **Deux crans d'avance** | passif | `tempo` +9 % | 9 | Ligne de tir |
| 3 · **Mèche courte** | 50 | Accord : **Détonateur** | accord | `dégât`·`une cible`· **consomme** la Brûlure pour un coup unique | — | Nappe de poix |
| 3 · **Réserve** | 50 | **Rien ne se perd** | passif | `wind` **+13,5 %** de récupération de munition *(teinte)* | 9 | Ce qui couve |
| 3 · **Réserve** | 50 | **Pointes durcies** | passif | `pierce` +6,3 pt | 9 | Ligne de tir |
| 3 · **Réserve** | 50 | Accord : **Tir couvrant** | accord | `entrave`·`plusieurs cibles`· **dépôt long** | — | Nappe de poix |
| **Capstone** | 100 | **Économie de guerre** | capstone | `thrift` **−11,8 %** de munition *contre une cible qui brûle* | 14 | **l'accord de branche** |
| *Dormant* | *150* | *Accord d'hybride (feu)* | dormant | — | — | rien |

**18 nœuds écrits · 15 apprenables · 390 points · 50 pb par branche.**

> **Le capstone applique le corollaire 2 à la lettre** (§7.1) : `thrift` plafonne
> à 15 pb, le capstone en consomme 14 — **il ne reste rien**. `thrift` n'apparaît
> donc nulle part ailleurs dans l'arbre, et c'est visible à l'œil nu dans le
> tableau. Le levier principal du sommet est absent des quinze autres nœuds.

### 13.3 Le carquois — ce que le registre distance change

Trois différences structurelles, et aucune n'est un chiffre :

1. **La ressource se vide dans la rencontre et ne revient pas au tour.** Le mage
   régénère, le guerrier attend sa reprise ; l'artificier compte. Sa ressource
   borne donc la **longueur** d'une rencontre — exactement la dimension que sa
   fonction augmente.
2. **`wind` et `thrift` changent de sens.** En distance, `wind` est la **chance
   de récupérer la munition tirée** (+1,5 % par pb) et `thrift` la **consommation
   par geste**. Un levier, une intention, trois lectures.
3. **L'élément vient de la matéria ; le carquois le remplace.** L'Artificier peut
   donc changer de contre-jeu entre deux combats sans refaire son build — et il
   est le seul. C'est sa souplesse, et elle s'achète une fois (un carquois par
   élément), jamais au coup par coup : **aucun archétype ne porte un coût
   récurrent en gils que les autres n'ont pas** (correction 17).

> **La tension qui définit l'archétype.** Le contrôle *fait durer* ; le carquois
> *se vide en durant*. Un artificier qui joue parfaitement sa fonction crée
> précisément la situation où sa ressource manque. C'est son coût structurel — et
> ça explique pourquoi sa teinte est `wind` plutôt qu'un levier de puissance : la
> teinte, ici, ne l'assaisonne pas, **elle le répare**.

### 13.4 La fourche — deux réponses à la même tension

***La Mèche courte*** refuse la tension : elle raccourcit. Plus de durée de
statut, plus d'initiative, et un accord qui **consomme** la Brûlure pour un coup
immédiat. Elle joue le contrôle comme une mise à mort : *le terrain sert à finir
plus vite, pas à durer.*

***La Réserve*** l'accepte et s'équipe pour : la munition revient, les carreaux
percent, et son accord est un **dépôt long** qui court sur les tours des autres —
donc utile en donjon semi-synchrone, où l'on n'est pas là quand ça se joue.

C'est encore une fourche qui oppose **deux contextes**, mais par un autre biais
que le Nécromancien : celui-ci opposait le solo au groupe ; celle-ci oppose **le
combat court au combat long**. Aucun levier commun (`grip`/`tempo` contre
`wind`/`pierce`), un accord chacune, et la teinte n'existe que dans la Réserve.

### 13.5 Les vérifications

| Invariant | Mesure |
|---|---|
| Budget = 50 pb | Mèche courte : 3+3+6+6+9+9+14 = **50** ✔ · Réserve : idem ✔ |
| Plafonds | `grip` 18/20 *(Mèche)* · `hit` 9/10 · `tempo` 9/12 · `wind` 9/12 · `pierce` 9/12 · `thrift` **14/15** ✔ |
| Palette ≥ 40, hors palette ≤ 10 | Mèche : **50** en palette ✔ · Réserve : 41 en palette, **9** hors (`wind`) ✔ |
| Levier du capstone absent du palier 3 | `thrift` : 14 au capstone, **0 ailleurs** ✔ *(corollaire 2)* |
| Triplet unique | feu × distance × contrôle : le seul ✔ |
| Intentions : ≥ 2 `entrave`, ≥ 1 `dégât` | **3** entraves (Mur, Nappe, Tir couvrant), **2** dégâts ✔ |
| ≥ 1 non-`dégât` · ≥ 1 exclusif | Bouclier d'étincelles ✔ · Détonateur ✔ |
| Accord d'entrée qui marque | Piège incendiaire applique `burn` — **déjà en base** ✔ |
| ≥ 2 passifs sans condition | **7 sur 7** — aucun conditionnel ✔ *(voir ci-dessous)* |
| Branches sans levier commun | {`grip`,`tempo`} ∩ {`wind`,`pierce`} = ∅ ✔ |
| Toute `protection` porte une durée | Bouclier d'étincelles = dépôt court ✔ |
| Échelons de port | ❌ **la famille arbalète n'existe pas** — §13.8 |

> **Un arbre sans aucun passif conditionnel est légal** (le garde-fou impose un
> minimum de deux nœuds nus, pas un maximum), mais c'est un signal : l'Artificier
> ne donne aucune raison de porter une pièce plutôt qu'une autre. **C'est le prix
> de sa famille d'arme manquante** — une condition doit être satisfaisable par ce
> que l'arbre débloque lui-même, et il ne débloque rien. Le jour où l'arbalète
> existe, deux de ses passifs devraient se conditionner à elle.

### 13.6 Les six matéria

| # | Rôle | Geste | Niv. | Slug | Rareté | Prix | Munitions | État |
|---|---|---|---:|---|---|---:|---:|---|
| 1 | l'entrée · la marque | Piège incendiaire (`fire-trap`) | 2 | `m2-fire-trap` | Rare | 180 | 2 | existe ✔ |
| 2 | le plan B | Bouclier d'étincelles (`ember-shield`) | 2 | `m2-ember-shield` | Rare | 180 | 0 | existe ✔ |
| 3 | l'entrave de zone | Mur de feu (`fire-wall`) | 2 | `m2-fire-wall` | Rare | 180 | 3 | existe ✔ · `domain: null` |
| 4 | l'entrave déposée | Nappe de poix (`tar-slick`) | 3 | `m3-tar-slick` | Epic | 280 | 3 | **créer** |
| 5 | branche Mèche courte | Détonateur (`detonator`) | 4 | `m4-detonator` | Legendary | 320 | 4 | **créer** |
| 6 | branche Réserve | Tir couvrant (`suppressing-fire`) | 3 | `m3-suppressing-fire` | Epic | 280 | 4 | **créer** |

**Trois existent, trois sont à créer** — et le ratio se lit : les gestes livrés
sont ceux que l'arbre partage avec la tradition du feu ; ceux à écrire sont ceux
qui font de lui un artificier plutôt qu'un mage avec une arbalète.

`m2-fire-wall` porte **`domain: null`**, et c'est la règle qui s'applique, pas une
exception : une matéria ouverte par plusieurs arbres n'appartient à aucun
(GAME_MATERIA §2.1). Le Pyromancien et l'Artificier l'ouvrent tous les deux — l'un
au palier 1, l'autre au palier 1 : **le même geste, deux raisons de le vouloir.**

> **Ce que la colonne « Munitions » suppose** : que `Spell` porte un coût en
> munitions comme il porte un `energyCost` et un `cooldown`. C'est la troisième
> ligne de la grille par palier (§9 bis.2), et elle manque au même titre que la
> reprise. Proposition symétrique : **2 / 2 / 3 / 4 / 5** par palier m1→m5.

### 13.7 Le test du voisin, croisé

| | Pyromancien *(feu, sorts, assaut)* | **Artificier** | Nécromancien *(ténèbres, sorts, contrôle)* | Archer *(air, distance, assaut)* |
|---|---|---|---|---|
| Partage avec lui | élément + marque | — | fonction | registre |
| Levier principal | `power` | **`grip`** | `grip` | `power` |
| Capstone | `power` | **`thrift`** | `grip` | `power` |
| Sa ressource | PM | **munitions** | PM | munitions |
| Ce qu'il fait de la Brûlure | la **pose et frappe dessus** | la **fait durer**, puis la **consomme** *(Mèche courte)* | *(autre marque)* | — |
| Son tour 1 | le pic de dégâts | **un piège posé, zéro dégât** | une entrave | un tir |

**Les deux voisins les plus dangereux sont traités différemment.** Face au
**Nécromancien** — même fonction, même levier principal — la séparation ne vient
pas des passifs mais du **capstone** (`thrift` contre `grip`) et de la ressource :
l'un économise ce qu'il tire, l'autre allonge ce qu'il pose. Face au
**Pyromancien** — même élément, même marque — elle vient de ce qu'ils *font* de
la Brûlure : le Pyromancien frappe une cible qui brûle, l'Artificier la maintient
en feu et, s'il a pris la Mèche courte, **l'éteint d'un coup pour encaisser la
mise**. Trois arbres, une seule marque, trois verbes : *frapper dessus, la faire
durer, la dépenser.*

### 13.8 Ce que le troisième arbre a trouvé

**Écart n° 10 — l'arbalète n'existait pas, et le catalogue public la promettait.**
`equipment_ports.yaml` déclare six familles (hache, épée, dague, lance, arc,
bâton) ; `ItemFixtures` ne contient **aucun** objet `crossbow`. Or le catalogue
public annonce à l'Artificier « Arbalètes, bombes et cuir », et le canon lui donne
un trait propre (« un gros coup, un long temps de reprise », §2.2). Conséquences :
l'arbre **ne peut pas porter ses deux échelons de port** (2 des 15 nœuds), et
aucun de ses passifs ne peut être conditionné à une famille d'arme.

> **Corrigé le 2026-08-01.** La famille `crossbow` existe : enseignée par
> **artificier, ingénieur et chasseur** — trois éléments différents (feu, métal,
> bête), ce que `EquipmentPortCatalog` exige déjà pour refuser qu'une arme impose
> un élément. Trois objets (`t1/t2/t3-crossbow`, effet `damage` là où l'arc porte
> `precision_boost` — *la frappe contre la cadence*), deux échelons
> (`artificer_weapon_t2/t3`, **sans statistique**, conformément à l'écart n° 5),
> et l'échelon 1 `port_crossbow` gratuit, généré depuis le catalogue.
>
> **Ce qui n'a délibérément pas changé** : le choix d'arme de l'acte I
> (`QuestFixtures`) garde ses six voies. Une septième alourdirait le tunnel
> d'entrée pour proposer l'archétype le moins lisible du jour 1 — et elle est
> inutile : le parchemin de l'artificier livre `port_crossbow` comme n'importe
> quel autre arbre livre le sien.

**Écart n° 11 — le capstone contredit la règle de fréquence.** Le §7 fixe le
capstone à ×2,0 d'amplitude « parce que sa condition de combat peut manquer ».
Mais le §4.3, corrigé au §9 bis, dit qu'**une condition de combat vraie plus des
deux tiers du temps se paie ×1,4**. Or la condition canonique de l'assaut et du
contrôle est *la marque de son propre élément*, posée par un accord **gratuit**
dès le tour 1 : elle est vraie du tour 2 à la fin de la rencontre.

| | Ancienne lecture | Lecture actée |
|---|---:|---:|
| Capstone de l'Assassin | `power` +28 % | `power` **+19,6 %** |
| Capstone du Nécromancien | `grip` +28 % | `grip` **+19,6 %** |
| Capstone du Pyromancien *(canon)* | `power` +28 % | `power` **+19,6 %** |
| Capstone de l'Artificier | `thrift` −16,8 % | `thrift` **−11,8 %** |
| Capstone du Guérisseur *(entretien)* | `mending` +28 % | `mending` +28 % — inchangé |

**Les capstones d'assaut, de contrôle et d'encaisse étaient donc surévalués de
43 %** — un écart plus grand que tout ce que la grille des leviers arbitre par
ailleurs.

> **Tranché le 2026-08-01** (GAME_ARCHETYPES §7, décision 23) : **le capstone
> n'échappe pas à la règle de fréquence.** Trois fonctions sur quatre voient leur
> sommet passer de +28 % à **+19,6 %** ; seul l'entretien, dont la condition
> canonique est réellement intermittente (« le combat dure, ou la cible est sous
> un seuil »), garde ×2,0.
>
> **La compensation ne se prend pas dans le multiplicateur, elle se prend dans la
> condition** : un arbre qui veut l'amplitude ×2,0 choisit une condition
> véritablement intermittente parmi les trois que le vocabulaire fermé offre. Ce
> n'est pas une remise, c'est un choix d'archétype — un sommet qui s'allume à la
> fin d'un combat récompense une autre façon de jouer que celui qui s'allume au
> tour 2.
>
> Les capstones des trois arbres de ce document sont recalculés en conséquence.
> Ce que la décision ne change pas : un capstone toujours vrai n'était pas un
> passif conditionnel, c'était **un passif plat de 28 % payé 14 pb** — exactement
> ce que le budget existe pour interdire.

**Observation n° 12 — un archétype dont la fonction attaque sa propre ressource.**
Le contrôle allonge les rencontres ; le carquois se vide en durant. Ce n'est pas un
défaut à corriger — c'est ce qui donne à l'Artificier une identité qu'aucun autre
n'a — mais ça a une conséquence sur la fourche : la branche **Mèche courte** (sans
`wind`) est structurellement plus exposée au combat long que la **Réserve**. Les
deux branches ne sont donc pas égales *dans tous les contenus* : l'une est
meilleure en zone, l'autre en donjon et contre les boss. C'est acceptable — le
canon demande que deux branches soient également bonnes, pas qu'elles le soient
dans les mêmes rencontres — mais c'est précisément le genre d'asymétrie que le
simulateur doit vérifier plutôt que l'auteur affirmer.

---

## 14. La paire de la terre — le Défenseur et le Gardien

### 14.0 Pourquoi les deux ensemble

Il restait les deux fonctions défensives — **encaisse** et **entretien**. Elles
sont écrites ici en paire, et sur **la même case élément × registre** : terre ×
mêlée. C'est le test du voisin dans sa forme extrême, plus dure encore que
l'Assassin et le Nécromancien, qui différaient au moins par le registre :

> **Défenseur et Gardien ont le même élément, le même registre, la même marque, la
> même famille d'arme et la même ligne d'armure. Seule leur fonction diffère.**

C'est exactement le cas que le §1 du canon invoque pour justifier le troisième
axe (« sans lui, la seule différenciation possible est le chiffre »). Si la paire
tient, l'axe est démontré ; si elle ne tient pas, il ne sert à rien.

La paire apporte aussi trois mécaniques que les trois premiers arbres n'avaient
pas : **l'aggro bornée** (décision 13.4), le **dépôt de protection de groupe** (la
loi du §7 bis, jamais posée dans un arbre concret) et la confrontation directe avec
la thèse « *la mitigation d'un tank vient de son armure, pas de son arbre* ».

**Marque commune** : **Alourdi** (terre — *temps de reprise allongé, punit les
rotations de techniques*). **Famille** : lance. **Ligne** : plaque.

### 14.1 Le Défenseur — terre × mêlée × **encaisse**

| | |
|---|---|
| **Promesse** | *Rien ne me casse, et ce qui me frappe ne frappe personne d'autre.* |
| **Coût structurel** | Le plafond : il ne fera jamais le gros chiffre, et ses combats sont les plus longs du jeu |
| **Profil temporel** | **Le plateau** — la même chose au tour 1 et au tour 20 |
| **Sa faiblesse** | Ce qui l'ignore : une rencontre qui frappe les autres pendant qu'il attend |
| **Teinte** | **`grip`** — l'Alourdi qui dure : *ce qui est lent ne le déborde pas* |

| Palier | Coût | Nœud | Nature | Effet | pb |
|---|---:|---|---|---|---:|
| Entrée | 0 | Accord : **Parade** (`rock-armor`) | accord | `protection`·`soi`· dépôt | — |
| Entrée | 0 | Accord : **Pic de terre** (`earth-spike`) | accord | `dégât`·`une cible`· **applique Alourdi** | — |
| 1 | 10 | **Carrure** | passif | `life` +4,5 % | 3 |
| 1 | 10 | **Coup d'œil** | passif | `hit` +1,5 pt | 3 |
| 1 | 10 | Accord : **Provocation** | accord | `entrave`·`une cible`· **transfert d'aggro borné** — *à créer* | — |
| 1 | 10 | *Port* : lance, échelon 2 | port | — | 0 |
| 2 | 25 | **Assise** | passif | `life` +9 % | 6 |
| 2 | 25 | **Tête froide** | passif | `ward` +6 % | 6 |
| 2 | 25 | Accord : **Bouclier partagé** (`shared-shield`) | accord | `protection`·**`le groupe`**· dépôt | — |
| 2 | 25 | *Port* : lance, échelon 3 | port | — | 0 |
| 3 · **le Mur** | 50 | **Fondations** · **Inébranlable** · Accord : **Bastion** (`bastion`) | passifs + accord | `life` +13,5 % · `ward` +9 % · `protection`·`le groupe`· dépôt long | 9+9 |
| 3 · **la Herse** | 50 | **Terrain lourd** · **Pas de côté** · Accord : **Pétrification** (`petrification`) | passifs + accord | `grip` +10,8 % *(teinte)* · `dodge` +3,15 pt · `entrave`·`une cible` | 9+9 |
| **Capstone** | 100 | **Le coup reçu durcit la garde** | capstone | `guard` **−11,76 %** de dégâts subis *si vous avez encaissé au tour précédent* (14 × 1,4) | 14 |
| *Dormant* | *150* | *Accord d'hybride (terre)* | dormant | — | — |

> **Le fait le plus instructif de tout ce document tient dans une ligne.**
> `guard` plafonne à **15 pb** et le capstone en consomme **14** : le Défenseur ne
> peut acheter son propre levier principal **qu'une seule fois, à son sommet**, et
> il en tire **−11,76 %** de dégâts subis. Sa mitigation réelle — **40 %** — vient
> de sa plaque. Le canon l'affirmait (§13.4 : « la mitigation d'un tank vient de
> son armure, pas de son arbre ») ; l'arbre le démontre, chiffre en main.

**Ses six matéria** — quatre existent (`rock-armor`, `earth-spike`,
`shared-shield`, `bastion`, `petrification` : **cinq**), une est à créer :
**Provocation**. C'est le premier arbre à écrire l'**aggro**, et elle suit la
décision 13.4 à la lettre : un geste de menace déplace **au plus la moitié** de la
riposte d'un allié, il n'y a **aucune table de menace cumulée**, et le geste
n'existe pleinement qu'une fois DON-02/03 livrés (une rencontre qui riposte).

### 14.2 Le Gardien — terre × mêlée × **entretien**

| | |
|---|---|
| **Promesse** | *Je ne perds pas le combat que les autres perdent au tour 8 — et je le perds encore moins quand je ne suis pas là.* |
| **Coût structurel** | La lenteur, et un plafond de dégâts bas : il use, il ne tue pas |
| **Profil temporel** | **Le rebond** — plus le combat dure, plus il gagne |
| **Sa faiblesse** | Les combats de trois tours, où provisionner ne sert à rien |
| **Teinte** | **`life`** — *il est la citerne du groupe* : ce qu'il dépose se mesure à ce qu'il peut tenir |

| Palier | Coût | Nœud | Nature | Effet | pb |
|---|---:|---|---|---|---:|
| Entrée | 0 | Accord : **Bouclier terreux** (`earth-shield`) | accord | `protection`+`soin`·`soi`· dépôt | — |
| Entrée | 0 | Accord : **Jet de cailloux** (`stone-throw`) | accord | `dégât`·`une cible`· **applique Alourdi** | — |
| 1 | 10 | **Main calme** | passif | `mending` +3 % | 3 |
| 1 | 10 | **Souffle long** | passif | `recovery` +0,75 % des PV max/tour | 3 |
| 1 | 10 | Accord : **Racines** | accord | `soin`·`soi ou un allié`· **dépôt** — *à créer* | — |
| 1 | 10 | *Port* : lance, échelon 2 | port | — | 0 |
| 2 | 25 | **Geste économe** | passif | `thrift` −3,6 % *(en mêlée : la reprise)* | 6 |
| 2 | 25 | **Sang-froid** | passif | `ward` +6 % | 6 |
| 2 | 25 | Accord : **Bouclier magique** (`stone-skin`) | accord | `protection`·**`le groupe`**· dépôt | — |
| 2 | 25 | *Port* : lance, échelon 3 | port | — | 0 |
| 3 · **la Source** | 50 | **Sourdre** · **Ce qui tient** · Accord : **Sève de pierre** | passifs + accord | `recovery` +2,25 %/tour · `life` +13,5 % *(teinte)* · `soin`·`le groupe`· dépôt 8 tours — *à créer* | 9+9 |
| 3 · **le Rempart** | 50 | **Eaux calmes** · **Longue patience** · Accord : **Rempart de pierre** (`stonewall`) | passifs + accord | `ward` +9 % · `thrift` −5,4 % · `protection`·`le groupe`· dépôt | 9+9 |
| **Capstone** | 100 | **Ce qui dure** | capstone | `mending` **+28 %** *au-delà du 6ᵉ tour de la rencontre* (14 × **2,0**) | 14 |
| *Dormant* | *150* | *Accord d'hybride (terre)* | dormant | — | — |

> **Le Gardien est le seul des cinq arbres de ce document dont le capstone garde
> ×2,0** (décision 23) : sa condition — *le combat dure* — est réellement
> intermittente, elle est fausse dans toutes les rencontres de trois tours. C'est
> la contrepartie exacte de son coût structurel, et elle tombe sans qu'on ait rien
> ajusté : **la fonction dont la promesse est la durée est celle dont le sommet se
> paie en durée.**

> **Depuis l'arbitrage de l'écart n° 13, `wind` lui est ouvert** (chance qu'une
> technique ne parte pas en reprise). L'arbre ci-dessus a été écrit avant, avec
> quatre leviers ; il reste valide — sa branche *le Rempart* pourrait aujourd'hui
> échanger `thrift` contre `wind` et opposer la reprise **raccourcie** à la
> reprise **sautée**. C'est un meilleur arbre, et c'est le premier bénéfice
> concret de la décision.

**Ses quatre accords de groupe sont tous des dépôts** — c'est la loi du §7 bis,
et pour un archétype d'entretien elle n'est pas une contrainte, c'est sa
définition : *il ne soigne pas, il provisionne*. Son soin direct reste le
Bouclier terreux, en portée `soi`. Deux gestes à créer (**Racines**, **Sève de
pierre**), quatre existent.

### 14.3 Les vérifications

| Invariant | Défenseur | Gardien |
|---|---|---|
| Budget = 50 pb | Mur : 3+3+6+6+9+9+14 ✔ · Herse : ✔ | Source : ✔ · Rempart : ✔ |
| Plafonds | `life` 18/20 · `ward` 15/15 · `guard` **14/15** · `dodge` 9/12 · `grip` 9/20 · `hit` 3/10 ✔ | `mending` 17/20 · `recovery` 12/12 · `ward` 15/15 · `thrift` 15/15 · `life` 9/20 ✔ |
| Palette ≥ 40 / hors ≤ 10 | Mur : 50 ✔ · Herse : 41 + `grip` 9 ✔ | Rempart : 50 ✔ · Source : 41 + `life` 9 ✔ |
| Levier du capstone hors du palier 3 | `guard` : nulle part ailleurs ✔ | `mending` : 3 pb au palier 1 ✔ |
| Intentions | ≥ 2 `protection` dont **2** de portée `le groupe` ✔ | ≥ 2 `soin`/`protection` dont **3** de portée `le groupe` ✔ |
| Accord d'entrée qui marque | Pic de terre → Alourdi ✔ | Jet de cailloux → Alourdi ✔ |
| Branches sans levier commun | {`life`,`ward`} ∩ {`grip`,`dodge`} = ∅ ✔ | {`recovery`,`life`} ∩ {`ward`,`thrift`} = ∅ ✔ |
| Toute `protection` porte une durée | Parade, Bouclier partagé, Bastion : dépôts ✔ | les quatre : dépôts ✔ |
| Triplet unique | terre × mêlée × encaisse ✔ | terre × mêlée × entretien ✔ |

**Le partage de palette est légal et mesuré** : encaisse et entretien ont
**`ward` en commun**, et rien d'autre — la règle en autorise deux.

### 14.4 Ce que la paire démontre

Deux arbres, une seule case, et voici ce qu'un joueur voit :

| | Défenseur | Gardien |
|---|---|---|
| Ce qu'il fait du coup reçu | il le **cherche** (Provocation) et le convertit en garde | il le **répare**, chez lui et chez les autres |
| Son capstone récompense | **d'avoir été frappé** (×1,4) | **d'avoir tenu longtemps** (×2,0) |
| Ses gestes de groupe | 2 protections déposées | 3 soins et protections déposés |
| Ce qu'il fait d'Alourdi | le **prolonge** (`grip`, branche la Herse) | le pose et l'oublie — c'est son seul geste offensif |
| Sa contribution en donjon | il **prend la place** d'un allié dans la riposte | il **provisionne** les tours où personne n'est là |
| Ce qui le tue | une rencontre qui l'ignore | une rencontre trop courte |

**Aucun de leurs leviers principaux n'est le même, aucune de leurs promesses n'est
interchangeable, et pourtant ils portent la même armure et la même lance.** C'est
la démonstration que le troisième axe cherchait : *la fonction distingue deux
voisins de case*, sans qu'un seul chiffre ait eu à faire le travail.

### 14.5 Ce que la paire a trouvé

**Écart n° 13 — `wind` n'avait aucune lecture en registre mêlée.** La note 1 du §4
donnait deux conversions : PM pour les sorts, chance de récupérer la munition pour
la distance. **Rien pour la mêlée** — et pour cause, sa ressource ne « régénère »
pas, elle s'écoule avec les tours. Or `wind` figure dans la palette de
l'**entretien**, et le Gardien est *entretien × mêlée* : un levier sur cinq lui
était inaccessible, comme à **tout arbre de mêlée** qui aurait voulu `wind` en
teinte — huit des vingt-quatre.

> **Tranché le 2026-08-01** (§4, note 1) : en mêlée, `wind` est **la chance qu'une
> technique ne parte pas en reprise**, à **+1,0 pt par pb** — le geste reste
> disponible au tour suivant. La forme est imposée par le critère d'admission d'un
> levier : raccourcir la reprise est déjà ce que fait `thrift` en mêlée, donc
> `wind` fait ce qu'il fait partout ailleurs — rendre de la ressource — sous la
> seule forme que la mêlée autorise. Continu et fiable d'un côté, binaire et
> volatil de l'autre : **le couple `guard` / `dodge` transposé sur la ressource**.

**Écart n° 14 — la fonction encaisse ne peut pas acheter son levier principal.**
`guard` plafonne à 15 pb, le capstone en consomme 14 : il reste **1 pb**,
c'est-à-dire rien (le nœud le plus modeste vaut 3). **Un arbre d'encaisse achète
`guard` une fois ou jamais.** Les trois arbres d'encaisse du jeu (Défenseur,
Soldat, Paladin) sont donc structurellement dans ce cas.

Ce n'est **pas** un défaut à corriger, et c'est ce qui le rend intéressant : le
canon a déjà tranché que la mitigation vient de l'armure (plaque 40 %, §2.2), et
que `guard` a le plafond le plus bas parce que son efficacité est hyperbolique.
Les deux décisions se rejoignent ici.

> **Tranché le 2026-08-01** (§5.0, décision 24) : **la palette effective se
> calcule, elle ne se lit pas** — ce qui reste achetable hors du capstone vaut *le
> plafond du levier principal moins 14*, soit 6 pb pour trois fonctions et **zéro**
> pour l'encaisse. Un arbre d'encaisse répartit donc quatre leviers (`dodge`,
> `life`, `ward`, `hit`) et pose son sommet en `guard`. Trois conséquences
> écrites au canon : ce capstone est le seul endroit du jeu où `guard` existe côté
> arbre, un pacte majeur sur `guard` est impossible (19 > 15), et **la fourche
> d'un arbre d'encaisse oppose naturellement `dodge` à `life`** — éviter ou
> absorber, la nuance même du cuir et de la plaque. La contrainte a produit la
> meilleure fourche possible pour cette fonction sans qu'on ait eu à la choisir.

**Observation n° 15 — l'aggro entre dans un arbre pour la première fois.** La
Provocation du Défenseur est le premier geste écrit qui applique la décision 13.4 :
déplacer **au plus la moitié** de la riposte d'un allié, sans aucune table de
menace cumulée. Elle est inerte tant que DON-02/03 n'ont pas donné une riposte aux
rencontres de donjon — ce qui en fait, avec les matéria de technique, la seconde
dépendance de contenu qu'un arbre pose au moteur.
