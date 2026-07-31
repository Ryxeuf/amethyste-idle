# Archétypes de combat — la grammaire des arbres, et comment les équilibrer

> **Statut : proposition instruite**, 2026-07-31. Source de vérité proposée pour la
> **conception et l'équilibrage des arbres de combat**. Ce document ne décrit pas
> l'existant : il définit ce qu'un arbre doit être, à partir des règles déjà actées.
>
> En amont, et jamais contredit : **règle 9** (les compétences sont passives —
> jamais un sort actif), **règle 10** (les actions de combat viennent
> exclusivement de la matéria, plus l'attaque de base de l'arme), **règle 6** (pas
> de niveau global), [GAME_DOMAINS.md](GAME_DOMAINS.md) (doctrine des trois
> couches, double borne élément × registre, équipement-build, gabarit 15 nœuds),
> [GAME_MATERIA.md](GAME_MATERIA.md) (la chaîne compétence → matéria → sort, les
> 2 accords gratuits par arbre), [GAME_PROGRESSION.md](GAME_PROGRESSION.md) (budget
> d'énergie, les quatre actes), [GAME_WORLD.md](GAME_WORLD.md) §2.1/§2.2 (la roue
> des huit flux).
>
> Décliné en jalons : [roadmap/PLAN_ARCHETYPES.md](roadmap/PLAN_ARCHETYPES.md).

---

## 0. Le problème que ce document résout

Un arbre de domaine ne peut contenir que **trois choses** — et c'est acté :

1. un **accord** de matéria (`actions.materia.unlock`) ;
2. un **passif** (des statistiques) ;
3. un **accès** (le droit de porter une pièce, un outil, un emplacement).

**Aucune action n'y vit.** Les gestes sont dans la matéria. Il en découle une
conséquence que rien, dans la documentation existante, ne tire :

> **L'archétype n'est pas dans l'arbre. Il est dans le couple (arbre, matéria que
> l'arbre ouvre).** L'arbre est la *grammaire* — ce qui qualifie un geste ; la
> matéria est le *verbe* — le geste lui-même. Concevoir un arbre isolément, c'est
> écrire une grammaire sans langue.

Et une seconde, plus dure : avec le vocabulaire de passifs actuel — cinq entiers
plats, `damage` / `heal` / `hit` / `critical` / `life` — **on ne peut pas
distinguer deux archétypes**. Un pyromancien et un archer diffèrent alors par la
couleur de leurs matéria, pas par leur façon de jouer. Trois arbres d'eau × sorts
(Hydromancien, Guérisseur, Marémancien) occupent *la même case* de la double
borne : rien, dans le modèle actuel, ne dit en quoi ils ne sont pas le même arbre
peint trois fois.

Ce document ajoute donc le minimum pour que des archétypes existent :
**un troisième axe** (§1), **une ressource par registre** (§2), **un vocabulaire
de leviers** (§4), **un budget de puissance** (§6) et **un protocole de
conception** (§8). Puis il l'éprouve sur quatre arbres écrits en entier (§9) et
range les 24 domaines de combat dans la grille (§10).

### 0.1 Trois constats mesurés, qui commandent les décisions

Mesurés le 2026-07-31 sur les fixtures ; ils ne sont pas des reproches, ils
cadrent l'ampleur.

| Constat | Chiffre | Ce qu'il impose |
|---|---|---|
| **Les passifs plats ne peuvent pas être équilibrés** | Les 253 sorts livrés font **1 à 12 points de dégâts** ; les monstres ont **11 à 3 200 PV**. Un `damage: +1` d'arbre vaut **+50 %** sur la Boule de feu (2 dégâts) et **+8 %** sur le geste de palier 5 (12 dégâts) | Les passifs deviennent des **pourcentages**, et l'échelle s'ancre sur la **durée d'un combat**, jamais sur des valeurs absolues (§4, §6.4) |
| **Les archétypes d'arme n'ont rien à qualifier** | L'attaque de base vaut `3 + variance` et **ne lit aucun passif d'arbre** (DOM-01, assumé) ; **aucune matéria de technique n'existe** (DOM-03, un test l'interdit même) | Un arbre de mêlée ou de distance est aujourd'hui **vide de sens** : ses passifs ne s'appliquent à rien. D'où la décision 1 (§3) |
| **Un arbre coûte presque toute une vie de personnage** | L'arbre de Pyromancie coûte **465 points** ; le plafond global est de **500** (`MAX_TOTAL_SKILL_POINTS`) | Un joueur ne peut pas mener « deux à quatre domaines » (GAME_PROGRESSION §1). L'échelle de coût et le plafond sont à reprendre ensemble (§6.2, §11.2) |

---

## 1. Décision 3 — Les trois axes d'un domaine

Un domaine de combat est aujourd'hui une case **élément × registre**. C'est
insuffisant : la grille a 24 domaines pour 24 cases théoriques, mais les cases
sont mal remplies (trois domaines d'eau × sorts, aucun de métal × sorts). Le
troisième axe résout le problème sans toucher aux deux premiers.

| Axe | Valeurs | Ce qu'il décide | Qui le porte |
|---|---|---|---|
| **Élément** | feu, eau, air, terre, métal, bête, lumière, ténèbres | la **couleur** : quelles matéria, quel contre-jeu (résistances), quelle fiction | `Domain::element` (livré) |
| **Registre** | sorts, mêlée, distance | le **geste** : quel support le porte (tissu / plaque / cuir), et **quelle ressource il coûte** (§2) | `Domain::register` (livré, DOM-01) |
| **Fonction** | assaut, contrôle, entretien, encaisse | le **rôle** : ce que l'arbre fait au combat, donc **quels leviers il a le droit d'acheter** (§5) | `Domain::role` — **à créer** |

> **La fonction est ce qui distingue deux voisins de case.** Hydromancien
> (contrôle), Guérisseur (entretien) et Marémancien (assaut) sont tous eau ×
> sorts : ils ne se ressemblent plus dès lors que leurs palettes de leviers sont
> disjointes. Sans ce troisième axe, la seule différenciation possible est le
> chiffre — et deux arbres qui ne diffèrent que par un chiffre sont un seul arbre
> mal rangé.

**Ce que l'axe ne fait pas** : il n'est pas une classe. Il ne ferme rien, ne
s'affiche nulle part comme un titre, et un joueur mène autant d'arbres qu'il veut
(doctrine des trois couches, GAME_DOMAINS §1). C'est une **contrainte d'auteur**,
lisible par le joueur seulement dans ce que l'arbre lui donne.

---

## 2. Décision 2 — Chaque registre a sa ressource

Trois registres qui coûtent la même chose ne sont qu'un registre décliné en trois
animations. La différence doit être **structurelle** : ce qu'on dépense pour
agir.

| Registre | Ressource dépensée | Propriété du profil | Ce que ça crée |
|---|---|---|---|
| **Sorts** | **PM** — un pool plafonné (`Player.energy`), régénéré au tour | **Le pic, puis la panne.** Fort tant que le pool tient, muet ensuite | Le combat court et cher. Le levier qui compte est l'économie (`thrift`, `wind`) |
| **Mêlée** | **Le tour** — un temps de reprise par technique (`Spell::cooldown`, déjà au modèle) ; **le vrai coût est en PV**, puisqu'on reste au contact | **Le plateau.** Toujours disponible, jamais spectaculaire | Le combat long, et une rotation de gestes plutôt qu'un pool. Le levier qui compte est la survie (`guard`, `life`) |
| **Distance** | **Munitions** — un objet consommable, acheté ou fabriqué | **La cadence, tant qu'on a de quoi.** | Le seul registre au coût **économique** : l'archer dépend d'un artisan (charpentier — « flèches en lot », DOM-06). C'est de l'interdépendance d'acte III **gratuite** (GAME_PROGRESSION §6c) |

> **Aucune de ces trois ressources n'est neuve** : le pool de PM est vivant, le
> `cooldown` existe sur `Spell` sans consommateur, et les munitions sont un objet
> comme un autre. Ce qui est neuf, c'est de **les répartir** — un registre, une
> ressource — au lieu de faire payer les trois avec la même monnaie.

**Trois garde-fous sur les munitions**, sans quoi la décision devient une taxe :

1. **La flèche de base est au plancher T1 PNJ** (GAME_PRINCIPLES) : toujours
   disponible, à prix fixe, en lot. On ne peut pas être « bloqué faute de
   flèches » — au pire on tire moins bien.
2. **Seules les munitions élémentaires et de haut palier se consomment
   vraiment** : ce sont elles qui portent l'élément du registre distance, donc la
   double borne. La flèche ordinaire est neutre et bon marché.
3. **Un levier de l'arbre les récupère** (`wind` appliqué au registre distance :
   une chance de récupérer la munition tirée, §4 note 1). L'archer chevronné
   dépense moins que le débutant — c'est *exactement* la forme que doit prendre la
   maîtrise.

> **Pourquoi ne pas laisser tout le monde payer en PM ?** Parce qu'alors le
> guerrier est un mage qui tape, et l'archer un mage qui vise. La règle 10 rend
> la matéria souveraine ; c'est la **ressource** qui rend les registres
> différents, pas le nom du geste.

---

## 3. Décision 1 — Le geste d'arme est une matéria

**C'est le prérequis dont tout le reste dépend.** Aujourd'hui, les 200 accords
promis par les arbres ouvrent des **sorts**. Un arbre de mêlée ou de distance
n'ouvre donc que des sorts, et ses passifs — bornés à son registre — ne
s'appliquent à aucune action existante : l'attaque de base ne les lit pas
(DOM-01), et il n'y a pas d'autre geste d'arme. **Un arbre d'archer, dans le
modèle actuel, est un arbre de mage avec un arc.**

**La décision** : la matéria porte aussi les **techniques** — les gestes d'arme.
Même objet, même chaîne (posséder → accorder → sertir), même dérivation
(GAME_MATERIA §2.1). Ce qui change tient en une ligne de modèle :

> **Le geste déclare son registre.** `Spell::register` (sorts / mêlée / distance)
> — la matéria l'hérite, comme elle hérite déjà de l'élément.

Ce que la décision débloque, et qui attend depuis deux jalons :

- **Les emplacements typés de DOM-03** cessent d'être un mur sans porte. Le
  report était explicite : « aucune pièce n'est typée `technique`, et un test
  l'interdit — aucune matéria de technique n'existe ». Elles existent.
- **Les 178 pièces d'équipement** prennent un sens : la plaque porte des
  emplacements de technique, le tissu des emplacements de sort, le cuir
  l'entre-deux (GAME_ITEMS).
- **La double borne mord enfin sur deux registres sur trois.**
- **L'attaque de base reste gratuite et non qualifiée** (règle 10 intacte) : elle
  est le geste de celui qui n'a rien serti, pas la colonne vertébrale du guerrier.

> **Ce n'est pas un contournement de la règle 9.** L'arbre ne donne toujours
> aucune action : il donne l'**accord** d'une technique, exactement comme il donne
> l'accord d'un sort. La technique se trouve, se possède et se sertit dans une
> pièce. Un guerrier qui ne sertit rien n'a que son attaque de base — c'est la
> règle 10, appliquée à la lettre pour la première fois hors des sorts.

---

## 4. Décision 4 — Le vocabulaire fermé des leviers

Les cinq statistiques plates sont remplacées par **treize leviers en
pourcentage**. L'ensemble est **fermé** : ajouter un levier est une décision de
moteur (une place dans une formule), jamais une décision de contenu. Un arbre qui
« aurait besoin » d'un quatorzième levier est un arbre mal conçu — ou une
extension à instruire.

| Levier | Effet | Position dans la formule | 1 point de budget vaut | Plafond / arbre |
|---|---|---|---:|---:|
| `power` | dégâts du geste | multiplicatif sur la valeur de base | **+1,0 %** | 20 pb |
| `mending` | soin rendu | multiplicatif sur la valeur de base | **+1,0 %** | 20 pb |
| `critical` | taux de critique | additif sur le taux (pts de %) | **+0,5 pt** | 12 pb |
| `critical_power` | dégâts d'un critique | multiplicatif sur le seul critique | **+1,5 %** | 15 pb |
| `hit` | précision | additif sur le taux (pts de %) | **+0,5 pt** | 10 pb |
| `pierce` | résistance élémentaire ignorée | **avant** la résistance de la cible | **+0,7 pt** | 12 pb |
| `thrift` | coût du geste en sa ressource (§2) | multiplicatif sur le coût | **−0,6 %** | 15 pb |
| `wind` | ressource rendue par tour | additif par tour | **+0,1 PM/tour** ¹ | 12 pb |
| `guard` | dégâts subis | multiplicatif, **après** résistance | **−0,6 %** | 15 pb |
| `life` | PV maximum | multiplicatif sur les PV de base | **+1,5 %** | 20 pb |
| `grip` | durée **et** intensité des statuts appliqués | sur `StatusEffect` | **+1,2 %** | 18 pb |
| `ward` | résistance à l'application d'un statut subi | sur le jet d'application | **+1,0 %** | 15 pb |
| `tempo` | initiative et ordre du tour | sur `speed` | **+1,0 %** | 12 pb |

> ¹ **`wind` et `thrift` portent sur la ressource du registre** (§2), pas sur les
> PM par principe. Leur taux se convertit donc : en registre **distance**, 1 point
> de budget de `wind` vaut **+1,5 % de chance de récupérer la munition tirée** (un
> pool de flèches ne « régénère » pas au tour) ; en **mêlée**, `thrift` réduit le
> temps de reprise au lieu d'un coût. Un levier, une intention — trois lectures.

### 4.1 Pourquoi les taux de change diffèrent

**Le taux de change est l'inverse de la pente d'efficacité du levier.** Un levier
dont la valeur marginale *croît* avec l'investissement s'achète plus cher :

- `power` et `life` sont **linéaires** : +10 % de dégâts, c'est +10 % de dégâts,
  quel que soit le reste. Taux plein (1,0 ; 1,5 pour la vie, moins volatile).
- `critical` **multiplie** : chaque point de taux vaut le multiplicateur de
  critique entier. Il s'achète à 0,5.
- `guard` est **hyperbolique** sur la survie effective : −10 % de dégâts subis,
  c'est +11 % de PV effectifs ; −50 %, c'est +100 %. Il s'achète à 0,6 **et** son
  plafond est bas — c'est le levier qui casse un jeu quand il ne l'est pas.
- `thrift` **agrandit le budget de tous les autres gestes** : son effet se compose
  avec tout ce que le joueur fera de la ressource économisée. 0,6, plafond 15.

> **La règle qui rend tout ça vérifiable** : *un levier a une place et une seule
> dans la formule de combat, et cette place est écrite ici.* Deux leviers qui
> s'appliquent au même endroit sont un seul levier — c'est ce qui interdit les
> empilements silencieux qui font exploser un équilibrage six mois plus tard.

### 4.2 Ce qui disparaît, et ce qui reste plat

- `damage` et `heal` plats **disparaissent** — remplacés par `power` / `mending`.
- `hit` et `critical` **restent additifs en points de pourcentage** : ce sont déjà
  des taux, les exprimer en pourcentage d'un pourcentage serait illisible.
- `life` **reste hors de la double borne** (décision DOM-01, inchangée) : les
  points de vie ne sont pas un geste, et les borner ferait varier la barre de vie
  d'un tour à l'autre selon le geste choisi. Il devient simplement un pourcentage
  des PV de base.

---

## 5. Les quatre fonctions et leurs palettes

Une fonction, c'est **une promesse au joueur, un coût structurel, et quatre
leviers**. La palette est la partie normative : elle borne l'auteur.

| Fonction | La promesse | Le coût structurel | Palette (leviers autorisés) |
|---|---|---|---|
| **Assaut** | *Je finis le combat avant qu'il ne devienne un problème.* | La fragilité et la ressource : un assaut raté est un combat perdu | `power`, `critical`, `critical_power`, `pierce` |
| **Contrôle** | *Je décide de qui joue, et quand.* | La mise en place : ses premiers tours ne tuent pas | `grip`, `tempo`, `thrift`, `hit` |
| **Entretien** | *Je ne perds pas le combat que les autres perdent au tour 8.* | La lenteur : ses combats sont plus longs | `mending`, `wind`, `thrift`, `ward` |
| **Encaisse** | *Rien ne me casse, rien ne me rate.* | Le plafond : il ne fera jamais le gros chiffre | `guard`, `life`, `ward`, `hit` |

**La règle des 80/20.** Sur les 50 points de budget d'un arbre (§6.3) :
**au moins 40 dans sa palette**, **au plus 10 hors palette**, et **sur un seul
levier étranger**. Ce levier étranger est la **teinte** de l'arbre — ce qui fait
que deux arbres de même fonction ne se confondent pas (le Pyromancien est un
assaut teinté `grip` — la brûlure ; l'Archer un assaut teinté `wind` — la flèche
récupérée).

> **En PvE, « contrôle » n'est pas « soutien ».** Un tour que l'adversaire ne joue
> pas est un tour de dégâts évité : le contrôle est une fonction **offensive**,
> mesurée en tours volés. C'est important à écrire, sinon les arbres de contrôle
> se font écrire comme des arbres d'assaut faibles.

---

## 6. Le gabarit d'un arbre de combat

### 6.1 Les quinze nœuds

Le gabarit de GAME_DOMAINS §5.1 (~15 nœuds, deux entrées à 0 point) est conservé
et **fixé** :

| Palier | Coût | Nœuds | Composition |
|---|---:|---:|---|
| **Entrée** | 0 pt | 2 | **2 accords** — les matéria du jour 1 (invariant GAME_MATERIA §3 : exactement 2 accords gratuits par arbre) |
| **Palier 1** | 10 pts | 4 | 2 passifs (3 pb chacun) · 1 accord · 1 échelon de port (échelon 2 de sa famille d'arme ou d'armure) |
| **Palier 2** | 25 pts | 4 | 2 passifs (6 pb) · 1 accord · 1 échelon de port (échelon 3) |
| **Palier 3** | 50 pts | 3 | 2 passifs (9 pb) · 1 accord |
| **Capstone** | 100 pts | 1 | 1 passif **conditionnel** signature (14 pb) — §7 |
| *Hybride* | *150 pts* | *1* | *l'accord dormant de DOM-07, hors budget tant que la fusion n'ouvre pas* |

**Totaux** : **5 accords** (dont les 2 gratuits d'entrée) · **7 passifs** (6 + le
capstone) · **2 accès de port** · **1 dormant** = 15 nœuds, et **390 points** pour
un arbre complet hors dormant (`4×10 + 4×25 + 3×50 + 100`).

> **L'échelon 1 de port ne figure pas dans l'arbre**, et c'est voulu : il est
> gratuit, partagé entre tous les arbres qui enseignent la famille (ONB-20b). Ce
> que l'arbre enseigne, ce sont les échelons **suivants** — *on ne se sert pas
> d'un arc à poulie sans maîtriser l'arc*.

### 6.2 L'échelle de coût, et le calendrier qu'elle vise

L'échelle **0 / 10 / 25 / 50 / 100** n'est pas choisie pour sa beauté : elle est
dérivée d'un calendrier, qui est la vraie décision de design.

| Palier | Quand il doit tomber | Acte (GAME_PROGRESSION §3) |
|---|---|---|
| Entrée (0) | **Jour 1**, avant la première matéria trouvée | I |
| Palier 1 (10) | Fin de semaine 1 | I |
| Palier 2 (25) | Semaines 3-4 — *le passage critique* | II |
| Palier 3 (50) | Semaines 6-8 | II → III |
| Capstone (100) | Mois 3 | III |

**Le taux de gain se dérive du calendrier, pas l'inverse.** Un joueur consacre au
mieux un tiers de son budget d'énergie à un domaine (il en mène deux à quatre —
GAME_PROGRESSION §1), soit ~16 combats par jour à 5 points d'énergie. Pour que
390 points tombent au mois 3 :

> **Un geste réussi rapporte des points de domaine selon le palier de
> l'adversaire** : T1 → 0,25 · T2 → 0,5 · T3 → 1 · T4 → 2 (paliers de
> [GAME_BESTIARY.md](GAME_BESTIARY.md)).

L'arithmétique, pour qu'elle soit vérifiable : 16 combats par jour sur de la faune
T2 valent **8 points par jour**, soit les 390 points en ~7 semaines de pratique
soutenue. Le mois 3 est la cible d'un joueur **réel** — celui qui partage son
budget entre deux ou trois domaines et ne combat pas tous les jours.

Propriété recherchée, et c'est elle qui justifie la règle : **on ne monte pas un
arbre en tapant des rats.** La progression pousse vers le contenu de son palier,
jamais vers le farm du contenu trivial — et elle se recalibre en un seul endroit
(BALANCE) sans toucher un seul nœud.

### 6.3 Le budget de puissance

> **Un arbre complet vaut 50 points de budget (pb), et pas un de plus.**

Répartition imposée par le gabarit : `2×3 + 2×6 + 2×9 + 14 = 50`.

| Palier | Valeur d'un passif |
|---|---:|
| Palier 1 | **3 pb** |
| Palier 2 | **6 pb** |
| Palier 3 | **9 pb** |
| Capstone | **14 pb** |

Deux plafonds se cumulent : celui de l'arbre (50 pb) et celui de **chaque levier**
(colonne de droite du §4). Un arbre ne peut donc pas mettre ses 50 points dans
`power` : le plafond du levier est à 20.

**Ce que ça donne, concrètement** — l'arbre du Pyromancien fini (§9.1) : **+17 %**
de dégâts (dont 14 conditionnels), **+4,5 points** de critique, **+13,5 %** de
dégâts critiques, **+4,2 points** de perce-résistance et des brûlures **+10,8 %**
plus tenaces. Un joueur qui a *fini* son arbre frappe environ **1,3 fois plus
fort** que celui qui vient de l'ouvrir — dans sa case, et à équipement égal.

> **Pourquoi 50 et pas 100 ?** Parce que la progression du personnage ne passe pas
> par l'arbre : elle passe par le **support** (les emplacements de matéria, les
> bonus de pièce — GAME_WORLD §2.1, « on ne progresse pas en changeant de sort, on
> progresse en le portant mieux »). L'arbre qualifie le geste ; il ne le remplace
> pas. Un arbre qui doublerait la puissance rendrait l'équipement décoratif et
> l'économie inutile — et l'économie est un pilier du jeu.

### 6.4 L'ancre d'échelle : la durée d'un combat

Les valeurs absolues actuelles (gestes de 1 à 12, monstres de 11 à 3 200 PV) ne
sont pas dans le même monde. **L'équilibrage ne s'ancre donc jamais sur un
nombre, mais sur un ratio** — et le ratio de référence est le **nombre de tours** :

| Adversaire | Durée cible d'un combat | Lecture |
|---|---:|---|
| Faune commune de son palier | **3 à 5 tours** | Le tout-venant ; on doit pouvoir en enchaîner |
| Élite de son palier | **6 à 10 tours** | Un vrai échange ; le build se voit |
| Boss de palier / donjon | **12 à 20 tours** | Là où l'entretien et l'encaisse existent |

D'où la seule formule d'équilibrage dont on ait besoin pour un geste :

> **Un geste de palier *n* retire à un adversaire commun de palier *n* environ
> **25 % de ses PV**.** Tout le reste (PV des monstres, dégâts des sorts, valeur
> des paliers) se dérive de là — et se vérifie par un test qui simule un combat,
> pas par un tableau de valeurs qu'on relit à la main.

Corollaire pour les fonctions : **un archétype d'entretien ou d'encaisse n'existe
que si des combats durent** — 3 tours ne laissent la place à aucun soin. La durée
cible des élites et des boss n'est donc pas un chiffre de confort : c'est la
**condition d'existence de deux fonctions sur quatre**.

---

## 7. Décision 7 — Le capstone est un passif conditionnel

Un capstone plat (« +14 % de dégâts ») est invisible : le joueur ne le *sent*
jamais. Mais un capstone actif est interdit (règle 9). La sortie est la
**condition** :

> **Le capstone est un passif dont la condition est le geste signature de
> l'archétype.** Il ne demande aucun clic ; il récompense une façon de jouer que
> le joueur a déjà.

| Fonction | La condition canonique | Exemple |
|---|---|---|
| **Assaut** | la cible porte déjà la marque de votre élément | *le feu qui a déjà brûlé brûle mieux* |
| **Contrôle** | la cible subit un de vos statuts | *ce qui est entravé ne vous échappe pas* |
| **Entretien** | le combat dure, ou la cible est sous un seuil | *le ressac revient plus fort* |
| **Encaisse** | vous avez encaissé au tour précédent | *le coup reçu durcit la garde* |

**Trois garde-fous.** (1) La condition doit être **atteignable au tour 2** avec le
seul kit d'entrée de l'arbre — sinon le capstone est un piège pour qui n'a pas
l'équipement. (2) Elle ne demande **jamais un second personnage** : le jeu est
asynchrone (GAME_PROGRESSION §1). (3) Le capstone **ne fait pas plus de 14 pb**,
même conditionné — un conditionnel qui vaudrait le double « parce qu'il ne
s'applique pas toujours » est la porte d'entrée classique des builds dégénérés.

---

## 8. Le protocole — les six tests d'un arbre

À passer **avant** d'écrire une seule ligne de fixture. Cinq sur six sont
automatisables (§12).

1. **Le test du geste répété** *(déjà canon, GAME_DOMAINS §5)* — pour chaque
   nœud : *quel geste répété m'a mené ici ?* Si la réponse est « avoir dépensé des
   points », le nœud n'a pas de raison d'exister.
2. **Le test de l'arbre nu** — retirez tous les accords. **L'archétype doit rester
   lisible dans les seuls passifs.** S'il ne l'est pas, l'arbre n'est pas un
   arbre : c'est un distributeur de matéria.
3. **Le test du voisin** — prenez l'autre arbre de la même case élément ×
   registre. **La différence doit être une fonction, pas un chiffre.** Si les deux
   palettes se recouvrent à plus d'un levier, fusionnez les arbres ou changez-en
   un de fonction.
4. **Le test du jour 1** — avec les seuls 2 accords gratuits et le kit T1, le
   personnage doit avoir **un geste utilisable et un plan B**. Deux matéria
   d'attaque pure sans réponse à un mauvais tour, c'est un jour 1 raté.
5. **Le test du plafond** — somme des passifs ≤ 50 pb, chaque levier sous son
   plafond, règle des 80/20 tenue.
6. **Le test du renoncement** — que perd un joueur qui a mené cet arbre *à la
   place* d'un autre ? Si la réponse est « rien, il pourra aussi faire l'autre »,
   l'arbre ne participe pas à l'identité — et c'est le seul test que le moteur ne
   peut pas passer à votre place (doctrine des trois couches : l'être se borne par
   les choix, jamais par les verrous).

---

## 9. Les quatre arbres étudiés

Quatre arbres écrits en entier, **un par fonction** et couvrant les trois
registres. Ce ne sont pas quatre exemples : ce sont les **quatre patrons** dont
les vingt autres se dérivent (§10). Les valeurs sont en points de budget (pb) et
leur conversion suit la grille du §4.

### 9.1 Le Pyromancien — feu × sorts × **Assaut** — *« le Foyer »*

| | |
|---|---|
| **Promesse** | *Je finis le combat avant qu'il ne devienne un problème.* |
| **Profil temporel** | **Le pic** — très fort les quatre premiers tours, muet quand les PM tombent |
| **Ce qu'il paie** | Les PM et le tissu : un assaut qui échoue laisse un mage sans réponse |
| **Sa faiblesse** | Le combat long, et l'adversaire résistant au feu |
| **Teinte** (levier hors palette) | `grip` — la brûlure, sa marque, et la condition de son capstone |

**Les quinze nœuds**

| Palier | Coût | Nœud | Ce qu'il donne | pb |
|---|---:|---|---|---:|
| Entrée | 0 | **Accord : Boule de feu** | le geste — dégâts francs, 5 PM | — |
| Entrée | 0 | **Accord : Flammèche** | le geste économe (3 PM) qui **applique la brûlure** — le plan B quand le pool est vide, et la mise en place du capstone | — |
| 1 | 10 | Souffle d'attisage | `power` **+3 %** | 3 |
| 1 | 10 | Points faibles | `critical` **+1,5 pt** | 3 |
| 1 | 10 | **Accord : Mur de feu** | le geste de zone / de temporisation | — |
| 1 | 10 | *Port* : canal de sort, échelon 2 | bâtons et baguettes de palier 2 | — |
| 2 | 25 | Fonte des écailles | `pierce` **+4,2 pt** | 6 |
| 2 | 25 | Lecture de la flamme | `critical` **+3 pt** | 6 |
| 2 | 25 | **Accord : Pluie de flammes** | le geste multi-cible | — |
| 2 | 25 | *Port* : canal de sort, échelon 3 | bâtons et baguettes de palier 3 | — |
| 3 | 50 | Cœur de braise | `critical_power` **+13,5 %** | 9 |
| 3 | 50 | Braise durable | `grip` **+10,8 %** *(teinte)* | 9 |
| 3 | 50 | **Accord : Nova de feu** | le geste de pointe | — |
| **Capstone** | 100 | **Foyer entretenu** | `power` **+14 %** *contre une cible qui brûle* | 14 |
| *Dormant* | *150* | *Accord d'hybride (feu)* | *réservé — DOM-07* | — |

**Vérification.** 50 pb pile · palette (assaut) 41 ≥ 40 ✔ · hors palette 9 ≤ 10 sur
un seul levier ✔ · `power` 17 ≤ 20 ✔ · `critical` 9 ≤ 12 ✔ · condition du capstone
atteignable **au tour 2 avec le seul kit d'entrée** (Flammèche brûle) ✔.

**Test de l'arbre nu** : dégâts, critique, perce-résistance, brûlure durable. On
lit un assaut élémentaire sans avoir vu un seul accord. ✔

### 9.2 Le Guérisseur — eau × sorts × **Entretien** — *« le Ressac »*

| | |
|---|---|
| **Promesse** | *Je ne perds pas le combat que les autres perdent au tour 8.* |
| **Profil temporel** | **Le rebond** — plus le combat dure, plus il gagne |
| **Ce qu'il paie** | La lenteur, et un plafond de dégâts bas : il ne tue pas, il use |
| **Sa faiblesse** | L'adversaire qui frappe plus vite qu'il ne rend, et les combats de 3 tours où le soin n'a pas le temps d'exister |
| **Teinte** | `guard` — l'eau qui amortit |

> **L'arbitrage qui décide de cet archétype : le soin sur soi est le cœur, le soin
> d'autrui est un bonus de contexte.** Le jeu est asynchrone — 1 à 2 joueurs
> simultanés à 50 quotidiens (GAME_WORLD §13.4). Un soigneur conçu pour soigner les
> autres est un archétype **injouable 95 % du temps**. Ici, l'entretien est d'abord
> une façon de gagner *seul* : ne jamais tomber, ne jamais consommer de potion, et
> ressortir d'un combat de vingt tours avec la même barre qu'en entrant. Le donjon
> de groupe le multiplie, il ne le fonde pas.

> **Et il a une vertu de PBBG que les trois autres n'ont pas** : l'énergie d'action
> se paie **par combat, jamais par tour** (GAME_PROGRESSION §1). Un archétype qui
> gagne en durant convertit donc du *temps de combat* — gratuit — en survie. Son
> rendement par point d'énergie est le meilleur du jeu, et c'est la compensation
> exacte de son plafond de dégâts.

**Les quinze nœuds**

| Palier | Coût | Nœud | Ce qu'il donne | pb |
|---|---:|---|---|---:|
| Entrée | 0 | **Accord : Soin** | le geste — rendre des PV |  — |
| Entrée | 0 | **Accord : Jet d'eau** | le geste offensif modeste : **sans lui, un combat ne finit jamais** | — |
| 1 | 10 | Main sûre | `mending` **+3 %** | 3 |
| 1 | 10 | Geste économe | `thrift` **−1,8 %** | 3 |
| 1 | 10 | **Accord : Rosée** *(régénération)* | le soin sur la durée — moins de PM par PV rendu | — |
| 1 | 10 | *Port* : canal de sort, échelon 2 | — | — |
| 2 | 25 | Souffle retenu | `thrift` **−3,6 %** | 6 |
| 2 | 25 | Seconde respiration | `wind` **+0,6 PM/tour** | 6 |
| 2 | 25 | **Accord : Dissipation** | retirer un statut — la réponse aux poisons et aux entraves | — |
| 2 | 25 | *Port* : canal de sort, échelon 3 | — | — |
| 3 | 50 | Sang-froid | `ward` **+9 %** | 9 |
| 3 | 50 | Écume | `guard` **−5,4 %** *(teinte)* | 9 |
| 3 | 50 | **Accord : Marée** | le soin de zone (solo : un gros soin ; en groupe : le pilier) | — |
| **Capstone** | 100 | **Ressac** | `mending` **+14 %** *sur une cible sous 40 % de ses PV* | 14 |
| *Dormant* | *150* | *Accord d'hybride (eau)* | *réservé* | — |

**Vérification.** 50 pb · palette 41 ✔ · teinte `guard` 9 ≤ 10 ✔ · `mending` 17 ≤ 20 ✔ ·
`thrift` 9 ≤ 15 ✔ · condition du capstone atteignable seul (soi-même sous 40 %) ✔.

**Test du voisin** : Hydromancien (eau × sorts × contrôle) et Marémancien (eau ×
sorts × assaut) partagent la case et **aucun levier de palette** avec lui. ✔

### 9.3 Le Soldat — métal × mêlée × **Encaisse** — *« la Ligne »*

| | |
|---|---|
| **Promesse** | *Rien ne me casse, rien ne me rate.* |
| **Profil temporel** | **Le plateau** — la même chose au tour 1 et au tour 20 |
| **Ce qu'il paie** | Le plafond : il ne fera jamais le gros chiffre, et il le sait |
| **Sa faiblesse** | L'adversaire qu'il ne peut pas tuer avant d'être à court de tours ; les dégâts qui ignorent l'armure |
| **Teinte** | `power` — il doit quand même tuer |

> **Cet archétype n'existe pas sans la décision 1 (§3).** Sans matéria de
> technique, l'arbre du Soldat est un jeu de passifs bornés au registre mêlée qui
> ne qualifient **aucune action** : l'attaque de base ne les lit pas. C'est le cas
> le plus net où le canon actuel promet un archétype qu'il ne peut pas livrer.

**Les quinze nœuds**

| Palier | Coût | Nœud | Ce qu'il donne | pb |
|---|---:|---|---|---:|
| Entrée | 0 | **Accord : Frappe appuyée** *(technique)* | le geste — sans temps de reprise |  — |
| Entrée | 0 | **Accord : Garde haute** *(technique)* | le tour défensif, reprise 2 — le plan B, et la mise en place du capstone | — |
| 1 | 10 | Œil du drill | `hit` **+1,5 pt** | 3 |
| 1 | 10 | Discipline | `ward` **+3 %** | 3 |
| 1 | 10 | **Accord : Estoc brisant** *(technique)* | le geste qui perce l'armure | — |
| 1 | 10 | *Port* : plaque, échelon 2 | l'armure lourde de palier 2 | — |
| 2 | 25 | Endurance de marche | `life` **+9 %** | 6 |
| 2 | 25 | Garde travaillée | `hit` **+3 pt** | 6 |
| 2 | 25 | **Accord : Coup de bouclier** *(technique)* | étourdissement bref, reprise 3 | — |
| 2 | 25 | *Port* : plaque, échelon 3 | l'armure lourde de palier 3 | — |
| 3 | 50 | Carcasse | `life` **+13,5 %** | 9 |
| 3 | 50 | Bras d'acier | `power` **+9 %** *(teinte)* | 9 |
| 3 | 50 | **Accord : Charge d'acier** *(technique)* | le gros geste, reprise 4 | — |
| **Capstone** | 100 | **Tenir la ligne** | `guard` **−8,4 %** *au tour qui suit un coup encaissé* | 14 |
| *Dormant* | *150* | *Accord d'hybride (métal)* | *réservé* | — |

**Vérification.** 50 pb · palette 41 ✔ · teinte `power` 9 ≤ 10 ✔ · `life` 15 ≤ 20 ✔ ·
`hit` 9 ≤ 10 ✔ · `guard` 14 ≤ 15 ✔ · condition atteignable au tour 2 (il suffit
d'être frappé — et il le sera) ✔.

**Ce que le capstone dit de l'archétype** : le Soldat est le seul dont la
condition ne se *provoque* pas — elle lui arrive. C'est la traduction exacte de sa
fonction : il ne décide pas du combat, il refuse de le perdre.

### 9.4 L'Archer — air × distance × **Assaut** — *« la Portée »*

| | |
|---|---|
| **Promesse** | *Le meilleur rendement par tour du jeu — si j'ai préparé le terrain.* |
| **Profil temporel** | **La cadence décroissante** — chaque tour coûte une munition, et le carquois n'est pas infini |
| **Ce qu'il paie** | Un coût **économique** (les munitions élémentaires se fabriquent ou s'achètent) et le cuir |
| **Sa faiblesse** | Le combat qui s'éternise, et l'adversaire qu'on n'a pas eu le temps d'entraver |
| **Teinte** | `wind` — la flèche récupérée, la maîtrise qui se voit au budget |

**Les quinze nœuds**

| Palier | Coût | Nœud | Ce qu'il donne | pb |
|---|---:|---|---|---:|
| Entrée | 0 | **Accord : Tir tendu** *(technique)* | le geste — une munition ordinaire | — |
| Entrée | 0 | **Accord : Tir entravant** *(technique)* | ralentit la cible — le plan B, et la condition du capstone | — |
| 1 | 10 | Souffle court | `critical` **+1,5 pt** | 3 |
| 1 | 10 | Bras d'arc | `power` **+3 %** | 3 |
| 1 | 10 | **Accord : Volée** *(technique)* | multi-cible, deux munitions | — |
| 1 | 10 | *Port* : arc, échelon 2 | l'arc de palier 2 | — |
| 2 | 25 | Lecture du vent | `critical` **+3 pt** | 6 |
| 2 | 25 | Pointe affûtée | `pierce` **+4,2 pt** | 6 |
| 2 | 25 | **Accord : Flèche de fracture** *(technique)* | ouvre la garde — dégâts sur la durée | — |
| 2 | 25 | *Port* : arc, échelon 3 | l'arc à poulie de palier 3 | — |
| 3 | 50 | Œil du faucon | `critical_power` **+13,5 %** | 9 |
| 3 | 50 | Trait récupéré | `wind` **+13,5 % de récupération de munition** *(teinte)* | 9 |
| 3 | 50 | **Accord : Tir du faucon** *(technique)* | le geste de pointe, reprise 3 | — |
| **Capstone** | 100 | **Trait dans le vent** | `power` **+14 %** *contre une cible ralentie ou entravée* | 14 |
| *Dormant* | *150* | *Accord d'hybride (air)* | *réservé* | — |

**Vérification.** 50 pb · palette 41 ✔ · teinte `wind` 9 ≤ 10 ✔ · `power` 17 ≤ 20 ✔ ·
`critical` 9 ≤ 12 ✔ · condition atteignable au tour 2 (Tir entravant, accord
d'entrée) ✔.

> **Pourquoi l'Archer n'est pas un Pyromancien avec un arc**, alors qu'ils
> partagent la fonction : sa ressource est **matérielle** (il dépend d'un artisan
> et d'un budget de gils), son profil décroît au lieu de s'effondrer d'un coup, et
> sa teinte porte sur l'**économie** de son geste plutôt que sur la marque qu'il
> laisse. Trois différences structurelles, aucune numérique. C'est le standard à
> tenir pour tous les couples d'arbres de même fonction.

### 9.5 Ce que les quatre donnent, côte à côte

| | Pyromancien | Guérisseur | Soldat | Archer |
|---|---|---|---|---|
| **Fonction** | Assaut | Entretien | Encaisse | Assaut |
| **Ressource** | PM | PM | le tour (reprise) | munitions |
| **Courbe** | pic | rebond | plateau | cadence décroissante |
| **Gagne contre** | ce qui meurt vite | ce qui use | ce qui frappe fort | ce qu'on peut tenir à distance |
| **Perd contre** | ce qui dure | ce qui tue vite | ce qui ignore l'armure | ce qui colle et encaisse |
| **Coût hors combat** | potions de PM | rien *(sa force)* | réparation d'armure | munitions *(un artisan)* |

**La lecture d'ensemble**, et c'est elle qui valide le modèle : **aucune ligne de
ce tableau n'est un chiffre.** Quatre archétypes se distinguent par leur rapport
au temps et à la matière, pas par leur puissance — laquelle est identique (50 pb)
et bornée par la même grille.

---

## 10. Les 24 domaines de combat dans la grille

Fonction proposée pour chacun. La règle qui la contraint : **aucun triplet
(élément, registre, fonction) ne doit exister deux fois** — c'est le test du
voisin (§8.3), rendu vérifiable.

| Élément | Sorts | Mêlée | Distance |
|---|---|---|---|
| **Feu** | Pyromancien — *assaut* | Berserker — *assaut* | Artificier — *contrôle* |
| **Eau** | Hydromancien — *contrôle* · Guérisseur — *entretien* · Marémancien — *assaut* | — | — |
| **Air** | Foudromancien — *assaut* | Vagabond — *contrôle* | Archer — *assaut* |
| **Terre** | Géomancien — *contrôle* | Défenseur — *encaisse* · Gardien — *entretien* | — |
| **Métal** | — | Soldat — *encaisse* · Chevalier — *assaut* | Ingénieur — *contrôle* |
| **Bête** | Druide — *entretien* | Dompteur — *contrôle* | Chasseur — *assaut* |
| **Lumière** | Prêtre — *entretien* | Paladin — *encaisse* · Inquisiteur — *assaut* | — |
| **Ténèbres** | Nécromancien — *contrôle* · Sorcier — *assaut* | Assassin — *assaut* | — |

**Répartition** : 10 assaut · 7 contrôle · 4 entretien · 3 encaisse. Le déséquilibre
est assumé (un jeu PvE se joue majoritairement en attaquant), **mais il commande
le choix des domaines à nourrir en contenu** : GAME_PROGRESSION §7.1 en prévoit
« ~4 combats couvrant des rôles distincts ». Ce sont exactement les quatre du §9 —
un par fonction, trois registres, quatre éléments différents.

### 10.1 La procédure pour les vingt autres

Cinq étapes, dans cet ordre. Aucune n'exige de décision de valeur : les valeurs
tombent des grilles.

1. **Placer le triplet** — élément (donné), registre (donné), fonction (choisie
   pour ne dupliquer aucun voisin de case). Écrire la **promesse en une phrase à
   la première personne**, et le **coût structurel** : si le coût est vide,
   l'archétype n'est pas fini.
2. **Choisir la teinte** — un seul levier hors palette, ≤ 10 pb. C'est ce qui
   distingue l'arbre de son jumeau de fonction. Écrire *pourquoi ce levier-là*.
3. **Poser les 7 passifs** sur la grille 3/6/9/14, palette d'abord, teinte au
   palier 3. Vérifier les plafonds par levier.
4. **Écrire le capstone** comme un conditionnel dont la condition est
   **atteignable au tour 2 avec les deux accords d'entrée**. Si elle ne l'est pas,
   changer la condition — jamais la valeur.
5. **Choisir les 5 accords** par **rôle dans le combat**, jamais par niveau de
   sort : le geste, le plan B *(entrée)*, le geste de zone, la réponse au statut,
   le geste de pointe. La matéria elle-même est **dérivée**, pas écrite
   (GAME_MATERIA §2.1).

> **Ce qui n'est jamais une étape** : ajuster un chiffre pour que « ça fasse plus
> costaud ». Les 50 pb sont les mêmes pour les 24 arbres. Un arbre qui semble
> faible est un arbre dont la **promesse** est faible, ou dont les **accords** ne
> servent pas sa fonction — jamais un arbre sous-payé.

---

## 11. Ce que ça coûte, et les deux arbitrages ouverts

### 11.1 Les dettes de moteur, par ordre de blocage

| # | Ce qu'il faut | Sans quoi | Taille |
|---|---|---|---|
| 1 | **`Spell::register`** + un premier lot de **matéria de technique** (§3) | Deux archétypes sur quatre n'existent pas ; DOM-03 reste un mur sans porte | M |
| 2 | **`Domain::role`** + palettes en configuration (§1, §5) | Aucun test de palette n'est possible ; les 24 arbres restent indiscernables | S |
| 3 | **Les leviers** : `Skill` porte une liste `(levier, pb)` au lieu de 5 entiers, et `CombatSkillResolver` les convertit à leur place dans la formule (§4) | Les passifs restent plats, donc inéquilibrables | **L** |
| 4 | **Les ressources par registre** : consommation de munition, reprise sur technique (§2) | Les trois registres restent la même chose repeinte | M |
| 5 | **L'ancre d'échelle** : recalibrer PV de monstres et valeurs de gestes sur la durée en tours (§6.4) | Les pourcentages s'appliquent à des nombres qui n'ont pas de sens entre eux | **L** |
| 6 | **L'échelle de coût** 0/10/25/50/100 sur les 24 arbres, et le gain de points indexé au palier (§6.2) | Un arbre coûte toujours 465 points pour un plafond de 500 | M |

**L'ordre est contraint** : 2 avant 3 (la palette borne le levier), 1 avant 4,
5 avant toute passe de valeurs. 3 et 5 sont les deux gros morceaux, et ils sont
indépendants l'un de l'autre.

### 11.2 Arbitrage A — le plafond global de 500 points

`PlayerSkillHelper::MAX_TOTAL_SKILL_POINTS = 500` **contredit la doctrine des
trois couches** : « le savoir n'est jamais borné » (GAME_DOMAINS §1). Un plafond
global de points est exactement un verrou de savoir — et il est serré au point
qu'un seul arbre (465 points) le consomme presque entièrement.

> **Recommandation : le supprimer.** Les bornes réelles sont déjà là et suffisent :
> l'**énergie** borne le rythme (couche « savoir »), le **build** borne
> l'expression — un domaine ne s'exprime que si l'équipement en porte une source,
> DOM-02 (couche « faire »), et la **spécialisation / le patronage** bornent
> l'identité (couche « être »). Le plafond global ne borne rien de tout ça : il
> borne le temps de jeu, ce qui est la seule chose que ce jeu a décidé de ne
> jamais punir (GAME_PROGRESSION §5).

Conséquence assumée : un vétéran de deux ans aura appris beaucoup d'arbres. Il
n'en **exprimera** toujours que deux ou trois à la fois, parce qu'il ne porte
qu'une arme et trois emplacements. C'est précisément le contrat des trois couches.

### 11.3 Arbitrage B — que devient l'équilibrage déjà livré ?

Les 358 nœuds de combat existants portent des passifs plats. Deux voies :

- **Conversion mécanique** (recommandée) : un `damage: +1` devient `power: +N pb`
  selon le palier du nœud, par une table ; puis **relecture arbre par arbre** avec
  le test du plafond. Le jeu étant en pur dev (GAME_MATERIA §2.2), aucune
  compatibilité n'est due au joueur.
- **Réécriture des 24 arbres** au gabarit du §6.1. Plus coûteux, mais c'est de
  toute façon ce que la mise en conformité progressive de GAME_DOMAINS §9 prévoit,
  « domaines fréquentés d'abord ».

**Recommandation** : conversion mécanique pour les 20 arbres non nourris,
réécriture complète pour les 4 du §9 — qui sont ceux que le contenu servira.

---

## 12. Invariants testables

Ce qui doit casser la CI si on le viole :

1. **Le budget tient** — la somme des points de budget d'un arbre de combat vaut
   exactement 50.
2. **Aucun levier ne déborde** — chaque levier reste sous son plafond d'arbre (§4).
3. **La palette est tenue** — ≥ 40 pb dans la palette de la fonction, ≤ 10 pb hors
   palette, **sur un seul levier**.
4. **Chaque domaine de combat a une fonction**, et aucun triplet (élément,
   registre, fonction) n'apparaît deux fois.
5. **Le gabarit est tenu** — 15 nœuds, échelle 0/10/25/50/100 (+ dormant),
   exactement **2 nœuds à 0 point, tous deux des accords** (invariant hérité de
   GAME_MATERIA §3).
6. **Le capstone est conditionnel**, unique, et vaut 14 pb — un capstone sans
   condition échoue le test.
7. **Chaque arbre ouvre au moins un geste de son registre** — un arbre de mêlée
   qui n'ouvre que des sorts est un archétype vide (le défaut que la décision 1
   répare).
8. **La condition du capstone est atteignable avec les seuls accords d'entrée** —
   vérifiable en données : l'un des deux accords d'entrée pose le statut ou l'état
   que le capstone exige.
9. **Aucun nœud n'accorde d'action** (règle 9) — déjà couvert par
   `DomainPlanContractTest`, à étendre aux matéria de technique.
10. **Aucun passif de combat n'est plat** — plus aucun `damage`/`heal` entier sur
    un nœud de domaine de combat.

---

## 13. Ce que ce document ne décide pas

- **Les valeurs absolues** — PV des monstres, dégâts des gestes, coûts en PM.
  Elles se dérivent de l'ancre d'échelle (§6.4) et vivent dans
  [BALANCE.md](BALANCE.md).
- **Les arbres de métier** (5 récoltes, 7 artisanats). Le gabarit de GAME_DOMAINS
  §5.2/§5.3 tient ; l'équivalent des fonctions et des leviers pour la récolte et
  l'artisanat est un second chantier, à instruire après celui-ci.
- **Le nom et le contenu des matéria de technique** — c'est du contenu, dérivé
  comme le reste (GAME_MATERIA §2.1).
- **La fusion et les domaines hybrides** — réserve d'extension (GAME_WORLD §2.2) ;
  le nœud dormant reste posé et hors budget.
- **La forme visuelle de l'arbre** — design d'écran, pas design de système.
- **Le nombre d'arbres qu'un joueur mènera réellement** : c'est une conséquence de
  l'énergie et du build, pas une règle à écrire.
