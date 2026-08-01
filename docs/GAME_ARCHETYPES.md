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

Ce document ajoute donc le minimum pour que des archétypes existent : **un
troisième axe** (§1) et **une marque par élément** (§1.1), **une ressource par
registre** (§2) avec ce que chaque famille d'arme et d'armure apporte (§2.2),
**deux étiquettes sur le geste** — ce qu'il fait, qui il touche (§3.1) —, **un
vocabulaire de leviers** et leurs **conditions d'équipement** (§4), **un budget de
puissance** (§6) avec **la fourche** (§6.1 bis) et **le pacte** (§6.5), la **loi du
dépôt** qui rend le jeu de groupe asynchrone possible (§7 bis), un **protocole de
conception** (§8) et ce que **l'écran doit dire** (§8 bis). Puis il l'éprouve sur
quatre arbres écrits en entier (§9), trois mélanges (§9.6), les **accointances**
(§9.7), et range les 24 domaines dans la grille (§10).

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

### 1.1 Décision 10 — Chaque élément a sa marque

L'élément est aujourd'hui une **couleur sans verbe** : il décide de quelles
résistances on souffre, et rien d'autre. Or trois pièces du système le supposent
déjà : le capstone d'assaut se déclenche « contre une cible qui porte la marque de
votre élément » (§7), le levier `grip` amplifie « les statuts appliqués » (§4), et
la palette de contrôle exige deux accords d'`entrave` (§5.1). **Aucun de ces trois
n'a d'objet tant que les marques n'existent pas.**

Une marque par élément. Une seule, canonique, portée par le geste et amplifiée par
`grip` :

| Élément | Marque | Ce qu'elle fait | Ce qu'elle punit |
|---|---|---|---|
| **Feu** | **Brûlure** | dégâts à chaque tour | ce qui a beaucoup de PV |
| **Eau** | **Trempé** | perd de l'initiative, subit plus de dégâts d'air | ce qui frappe souvent |
| **Air** | **Déséquilibre** | rate plus souvent | ce qui mise sur un gros coup |
| **Terre** | **Alourdi** | temps de reprise allongé | les rotations de techniques |
| **Métal** | **Entaille** | dégâts qui **augmentent quand la cible agit** | ce qui joue vite |
| **Bête** | **Traqué** | subit plus de critiques | ce qui encaisse en absorbant |
| **Lumière** | **Révélé** | perd ses résistances élémentaires | ce qui se protège par l'élément |
| **Ténèbres** | **Aveuglé** | ses gestes ratent leur cible | ce qui frappe fort et rarement |

**Quatre règles**, qui sont ce qui rend la marque utilisable plutôt que
décorative :

1. **Un des deux accords d'entrée de chaque arbre l'applique.** Sans ça, le
   capstone est inatteignable pour qui n'a que le kit du jour 1 — le garde-fou du
   §7, appliqué à la lettre.
2. **La marque ne se cumule pas avec elle-même** : elle se **rafraîchit**. Sinon
   l'archétype optimal est « appliquer la même marque huit fois », ce qui n'est pas
   un jeu.
3. **Deux marques différentes coexistent.** C'est le rendement du mélange : un
   pyromancien accompagné d'un archer laisse une cible qui brûle *et* qui
   trébuche. Aucune règle spéciale — elles s'additionnent parce qu'elles sont
   distinctes.
4. **La marque dit à quoi sert un élément**, et c'est ce que le catalogue public
   (GAME_ONBOARDING §6) peut enfin afficher : *le feu use, le métal punit qui
   joue, les ténèbres font rater*. Un joueur choisit son élément pour une raison.
5. **Les monstres les portent aussi** *(correction issue du §9 ter)*. Mesuré :
   **21 monstres sur 65 possèdent un sort**, et **9 de ces sorts appliquent un
   statut**. Or `ward` — la résistance aux statuts subis — figure dans **deux
   palettes sur quatre**, et l'accord *Dissipation* du Guérisseur ne sert à rien
   s'il n'y a rien à dissiper. Une marque qui n'existe que dans un sens est un
   levier mort pour la moitié des fonctions.

> **Le lien avec la roue des oppositions** (GAME_WORLD §2.2) : chaque marque punit
> ce que son opposé fait le mieux. L'air (déséquilibre) mord sur la terre, qui
> mise sur le gros coup ; l'eau (trempé) mord sur le feu, qui multiplie les
> gestes. La roue cesse d'être une table de résistances pour devenir un
> **contre-jeu**.

---

## 2. Décision 2 — Chaque registre a sa ressource

Trois registres qui coûtent la même chose ne sont qu'un registre décliné en trois
animations. La différence doit être **structurelle** : ce qu'on dépense pour
agir.

| Registre | Ressource dépensée | Propriété du profil | Ce que ça crée |
|---|---|---|---|
| **Sorts** | **PM** — un pool plafonné (`Player.energy`), régénéré au tour | **Le pic, puis la panne.** Fort tant que le pool tient, muet ensuite | Le combat court et cher. Le levier qui compte est l'économie (`thrift`, `wind`) |
| **Mêlée** | **Le tour** — un temps de reprise par technique (`Spell::cooldown`, déjà au modèle) ; **le vrai coût est en PV**, puisqu'on reste au contact | **Le plateau.** Toujours disponible, jamais spectaculaire | Le combat long, et une rotation de gestes plutôt qu'un pool. Le levier qui compte est la survie (`guard`, `life`) |
| **Distance** | **Le carquois** — il se vide pendant la rencontre et **se ramasse après** | **La cadence décroissante** — le combat long épuise le carquois | Une ressource **intra-rencontre**, comme les PM, mais qui ne se régénère pas au tour : elle borne les **longues** rencontres. Le carquois lui-même est une **pièce d'équipement durable** (charpentier, DOM-06) |

> **Corrigé au §9 septies : les munitions ne sont plus un consommable payant.**
> Une première rédaction les faisait acheter à l'unité — l'archer payait alors 90 à
> 230 gils par jour quand aucun autre archétype ne payait un gil. **Aucun archétype
> ne doit porter un coût récurrent en monnaie que les autres n'ont pas.** Le carquois
> devient un bien durable qu'on possède en plusieurs exemplaires (un par élément),
> exactement comme un mage possède plusieurs matéria.

> **Aucune de ces trois ressources n'est neuve** : le pool de PM est vivant, le
> `cooldown` existe sur `Spell` sans consommateur, et les munitions sont un objet
> comme un autre. Ce qui est neuf, c'est de **les répartir** — un registre, une
> ressource — au lieu de faire payer les trois avec la même monnaie.

**Quatre garde-fous sur le carquois** *(réécrits au §9 septies — les cinq
précédents supposaient un consommable payant, ce qu'il n'est plus)* :

1. **Le carquois de base est au plancher T1 PNJ** (GAME_PRINCIPLES), et il est
   **pleinement jouable**, passifs d'arbre compris (§2.1). L'élément vient de la
   matéria ; le carquois élémentaire ne fait que le **remplacer**.
2. **Il ne se consomme pas, il se vide.** Sa capacité borne la **durée** d'une
   rencontre, jamais la journée : entre deux combats, on ramasse ses flèches.
3. **Un levier de l'arbre l'étend** — `wind` appliqué au registre distance rend des
   **tirs quand ils manquent** (§4 note 1), ce qui ne compte que dans les longues
   rencontres. C'est exactement la forme que doit prendre la maîtrise.
4. **Posséder plusieurs carquois est un investissement, jamais un péage.** On en
   achète un par élément, une fois, comme on achète une matéria — et l'archer qui
   n'en a qu'un joue son archétype en entier.

> **Pourquoi ne pas laisser tout le monde payer en PM ?** Parce qu'alors le
> guerrier est un mage qui tape, et l'archer un mage qui vise. La règle 10 rend
> la matéria souveraine ; c'est la **ressource** qui rend les registres
> différents, pas le nom du geste.

### 2.1 Décision 11 — La munition porte l'élément

Où vit l'élément d'un geste ? La réponse diffère par registre, et c'est une
troisième différence structurelle — gratuite, puisqu'elle découle de §2 :

| Registre | Ce qui porte l'élément | Ce que ça coûte d'en changer |
|---|---|---|
| **Sorts** | la **matéria** sertie | un passage par l'écran de build, hors combat |
| **Mêlée** | la **technique** sertie | idem, plus l'arme qui va avec |
| **Distance** | la **technique** sertie — et le **carquois équipé la remplace** | **changer de carquois** entre deux combats — un bien durable qu'on possède en plusieurs exemplaires |

> **Corrigé au §9 quater, et c'est important.** Une première rédaction faisait
> porter l'élément **par la munition seule**. Conséquence non vue : une flèche
> ordinaire — neutre, celle du plancher T1 PNJ — produisait une action **sans
> élément**, donc hors de la case du domaine, donc **sans aucun passif de l'arbre**.
> Le filet de sécurité éteignait l'archétype. L'élément vient donc de la matéria,
> comme partout ; la munition ne fait que le **remplacer**.

> **L'archer est le seul qui *achète* sa souplesse.** Il ne paie pas sa puissance —
> son archétype tourne à la flèche ordinaire — il paie le droit de **changer de
> contre-jeu** entre deux combats, ce qu'aucun autre registre ne peut faire sans
> refaire son build.

Ce que ça règle d'un coup : la double borne sur le registre distance (une flèche
de feu qualifie un geste de feu, donc les passifs de l'arbre de feu), la demande
économique du charpentier et de l'alchimiste, et la raison pour laquelle un archer
voyage avec plusieurs carquois. Garde-fou inchangé : **la flèche ordinaire est
neutre, au plancher T1 PNJ, et n'est jamais épuisable au point de bloquer.**

### 2.2 Décision 12 — Chaque famille apporte quelque chose de nommé

« +10 % de dégâts avec une dague » ne veut rien dire tant qu'une dague n'est pas
autre chose qu'une épée plus petite. Chaque famille porte donc **un trait**, et
c'est lui que les passifs conditionnels viennent creuser :

| Famille | Son trait | Qui la veut |
|---|---|---|
| **Dague** | critique élevé, dégâts de base bas | l'assaut qui multiplie les gestes |
| **Épée** | l'équilibre — rien d'excellent, rien de mauvais | tout le monde, et personne en particulier |
| **Hache** | gros dégâts, précision basse | l'assaut qui accepte de rater |
| **Masse** | perce l'armure, lente | l'encaisse qui doit quand même tuer |
| **Bâton / baguette** | canalise : plus d'emplacements de sort, frappe mal | les sorts |
| **Arc** | cadence, portée | la distance |
| **Arbalète** | un gros coup, un long temps de reprise | la distance qui prépare |
| **Bouclier** *(main gauche)* | `guard`, et il occupe la main | l'encaisse, et quiconque renonce à une seconde arme |

Et trois lignes d'armure, trois profils défensifs **qui ne se remplacent pas** :

| Ligne | Ce qu'elle donne | Sa façon de survivre |
|---|---|---|
| **Tissu** | emplacements de **sort**, PM | elle ne survit pas — elle finit le combat avant |
| **Cuir** | `dodge`, `tempo`, emplacements mixtes | **éviter** : volatil, rapide, joue plus de tours |
| **Plaque** | `guard`, `life`, emplacements de **technique** | **absorber** : fiable, lent, ne surprend jamais |

> **C'est ici que `dodge` et `guard` cessent d'être deux dosages de la même
> chose.** Le cuir évite entièrement ou pas du tout ; la plaque réduit toujours un
> peu. Sur dix coups, les deux perdent autant de PV — mais le porteur de cuir a
> connu deux tours gratuits et un tour catastrophique, et celui de plaque dix tours
> identiques. **Le même total, deux jeux différents** : c'est la définition d'une
> nuance réussie.

#### Combien exactement — la fourchette utile *(mesurée)*

**L'arbre ne peut pas créer l'écart** : `life` plafonne à 20 pb (+30 %) et `guard`
à 15 pb (−9 %). Par l'arbre seul, un tank a **×1,39** les points de vie effectifs
d'un porteur de tissu — ce qui n'est pas un écart, c'est une nuance. **C'est
délibéré : l'arbre qualifie, l'armure décide.** Mais encore faut-il dire de
combien.

| Mitigation de la plaque | PV effectifs du tank | Écart / tissu | Dégâts subis en solo *(élite)* |
|---:|---:|---:|---|
| 0 % *(arbre seul)* | 167 | ×1,39 | tank 168 / archer 82 |
| 28 % *(minimum pour l'aggro, §13.4)* | 231 | ×1,93 | tank 121 / archer 82 |
| **40 %** | **278** | **×2,31** | tank 101 / archer 82 |
| 50 % | 333 | ×2,78 | tank 84 / archer 82 — *à l'équilibre* |
| 60 % | 417 | ×3,47 | tank 67 / archer 82 — **le solo casse** |

**La borne haute est à ~50 %**, et elle sort d'un calcul et non d'un avis : c'est
le point où la mitigation du tank annule exactement sa lenteur (14 tours contre 6).
Au-delà, il encaisse moins que l'archer **tout en survivant mieux**, et redevient
le meilleur choix partout — le défaut du §9 sexies, réintroduit par l'équipement.

> **Recommandation : plaque ~40 %, cuir ~20 %, tissu 0 %.** L'écart de points de
> vie effectifs devient **×2,3 / ×1,6 / ×1** — franc, lisible au premier coup
> d'œil, et il laisse l'archer meilleur en dégâts subis (82 contre 101). C'est un
> chiffre de [GAME_ITEMS.md](GAME_ITEMS.md), pas un chiffre d'arbre.

> **Ce qui autorise une mitigation aussi forte, c'est la lenteur.** Le tank met
> deux fois plus de tours à finir un combat ; chaque tour est un coup encaissé de
> plus. La plaque ne le rend pas invulnérable — elle lui rembourse le temps qu'il
> perd.

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

### 3.1 Décision 8 — Le geste porte deux étiquettes de plus

Le registre dit **comment** on agit. Il ne dit ni **ce qu'on fait**, ni **à qui**.
Deux étiquettes s'ajoutent, et elles ne sont pas cosmétiques : sans elles, ni les
leviers ni les palettes ne peuvent viser juste.

| Étiquette | Valeurs | Ce qu'elle décide |
|---|---|---|
| **`intent`** — ce que le geste fait | `dégât` · `soin` · `protection` · `amélioration` · `entrave` | **quels leviers le qualifient** (`mending` ne touche que le soin, `grip` que l'entrave) et **quelle fonction a le droit de l'ouvrir** (§5.1) |
| **`scope`** — qui il touche | `soi` · `un allié` · `le groupe` · `une cible` · `plusieurs cibles` | le jeu de groupe (§7 bis), et la lecture d'écran — un geste dit ce qu'il vise avant d'être lancé |

**Trois choses deviennent possibles, dont une qui manquait au document.**

1. **Les leviers visent juste.** Avec 15 leviers (§4), il faut dire à quoi chacun
   s'applique. `intent` le dit, une fois, sur le geste — pas quinze fois dans
   quinze formules.
2. **La boucle « arbre × matéria » se ferme.** Ce document affirme depuis §0 que
   l'archétype vit dans le couple (arbre, matéria) — mais rien n'imposait *quelles*
   matéria un arbre ouvre. Une **palette d'intentions** par fonction (§5.1) le
   fait, et devient testable : un arbre d'entretien qui n'ouvrirait que des gestes
   de dégât échoue.
3. **Le groupe existe.** `scope: le groupe` est ce sur quoi repose toute la
   section §7 bis.

> **`intent` n'est pas `register`, et ne se déduit pas de lui.** Une protection
> peut être un sort (bouclier d'eau), une technique de mêlée (garde haute) ou une
> pièce d'équipement. Un dégât peut être les trois. Croiser les deux étiquettes
> est précisément ce qui donne 3 × 5 façons de faire une chose plutôt que 3.

---

## 4. Décision 4 — Le vocabulaire fermé des leviers

Les cinq statistiques plates sont remplacées par **quinze leviers en
pourcentage**. L'ensemble est **fermé** : ajouter un levier est une décision de
moteur (une place dans une formule), jamais une décision de contenu. Un arbre qui
« aurait besoin » d'un seizième levier est un arbre mal conçu — ou une extension à
instruire.

> **Le critère d'admission d'un levier**, puisqu'il faut qu'il en existe un :
> *un levier occupe une place dans la formule qu'aucun autre n'occupe.* `dodge`
> (éviter entièrement, avant tout calcul) et `guard` (réduire, après résistance)
> ne sont pas deux dosages de la même chose : l'un est binaire et volatil, l'autre
> continu et fiable. C'est ce qui les rend tous deux nécessaires — et c'est ce qui
> distingue une armure de cuir d'une armure de plaque autrement que par un chiffre.

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
| `dodge` | chance d'éviter entièrement | binaire, **avant** tout calcul de dégâts | **+0,35 pt** | 12 pb |
| `life` | PV maximum | multiplicatif sur les PV de base | **+1,5 %** | 20 pb |
| `recovery` | PV rendus en fin de tour | additif, hors soin | **+0,25 % des PV max** | 12 pb |
| `grip` | durée **et** intensité des statuts appliqués | sur `StatusEffect` | **+1,2 %** | 20 pb |
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

> **Les leviers principaux plafonnent tous à 20** — `power`, `mending`, `grip` —
> **sauf `guard`, à 15**, dont l'efficacité est hyperbolique. *Correction issue du
> §9 quinquies : `grip` était à 18, et comme le capstone d'un arbre en consomme 14,
> un arbre de contrôle ne pouvait acheter son propre levier principal nulle part
> ailleurs qu'à son sommet.*

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
- `recovery` **suit `life`** hors de la double borne, pour la même raison : une
  régénération qui s'allumerait selon le sort du tour serait illisible.

### 4.3 Décision 9 — Les passifs conditionnels d'équipement

Un passif toujours vrai ne fait pas un build : il fait un total. Un passif
**conditionné à ce qu'on porte** transforme l'équipement en décision — et c'est
exactement ce que GAME_DOMAINS §3 promet (« l'équipement **est** le build »,
« l'auto-limitation est émergente ») sans avoir jamais eu de quoi le tenir.

Un nœud passif s'écrit donc `(levier, points de budget, condition ?)`. Trois
familles de conditions, et un multiplicateur d'effet par famille :

| Famille de condition | Exemples | Multiplicateur d'effet |
|---|---|---|
| **Aucune** | — | **×1,0** |
| **De build** — vraie ou fausse avant le combat, stable jusqu'à la fin | une **dague** en main · la ligne **cuir** portée · un **bouclier** équipé · la main gauche **libre** · deux armes | **×1,4** |
| **De combat** — elle se joue, et elle peut manquer | la cible **brûle** · vous avez **encaissé** au tour précédent · la cible est **sous 40 %** de ses PV | **×2,0** |

> **Le multiplicateur suit la fréquence réelle, pas la famille** *(correction issue
> du §9 bis)*. « Vous avez encaissé au tour précédent » est vraie **dès le tour 2 et
> jusqu'à la fin** pour qui se bat au contact : la payer ×2,0 comme une condition
> qui peut manquer est une erreur de comptage. **Une condition de combat vraie plus
> des deux tiers du temps se paie au tarif d'une condition de build (×1,4)** — et
> c'est le simulateur d'ARC-05 qui mesure cette fréquence, pas l'auteur qui
> l'estime.

> **Le budget compte ce qu'un passif rapporte *en moyenne*, pas ce qu'il
> affiche.** Une condition ne rend pas un nœud plus fort : elle échange de la
> **constance** contre de l'**amplitude**. Un nœud de 6 pb donne +6 % s'il est
> toujours vrai, **+8,4 %** s'il exige une pièce, **+12 %** s'il exige un état de
> combat. Les plafonds du §4 restent exprimés **en points de budget** — ils ne
> bougent pas d'un pouce.

#### Le vocabulaire fermé des conditions

Comme les leviers, les conditions sont un **ensemble fermé** — sinon chaque auteur
en invente une, et plus rien n'est comparable ni testable. **Six de build, six de
combat, et rien d'autre :**

| De build (×1,4) | De combat (×2,0) |
|---|---|
| une **famille d'arme** en main (dague, hache, arc…) | la cible porte **votre marque** (§1.1) |
| une **ligne d'armure** portée (tissu / cuir / plaque) | la cible porte **une marque quelconque** |
| un **bouclier** équipé | vous avez **encaissé** au tour précédent |
| la **main gauche libre** (à deux mains, ou rien) | la cible est **sous 40 %** de ses PV |
| **deux armes** portées | **vous** êtes sous 40 % de vos PV |
| un **emplacement de matéria** d'un type donné | le **premier tour** de la rencontre |

Trois refus qui vont avec, et qui comptent autant que la liste :

- **Jamais une pièce nommée ni une rareté** — sinon le butin devient un prérequis
  de build, et la chance un axe de progression.
- **Jamais une condition d'élément de la cible** (« +X % contre les créatures
  d'eau ») : ce serait un second système de résistances, en double de celui qui
  existe.
- **Jamais un malus conditionné à l'équipement** (« −10 % en plaque ») — c'est
  l'interdit de port réintroduit par la bande. Une condition **récompense**, elle
  ne punit jamais. Le seul malus légitime est celui du pacte (§6.5) : assumé,
  permanent et lisible.

#### La condition en escalier plutôt qu'en interrupteur

Une condition binaire crée une falaise : quatre pièces de cuir sur cinq ne valent
rien. Une condition de **ligne d'armure** ou de **nombre de pièces** se lit donc en
**escalier** — l'effet est proportionnel à ce qu'on porte :

> « +4,2 % de vitesse en cuir » se lit **+0,84 % par pièce de cuir portée**, cinq
> pièces donnant le plein effet.

Ce qui change : un joueur qui panache trois pièces de cuir et deux de plaque
touche **60 % des deux** bonus au lieu de zéro des deux. Le mélange devient un
curseur, pas un pari — et le budget est inchangé, puisqu'on paie l'effet **plein**.

Les conditions de **combat** et les conditions **binaires par nature** (bouclier,
main gauche libre, famille d'arme) restent des interrupteurs : on ne porte pas 40 %
d'un bouclier.

**Cinq garde-fous**, sans lesquels la mécanique devient un péage déguisé :

1. **Jamais de condition au palier 1.** Le joueur de la première semaine n'a pas
   encore l'équipement ; un arbre dont l'entrée est conditionnée est un arbre
   fermé.
2. **Au moins 2 passifs sur 7 sans condition.** Un personnage mal équipé garde un
   arbre qui fait quelque chose.
3. **La condition doit être satisfaisable par ce que l'arbre débloque lui-même** —
   son échelon de port, ou un statut posé par un de ses deux accords d'entrée.
   Même garde-fou que pour le capstone (§7).
4. **La condition porte sur une famille, jamais sur une pièce ni sur une rareté.**
   « +10 % à la dague » est légitime ; « +10 % avec l'Épée du Fanal » ferait du
   butin un prérequis de build, et de la chance un axe de progression.
5. **Une condition ne ferme rien.** Le mage en plaque existe toujours (garde-fou 1
   de GAME_DOMAINS §3) — il n'a simplement pas le bonus. On ne lit jamais un
   interdit : on lit **ce qu'on gagnerait à porter autre chose**.

#### Ce que le budget accepte, et ce qu'il refuse

Les trois exemples qui ont motivé cette décision, chiffrés contre la grille — le
budget fait ici exactement son travail :

| Idée | Coût réel | Verdict |
|---|---|---|
| « **+10 % de dégâts avec une dague** » | 10 / 1,0 / 1,4 = **7,1 pb** | ✅ **Abordable** — un nœud de palier 3 (9 pb) le paie et rend même +12,6 % |
| « **−15 % de dégâts subis avec un bouclier** » | 15 / 0,6 / 1,4 = **17,9 pb** | ❌ **Au-dessus du plafond de `guard` (15 pb)** — et ce serait plus du tiers du budget d'un arbre. À ramener à **−8 %** (9 pb), ou à conditionner au combat |
| « **+2 % de vitesse en armure de cuir** » | 2 / 1,0 / 1,4 = **1,4 pb** | ⚠️ **Trop petit pour un nœud** : le plus modeste vaut 3 pb. À monter à **+4,2 %**, sinon le joueur ne sentira jamais l'avoir appris |

C'est la vertu qu'on attend d'un budget : il ne dit pas « non », il dit
**combien** — et il attrape aussi bien le nœud trop fort que le nœud trop petit
pour valoir un clic.

---

## 5. Les quatre fonctions et leurs palettes

Une fonction, c'est **une promesse au joueur, un coût structurel, et quatre
leviers**. La palette est la partie normative : elle borne l'auteur.

| Fonction | La promesse | Le coût structurel | Palette de leviers (le **principal** en gras) |
|---|---|---|---|
| **Assaut** | *Je finis le combat avant qu'il ne devienne un problème.* | La fragilité et la ressource : un assaut raté est un combat perdu | **`power`**, `critical`, `critical_power`, `pierce`, `tempo` |
| **Contrôle** | *Je décide de qui joue, et quand.* | La mise en place : ses premiers tours ne tuent pas | **`grip`**, `hit`, `thrift`, `tempo`, `pierce` |
| **Entretien** | *Je ne perds pas le combat que les autres perdent au tour 8.* | La lenteur : ses combats sont plus longs | **`mending`**, `recovery`, `wind`, `thrift`, `ward` |
| **Encaisse** | *Rien ne me casse, rien ne me rate.* | Le plafond : il ne fera jamais le gros chiffre | **`guard`**, `dodge`, `life`, `ward`, `hit` |

**Deux règles tiennent la grille.**

- **Le levier principal est exclusif** : `power`, `grip`, `mending` et `guard`
  n'apparaissent que dans **une** palette. Deux palettes peuvent partager jusqu'à
  deux leviers secondaires — pas leur cœur.
- **La règle des 80/20.** Sur les 50 points de budget d'un arbre (§6.3) : **au
  moins 40 dans sa palette**, **au plus 10 hors palette**, et **sur un seul levier
  étranger**. Ce levier étranger est la **teinte** — ce qui fait que deux arbres de
  même fonction ne se confondent pas (le Pyromancien est un assaut teinté `grip` —
  la brûlure ; l'Archer un assaut teinté `wind` — la flèche récupérée). Une teinte
  peut viser le principal d'une autre fonction : à 10 pb maximum, elle n'en
  attrape jamais l'identité.

### 5.1 La seconde moitié de la palette : les intentions

Une palette de leviers borne les **passifs**. Elle ne dit rien des **accords** — et
c'est le trou que la décision 8 (§3.1) permet de fermer. Chaque fonction borne
aussi les **intentions** des matéria que son arbre ouvre :

| Fonction | Sur ses 5 accords |
|---|---|
| **Assaut** | ≥ 3 `dégât` |
| **Contrôle** | ≥ 2 `entrave`, ≥ 1 `dégât` |
| **Entretien** | ≥ 2 `soin` ou `protection`, dont **≥ 1 de portée `le groupe`** |
| **Encaisse** | ≥ 2 `protection`, dont **≥ 1 de portée `un allié` ou `le groupe`** |

**Et deux règles valables pour les 24 arbres**, qui décident de la jouabilité bien
plus que n'importe quel chiffre :

1. **Tout arbre ouvre au moins un accord de `dégât`.** Sans lui, un combat ne
   finit jamais — et l'archétype est injouable seul, c'est-à-dire 95 % du temps
   (§7 bis).
2. **Tout arbre ouvre au moins un accord qui n'est pas un `dégât`.** C'est le
   plan B du test du jour 1 (§8.4) : le tour où frapper n'est pas la réponse.
3. **Tout arbre ouvre au moins un accord que nul autre n'ouvre.** C'est la réponse
   mécanique à *« pourquoi mener ce domaine plutôt qu'un autre ? »* — un geste
   qu'on ne verra nulle part ailleurs. Le modèle le prévoit déjà : une matéria
   ouverte par plusieurs arbres n'appartient à aucun (`domain: null`,
   GAME_MATERIA §2.1) ; celle-ci en a un.
4. **Un des deux accords d'entrée applique la marque de l'élément** (§1.1) — sans
   quoi le capstone est hors de portée du joueur du jour 1.

> **Ce que le contrôle est vraiment** *(rectifié au §9 quinquies, où la première
> rédaction s'est révélée fausse)*. On lisait ici qu'« un tour que l'adversaire ne
> joue pas est un tour de dégâts évité, donc le contrôle est offensif ». **En duel,
> c'est arithmétiquement nul** : le combat s'allonge exactement de ce qu'on a volé,
> et les dégâts subis ne bougent pas d'un point. Deux règles en découlent, et elles
> valent pour les sept arbres de contrôle de la grille :
>
> 1. **Une entrave n'est jamais un tour perdu** : soit elle vole **plus d'un tour**
>    (durée ≥ 2), soit elle **accompagne un geste de dégât**. C'est ce qui fait de
>    `grip` — la durée — la **condition d'existence** de la fonction, et pas un
>    bonus de confort.
> 2. **Le contrôle est défensif en duel, et il ne se multiplie pas en groupe**
>    (§7 bis) — mais il est **la seule fonction dont la valeur ne change pas** selon
>    le contexte. C'est sa compensation, et elle vaut mieux qu'un multiplicateur
>    dans un jeu à 1-2 joueurs simultanés.

---

## 6. Le gabarit d'un arbre de combat

### 6.1 Les quinze nœuds

Le gabarit de GAME_DOMAINS §5.1 (~15 nœuds, deux entrées à 0 point) est conservé
et **fixé** :

| Palier | Coût | Nœuds | Composition |
|---|---:|---:|---|
| **Entrée** | 0 pt | 2 | **2 accords** — les matéria du jour 1 (invariant GAME_MATERIA §3 : exactement 2 accords gratuits par arbre), dont **un qui applique la marque** (§1.1) |
| **Palier 1** | 10 pts | 4 | 2 passifs (3 pb chacun, **jamais conditionnels**) · 1 accord · 1 échelon de port (échelon 2 de sa famille d'arme ou d'armure) |
| **Palier 2** | 25 pts | 4 | 2 passifs (6 pb) · 1 accord · 1 échelon de port (échelon 3) |
| **Palier 3** | 50 pts | 6 | **la fourche** — 2 branches de 2 passifs (9 pb) **et d'un accord chacune**, on n'en apprend qu'une (§6.1 bis) |
| **Capstone** | 100 pts | 1 | 1 passif **conditionnel** signature (14 pb) — §7 |
| *Hybride* | *150 pts* | *1* | *l'accord dormant de DOM-07, hors budget tant que la fusion n'ouvre pas* |

**Totaux** : **6 accords écrits, 5 apprenables** (dont les 2 gratuits d'entrée) ·
**9 passifs écrits, 7 apprenables** (6 + le capstone) · **2 accès de port** ·
**1 dormant** = **18 nœuds écrits, 15 apprenables**, et **390 points** pour un arbre
complet hors dormant (`4×10 + 4×25 + 3×50 + 100`).

> **Amendement au gabarit de GAME_DOMAINS §5.1**, qui dit « ~15 nœuds ». Il en
> reste 15 **pour un personnage** ; l'arbre en **écrit** deux de plus, qui sont les
> deux moitiés d'un choix. Le nombre que le canon fixait — ce qu'un joueur
> apprend — n'a pas bougé.

### 6.1 bis — Décision 13 : la fourche

> **Un arbre propose 60 points de budget ; un personnage en porte 50.**

Au palier 3, l'arbre offre **deux branches de deux passifs**, et n'en laisse
apprendre qu'une. C'est le **renoncement à l'intérieur de l'arbre** — exactement ce
que DOM-04 et DOM-06 ont livré pour l'artisanat, jusqu'au motif de refus
(`other_branch`) et au respec de branche payant. Rien à inventer : le mécanisme
existe, il n'a jamais servi au combat.

**Ce que ça règle**, et qui manquait : jusqu'ici, **deux pyromanciens finis étaient
identiques**. L'archétype distinguait les arbres entre eux, rien ne distinguait
deux joueurs du même arbre. Les conditions d'équipement (§4.3) faisaient une part
du travail ; la fourche fait le reste, et à un endroit que le joueur choisit
délibérément plutôt que par sa tenue.

**Quatre règles :**

1. **Les deux branches tiennent la même fonction, autrement.** Jamais « la bonne et
   la mauvaise », jamais « l'offensive et la défensive » — la fonction ne change
   pas, sa **façon** change.
2. **Elles ne partagent aucun levier.** Deux branches qui se recouvrent sont un
   choix de façade.
3. **La teinte peut n'exister que dans une branche** (§5). C'est même le meilleur
   usage qu'on puisse en faire : *la teinte devient un choix, pas une fatalité*.
4. **Le respec de branche se paie**, comme en artisanat ; le respec de points
   ordinaire reste doux. On change d'avis, mais pas tous les matins.
5. **Chaque branche ouvre son accord** — et c'est la règle qui décide si la fourche
   est un choix ou une décoration. *Constat mesuré au §9 bis : deux branches qui ne
   diffèrent que par leurs passifs produisent le même combat, au tour près.* Ce sont
   les **gestes** qui séparent deux façons de jouer, jamais les pourcentages.
6. **Une fourche peut opposer deux contextes**, pas seulement deux dosages : le
   Soldat choisit entre *celui qu'on veut en donjon* et *celui qui se débrouille
   seul* (§9.3). C'est la forme la plus forte qu'on ait trouvée — et elle n'oblige
   personne, puisqu'aucun contenu n'exige un rôle (§7 bis).

> **L'échelon 1 de port ne figure pas dans l'arbre**, et c'est voulu : il est
> gratuit, partagé entre tous les arbres qui enseignent la famille (ONB-20b). Ce
> que l'arbre enseigne, ce sont les échelons **suivants** — *on ne se sert pas
> d'un arc à poulie sans maîtriser l'arc*.

### 6.2 Décision 5 — L'échelle de coût, et le calendrier qu'elle vise

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

### 6.3 Décision 6 — Le budget de puissance

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

**Ce que ça donne, concrètement** — l'arbre du Pyromancien fini (§9.1) : **+3 %**
de dégâts en toutes circonstances et **+28 %** contre une cible qui brûle, **+4,2
points** de critique en robe de tissu, **+13,5 %** de dégâts critiques, **+4,2
points** de perce-résistance, et des brûlures **+10,8 %** plus tenaces. Un joueur
qui a *fini* son arbre frappe environ **1,3 fois plus fort** que celui qui vient
de l'ouvrir, **quand sa condition est remplie** — dans sa case, et à équipement
égal.

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
| Élite de son palier | **6 à 10 tours** | **Une rencontre de groupe** — mortelle pour un joueur seul, quel que soit son archétype (§9 octies) |
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

#### La seconde ancre : le coût d'une rencontre, rapporté à la journée

*(Correction issue du §9 ter.)* La durée en tours ne suffit pas. Mesuré : un
Soldat et un Guérisseur tiennent **onze tours tous les deux** et sortent avec une
barre comparable — mais le premier n'a **rien dépensé** et le second a vidé
**~108 PM sur 120**. Sur un combat ils sont équivalents ; sur les ~16 combats
qu'autorise une journée d'énergie (§6.2), ils n'ont rien à voir.

> **Un archétype ne se juge pas sur un combat, il se juge sur la journée que la
> barre d'énergie autorise.** Second ratio de référence : **le coût d'une rencontre
> en ressource du registre**, rapporté au budget quotidien.

| Registre | Ce que la seconde ancre mesure |
|---|---|
| **Sorts** | les PM consommés par rencontre, contre le pool et sa régénération hors combat |
| **Mêlée** | rien à mesurer — le temps de reprise se paie **dans** le combat, jamais après |
| **Distance** | les munitions consommées par rencontre, **contre les gils du jour** |

C'est cette ancre qui donne leur sens aux leviers d'économie (`thrift`, `wind`) :
ils n'agrandissent pas un combat, ils agrandissent une **journée**. Et c'est elle
qui rend le registre mêlée structurellement différent des deux autres — il est le
seul dont la ressource ne se reporte pas d'un combat au suivant.

### 6.5 Décision 14 — Le pacte : un malus rend du budget

Un archétype mémorable n'est pas seulement bon à quelque chose : il est **mauvais
à autre chose, et il l'a choisi**. Le budget permet de l'écrire proprement.

> **Un nœud peut prendre un malus. Sa valeur, au taux de change du levier, s'ajoute
> au budget du nœud.** Un arbre porte alors jusqu'à **60 pb de bonus et 10 pb de
> malus** — la somme reste 50. *Le pacte ne change pas ce qu'un arbre pèse, il
> change sa forme.*

Exemple : un nœud de palier 3 vaut 9 pb. Avec un pacte de 10 pb de malus, il en
dépense 19 :

> **« Sang qui bout »** — `power` **+19 %**, `life` **−15 %**. Permanent.

**Six règles**, et elles sont serrées parce que c'est la mécanique la plus facile à
dégénérer du document :

1. **Un seul pacte par arbre**, et jamais au palier 1. C'est une signature, pas un
   outil.
2. **Le malus est hors de la palette de la fonction.** L'assaut paie en survie,
   l'encaisse paie en dégâts. Un assaut qui paierait en `pierce` échangerait de la
   monnaie contre elle-même.
3. **Le malus est permanent, inconditionnel et tient en une ligne.** Jamais « −X %
   si vous portez Y » — ce serait l'interdit de port réintroduit par la bande
   (§4.3). Jamais deux stats. Jamais un effet caché.
4. **Le nœud de pacte est une feuille** : aucun autre nœud ne l'exige. Un arbre où
   le pacte est sur le chemin du capstone n'offre pas un choix, il pose un péage.
5. **Les plafonds par levier tiennent toujours.** Le pacte contourne le budget de
   l'arbre, jamais le plafond d'un levier — sinon il devient la porte de sortie de
   tout l'équilibrage.
6. **Le malus est visible avant d'apprendre**, et le respec de points ordinaire
   suffit à revenir en arrière. On assume un choix, on ne se fait pas piéger.

> **Pourquoi c'est plus qu'un gadget** : le pacte est la seule mécanique du
> document qui rende un personnage **mesurablement plus faible** quelque part. Sans
> lui, tous les builds sont des additions et le mot « spécialisation » ne désigne
> qu'un ordre d'achat. Avec lui, un berserker à −15 % de PV est un pari, une
> anecdote de partie, et une raison d'avoir un guérisseur dans le groupe.

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
asynchrone (GAME_PROGRESSION §1). (3) Le capstone **coûte 14 pb, pas un de plus** :
sa condition de combat lui donne déjà ×2 d'amplitude (§4.3), soit **+28 %** au
lieu de +14. Lui accorder en plus un budget majoré « parce qu'il ne s'applique pas
toujours » est la porte d'entrée classique des builds dégénérés.

---

## 7 bis. Le jeu de groupe asynchrone — la loi du dépôt

### 7 bis.1 Ce que le donjon de groupe est réellement

Mesuré dans `GroupDungeonCombatService` : un donjon de groupe est **tour par tour,
un seul joueur actif à la fois**, contre une rencontre à **PV partagés**. Un délai
par tour (45 s par défaut) borne l'attente, et au-delà **le tour d'un absent est
résolu tout seul** — par une attaque de base, résolue paresseusement au prochain
chargement d'écran. Aucune présence simultanée n'est requise, et c'est la
condition même du jeu (1 à 2 joueurs simultanés à 50 quotidiens).

**Ce que ce modèle interdit** : le soin réactif. « Un allié tombe à 20 %, je le
soigne » suppose que je sois là quand ça arrive. Dans un donjon dont les tours
peuvent s'étaler sur des heures, c'est une mécanique morte — et avec elle, tout
l'archétype d'entretien en groupe.

### 7 bis.2 La loi

> **Un geste qui touche le groupe ne réagit pas : il se dépose.** Il pose une
> durée sur les alliés, et cette durée agit **que son lanceur soit connecté ou
> non**.

> **Extension (correction issue du §9 bis) — la loi vaut pour toute `protection`,
> quelle que soit sa portée.** Une garde qui coûte un tour entier pour couvrir *ce*
> tour punit l'archétype d'encaisse de se défendre : il perd en dégâts exactement ce
> qu'il gagne en survie, et son tour défensif est toujours un mauvais calcul. Une
> protection **pose une absorption qui dure** — un tour payé, trois tours couverts.
> Ce qui était une règle de jeu de groupe devient une règle d'**intention**, et
> l'archétype d'encaisse redevient jouable seul.

Trois conséquences, et elles suffisent à faire exister quatre rôles en groupe :

| Ce qu'on dépose | Exemple | Ce que ça règle |
|---|---|---|
| **Une régénération de groupe** | à mon tour, tous les alliés récupèrent des PV pendant N tours de rencontre | Le soigneur agit **en avance** sur des dégâts qu'il ne verra pas tomber |
| **Un bouclier / une absorption** | un montant absorbé sur chaque allié, jusqu'à consommation ou expiration | L'encaisse **protège autrui** sans avoir à être ciblé — il n'existe pas d'aggro dans une rencontre à PV partagés |
| **Une amélioration** | +X % à un levier pour le groupe pendant N tours | Le contrôle et l'entretien pèsent sur les tours des autres, sans les jouer |

**La durée se compte en tours de la rencontre — jamais en temps réel, jamais en
« mes tours ».** C'est le seul compteur que le moteur possède déjà et que
l'asynchronie ne dérègle pas : un dépôt de 6 tours dure 6 actions du groupe,
qu'elles tombent en trois minutes ou en trois jours.

> **La durée étale la valeur, elle ne l'augmente pas** *(correction issue du
> §9 ter)*. La valeur totale d'un dépôt est fixée par le **palier de la matéria** ;
> la durée décide seulement de son étalement. Sans cette règle, allonger un dépôt
> est le levier le moins cher du jeu : mesuré, un dépôt de 10 tours sur quatre
> alliés vaut **14,7 tours d'attaque**, et un groupe sans entretien cesse d'être
> « plus lent » pour devenir non viable — exactement ce que le garde-fou ci-dessous
> interdit. Une durée longue n'achète pas de la puissance, elle achète de la
> **robustesse à l'absence** : c'est ce dont un donjon semi-synchrone a besoin, et
> c'est déjà bien assez.

> **Le dépôt multiplie par la taille du groupe, et c'est sa raison d'être.** Mesuré :
> un tour déposé vaut **2,2 tours d'attaque en solo** et **8,8 à quatre**. L'entretien
> et l'encaisse sont donc modestes seuls et décisifs en groupe **sans jouer un autre
> jeu** — même geste, autre portée. C'est la meilleure justification qu'on ait de la
> loi du dépôt, et elle impose son garde-fou : *la valeur par cible reste modeste* —
> c'est la multiplication qui fait la valeur, jamais le chiffre affiché.

### 7 bis.3 Ce que ça change pour les quatre fonctions

- **Entretien** — sa contribution de groupe **survit à sa déconnexion**. C'est le
  renversement complet du problème : le soigneur absent laisse quelque chose
  derrière lui, là où un absent ordinaire ne laisse qu'une attaque de base par
  défaut. *Le soin en donjon n'est plus une réaction, c'est une provision.*
- **Encaisse** — sa protection se déporte (`scope: le groupe`), ce qui lui donne
  un rôle collectif sans exiger le moindre système d'aggro. Une rencontre à PV
  partagés ne peut pas être « prise en charge » ; elle peut être **amortie**.
- **Contrôle** — l'entrave posée sur la rencontre vaut pour tous les tours
  suivants, donc pour les tours des autres. **Mais elle ne se multiplie pas par la
  taille du groupe**, et c'est mesuré (§9 quinquies) : un dépôt sur les alliés rend
  ×8,8 à quatre, une marque sur l'ennemi ×0,9. Le contrôle gagne au groupe la même
  chose qu'en solo — ni plus, ni moins.
- **Assaut** — inchangé, et c'est le point : il est **suffisant seul**. Aucune
  fonction n'a besoin d'un groupe pour exister ; le groupe multiplie les trois
  autres.

> **L'asymétrie du donjon semi-synchrone, et elle est structurelle** *(mesurée au
> §9 quinquies)*. **Un effet posé sur les alliés se multiplie par leur nombre ; un
> effet posé sur l'ennemi ne se multiplie pas** — il n'y a qu'un flux d'actions à
> améliorer, et un seul joueur le joue à la fois. Donc **l'entretien et l'encaisse
> gagnent mécaniquement au groupe, l'assaut et le contrôle n'y gagnent rien**. Ce
> n'est pas un défaut à corriger : c'est une propriété du modèle, et la nier
> reviendrait à équilibrer le contrôle comme un soutien qu'il n'est pas.

### 7 bis.2 bis Le direct et le dépôt — deux outils, jamais une interdiction

**La loi du dépôt n'interdit pas le soin direct.** Elle dit qu'un geste **qui
touche le groupe** se dépose, parce qu'on ne peut pas réagir pour quatre personnes
dont on ne voit pas les tours. Le soin direct, lui, reste parfaitement jouable —
et il est même le geste d'entrée naturel de l'entretien.

**Ce qui les sépare n'est pas la portée, c'est le moment.**

| | Soin direct | Dépôt |
|---|---|---|
| Ce que ça rend | 40 PV **à l'instant choisi** | 8 PV/tour × 6 tours = 48, **étalés** |
| En solo | l'**urgence** — le seul qui sauve quelqu'un à 20 PV | 48 PV, mais trop tard si on tombe au tour 2 |
| À quatre | 40 PV à **un** allié | **192** — il touche les quatre corps, chaque tour |
| Quand on est absent | rien | il court |

> **Le direct est l'urgence, le dépôt est la provision.** Un guérisseur solo joue
> surtout le premier, un guérisseur de donjon surtout le second — et c'est
> exactement ce que la fourche *le Ressac / la Marée* (§9.2) sépare.

**La règle fine, et elle vaut pour tout le reste** *(précision du §9 quinquies)* :

> **Ce qui agit sur un état se multiplie par le nombre d'alliés. Ce qui agit sur
> une action ne se multiplie pas.**

Parce qu'un seul joueur agit par tour. Un soin, une absorption, une résistance
touchent **quatre corps** à chaque tour → ×4. Une amélioration de dégâts ne touche
que **l'action du tour** → ×1, qu'on soit un ou quatre.

#### Ce que ça décide pour les améliorations *(le « barde »)*

| Ce qu'on dépose, 6 tours | Valeur à quatre | En tours d'attaque |
|---|---:|---:|
| Un soin *(état, 4 corps)* | 192 PV | **×8,7** |
| Une amélioration de dégâts *(action, 1/tour)* | 20 dégâts | **×0,9** |

Un archétype de barde **n'est donc pas un archétype de groupe** dans notre
modèle : son amélioration vaut la même chose seul et à quatre. C'est le même
constat que pour le contrôle (§9 quinquies), et la même compensation — **sa valeur
ne dépend pas du contexte.**

**Le curseur qui en découle**, et il est simple :

> **Un tour passé à améliorer doit valoir au plus un tour passé à frapper.**
> Une amélioration de +X % pendant N tours rend N × X % d'un tour. Donc
> **N × X ≤ 100**.

| Durée du dépôt | Ampleur maximale |
|---:|---:|
| 6 tours | **+16,7 %** |
| 8 tours | **+12,5 %** |
| 10 tours | **+10 %** |

Au-delà, améliorer bat frapper — et l'archétype optimal devient « ne jamais
attaquer ». C'est le garde-fou le moins cher du document : une multiplication.

> **Un barde qui veut compter en groupe doit donc améliorer un *état*, pas une
> *action*** : +PV maximum, une absorption, une résistance. Ceux-là se multiplient
> par quatre. C'est ce qui le rapproche de l'entretien plutôt que du contrôle.

> **Le garde-fou qui va avec** : un dépôt ne rend jamais un joueur *nécessaire*.
> Un groupe sans entretien met plus de tours et perd plus de PV — il ne se heurte
> pas à un mur. Exiger un rôle, c'est exiger une présence, et exiger une présence
> dans un jeu à 1-2 joueurs simultanés, c'est fermer le contenu.

> **Et l'effet de bord qu'on garde** : ces gestes déposés fonctionnent **aussi en
> solo**, en `scope: soi`. Le soigneur solo ne joue pas un autre jeu que le
> soigneur de donjon — il joue les mêmes gestes sur une seule cible. C'est la
> condition pour que l'archétype ne soit pas deux archétypes.

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

## 8 bis. Ce que l'écran doit dire

Un système de nuances qui ne se lit pas est un système de hasard. Cinq exigences
d'interface, qui sont des **décisions de design** et pas du confort :

1. **Ce qui s'exprime, et ce qu'il faudrait porter.** L'écran des arbres marque
   déjà « Exprimé / Non exprimé » et dit quoi porter (DOM-02, livré). Les
   conditions d'équipement étendent la même exigence au **nœud** : un passif
   inactif dit *pourquoi*.
2. **Un nœud conditionnel affiche ses deux valeurs** — nue et remplie — et sa
   condition en toutes lettres. Un chiffre qui change quand on change de gants,
   sans que rien ne l'explique, est la pire chose qu'on puisse livrer.
3. **La fourche se compare avant de se choisir** : les deux branches côte à côte,
   et le **prix du respec** affiché au moment du choix, pas après.
4. **Le pacte montre son malus avant l'apprentissage**, et le net après. On assume
   un choix ; on ne se fait pas piéger par une ligne de description.
5. **La cible affiche les marques qu'elle porte.** Sans ça, le capstone d'assaut
   est un bonus invisible — et un bonus invisible n'existe pas.

> **La règle qui les résume** : *un bonus silencieusement inactif est un bug
> d'interface, pas un choix de build.* Le joueur a le droit de se tromper ; il n'a
> pas à deviner.

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
| Entrée | 0 | **Accord : Boule de feu** — `dégât`, `une cible` | le geste — dégâts francs, 5 PM | — |
| Entrée | 0 | **Accord : Flammèche** — `dégât`, `une cible` | le geste économe (3 PM) qui **applique la brûlure** — le plan B quand le pool est vide, et la mise en place du capstone | — |
| 1 | 10 | Souffle d'attisage | `power` **+3 %** | 3 |
| 1 | 10 | Points faibles | `critical` **+1,5 pt** | 3 |
| 1 | 10 | **Accord : Mur de feu** — `entrave`, `plusieurs cibles` | le geste de temporisation : il ne tue pas, il retient | — |
| 1 | 10 | *Port* : canal de sort, échelon 2 | bâtons et baguettes de palier 2 | — |
| 2 | 25 | Cœur de braise | `critical_power` **+9 %** | 6 |
| 2 | 25 | Chaleur sèche | `critical` **+4,2 pt** *en tissu* (×1,4, **en escalier** : +0,84 pt par pièce) | 6 |
| 2 | 25 | **Accord : Pluie de flammes** — `dégât`, `plusieurs cibles` | le geste multi-cible | — |
| 2 | 25 | *Port* : canal de sort, échelon 3 | bâtons et baguettes de palier 3 | — |
| 3 | 50 | **Fourche — la Braise** · Braise durable | `grip` **+10,8 %** *(teinte)* | 9 |
| 3 | 50 | **Fourche — la Braise** · Souffle de forge | `critical_power` **+13,5 %** | 9 |
| 3 | 50 | **Fourche — l'Éclat** · Fonte des écailles | `pierce` **+6,3 pt** | 9 |
| 3 | 50 | **Fourche — l'Éclat** · Départ de feu | `tempo` **+9 %** | 9 |
| 3 | 50 | **Fourche — la Braise** · **Accord : Brasier** — `dégât`, `plusieurs cibles`, **sur la durée** | il ne frappe pas, il **reste** — et `grip` l'allonge | — |
| 3 | 50 | **Fourche — l'Éclat** · **Accord : Nova de feu** — `dégât`, `plusieurs cibles` | le geste de pointe, tout de suite | — |
| **Capstone** | 100 | **Foyer entretenu** | `power` **+28 %** *contre une cible qui brûle* (14 pb × 2) | 14 |
| *Dormant* | *150* | *Accord d'hybride (feu)* | *réservé — DOM-07* | — |

**La fourche.** Chaque branche ouvre **son geste** (§6.1 bis, règle 5) : le Brasier
qui reste sur le terrain, ou la Nova qui tombe tout de suite. *La Braise* tient le
feu qui **dure** — des brûlures plus tenaces,
et un critique qui fait mal quand il tombe. *L'Éclat* tient le feu qui **passe** —
il ronge la résistance et frappe le premier. Même fonction, deux façons ; et c'est
dans la Braise seule que vit la teinte `grip`.

**Vérification.** 50 pb pile **par branche** · palette (assaut) 41 ou 50 ≥ 40 ✔ ·
hors palette ≤ 9 sur un seul levier ✔ · `power` 17 ≤ 20 ✔ · `critical` 9 ≤ 12 ✔ ·
`critical_power` 15 ≤ 15 ✔ (branche Braise) · branches sans levier commun ✔ ·
condition du capstone atteignable **au tour 2 avec le seul kit d'entrée**
(Flammèche applique la marque) ✔.
**Intentions** : 4 `dégât` ≥ 3 ✔ · 1 accord non-`dégât` (Mur de feu) ✔ · un accord
d'entrée applique la **Brûlure** ✔.

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

> **L'arbitrage qui décide de cet archétype : il ne soigne pas, il provisionne.**
> Le jeu est asynchrone — 1 à 2 joueurs simultanés à 50 quotidiens (GAME_WORLD
> §13.4), et un donjon de groupe se joue un tour à la fois, sur des heures. Un
> soigneur **réactif** y serait injouable : il n'est pas là quand l'allié tombe.
> Tous ses gestes collectifs sont donc des **dépôts** (§7 bis) — une régénération,
> un bouclier, une amélioration posés à son tour, qui courent sur les tours des
> autres, **connecté ou non**. En solo, ce sont exactement les mêmes gestes en
> `scope: soi` : ne jamais tomber, ne jamais boire de potion, et ressortir d'un
> combat de vingt tours avec la même barre qu'en entrant.
>
> Ce n'est pas deux archétypes : c'est le même, avec une portée différente. **Et il
> garde un soin direct** — le geste d'urgence, celui qui sauve quelqu'un à 20 PV
> quand aucune provision ne le ferait à temps (§7 bis.2 bis).

> **Et il a une vertu de PBBG que les trois autres n'ont pas** : l'énergie d'action
> se paie **par combat, jamais par tour** (GAME_PROGRESSION §1). Un archétype qui
> gagne en durant convertit donc du *temps de combat* — gratuit — en survie. Son
> rendement par point d'énergie est le meilleur du jeu, et c'est la compensation
> exacte de son plafond de dégâts.

**Les quinze nœuds**

| Palier | Coût | Nœud | Ce qu'il donne | pb |
|---|---:|---|---|---:|
| Entrée | 0 | **Accord : Soin** — `soin`, `soi ou un allié`, **direct** | le geste d'**urgence** : tout, tout de suite, au moment choisi (§7 bis.2 bis) | — |
| Entrée | 0 | **Accord : Jet d'eau** — `dégât`, `une cible` | le geste offensif modeste : **sans lui, un combat ne finit jamais** (§5.1). Il applique **Trempé** — la marque, donc la condition du capstone | — |
| 1 | 10 | Main sûre | `mending` **+3 %** | 3 |
| 1 | 10 | Geste économe | `thrift` **−1,8 %** | 3 |
| 1 | 10 | **Accord : Rosée** — `soin`, `soi ou un allié`, **déposée** | la **provision** personnelle : moins par tour, mais elle court quand on ne joue pas | — |
| 1 | 10 | *Port* : canal de sort, échelon 2 | — | — |
| 2 | 25 | Seconde respiration | `wind` **+0,6 PM/tour** | 6 |
| 2 | 25 | Sang-froid | `ward` **+6 %** | 6 |
| 2 | 25 | **Accord : Marée** — `soin`, **`le groupe`**, dépôt 6 tours | **le dépôt**, et il est au palier 2 : les deux branches l'ont, donc le soigneur sert son groupe quel que soit son choix | — |
| 2 | 25 | *Port* : canal de sort, échelon 3 | — | — |
| 3 | 50 | **Fourche — le Ressac** · Sourdre | `recovery` **+2,25 % des PV max par tour** | 9 |
| 3 | 50 | **Fourche — le Ressac** · Écume | `guard` **−7,6 %** *si un bouclier ou un focus occupe la main gauche* (×1,4) — *teinte* | 9 |
| 3 | 50 | **Fourche — la Marée** · Litanie | `thrift` **−5,4 %** | 9 |
| 3 | 50 | **Fourche — la Marée** · Eaux calmes | `ward` **+9 %** | 9 |
| 3 | 50 | **Fourche — le Ressac** · **Accord : Dissipation** — `protection`, `soi ou un allié` | retirer un statut : la réponse **solitaire** aux poisons et aux entraves | — |
| 3 | 50 | **Fourche — la Marée** · **Accord : Grande Marée** — `soin`, **`le groupe`**, dépôt 10 tours | le dépôt qui couvre une rencontre entière | — |
| **Capstone** | 100 | **Ressac** | `mending` **+28 %** *sur une cible sous 40 % de ses PV* (14 pb × 2) | 14 |
| *Dormant* | *150* | *Accord d'hybride (eau)* | *réservé* | — |

**La fourche.** La Marée — le dépôt de groupe — est au **palier 2**, donc les deux
branches l'ont : un guérisseur sert son groupe quel que soit son choix. La fourche
décide seulement *jusqu'où*. *Le Ressac* tient **seul** — il se régénère et amortit, c'est le
soigneur qui n'a besoin de personne. *La Marée* tient **le groupe** — elle dépose
plus souvent (`thrift`) et ne se laisse pas interrompre (`ward`). La teinte `guard`
n'existe que dans le Ressac : le soigneur de donjon n'a pas de main gauche à donner
à un bouclier.

**Vérification.** 50 pb **par branche** · palette 41 ou 50 ≥ 40 ✔ · teinte `guard`
9 ≤ 10 ✔ · `mending` 17 ≤ 20 ✔ · `recovery` 9 ≤ 12 ✔ · `ward` 15 ≤ 15 ✔ (branche
Marée) · `thrift` 12 ≤ 15 ✔ · branches sans levier commun ✔.
**Intentions** : 3 `soin`/`protection` dont **un de portée `le groupe`** ✔ ·
1 `dégât` ✔ · un accord d'entrée applique **Trempé** ✔.

**Test du voisin** : Hydromancien (eau × sorts × contrôle) et Marémancien (eau ×
sorts × assaut) partagent la case et **aucun levier principal** avec lui. ✔

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
| Entrée | 0 | **Accord : Frappe appuyée** *(technique)* — `dégât`, `une cible` | le geste — sans temps de reprise, et il laisse l'**Entaille** |  — |
| Entrée | 0 | **Accord : Garde haute** *(technique)* — `protection`, `soi`, **dépôt 3 tours** | un tour payé, trois tours couverts (§7 bis) — le plan B, et la mise en place du capstone | — |
| 1 | 10 | Œil du drill | `hit` **+1,5 pt** | 3 |
| 1 | 10 | Discipline | `ward` **+3 %** | 3 |
| 1 | 10 | **Accord : Estoc brisant** *(technique)* — `dégât`, `une cible` | le geste qui perce l'armure | — |
| 1 | 10 | *Port* : plaque, échelon 2 | l'armure lourde de palier 2 | — |
| 2 | 25 | Endurance de marche | `life` **+9 %** | 6 |
| 2 | 25 | Garde travaillée | `hit` **+4,2 pt** *si un bouclier est équipé* (×1,4) | 6 |
| 2 | 25 | **Accord : Mur de boucliers** *(technique)* — `protection`, **`le groupe`**, 4 tours | **le dépôt de l'encaisseur** : une absorption sur chaque allié. Une rencontre à PV partagés ne se « prend » pas — elle s'amortit (§7 bis) | — |
| 2 | 25 | *Port* : plaque, échelon 3 | l'armure lourde de palier 3 | — |
| 3 | 50 | **Fourche — le Mur** · Carcasse | `life` **+13,5 %** | 9 |
| 3 | 50 | **Fourche — le Mur** · Pied ferme | `ward` **+9 %** | 9 |
| 3 | 50 | **Fourche — la Ligne mobile** · Jeu de jambes | `dodge` **+3,15 pt** | 9 |
| 3 | 50 | **Fourche — la Ligne mobile** · Bras d'acier | `power` **+9 %** *(teinte)* | 9 |
| 3 | 50 | **Fourche — le Mur** · **Accord : Rempart** *(technique)* — `protection`, **`le groupe`**, dépôt long | ce qu'on vient chercher en donjon | — |
| 3 | 50 | **Fourche — la Ligne mobile** · **Accord : Charge d'acier** *(technique)* — `dégât`, `une cible` | 60 dégâts, reprise 4 — de quoi finir un combat seul | — |
| **Capstone** | 100 | **Tenir la ligne** | `guard` **−11,8 %** *au tour qui suit un coup encaissé* (14 pb × **1,4** — la condition est vraie presque tous les tours, §4.3) | 14 |
| *Dormant* | *150* | *Accord d'hybride (métal)* | *réservé* | — |

**La fourche, et c'est la plus parlante des quatre.** *Le Mur* encaisse et donne —
PV, sang-froid, et le Rempart qu'on vient chercher en donjon. *La Ligne mobile*
évite et finit — esquive, riposte, et une Charge qui règle un combat seul. **Ce
n'est pas offensif contre défensif : c'est en groupe contre seul**, et les deux
tiennent la fonction *encaisse*. Le tank en cuir n'est plus un mélange exotique — il
est écrit dans l'arbre du Soldat, et le mélanger au Vagabond ne fait que le pousser
plus loin (§9.6). Chiffré tour par tour en **§9 bis.4**.

**Vérification.** 50 pb **par branche** · palette 41 ou 50 ≥ 40 ✔ · teinte `power`
9 ≤ 10 ✔ · `life` 15 ≤ 20 ✔ · `hit` 9 ≤ 10 ✔ · `ward` 12 ≤ 15 ✔ · `dodge` 9 ≤ 12 ✔ ·
`guard` 14 ≤ 15 ✔ · branches sans levier commun ✔ · condition atteignable au tour 2
(il suffit d'être frappé — et il le sera) ✔.
**Intentions** : 2 `protection` dont **une de portée `le groupe`** ✔ · 3 `dégât` ✔ ·
un accord d'entrée applique l'**Entaille** ✔.

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
| Entrée | 0 | **Accord : Tir tendu** *(technique)* — `dégât`, `une cible` | le geste — une munition ordinaire | — |
| Entrée | 0 | **Accord : Tir entravant** *(technique)* — `entrave`, `une cible` | applique le **Déséquilibre** — le plan B, et la condition du capstone | — |
| 1 | 10 | Souffle court | `critical` **+1,5 pt** | 3 |
| 1 | 10 | Bras d'arc | `power` **+3 %** | 3 |
| 1 | 10 | **Accord : Volée** *(technique)* — `dégât`, `plusieurs cibles` | deux munitions | — |
| 1 | 10 | *Port* : arc, échelon 2 | l'arc de palier 2 | — |
| 2 | 25 | Lecture du vent | `critical` **+4,2 pt** *si aucun bouclier n'est porté* (×1,4) | 6 |
| 2 | 25 | Encoche haute | `critical_power` **+9 %** | 6 |
| 2 | 25 | **Accord : Flèche de fracture** *(technique)* — `dégât`, `une cible` | ouvre la garde — dégâts sur la durée | — |
| 2 | 25 | *Port* : arc, échelon 3 | l'arc à poulie de palier 3 | — |
| 3 | 50 | **Fourche — le Guet** · Œil du faucon | `critical_power` **+13,5 %** | 9 |
| 3 | 50 | **Fourche — le Guet** · Avantage du guet | `tempo` **+9 %** | 9 |
| 3 | 50 | **Fourche — la Volée** · Pointe affûtée | `pierce` **+6,3 pt** | 9 |
| 3 | 50 | **Fourche — la Volée** · Trait récupéré | `wind` **+13,5 % de récupération de munition** *(teinte)* | 9 |
| 3 | 50 | **Fourche — le Guet** · **Accord : Tir du faucon** *(technique)* — `dégât`, `une cible` | le gros coup préparé, reprise 3 | — |
| 3 | 50 | **Fourche — la Volée** · **Accord : Grêle** *(technique)* — `dégât`, `plusieurs cibles` | quatre munitions, et `wind` en récupère la moitié | — |
| **Capstone** | 100 | **Trait dans le vent** | `power` **+28 %** *contre une cible ralentie ou entravée* (14 pb × 2) | 14 |
| *Dormant* | *150* | *Accord d'hybride (air)* | *réservé* | — |

**La fourche.** Chaque branche ouvre son geste : le Tir du faucon qu'on prépare, ou
la Grêle qu'on paie en munitions. *Le Guet* prépare — un gros coup, et le droit de le tirer en
premier. *La Volée* entretient la cadence — elle perce et elle **coûte moins**, ce
qui pour ce registre veut dire quelque chose de très concret : moins de flèches
achetées. La teinte `wind` n'est que dans la Volée.

**Vérification.** 50 pb **par branche** · palette 41 ou 50 ≥ 40 ✔ · teinte `wind`
9 ≤ 10 ✔ · `power` 17 ≤ 20 ✔ · `critical` 9 ≤ 12 ✔ · `critical_power` 15 ≤ 15 ✔
(branche Guet) · branches sans levier commun ✔ · condition atteignable au tour 2
(Tir entravant, accord d'entrée) ✔.
**Intentions** : 4 `dégât` ≥ 3 ✔ · 1 accord non-`dégât` (Tir entravant) ✔ · un
accord d'entrée applique le **Déséquilibre** ✔.

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
| **Ce qu'il dépose en donjon** | rien — il frappe | une régénération de groupe | une absorption de groupe | une entrave qui vaut pour tous les tours suivants |
| **Ce qu'il récompense de porter** | la ligne tissu | un focus en main gauche | un bouclier | pas de bouclier — les deux mains à l'arc |
| **Sa marque** | Brûlure | Trempé | Entaille | Déséquilibre |
| **Sa fourche** | la Braise *(durer)* / l'Éclat *(passer)* | le Ressac *(tenir seul)* / la Marée *(tenir le groupe)* | le Mur *(encaisser)* / la Ligne mobile *(éviter)* | le Guet *(préparer)* / la Volée *(cadencer)* |

**La lecture d'ensemble**, et c'est elle qui valide le modèle : **aucune ligne de
ce tableau n'est un chiffre.** Quatre archétypes se distinguent par leur rapport
au temps, à la matière et à ce qu'ils portent — pas par leur puissance, laquelle
est identique (50 pb) et bornée par la même grille.

### 9.6 Trois mélanges que ces règles rendent possibles

C'est le test réel des passifs conditionnels (§4.3) : **est-ce qu'ils donnent
envie de panacher ?** Rappel du cadre — un domaine ne s'exprime que si le build en
porte une source (DOM-02), et une arme plus trois emplacements font mécaniquement
deux à trois domaines actifs.

**1. Le tank en cuir** — Soldat *(métal × mêlée × encaisse)*, **branche la Ligne
mobile** (§9.3) + Vagabond *(air × mêlée × contrôle)*. Porté : **cuir, bouclier,
épée**.
Il garde du Soldat tout ce qui n'exige pas la plaque — `life`, le capstone
« Tenir la ligne », et « Garde travaillée » que **son bouclier** valide. Il prend
du Vagabond l'`dodge` et le `tempo` conditionnés au **cuir**. Il perd les nœuds du
Soldat conditionnés à la plaque.
*Résultat* : un encaisseur **par l'évitement** plutôt que par l'absorption — plus
volatil, plus rapide, et il joue plus de tours. Personne ne lui a interdit la
plaque : il a simplement trouvé mieux ailleurs.

**2. Le duelliste à deux dagues** — Assassin *(ténèbres × mêlée × assaut)* +
Vagabond. Porté : **cuir, dague, dague**.
Il cumule les conditions « une dague en main » et « main gauche armée », et
renonce à tout ce qui exige un bouclier — donc à `guard`. Il encaisse par
l'esquive ou pas du tout.
*Résultat* : le build le plus tranchant du jeu, et le plus fragile. C'est un choix
lisible, pas un piège : les nœuds qu'il n'exprime pas **disent** ce qu'il faudrait
porter.

**3. Le soutien de donjon** — Guérisseur *(entretien)* + Archer *(assaut ×
distance)*. Porté : **cuir, arc, focus en main gauche**.
En donjon : il dépose la Marée à son tour, entrave la rencontre, et tire. En solo :
la même régénération sur lui-même, et le même arc.
*Résultat* : le rôle collectif existe **sans exiger la simultanéité** — c'est la
loi du dépôt (§7 bis) qui le rend jouable, pas une mécanique de groupe en plus.

> **Ce qui donne envie de mener un domaine**, et qui se vérifie sur les trois
> mélanges : (1) une **promesse** qu'aucun autre ne tient ; (2) un **accord
> exclusif** — un geste qu'on ne verra nulle part ailleurs (§5.1) ; (3) des passifs
> conditionnés qui récompensent une **tenue reconnaissable** — on voit un archétype
> à ce qu'il porte, avant même qu'il agisse ; (4) une **fourche** qui fait que deux
> praticiens du même arbre ne se ressemblent pas ; (5) un **capstone** dont la
> condition raconte sa façon de jouer. Un arbre qui n'a que le dernier est un arbre
> qu'on prend « en passant ».

### 9.7 Décision 15 — Les accointances : récompenser le mélange sans le rendre obligatoire

Le jeu porte déjà des **synergies inter-domaines** (`SynergyCalculator`,
`DomainSynergy` — actives quand deux arbres dépassent un seuil d'XP commun). Elles
donnent aujourd'hui des **statistiques plates** : « Forge ardente » (pyromancie +
soldat) rend `damage +10`, « Purification » `heal +15`.

**C'est une fuite de budget, et elle est franche** : ces bonus s'ajoutent dans
`CombatSkillResolver` **hors de tout arbre**, donc hors des 50 pb, hors des
plafonds par levier, hors des palettes. Un système qui compte soigneusement 50
points et laisse une porte de service à +10 ne compte rien.

> **Décision : une accointance ne donne jamais de puissance. Elle donne de la
> souplesse.**

Quatre formes légales, et rien d'autre :

| Forme | Exemple |
|---|---|
| **Élargir ce qui satisfait une condition** | **Soldat + Vagabond — « Pied sûr »** : les passifs conditionnés « en cuir » sont aussi satisfaits par la maille |
| **Élargir ce qui exprime un domaine** (DOM-02) | **Pyromancien + Artificier — « Poudre »** : une munition de feu compte comme source du domaine de pyromancie — le pyromancien s'exprime en tirant |
| **Élargir ce qu'un emplacement accepte** | **Guérisseur + Prêtre — « Liturgie »** : un emplacement de sort accepte une matéria de l'élément voisin |
| **Réduire un coût d'accès** | **Archer + Charpentier — « Fût droit »** : l'échelon 3 de port de l'arc coûte un palier de moins |

**Trois règles.** (1) **Une accointance par paire**, et son effet est unique — pas
une liste qui s'allonge. (2) **Jamais nécessaire** : aucun build, aucune recette,
aucun contenu n'en dépend. (3) **Elle ne rapporte aucun point de budget ni aucun
levier** — c'est l'invariant qui la distingue d'un bonus déguisé, et il est
testable en une ligne.

> **Ce que ça change dans la sensation** : une synergie qui donne +10 dégâts pousse
> à monter deux arbres pour être plus fort. Une accointance qui lève une condition
> pousse à monter deux arbres pour **jouer autrement** — porter ce qu'on ne pouvait
> pas porter, sertir ce qu'on ne pouvait pas sertir. C'est la même invitation au
> mélange, sans la course à la puissance.

---

## 9 bis. Un exemple complet — Roshen, soldat, du jour 3 au mois 3

Tout ce qui précède est une grammaire. Voici une phrase. **Cet exercice n'est pas
une illustration : il a produit trois corrections au document**, listées en §9 bis.6
— c'est pour ça qu'il est ici.

> **Les valeurs sont illustratives.** Elles respectent l'ancre du §6.4 (un geste de
> palier *n* retire ~25 % des PV d'un adversaire commun de palier *n*) mais les
> nombres réels viennent d'**ARC-05**. Ce qui compte ici, ce sont les **écarts**,
> pas les unités. Repères T2 : joueur 120 PV · commun 100 PV, frappe 9 · élite
> 180 PV, frappe 16 · précision de base 85 %, critique 5 % à ×1,5.

### 9 bis.1 Jour 3 — deux accords et rien d'autre

Roshen a lu le parchemin du Soldat. Il n'a **dépensé aucun point** : il a les deux
accords gratuits, une épée de bois et une tunique de lin.

| Ce qu'il a | D'où ça vient |
|---|---|
| **Frappe appuyée** — 25 dégâts, aucune reprise, laisse l'**Entaille** | accord d'entrée, 0 pt |
| **Garde haute** — absorbe la moitié du coup, reprise 2 | accord d'entrée, 0 pt |
| L'attaque de base de son épée | gratuite, règle 10 — et elle ne lit aucun passif |

**Son premier vrai combat**, contre un commun T2 (100 PV) :

| Tour | Ce qu'il fait | État |
|---|---|---|
| 1-2 | Frappe appuyée ×2 | la cible à 56 PV, elle porte l'Entaille |
| 3 | Garde haute — il a vu venir le gros coup | il encaisse 4 au lieu de 9 |
| 4-5 | Frappe appuyée ×2 | la cible tombe |

**5 tours, il sort à 82/120 PV.** Dans la fourchette « commun : 3 à 5 tours » du
§6.4 — au plafond, parce qu'il a dépensé un tour à se garder. C'est déjà l'archétype
en entier : *il ne tue pas vite, il ne meurt pas.*

### 9 bis.2 Semaine 4 — la première condition s'allume

Roshen a 40 points dans le domaine (palier 1 tombé en semaine 1, palier 2 en cours),
et il vient de faire forger un **bouclier**.

| Ce qui change | Effet | Pourquoi |
|---|---|---|
| « Œil du drill » | `hit` **+1,5 pt** | palier 1, sans condition (§4.3, garde-fou 1) |
| « Garde travaillée » | `hit` **+4,2 pt** | palier 2, **conditionné au bouclier** (×1,4) |
| Précision totale | 85 % → **90,7 %** | |

**Ce que ça vaut, concrètement** : sur un combat de onze tours, c'est **un coup de
plus qui touche**. Ce n'est pas spectaculaire, et c'est normal — un levier de
pourcentage ne fait pas une sensation (§9 bis.6, constat A). Ce qui fait la
sensation, ce jour-là, c'est l'écran : *« Garde travaillée — inactive : exige un
bouclier »*, lu la semaine d'avant. Roshen a forgé le bouclier **pour cette
ligne-là**.

### 9 bis.3 Mois 2 — la fourche, telle qu'il la voit

Palier 3. L'écran lui présente deux branches côte à côte, et le prix du respec
(§8 bis, exigence 3) :

| | **le Mur** | **la Ligne mobile** |
|---|---|---|
| Passif | `life` **+13,5 %** | `dodge` **+3,15 pt** |
| Passif | `ward` **+9 %** | `power` **+9 %** |
| **Accord** | **Rempart** — `protection`, `le groupe`, dépôt long | **Charge d'acier** — `dégât`, 60, reprise 4 |
| Ce qu'il devient | celui qu'on veut **dans un donjon** | celui qui **se débrouille seul** |
| Ce qu'il perd | il ne tuera jamais vite | il n'a rien à donner aux autres |

**Ce n'est pas « offensif contre défensif »** — les deux tiennent la fonction
*encaisse*. C'est **seul contre en groupe**, et c'est une opposition que le
document n'avait pas prévue : elle est sortie de l'arithmétique (§9 bis.6,
constat B).

Roshen joue surtout seul, avec une guilde le week-end. Il prend **la Ligne
mobile**, et note que le respec coûte 2 500 gils s'il se trompe.

### 9 bis.4 Mois 3 — l'arbre fini, face à une élite

Arbre complet, capstone « Tenir la ligne » acquis. Le même adversaire (élite T2,
180 PV, frappe 16), affronté par les deux branches :

| | **le Mur** | **la Ligne mobile** |
|---|---|---|
| PV | 147 *(+22,5 %)* | 131 *(+9 %)* |
| Dégâts par frappe | 25 | 27 *(+9 %)* |
| Précision | 90,7 % | 90,7 % |
| Réduction | `guard` **−11,8 %** au tour qui suit un coup encaissé | idem, plus **3,15 %** d'esquive |
| **Durée du combat** | **11 tours** | **9 tours** |
| **PV restants** | **68 / 147** | **78 / 131** |

Et le déroulé de la Ligne mobile, tour par tour :

| Tour | Action | Ce qui se passe |
|---|---|---|
| 1 | **Garde haute** | l'absorption est **posée pour 3 tours** — elle ne se rejoue pas à chaque coup |
| 2-4 | Frappe appuyée ×3 | l'Entaille court ; le capstone s'allume dès le tour 2 (il a été frappé) |
| 5 | **Charge d'acier** | 60 dégâts — l'élite est à moitié |
| 6 | Garde haute | la couverture repart |
| 7-8 | Frappe appuyée ×2 | |
| 9 | **Charge d'acier** | l'élite tombe |

**Ce que l'écart dit** : 2 tours et 10 PV séparent les deux branches. Ce n'est pas
un écart de puissance — c'est un écart de **forme**. Le Mur passe onze tours à ne
presque rien risquer ; la Ligne mobile en passe neuf à alterner un gros geste et
une garde. Les deux sont des soldats.

### 9 bis.5 Le samedi — le donjon, et la déconnexion

Roshen entre à quatre dans un donjon T2. C'est son ami Terel qui a pris **le Mur**.

| | Ce qui se passe |
|---|---|
| Tour 1 (Terel) | Il pose **Rempart** — une absorption sur **les quatre**, pour 6 tours de rencontre |
| Tour 2-3 | Deux autres jouent, l'absorption les couvre |
| **Terel se déconnecte** | Son tour suivant est résolu tout seul : une attaque de base (`GroupDungeonCombatService`) |
| Tours 4-6 | **Rempart court toujours.** Il avait été *déposé*, pas *joué en réaction* |
| Tour 7 | L'absorption expire ; Terel n'est pas revenu |

**C'est toute la loi du dépôt** (§7 bis) en une scène : la contribution de Terel a
survécu à son absence. Dans un donjon dont les tours s'étalent sur des heures,
c'est la seule forme de jeu de groupe qui tienne — et c'est pour ça que le Mur est
la branche « donjon » sans qu'aucune règle n'oblige personne à la prendre. Un
groupe sans Mur met plus de tours et perd plus de PV ; il ne rencontre pas un mur.

### 9 bis.6 Ce que l'exercice a révélé — trois corrections

**Constat A — un levier de pourcentage ne se sent pas.** +9 % de dégâts font passer
une frappe de 25 à 27 : sur un combat de onze tours, **cela ne retire pas un seul
tour**. Ce qui se sent, ce sont les gestes (une Charge à 60), les états binaires
(l'esquive, la marque) et les durées (un dépôt de 3 tours).

> **Ce constat ne casse rien : il confirme la thèse du §0, et il la chiffre.**
> *L'archétype est dans le couple (arbre, matéria), pas dans l'arbre.* Les 50 points
> de budget **qualifient** un geste ; ils ne le remplacent pas. Un auteur qui
> chercherait l'identité d'un arbre dans ses pourcentages perdra son temps — et le
> budget serré est ce qui l'empêche d'essayer.

**Correction 1 — la fourche porte aussi un accord.** *Avant* correction, les deux
branches du Soldat tuaient l'élite en **11 tours toutes les deux**, et se
séparaient de 13 PV : le choix était cosmétique. En donnant à chaque branche **son
geste**, elles se séparent de 2 tours et de deux façons de jouer. Appliqué en
§6.1 bis, et aux quatre arbres du §9.

**Correction 2 — toute protection se dépose, pas seulement celles du groupe.**
Garde haute coûtait un tour entier pour couvrir un tour : un archétype d'encaisse
était **puni de se défendre**. Elle pose désormais une absorption de 3 tours — un
tour payé, trois tours couverts. La loi du dépôt (§7 bis) cesse d'être une règle de
groupe pour devenir une règle d'`intent : protection`.

**Correction 3 — le multiplicateur suit la fréquence réelle, pas la famille.** La
condition du capstone du Soldat — « vous avez encaissé au tour précédent » — est
vraie **dès le tour 2 et jusqu'à la fin** pour quelqu'un qui se bat au contact. La
payer ×2,0 comme une condition qui peut manquer était une erreur de comptage.
Appliqué en §4.3 : *une condition de combat vraie plus des deux tiers du temps se
paie au tarif d'une condition de build (×1,4).*

---

## 9 ter. Le second exemple — le Guérisseur, et ce qu'il casse

Le §9 bis a modélisé un archétype qui paie en **temps de reprise**. Le Guérisseur
paie en **PM** — et c'est justement ce que le premier exercice n'avait pas eu
besoin de modéliser. Mêmes repères T2.

### 9 ter.1 Le combat seul — un curseur, pas une contrainte

Guérisseur branche *le Ressac*, mois 3, face à une élite (180 PV, frappe 16). Son
geste offensif vaut 25 et **ne progresse jamais** : l'entretien n'a aucun levier de
dégâts. Il choisit, tour par tour, entre frapper et déposer.

| Tours de Rosée déposés | Durée du combat | PV restants |
|---:|---:|---:|
| 0 | **9 tours** | 31 / 120 |
| 1 | **10 tours** | 54 / 120 |
| 2 | **11 tours** | 77 / 120 |

**C'est le profil « rebond » en trois lignes** : chaque tour qu'il ne passe pas à
frapper lui rend plus que ce qu'il coûte. Il ne subit pas la durée du combat, **il
la choisit** — et c'est le seul archétype des quatre dont le joueur règle
lui-même le curseur risque/temps, à chaque tour.

### 9 ter.2 La convergence avec le Soldat — le problème

| | Soldat *(le Mur)* | Guérisseur *(le Ressac, 2 dépôts)* |
|---|---:|---:|
| Durée | 11 tours | 11 tours |
| PV restants | 68 / 147 *(46 %)* | 77 / 120 *(64 %)* |
| Tours dépensés à se protéger | 3 | 2 |
| **Ressource consommée** | **aucune** | **~108 PM sur un pool de 120** |

**Sur un combat, les deux archétypes convergent.** Même durée, survie comparable —
et le « plafond de dégâts bas » promis à l'entretien ne vaut en réalité que −25 %
face à un assaut. Si l'on s'arrêtait là, le guérisseur serait le meilleur
solitaire du jeu : aussi endurant qu'un tank, presque aussi rapide, et pilier en
donjon par-dessus le marché.

**La dernière ligne est la réponse, et je ne l'avais pas modélisée.** Le soldat
sort du combat prêt à recommencer ; le guérisseur sort avec un pool vide. À
~16 combats par jour (§6.2), ce n'est pas le combat qui les sépare — **c'est la
journée**.

### 9 ter.3 Les PM, ou la vraie différence entre deux arbres de sorts

Pool 120, régénération +6/tour, coûts de la grille GAME_MATERIA §2.3 :

| Qui | Son geste | Combien de tours avant la panne |
|---|---|---:|
| Pyromancien qui enchaîne ses **Nova** (m3, 20 PM) | le pic | **8 tours** |
| Pyromancien retombé sur sa **Boule de feu** (m1, 10 PM) | le fond | 28 tours |
| Guérisseur, `thrift` du palier 1 | — | 35 tours |
| Guérisseur **branche la Marée** (`thrift` −7,2 %) | — | **au-delà de 40** |

> **Voilà ce qui sépare deux arbres de sorts, et ce n'est aucun des pourcentages
> de puissance.** Le pyromancien tombe en panne au tour 8 — exactement le « pic
> puis la panne » que le §2 lui promettait, mais que rien dans le document ne
> mesurait. Le guérisseur ne tombe jamais en panne **dans un combat** ; il tombe en
> panne **dans une journée**. Ce sont deux économies différentes, et `thrift` et
> `wind` sont dans la palette de l'entretien parce que c'est lui qui tient sur la
> longueur, pas sur l'instant.

### 9 ter.4 Le donjon à quatre — ce que vaut un tour déposé

Un tour d'attaque vaut 22 dégâts. Un tour déposé vaut :

| Dépôt | Portée | Valeur rendue | En tours d'attaque |
|---|---|---:|---:|
| **Marée** (8 PV/tour, 6 tours) | seul | 48 PV | **2,2** |
| **Marée** (8 PV/tour, 6 tours) | à quatre | 192 PV | **8,8** |
| **Grande Marée** (8 PV/tour, 10 tours) | à quatre | 320 PV | **14,7** |

**La propriété qu'on cherchait sans l'avoir écrite : le dépôt multiplie par la
taille du groupe.** Le guérisseur est modeste seul (2,2) et décisif à quatre
(8,8) — sans qu'aucune règle n'impose sa présence, et sans qu'il joue un autre
jeu. C'est la meilleure justification qu'on ait trouvée à la loi du dépôt.

**Mais la troisième ligne est un défaut.** Grande Marée vaut **14,7 tours
d'attaque** : à ce prix, un groupe sans guérisseur cesse d'être « plus lent » pour
devenir « non viable » — ce que le garde-fou du §7 bis interdit explicitement.
D'où la correction 5 ci-dessous.

### 9 ter.5 Ce que l'exercice a révélé — quatre corrections

**Correction 4 — l'ancre d'échelle a besoin d'une seconde moitié.** Le §6.4 ancre
l'équilibrage sur la **durée d'un combat en tours**. C'est insuffisant : deux
archétypes peuvent tenir onze tours de la même façon et n'avoir rien à voir à
l'échelle de la journée. La seconde ancre : **le coût d'une rencontre en ressource,
rapporté au budget du jour** — un archétype ne se juge pas sur un combat, il se
juge sur les ~16 que la barre d'énergie autorise. Appliqué en §6.4.

**Correction 5 — la durée d'un dépôt étale sa valeur, elle ne l'augmente pas.**
Sans cette règle, allonger un dépôt est le levier le moins cher du jeu, et Grande
Marée vaut quatorze tours d'attaque. **La valeur totale d'un dépôt est fixée par le
palier de la matéria ; la durée décide seulement de son étalement.** Une durée
longue n'achète pas de la puissance — elle achète de la **robustesse à
l'absence**, qui est précisément ce dont un donjon semi-synchrone a besoin.
Appliqué en §7 bis.

**Correction 6 — les marques doivent se porter des deux côtés.** Mesuré :
**21 monstres sur 65 possèdent un sort**, et **9 de ces sorts appliquent un
statut**. Or `ward` (résistance aux statuts) figure dans **deux palettes sur
quatre**, et l'accord Dissipation du Guérisseur ne sert à rien s'il n'y a rien à
dissiper. ARC-13 doit poser les huit marques **pour les monstres aussi**, sinon
deux fonctions portent un levier mort. Appliqué en §1.1.

**Constat B (sans correction) — l'entretien est le seul dont le joueur règle le
curseur.** Les trois autres archétypes subissent la durée du combat ; celui-ci la
choisit, tour par tour, en arbitrant entre frapper et déposer. C'est une qualité de
jeu qu'aucun budget ne produit, et elle vient entièrement de la structure de ses
**accords** — une fois de plus, pas de ses pourcentages.

---

## 9 quater. Le troisième exemple — l'Archer, ou ce qu'un archétype coûte en gils

Les deux premiers exercices ont mesuré des tours et des PM. Celui-ci mesure la
seule chose que ni le Soldat ni le Guérisseur ne pouvaient tester : **ce qu'un
archétype coûte en monnaie**. C'est le troisième registre, et il paie en
munitions.

**Il n'a rien appris sur le combat. Il a tout appris sur la journée** — et il a
trouvé un défaut structurel que les deux autres ne pouvaient pas révéler.

### 9 quater.1 Le défaut : la flèche ordinaire éteint l'arbre

Le §2.1 dit que **la munition porte l'élément**. La double borne (§2 de
GAME_DOMAINS) dit qu'un passif ne s'applique que sur la case *élément × registre*
de son domaine. Mettez les deux ensemble :

> Un archer qui tire une **flèche ordinaire** — neutre, celle du plancher T1 PNJ —
> produit une action **sans élément**. Aucun passif de son arbre ne s'y applique.
> **Le filet de sécurité désactive l'archétype qu'il était censé protéger.**

C'est le genre de défaut qu'on ne voit qu'en déroulant. **Correction 7 : la matéria
porte l'élément, la munition le *remplace*.** Le Tir tendu est un geste d'air parce
que sa matéria est d'air — comme pour les deux autres registres. Une flèche
élémentaire **substitue** son élément à celui du geste.

Ce que ça change dans la formule du §2.1, et c'est mieux ainsi : **l'archer
n'achète pas sa puissance, il achète sa souplesse.** Il garde son archétype
gratuitement, et paie quand il veut *changer de contre-jeu* — tirer du feu sur une
créature de glace le temps d'un combat. C'est exactement la promesse du registre,
et elle ne tenait pas.

### 9 quater.2 Ce qu'il paie, et ce qu'il achète

16 combats par jour (14 communs à 5 tours, 2 élites à 10) = **90 tirs**.

| Ce qu'il tire | Coût quotidien |
|---|---:|
| Tout en flèche ordinaire (1 gil) | **90 gils** |
| Idem, avec `wind` +13,5 % | 78 gils |
| Élémentaire sur les élites (8 gils) | **230 gils** |
| Idem, avec `wind` | 199 gils |

Et ce que ça lui achète, comparé au pyromancien qui ne paie rien :

| | Par tour | Sur la marque |
|---|---:|---:|
| **Archer** *(le Guet)* | 23,6 | 30,1 |
| **Pyromancien** *(l'Éclat)* | 23,2 | 29,5 |
| **Écart** | **+1,8 %** | +2,0 % |

> **90 à 230 gils par jour pour +1,8 %.** Un coffre d'exploration rend 2 à 12 gils
> (BALANCE §10) : l'archer travaillerait une journée entière pour ses flèches. Le
> registre le plus cher du jeu était aussi celui qui n'achetait rien.

> **Corrections 8 et 9, annulées au §9 septies.** Elles cherchaient à *compenser*
> le coût ; l'arbitrage rendu a été de le **supprimer**. Le problème était le coût
> lui-même, pas son absence de contrepartie — aucun archétype ne doit porter une
> dépense récurrente que les autres n'ont pas. Ce qui suit reste consigné parce que
> la mesure, elle, tient : elle est ce qui a motivé la suppression.

**Correction 8 *(annulée)* — une meilleure munition rend de la puissance.** La
munition n'est pas une taxe, c'est un **investissement** : le geste de distance porte une prime
indexée sur le palier de sa munition. Deux garde-fous, sans lesquels on ouvre une
porte que le jeu refuse ailleurs : la prime est **fixe et plafonnée** — payer plus
cher au-delà du palier ne rend pas plus fort —, et la flèche ordinaire reste
**pleinement jouable** (correction 7). Ce n'est pas un axe de progression, c'est un
**choix d'allocation** : le seul endroit du jeu où le combat et l'économie se
touchent, ce qui est une qualité tant que ça reste borné.

**Correction 9 *(annulée)* — le prix se calibre contre le revenu du jour, jamais
contre la valeur du geste.** Règle : la munition **ordinaire** ne dépasse pas ~10 % du revenu
quotidien ; l'**élémentaire** peut monter à ~25 % **un jour où l'archer la
choisit**. Au-delà, l'archétype cesse d'être un style de jeu pour devenir un
métier — et un métier qu'on n'a pas choisi.

### 9 quater.3 `wind`, ou un levier qui ne vaut rien

La teinte de la branche *la Volée* coûte **9 points de budget** — 18 % de tout ce
qu'un arbre peut donner. Ce qu'elle rend :

| Ce qu'on mesure | Valeur |
|---|---:|
| 13,5 % de récupération sur 90 tirs à 1 gil | **12 gils par jour** |
| Le même budget en `critical_power` | +13,5 % de dégâts critiques |

**Douze gils.** Le levier économique du registre économique est économiquement
négligeable — parce que la munition n'a qu'un **prix**, et jamais de **rareté**.

**Correction 10 — le carquois a une capacité.** Ce qu'on porte est borné ; la
récupération rend alors non pas des gils, mais **des tirs quand ils manquent** :
13,5 % d'un carquois de 20 valent **2,7 tirs de plus par rencontre**. Et la
contrainte est réglée pour **ne mordre que sur les longues rencontres** — un boss,
un donjon —, jamais sur le tout-venant, où elle ne serait que de la gestion
d'inventaire.

### 9 quater.4 Ce que les trois exemples disent ensemble

| Registre | Sa ressource | Où elle mord | Ce que l'exercice a corrigé |
|---|---|---|---|
| **Sorts** | PM | dans le combat *(pic tour 8)* **et** dans la journée | la seconde ancre (§6.4) |
| **Mêlée** | temps de reprise | dans le combat seulement — rien ne se reporte | la protection déposée (§7 bis) |
| **Distance** | le carquois | **dans la rencontre** *(corrigé §9 septies)* | l'élément, et la suppression du coût en gils |

> **Les trois archétypes se distinguent par leur rapport à la ressource, et c'est
> cette ligne-là qui porte l'identité — pas les pourcentages.** Le soldat n'a rien
> dépensé, le guérisseur a vidé son pool, l'archer a vidé sa bourse. Ils ont tenu le
> même nombre de tours.

---

## 9 quinquies. Le quatrième exemple — l'Hydromancien, et l'unité du contrôle

Les trois premiers exercices ont mesuré des tours, des PM, des gils. Le contrôle
se mesure en **tours volés** — une unité que le document affirmait depuis le §5
sans l'avoir jamais posée sur une feuille. C'est le seul exemple qui ait **infirmé
une affirmation du document** au lieu de la préciser.

L'arbre n'existait pas : il est écrit ici en appliquant la procédure du §10.1, ce
qui la met à l'épreuve au passage.

### 9 quinquies.1 L'arbre, au gabarit

**Hydromancien** — eau × sorts × **Contrôle**. Marque : **Trempé**. Teinte :
`power` — il doit quand même tuer.

| Palier | Coût | Nœud | Ce qu'il donne | pb |
|---|---:|---|---|---:|
| Entrée | 0 | **Accord : Jet glacé** — `dégât`, `une cible` | le geste, **et il applique Trempé** : la marque ne coûte jamais un tour (§9 quinquies.3) | — |
| Entrée | 0 | **Accord : Remous** — `entrave`, `une cible`, **2 tours** | le plan B, et la condition du capstone | — |
| 1 | 10 | Lecture du courant | `hit` **+1,5 pt** | 3 |
| 1 | 10 | Devancer | `tempo` **+3 %** | 3 |
| 1 | 10 | **Accord : Gel** — `entrave`, `une cible`, 3 tours | l'entrave qui compte | — |
| 1 | 10 | *Port* : canal de sort, échelon 2 | — | — |
| 2 | 25 | Économie du geste | `thrift` **−3,6 %** | 6 |
| 2 | 25 | Poigne froide | `grip` **+7,2 %** | 6 |
| 2 | 25 | **Accord : Nappe** — `entrave`, `plusieurs cibles` | — | — |
| 2 | 25 | *Port* : canal de sort, échelon 3 | — | — |
| 3 | 50 | **Fourche — le Reflux** · Litanie basse | `thrift` **−5,4 %** | 9 |
| 3 | 50 | **Fourche — le Reflux** · Prendre le devant | `tempo` **+9 %** | 9 |
| 3 | 50 | **Fourche — le Reflux** · **Accord : Prison de glace** — `entrave`, longue | l'entrave qui vole quatre tours | — |
| 3 | 50 | **Fourche — la Vague** · Pointe d'eau | `pierce` **+6,3 pt** | 9 |
| 3 | 50 | **Fourche — la Vague** · Force du flot | `power` **+9 %** *(teinte)* | 9 |
| 3 | 50 | **Fourche — la Vague** · **Accord : Lame d'eau** — `dégât`, amplifié contre une cible entravée | l'entrave devient des dégâts | — |
| **Capstone** | 100 | **Emprise** | `grip` **+23,5 %** *quand la cible subit un de vos statuts* (14 pb × **1,4** — condition quasi permanente) | 14 |
| *Dormant* | *150* | *Accord d'hybride (eau)* | *réservé* | — |

**Vérification.** 50 pb par branche · palette 50 *(Reflux)* ou 41 *(Vague)* ✔ ·
teinte `power` 9 ≤ 10 ✔ · `thrift` 15 ≤ 15 ✔ · `tempo` 12 ≤ 12 ✔ · `grip` **20 ≤ 20**
✔ *(voir correction 13)* · branches sans levier commun ✔.
**Intentions** : 3 `entrave` ≥ 2 ✔ · 1 `dégât` ✔ · un accord d'entrée applique
**Trempé** ✔.
**Test du voisin** : Guérisseur *(entretien)* et Marémancien *(assaut)* partagent la
case eau × sorts et **aucun levier principal** avec lui ✔.

### 9 quinquies.2 Le résultat qui infirme le document

Le §5 affirmait : *« un tour que l'adversaire ne joue pas est un tour de dégâts
évité : le contrôle est une fonction offensive, mesurée en tours volés. »*
**C'est faux, et l'arithmétique est sans appel.** Face à l'élite (180 PV,
frappe 16), l'hydromancien inflige 24,2 par tour et subit 13,6 :

| Entraves posées (1 tour volé chacune) | Durée du combat | **Dégâts subis** |
|---:|---:|---:|
| 0 | 7,5 tours | **101** |
| 1 | 8,5 tours | **101** |
| 2 | 9,5 tours | **101** |
| 3 | 10,5 tours | **101** |

> **En duel, échanger un de ses tours contre un tour adverse est rigoureusement
> nul.** Le combat s'allonge d'exactement ce qu'on a volé. Ce n'est pas une
> question de calibrage : c'est une identité arithmétique, vraie pour n'importe
> quelle valeur de dégâts.

La sortie tient en un mot : **la durée**.

| Durée de l'entrave | Dégâts subis | Économisé |
|---:|---:|---:|
| 1 tour | 101 | **0** |
| 2 tours | 88 | 14 |
| 3 tours | 74 | 27 |
| 4 tours | 61 | **41** |

**Correction 11 — une entrave n'est jamais un tour perdu.** Soit elle **vole plus
d'un tour** (durée ≥ 2), soit elle **accompagne un geste de dégât** — comme le Jet
glacé qui applique Trempé sans rien coûter. Une entrave d'un tour posée par un
geste dédié est un nœud mort, et c'est exactement ce qu'on aurait écrit sans faire
le calcul.

C'est aussi ce qui fait de `grip` — la durée — **la condition d'existence de la
fonction**, et non un bonus de confort. Le capstone *Emprise* n'ajoute pas de la
puissance : il transforme une entrave de 3 tours en une entrave de 3,7.

### 9 quinquies.3 L'asymétrie du donjon, que personne n'avait vue

Le §7 bis dit que le contrôle est « la fonction dont le rendement collectif est le
plus élevé ». Mesuré, dans un donjon à quatre où **un seul joueur agit par tour** :

| Ce qu'on pose | Sur qui | Ce que ça rend | En tours d'attaque |
|---|---|---:|---:|
| **Un dépôt de soin** (8 PV/tour, 6 tours) | les **alliés** | 8 × 6 × **4 corps** = 192 PV | **×8,8** |
| **Une marque offensive** (+15 %, 6 tours) | l'**ennemi** | 6 actions × 22 × 15 % = 20 dégâts | **×0,9** |

> **Un effet posé sur les alliés se multiplie par leur nombre. Un effet posé sur
> l'ennemi ne se multiplie pas** — parce qu'il n'y a qu'un flux d'actions à
> améliorer, et qu'un seul joueur le joue à la fois.

**Correction 12 — l'asymétrie est structurelle, et il faut l'écrire.**
L'**entretien** et l'**encaisse** (effets sur les alliés) gagnent mécaniquement au
groupe ; l'**assaut** et le **contrôle** (effets sur l'ennemi) n'y gagnent rien. Ce
n'est pas un défaut à corriger — c'est une propriété du donjon semi-synchrone, et
la nier reviendrait à équilibrer le contrôle comme un soutien qu'il n'est pas.

**Sa compensation, et c'est une qualité** : le contrôle est **la seule fonction
dont la valeur ne change pas entre le solo et le groupe**. Les trois autres jouent
à moitié quand le contexte ne leur convient pas ; celui-ci joue pareil partout. Un
joueur qui alterne les deux n'a rien à réapprendre — ce qui, dans un jeu à 1-2
joueurs simultanés, vaut mieux qu'un multiplicateur.

**Correction 13 — le plafond de `grip` passe de 18 à 20.** À 18, le capstone
(14 pb) ne laissait que 4 points : un arbre de contrôle ne pouvait acheter son
**levier principal** nulle part ailleurs qu'à son sommet. Les principaux
s'alignent donc à **20** (`power`, `mending`, `grip`), sauf `guard` qui reste à
**15** parce que son efficacité est hyperbolique (§4.1).

### 9 quinquies.4 Ce que les quatre exemples ont produit

| Exemple | Ce qu'il mesurait | Ce qu'il a trouvé |
|---|---|---|
| **Soldat** (§9 bis) | des tours | la fourche était cosmétique · la défense punissait · un multiplicateur mal compté |
| **Guérisseur** (§9 ter) | des PM | l'ancre était à moitié aveugle · la durée gonflait la valeur · les marques manquaient d'un côté |
| **Archer** (§9 quater) | des gils | la flèche ordinaire éteignait l'arbre · la munition n'achetait rien · un levier à 12 gils/jour |
| **Hydromancien** (§9 quinquies) | des tours volés | **une entrave d'un tour est nulle** · le groupe ne multiplie que ce qui porte sur les alliés · un plafond mal aligné |

**Treize corrections en quatre exercices, et pas une seule n'a porté sur un
pourcentage.** Toutes ont porté sur des **gestes**, des **durées**, des
**ressources** ou des **conditions** — c'est-à-dire sur ce que le §0 appelait le
couple *(arbre, matéria)*. Le budget de puissance n'a jamais été le problème : il
est ce qui empêche d'en faire un.

---

## 9 sexies. La comparaison croisée — **non, ce n'est pas équilibré**

Les quatre exercices précédents ont chacun mesuré **un** archétype. Aucun n'a mis
les six builds sur la même ligne. C'est cette table-là qui manquait, et elle est
sans appel.

### 9 sexies.1 Les six builds contre la même élite

Élite T2 (180 PV, frappe 16). Arbres complets, équipement **non modélisé** — c'est
la contribution des arbres seuls.

| Build | Durée | PV restants | Ressource | Gils/jour |
|---|---:|---:|---|---:|
| **Soldat — la Ligne mobile** | **9 tours** | **78** | — | **0** |
| Soldat — le Mur | 11 | 68 | — | 0 |
| Guérisseur — le Ressac | 11 | 76 | 108 PM | 0 |
| Hydromancien — la Vague | 9 | 38 | ~90 PM | 0 |
| Archer — le Guet | 7 | 38 | — | **230** |
| Pyromancien — l'Éclat | 8 | 11 | 160 PM *(panne t8)* | 0 |

> **Le guerrier domine, et il domine sur les deux tableaux à la fois** : il tue
> aussi vite que l'hydromancien, survit mieux que tout le monde, et **ne paie
> rien**. L'archer gagne deux tours et les paie 230 gils par jour. Le pyromancien
> gagne un tour, finit à 11 PV et tombe en panne de PM avant la fin.

### 9 sexies.2 Trois causes, mesurées

**Cause 1 — les leviers de survie rendent 2,5 fois les leviers de dégâts, à budget
strictement égal.**

| 50 points de budget dépensés en… | Ce que ça rend |
|---|---:|
| **Assaut** (+17 % de dégâts) | le combat passe de 8,3 à 7,1 tours → **16 points de valeur** |
| **Encaisse** (+22,5 % de PV, −11,8 % de dégâts subis) | +27 PV et 13 dégâts évités → **40 points de valeur** |

Le budget est le même, le rendement ne l'est pas. Aucune palette n'a jamais été
comparée à une autre — **c'est le trou de méthode du document**.

**Cause 2 — une ressource payée n'est jamais compensée.** L'archer paie
230 gils/jour, le pyromancien tombe en panne au tour 8 ; ni l'un ni l'autre
n'obtient un avantage proportionné. La correction 8 (§9 quater) posait une prime de
munition sans la chiffrer — **elle n'a donc encore rien corrigé**.

**Cause 3, et c'est la vraie — dans ce jeu, la vitesse n'a pas de valeur.**

> Une chasse coûte **5 points d'énergie, quel que soit le nombre de tours**
> (GAME_PROGRESSION §1). Tuer en 7 tours au lieu de 14 ne permet donc **pas un
> combat de plus** dans la journée.

La promesse de l'assaut — *« je finis le combat avant qu'il ne devienne un
problème »* — **n'a aucun débouché économique en solo**. Les deux seules choses qui
valent quelque chose dans une journée de PBBG sont les **PV restants** (moins de
régénération, moins de potions) et la **ressource dépensée**. L'assaut ne gagne ni
l'un ni l'autre. Le déséquilibre n'est pas dans les chiffres des arbres : il est
dans ce que le jeu récompense.

### 9 sexies.3 Ce qui corrige, et ce qui reste à trancher

**Correction 14 — le palier des accords suit la fonction.** L'assaut ouvre ses
gestes de dégât au **palier plein** ; contrôle, entretien et encaisse les ouvrent
**un palier en dessous** (≈ −25 %). Une fois de plus, la différence passe par les
**gestes**, pas par les pourcentages. Mesuré :

| | Aujourd'hui | Avec la correction |
|---|---|---|
| Soldat — la Ligne mobile | 9 tours, 78 PV | **11 tours, 55 PV** |
| Guérisseur — le Ressac | 11 tours, 76 PV | **14 tours, 47 PV** |
| Archer — le Guet | 7 tours, 38 PV | 7 tours, 38 PV |
| Pyromancien — l'Éclat | 8 tours, 11 PV | 8 tours, 11 PV |

L'écart devient lisible — 7 tours contre 11 à 14 — sans qu'aucun levier ne bouge.

**Correction 15 — la prime de munition se chiffre, ou le coût disparaît.** Une
ressource qui coûte sans rendre est un impôt sur un archétype. Soit la prime vaut
au moins l'écart mesuré (230 gils/jour), soit les munitions cessent d'être
payantes. **Il n'y a pas de troisième option**, et la laisser en suspens revient à
choisir la première par défaut.

**Correction 16 — l'ancre de fonction, l'invariant qui manquait.**

> **À arbre complet et équipement égal, les quatre fonctions doivent enchaîner le
> même nombre de rencontres de leur palier par jour, et en sortir dans un état
> comparable.** Ce qui diffère, c'est **comment on paie** : le soldat en tours, le
> guérisseur en PM, l'archer en gils, le pyromancien en fragilité.

C'est le seul invariant qui ne se vérifie pas sur un archétype isolé — il exige la
table du §9 sexies.1. **Le simulateur d'ARC-05 doit la produire, pas des durées
individuelles.**

### 9 sexies.4 L'arbitrage qui reste — donner une valeur à la vitesse

La cause 3 dépasse les arbres : c'est une décision de conception du jeu.

| Option | Ce que ça implique | Verdict |
|---|---|---|
| **A. Les rencontres à fenêtre** — un boss se termine en 12-20 tours ou pas du tout | Mesuré : contre un boss de 400 PV, l'archer et le pyromancien tiennent la fenêtre (15 tours) ; le soldat (25) et le guérisseur (29) **ne la tiennent pas**. La vitesse décide enfin de quelque chose | ✅ **Recommandé** — c'est du contenu, pas du moteur, et ça donne un débouché propre à l'assaut |
| **B. Indexer le coût d'énergie sur la durée** | Un combat long coûterait plus cher. Cohérent, mais ça contredit « l'énergie se paie par geste » (GAME_PROGRESSION §1) et ça punirait l'entretien deux fois | ❌ |
| **C. Assumer que l'assaut est un archétype de groupe** | Faux : §9 quinquies a montré qu'un effet sur l'ennemi ne se multiplie pas par la taille du groupe | ❌ |

**Recommandation : l'option A**, et elle a une conséquence à écrire dans
`GAME_DUNGEONS` et `GAME_BESTIARY` — **un boss doit avoir assez de points de vie
pour qu'un archétype lent n'en vienne pas à bout**. Sans contenu à fenêtre,
l'assaut n'a pas de raison d'exister, et le guerrier restera le meilleur choix
dans tous les cas.

---

## 9 septies. La journée renverse le classement

Le §9 sexies concluait que **le guerrier domine**. Ce n'était pas faux — c'était
mesuré à la mauvaise échelle. Simulé sur une **journée** (14 communs + 2 élites,
correction 14 appliquée) :

| Build | Tours (commun / élite) | PV perdus sur la journée |
|---|---|---:|
| **Guérisseur — le Ressac** | 9 / 14 | **70** |
| Hydromancien — la Vague | 7 / 11 | 445 |
| Soldat — la Ligne mobile | 5 / 10 | 494 |
| Archer — le Guet | 4 / 6 | 592 |
| Pyromancien — l'Éclat | 4 / 7 | 619 |
| **Soldat — le Mur** | 8 / 14 | **710** |

> **Le classement s'inverse.** Sur *un* combat, le guerrier dominait. Sur *une
> journée*, c'est le **guérisseur** — il ne perd rien, donc il ne s'arrête jamais —
> et le tank pur devient le **pire des six**, parce qu'il est lent et que la lenteur
> se paie en coups reçus.

Et l'assaut, lui, cesse d'être mauvais : **la vitesse est de la survie**. L'archer
encaisse 82 sur une élite là où le Mur en encaisse 115, simplement parce que son
combat dure six tours au lieu de quatorze. Ce que le §9 sexies n'avait pas vu en
mesurant un seul échange.

### 9 septies.1 Correction 17 — les munitions cessent d'être un consommable

**Aucun archétype ne doit payer un coût récurrent en gils que les autres n'ont
pas.** C'est une asymétrie que rien ne justifie : les trois registres paient une
ressource *dans* la rencontre, un seul payait *après*.

| Ce qui change | Avant | Après |
|---|---|---|
| **Le carquois** | des flèches achetées à l'unité | une **pièce d'équipement durable**, craftée par le charpentier, comme une arme |
| **Les flèches** | consommées, rachetées, 90 à 230 gils/jour | elles **se vident pendant la rencontre et se ramassent après** |
| **La ressource du registre** | économique — en gils | **intra-rencontre**, comme les PM |
| **L'élément** | acheté au carquois | **porté par le carquois équipé** — on en possède plusieurs, comme un mage possède plusieurs matéria |
| **Le levier `wind`** | 12 gils par jour | des **tirs quand ils manquent**, dans les longues rencontres |

Ce qui survit intact : la **souplesse** de l'archer (changer de carquois entre deux
combats, ce qu'aucun autre registre ne peut faire sans refaire son build), le
**débouché du charpentier** (il fabrique des carquois, un bien durable, au lieu
d'un consommable), et le **profil de cadence décroissante** — le carquois se vide,
et c'est ce qui borne les longues rencontres.

Ce qui disparaît : la prime de munition (correction 8), devenue sans objet, et
l'obligation de la chiffrer (correction 15). **Le problème était le coût, pas sa
compensation.**

### 9 septies.2 Correction 18 — les PM régénèrent comme les PV

Une fois le gil retiré, il reste une asymétrie plus grave : **le guérisseur ne paie
rien**. 70 PV perdus sur une journée, contre 494 à 710 pour les autres — il
enchaîne les combats jusqu'à épuiser son énergie d'action pendant que les autres
attendent leur régénération.

Sauf qu'il paie — en **PM**, et que ce compteur n'a **aucun curseur** : la
régénération de l'énergie de combat hors combat n'a jamais été calibrée
(BALANCE §24.2, ouvert). Tant qu'elle est gratuite, l'entretien joue trois fois
plus de contenu que tout le monde.

**Converti dans la seule monnaie commune — du temps** (PV à 12 s/point, livré ;
PM à ~6 s/point, à calibrer) :

| Build | Attente PV | Attente PM | **Total** |
|---|---:|---:|---:|
| Soldat — la Ligne mobile | 99 mn | — | **99 mn** |
| Archer — le Guet | 118 mn | — | **118 mn** |
| Soldat — le Mur | 142 mn | — | **142 mn** |
| Guérisseur — le Ressac | 14 mn | 144 mn | **158 mn** |
| Hydromancien — la Vague | 89 mn | 90 mn | **179 mn** |
| Pyromancien — l'Éclat | 124 mn | 160 mn | **284 mn** |

> **Six builds entre 99 et 179 minutes** — sauf le pyromancien, qui paie deux fois
> (fragile *et* dépensier) et reste à recalibrer. **Sans le curseur PM, le
> guérisseur paie 14 minutes.** C'est le curseur qui décide de tout l'équilibre
> solo, et il n'existe pas.

**La symétrie à tenir** : *les PV paient les coups reçus, les PM paient les gestes
faits ; les deux se rechargent en temps réel, et c'est ce temps qui est la vraie
monnaie du jeu.* Le registre mêlée ne paie ni l'un ni l'autre — il paie en **tours
de combat**, immédiatement, ce qui est la troisième forme de la même chose.

### 9 septies.3 La matrice — chaque build doit avoir son contexte

| Fonction | En solo | En donjon d'équipe |
|---|---|---|
| **Assaut** | ✅ la vitesse réduit les coups reçus, et les **rencontres à fenêtre** (§9 sexies.4) sont son terrain exclusif | ✅ il abrège l'exposition de **tout** le groupe |
| **Contrôle** | ✅ l'entrave réduit les coups reçus | ✅ **identique** — la seule fonction dont la valeur ne change pas (§9 quinquies) |
| **Entretien** | ✅ il ne perd rien, et paie en PM | ✅✅ le dépôt se multiplie par le groupe (×8,8 à quatre) |
| **Encaisse** | ⚠️ **la branche défensive est la pire en solo** (710 PV/jour) | ✅✅ l'absorption déposée se multiplie par le groupe |

**Correction 19 — une fourche oppose deux contextes, pas deux dosages.** La case
en garde-fou ci-dessus n'est pas un défaut : *le Mur* est un archétype de donjon,
et il est mauvais seul. Ce qui serait un défaut, c'est qu'un joueur d'encaisse
n'ait **que** cette branche. La fourche est précisément le mécanisme qui
l'empêche :

> **Tout arbre porte une branche jouable seul et une branche qui sert le groupe.**
> Le Soldat le fait déjà — *la Ligne mobile* (494 PV/jour, la meilleure des non-
> soigneurs) et *le Mur* (l'absorption de groupe). C'est ce que les 24 arbres
> doivent tenir, et c'est testable.

Ce qui répond aux deux exigences posées : **un guerrier offensif est viable en
solo** — c'est la Ligne mobile, et c'est le meilleur build non-soigneur de la
table — et **un défenseur est utile en donjon d'équipe** — c'est le Mur, dont le
Rempart se multiplie par quatre.

---

## 9 octies. Les dégâts subis — et ce qu'une élite doit être

### 9 octies.1 La réponse directe

Dégâts subis par rencontre, en **pourcentage de sa propre barre** — c'est la seule
lecture comparable, les six builds n'ayant pas les mêmes points de vie.

| Build | Commun *(100 PV, frappe 9)* | Élite *(180 PV, frappe 16)* |
|---|---:|---:|
| Guérisseur — le Ressac | **0 %** *(il regagne)* | 29 % |
| Hydromancien — la Vague | 16 % | 74 % |
| Soldat — la Ligne mobile | 18 % | 63 % |
| Soldat — le Mur | 23 % | 78 % |
| Archer — le Guet | 26 % | 68 % |
| Pyromancien — l'Éclat | 26 % | 79 % |

**Deux choses se lisent d'un coup.** Sur le tout-venant, l'écart est faible (16 à
26 %) : la mitigation du tank compense sa lenteur, la vitesse de l'assaut compense
sa fragilité — c'est équilibré, et ce n'est pas un hasard, c'est la même
arithmétique vue au §9 septies. Sur l'élite, **tout le monde survit** — le pire
finit à 21 % de sa barre. **C'est ça, le vrai défaut du modèle.**

### 9 octies.2 Décision 20 — une élite n'est pas un palier solo

> **Une élite n'est pas un commun plus gros. C'est une rencontre de groupe, et un
> joueur seul de puissance équivalente ne doit pas en venir à bout — quel que soit
> son archétype.**

La calibration qui le tient : **frappe 26 au lieu de 16**, à points de vie
inchangés.

| Build | Tours | Dégâts subis | Issue |
|---|---:|---:|---|
| Guérisseur — le Ressac | 14 | 122 | **102 % de sa barre — mort** |
| Soldat — la Ligne mobile | 10 | 134 | **102 % — mort** |
| Archer — le Guet | 6 | 133 | **111 % — mort** |
| Hydromancien — la Vague | 11 | 144 | **120 % — mort** |
| Soldat — le Mur | 14 | 187 | **127 % — mort** |
| Pyromancien — l'Éclat | 7 | 155 | **129 % — mort** |

**L'écart va de 102 à 129 %** : c'est uniformément létal, et aucun archétype ne
s'en sort par une astuce. Le « quelle que soit l'archétype » est tenu — y compris
pour le guérisseur, qui peut bien allonger le combat en soignant, mais cesse alors
de tuer.

### 9 octies.3 Pourquoi c'est mortel seul et confortable à quatre

Ce n'est pas une décision arbitraire : **c'est l'asymétrie du §9 quinquies qui le
produit**, et c'est ce qui rend la décision cohérente avec tout le reste.

| À quatre, sur une rencontre de 800 PV | |
|---|---:|
| Durée | 36 tours |
| Dégâts distribués au total | 800 |
| **Par joueur** | **200** |
| Ce qu'un dépôt de soin maintenu rend **à chacun** | **290** |

> **Les dégâts ne se divisent pas par la taille du groupe — les dépôts, si.** Un
> joueur seul encaisse tout ; à quatre, chacun encaisse le quart *et* reçoit
> l'intégralité du soin déposé. C'est la même mécanique qui donne leur rôle à
> l'entretien et à l'encaisse (§7 bis), et c'est elle qui fait qu'une élite
> impossible seul devient une rencontre normale à plusieurs. **Aucune règle
> spéciale n'est nécessaire.**

### 9 octies.4 Ce que ça change pour la journée solo

Si l'élite sort de la boucle solo, la journée est faite de **communs seuls** :

| Build | PV/jour | PM/jour | Attente PV | Attente PM | **Total** |
|---|---:|---:|---:|---:|---:|
| Soldat — la Ligne mobile | 368 | — | 74 mn | — | **74 mn** |
| Archer — le Guet | 496 | — | 99 mn | — | **99 mn** |
| Soldat — le Mur | 544 | — | 109 mn | — | **109 mn** |
| Hydromancien — la Vague | 304 | 700 | 61 mn | 70 mn | **131 mn** |
| Guérisseur — le Ressac | 0 | 1 344 | — | 134 mn | **134 mn** |
| Pyromancien — l'Éclat | 496 | 640 | 99 mn | 64 mn | **163 mn** |

**Écart de ×2,2 entre le meilleur et le pire.** C'est encore trop — la cible
raisonnable est **moins de ×1,5**, et c'est le chiffre que la simulation d'ARC-17
doit tenir. Deux points à corriger :

- **Le pyromancien paie deux fois** — fragile *et* dépensier. C'est le seul cas où
  la fragilité de l'assaut n'est pas compensée par sa vitesse, parce qu'il paie
  aussi le pool. Son coût en PM doit descendre, ou ses PV monter.
- **Le guerrier de mêlée reste le plus économe du tout-venant**, et c'est
  acceptable : c'est le seul qui ne paie **aucune** ressource. Ce qu'il ne peut pas
  faire — l'élite, le boss à fenêtre, le dépôt de groupe — est ailleurs.

### 9 octies.5 Ce que ça impose au contenu

Deux conséquences à porter hors de ce document :

- **[GAME_BESTIARY.md](GAME_BESTIARY.md)** — le gabarit `tier × rank` doit produire
  un rang **Elite** qui **tue un joueur seul de son palier**, pas un commun aux
  statistiques gonflées. Le rapport mesuré : *une élite frappe ~2,9 fois plus fort
  qu'un commun du même palier* (26 contre 9), pour ~1,8 fois ses points de vie.
- **[GAME_DUNGEONS.md](GAME_DUNGEONS.md)** — l'élite est l'étape **normale** d'un
  donjon (trois étapes : commun → élite → boss) et c'est là qu'elle vit. En zone,
  elle reste une rencontre exceptionnelle **dont on peut fuir** : un mur qu'on ne
  peut ni franchir ni contourner n'est pas du contenu, c'est un panneau.

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

## 11. Ce que ça coûte, et ce qui a été tranché

### 11.1 Les dettes de moteur, par ordre de blocage

| # | Ce qu'il faut | Sans quoi | Taille |
|---|---|---|---|
| 1 | **`Spell::register`** + un premier lot de **matéria de technique** (§3) | Deux archétypes sur quatre n'existent pas ; DOM-03 reste un mur sans porte | M |
| 2 | **`Domain::role`** + palettes en configuration (§1, §5) | Aucun test de palette n'est possible ; les 24 arbres restent indiscernables | S |
| 3 | **Les leviers** : `Skill` porte une liste `(levier, pb)` au lieu de 5 entiers, et `CombatSkillResolver` les convertit à leur place dans la formule (§4) | Les passifs restent plats, donc inéquilibrables | **L** |
| 4 | **Les ressources par registre** : consommation de munition, reprise sur technique (§2) | Les trois registres restent la même chose repeinte | M |
| 5 | **L'ancre d'échelle** : recalibrer PV de monstres et valeurs de gestes sur la durée en tours (§6.4) | Les pourcentages s'appliquent à des nombres qui n'ont pas de sens entre eux | **L** |
| 6 | **L'échelle de coût** 0/10/25/50/100 sur les 24 arbres, et le gain de points indexé au palier (§6.2) | Un arbre coûte toujours 465 points pour un plafond de 500 | M |
| 7 | **Les huit marques** (§1.1) | Le capstone d'assaut, le levier `grip` et la palette de contrôle n'ont **aucun objet** | M |
| 8 | **La fourche** (§6.1 bis) — extension au combat d'un mécanisme livré (`other_branch`) | Deux praticiens du même arbre restent identiques | S |
| 9 | **Le pacte** (§6.5) et les **accointances** (§9.7) | Aucun build n'est faible quelque part ; et les synergies continuent de fuir hors budget | S + M |

**L'ordre est contraint** : 2 avant 3 (la palette borne le levier), 1 avant 4,
**7 avant tout capstone d'assaut**, 8 après les quatre arbres patrons, et 5 avant
toute passe de valeurs. 3 et 5 sont les deux gros morceaux, et ils sont
indépendants l'un de l'autre.

> **Le noyau minimal**, si le chantier doit se livrer par morceaux : les lignes 1,
> 2, 3 et les intentions (§3.1). Sans elles, ni les archétypes d'arme ni les
> fonctions n'existent. Les nuances (7, 8, 9) sont ce qui fait qu'on **rejoue** un
> archétype ; le socle est ce qui fait qu'il **existe**.

### 11.2 Le plafond global de 500 points — **tranché le 2026-07-31 : supprimé**

`PlayerSkillHelper::MAX_TOTAL_SKILL_POINTS = 500` **contredit la doctrine des
trois couches** : « le savoir n'est jamais borné » (GAME_DOMAINS §1). Un plafond
global de points est exactement un verrou de savoir — et il est serré au point
qu'un seul arbre (465 points) le consomme presque entièrement.

> **Décision : le supprimer.** Les bornes réelles sont déjà là et suffisent :
> l'**énergie** borne le rythme (couche « savoir »), le **build** borne
> l'expression — un domaine ne s'exprime que si l'équipement en porte une source,
> DOM-02 (couche « faire »), et la **spécialisation / le patronage** bornent
> l'identité (couche « être »). Le plafond global ne borne rien de tout ça : il
> borne le temps de jeu, ce qui est la seule chose que ce jeu a décidé de ne
> jamais punir (GAME_PROGRESSION §5).

Conséquence assumée : un vétéran de deux ans aura appris beaucoup d'arbres. Il
n'en **exprimera** toujours que deux ou trois à la fois, parce qu'il ne porte
qu'une arme et trois emplacements. C'est précisément le contrat des trois couches.

> **Et les passifs conditionnels (§4.3) resserrent encore cette borne sans rien
> interdire** : un joueur qui a tout appris ne peut pas porter à la fois la
> plaque, le cuir, le bouclier, la dague et l'arc. Plus il apprend, plus ce qu'il
> **choisit de porter** décide de ce qu'il est. Le plafond n'a plus rien à borner.

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
8. **Chaque arbre ouvre au moins un accord de `dégât`, et au moins un accord qui
   n'en est pas un** — la jouabilité en solo, et le plan B (§5.1).
9. **La palette d'intentions est tenue** — un arbre d'entretien ou d'encaisse
   ouvre au moins un geste de portée `le groupe` ou `un allié` ; un arbre d'assaut
   au moins 3 gestes de `dégât` ; un arbre de contrôle au moins 2 `entrave`.
10. **Tout geste de portée `le groupe` porte une durée** — aucun soin, aucune
    protection, aucune amélioration collective n'est instantanée. C'est la loi du
    dépôt (§7 bis), et c'est elle qui rend le jeu de groupe asynchrone possible.
11. **La durée d'un dépôt se compte en tours de la rencontre** — jamais en temps
    réel, jamais en tours de son lanceur.
12. **Les conditions sont légales** — aucune au palier 1, au moins 2 des 7 passifs
    sans condition, condition satisfaisable par ce que l'arbre débloque lui-même,
    et jamais portée sur une pièce nommée ni sur une rareté (§4.3).
13. **La condition du capstone est atteignable avec les seuls accords d'entrée** —
    vérifiable en données : l'un des deux accords d'entrée pose le statut ou l'état
    que le capstone exige.
14. **Aucun nœud n'accorde d'action** (règle 9) — déjà couvert par
    `DomainPlanContractTest`, à étendre aux matéria de technique.
15. **Aucun passif de combat n'est plat** — plus aucun `damage`/`heal` entier sur
    un nœud de domaine de combat.
16. **Aucun plafond global de points** — le refus `global_cap` n'existe plus
    (§11.2). Un test qui le rétablirait doit échouer.
17. **Une marque par élément, et une seule** (§1.1) — et un des deux accords
    d'entrée de chaque arbre l'applique, sans quoi son capstone est hors de portée
    du joueur du jour 1.
18. **La fourche est un vrai choix** — deux branches, deux passifs **et un accord**
    chacune, **aucun levier commun**, exclusives entre elles, et le respec de branche
    se paie (mécanisme `other_branch`, livré par DOM-06). Une branche sans accord
    échoue le test : *deux branches qui ne diffèrent que par leurs passifs produisent
    le même combat* (§9 bis).
19. **Toute `protection` porte une durée**, quelle que soit sa portée (§7 bis) — une
    garde qui ne couvre que le tour où elle est jouée punit l'encaisse de se défendre.
20. **Le multiplicateur de condition suit la fréquence mesurée** — une condition de
    combat vraie plus des deux tiers du temps se paie ×1,4, pas ×2,0 (§4.3). Le
    simulateur d'ARC-05 la mesure ; l'auteur ne l'estime pas.
21. **Le pacte est unique et borné** — au plus un par arbre, au plus 10 pb de
    malus, malus **hors de la palette** de la fonction, nœud **feuille**, jamais au
    palier 1, et les plafonds par levier tiennent malgré lui.
22. **La durée d'un dépôt étale sa valeur, elle ne l'augmente pas** (§7 bis) — la
    valeur totale est fixée par le palier de la matéria ; une durée longue achète de
    la robustesse à l'absence, jamais de la puissance.
23. **Les marques se portent des deux côtés** (§1.1) — un monstre de chaque élément
    peut appliquer la sienne, sans quoi `ward` et les accords de dissipation sont des
    leviers morts pour deux fonctions sur quatre.
24. **Toute condition vient du vocabulaire fermé** (§4.3) — douze entrées, six de
    build et six de combat. Aucune condition portée sur une pièce nommée, une
    rareté ou l'élément de la cible ; aucun malus conditionné à l'équipement.
25. **La flèche ordinaire est pleinement jouable** (§2.1) — l'élément vient de la
    matéria, la munition ne fait que le remplacer. Un archer au plancher T1 exprime
    son arbre en entier.
26. **Une meilleure munition rend une prime fixe et plafonnée** (§2) — payer au-delà
    du palier ne rend jamais plus fort, et le coût quotidien des munitions reste sous
    les seuils du §9 quater.
27. **Aucune entrave ne coûte un tour pour en voler un** (§5, §9 quinquies) — durée
    ≥ 2 tours, ou marque portée par un geste de dégât. En duel, l'échange à un pour
    un est arithmétiquement nul.
28. **Les leviers principaux plafonnent à 20**, sauf `guard` à 15 (§4.1).
29. **L'ancre de fonction** (§9 sexies) — à arbre complet et équipement égal, les
    quatre fonctions enchaînent le **même nombre de rencontres par jour** et en
    sortent dans un état comparable. Seul invariant qui ne se vérifie pas sur un
    archétype isolé : il exige la **table croisée**, que le simulateur d'ARC-05 doit
    produire.
30. **Le palier des accords suit la fonction** (§9 sexies) — l'assaut ouvre ses
    gestes de dégât au palier plein, les trois autres fonctions un palier en dessous.
31. **Aucun archétype ne paie un coût récurrent en gils** (§9 septies) — les trois
    registres paient une ressource **dans** la rencontre. Le carquois est une pièce
    d'équipement durable, pas un consommable.
32. **Les PM se régénèrent hors combat**, à un rythme calibré contre celui des PV
    (§9 septies.2) — sans ce curseur, l'entretien joue trois fois plus de contenu
    que les autres pour la même énergie d'action.
33. **Toute fourche oppose deux contextes** (§9 septies.3) — chaque arbre porte une
    branche jouable **seul** et une branche qui sert le **groupe**. Une fonction
    dont aucune branche n'est jouable seule est une fonction fermée à 95 % du jeu.
34. **Une élite tue un joueur seul de son palier** (§9 octies) — quel que soit son
    archétype, et sans qu'aucun ne s'en sorte par une astuce. Mesuré : 102 à 129 %
    de la barre de chacun. C'est ce qui distingue un rang **Elite** d'un commun aux
    statistiques gonflées.
35. **L'écart d'attente quotidienne entre le meilleur et le pire build reste sous
    ×1,5** (§9 octies.4) — mesuré à ×2,2 aujourd'hui, c'est la cible de calibrage
    d'ARC-17.
36. **Toute forme de geste vient du vocabulaire fermé** (§13.1) — huit formes, et
    chacune répare un défaut mesuré. Une neuvième est une décision de moteur,
    instruite, jamais un ajout de fixture.
37. **Aucune ressource ne persiste entre deux rencontres** (§13.3) — charges,
    postures et différés meurent avec la rencontre ; seuls les PV, les PM et le
    carquois se reportent, et ils se rechargent en **temps réel**.
38. **Le familier est un dépôt, jamais un acteur** (§13.3) — il ne peut être ni
    ciblé, ni tué ; il meurt avec la rencontre, un seul à la fois, et les passifs de
    son arbre qualifient ses gestes (la double borne s'applique).
39. **L'aggro est bornée à la moitié de la riposte** (§13.4) — par défaut chacun
    encaisse la sienne. Aucun score cumulé, aucune perte d'aggro : un geste, une
    part, une durée.
40. **Une rencontre de groupe se calibre sur le pool de PV du groupe**, jamais sur
    un multiple du nombre de joueurs (§13.4) — sinon sa difficulté ne dépend pas de
    la composition, et elle exige donc la meilleure.
41. **Le soin direct n'est jamais interdit** (§7 bis.2 bis) — seule la portée `le
    groupe` impose le dépôt. Tout arbre d'entretien ouvre un soin direct au palier
    d'entrée : c'est le geste d'urgence, et le dépôt ne le remplace pas.
42. **Une amélioration déposée respecte `N × X ≤ 100`** (§7 bis.2 bis) — durée en
    tours × ampleur en pourcentage. Au-delà, améliorer bat frapper et l'archétype
    optimal devient « ne jamais attaquer ».
43. **Aucune accointance ne donne de puissance** (§9.7) — ni point de budget, ni
    levier, ni statistique. Quatre formes légales, et rien d'autre.
44. **Chaque arbre ouvre un accord exclusif** — une matéria qu'aucun autre arbre
    n'ouvre (§5.1).

---

## 13. Les formes de geste — ce que les autres jeux savent faire

Quatre exercices, vingt corrections, **aucune sur un pourcentage**. Toutes ont
porté sur des gestes, des durées, des ressources ou des conditions. Il reste donc
un angle mort, et c'est le dernier :

> Le vocabulaire d'intentions (§3.1) dit ce qu'un geste **fait** — dégât, soin,
> protection, amélioration, entrave. Il ne dit rien de sa **forme**. Or c'est
> exactement là que vivent les archétypes des autres MMO : un chasseur et un
> nécromancien ne diffèrent pas par leurs statistiques, ils diffèrent parce qu'un
> **familier joue à leur place**.

**Le critère d'admission**, le même que pour les leviers (§4) : *une forme occupe
une place qu'aucune autre n'occupe, et elle répare un défaut mesuré.* Les huit
retenues le font ; ce qui n'est pas dans la liste est de la saveur, pas de la
mécanique.

**Aucune ne demande une cinquième fonction.** La grille assaut / contrôle /
entretien / encaisse tient — ce qui manquait n'était pas un rôle, c'était un
vocabulaire du côté de la **matéria**. Ce qui est, mot pour mot, la thèse du §0.

### 13.1 Les huit formes

**1. Le familier** — *un acteur qui joue à votre place.*
Chasseur (WoW), Nécromancien (EverQuest), Osamodas (Wakfu), Invocateur (FFXI).
**Ce qu'il répare ici** : le donjon semi-synchrone résout le tour d'un absent par
une **attaque de base** (`GroupDungeonCombatService`). Un joueur déconnecté ne
laisse rien derrière lui. Le familier fait de l'absence une contribution — c'est
**le dépôt qui agit**, et c'est la forme la plus alignée sur notre modèle.
*Porté par* : bête × sorts (Druide), ténèbres × sorts (Nécromancien), métal ×
distance (Ingénieur — la tourelle). *Coût moteur* : **M** — arbitré au §13.3, il est
un **dépôt offensif**, pas un acteur : aucune IA, aucune cible supplémentaire.
*Garde-fou* : il ne double pas la puissance, il la **déplace**.

**2. La charge** — *une ressource qui se construit dans la rencontre.*
Points de combo (Roublard), chi (Moine), rage, puissance sacrée.
**Ce qu'elle répare** : la mêlée n'a qu'un temps de reprise, donc une rotation sans
récompense. Surtout, la charge **paie les longs combats** — précisément là où on
veut que la mêlée brille (l'élite, le boss — §9 octies), et là où elle est
aujourd'hui la plus mauvaise.
*Coût* : deux champs sur `Spell` (`generates` / `consumes`) et un compteur par
rencontre. *Garde-fou* : **la charge meurt avec la rencontre.** Une ressource qui
persiste entre les combats double la comptabilité de la journée (§9 septies) et
transforme le jeu en gestion de stock.

**3. Le transfert** — *une part des dégâts des alliés vous revient.*
Main de sacrifice, vigilance, l'interposition (Paladin WoW, GW).
**Ce qu'il répare** : **notre modèle ne peut pas avoir d'aggro.** La rencontre
frappe le joueur actif ; il n'y a personne à provoquer, donc le tank perd son
geste identitaire. Le transfert le lui rend — et comme il porte sur les **alliés**,
il se multiplie par la taille du groupe (§9 quinquies).
*Porté par* : encaisse, branche de groupe (le Mur). *Garde-fou* : borné en
pourcentage **et** en durée — sinon le tank meurt pour les autres, ce qui est un
beau geste et un mauvais jeu.

**4. La riposte** — *être frappé est une action.*
Épines (Diablo, EQ), représailles, le samouraï à contre-attaque.
**Ce qu'elle répare** : **le tank ne tue pas** — le Mur met 14 tours là où l'archer
en met 6 (§9 sexies). La riposte lui donne des dégâts **sans lui donner de la
vitesse**, donc sans effacer son coût structurel.
*Coût* : presque nul — un point d'accroche au moment d'encaisser. *Garde-fou* :
elle ne s'applique **jamais aux dégâts évités** (esquive, absorption), sinon
l'encaisse optimale consiste à se faire toucher exprès.

**5. L'ouverture** — *le combat commence avant le combat.*
Pièges du rôdeur (EQ, GW), ouverture furtive (Roublard), tir de préparation.
**Ce qu'elle répare** : `tempo` — l'initiative — **n'a aucun effet modélisé**
(§9 sexies). C'est un levier décoratif dans deux palettes. Une ouverture le rend
concret : un geste posé **depuis l'écran de zone**, qui s'applique à la rencontre
suivante. Le premier tour est joué avant que l'ennemi n'existe.
*Porté par* : assaut × distance (Archer), contrôle (Hydromancien), ténèbres ×
mêlée (Assassin). *Garde-fou* : elle coûte de l'**énergie d'action**, jamais un
tour — c'est ce qui l'empêche d'être systématique, et ça la met en concurrence
avec un combat de plus.

**6. La conversion** — *échanger une ressource contre une autre.*
Connexion vitale (Démoniste), magie de sang (PoE), le drain.
**Ce qu'elle répare** : la **facture quotidienne** est notre monnaie d'équilibrage
(§9 septies), et le pyromancien paie deux fois — fragile *et* dépensier
(§9 octies). La conversion lui rend un choix : payer en PV ce qu'il ne peut plus
payer en PM.
*Garde-fou* : **le taux de change est défavorable.** On perd à convertir, sinon
convertir est toujours correct et ce n'est plus une décision.

**7. La posture** — *un choix durable qu'on remplace.*
Postures du guerrier, chants du barde (FFXI), auras, mantras.
**Ce qu'elle répare** : la fourche est un choix **permanent** (respec payant) ; la
posture est le même choix à l'échelle de **la rencontre**. Elle donne une décision
par combat sans ajouter une action à jouer chaque tour — ce que la règle 9 rend
précieux.
*Forme technique* : un dépôt `scope: soi`, **sans durée**, exclusif. *Garde-fou* :
**une seule active**, et en changer coûte le tour. Sinon c'est un bouton gratuit.

**8. Le différé** — *un geste qui frappe plus tard.*
Bombes à retardement (Wakfu), sceaux à déclenchement, DoT à explosion.
**Ce qu'il répare** : c'est **la seule forme qui exploite l'asynchronie au lieu de
la subir.** Dans un donjon dont les tours s'étalent sur des heures, un geste qui se
résout deux tours plus tard se résout **pendant le tour de quelqu'un d'autre** — le
joueur absent continue d'agir. C'est le cousin offensif du dépôt : là où le dépôt
étale un effet sur les alliés, le différé étale une **action** sur la rencontre.
*Garde-fou* : il se résout **en tours de rencontre**, jamais en temps réel — même
compteur que les dépôts (§7 bis).

### 13.2 Chaque forme répond à un défaut mesuré

C'est ce qui distingue cette liste d'un catalogue d'idées :

| Forme | Le défaut qu'elle répare | Où il a été mesuré |
|---|---|---|
| Le familier | le tour d'un absent ne produit qu'une attaque de base | §7 bis.1 |
| *(le familier, suite)* | et il ne sert **que** sur les longues rencontres : +2 % sur un commun, +9 % sur une élite | §13.3 |
| La charge | la mêlée n'a aucune raison d'aimer les longs combats | §9 octies |
| Le transfert | l'aggro est impossible, le tank perd son geste de groupe | §9 quinquies |
| La riposte | le tank ne tue pas (14 tours contre 6) | §9 sexies.1 |
| L'ouverture | `tempo` n'a aucun effet modélisé | §9 sexies.2 |
| La conversion | le pyromancien paie deux fois | §9 octies.4 |
| La posture | aucun choix à l'échelle d'une rencontre | §6.1 bis |
| Le différé | l'asynchronie n'est jamais un avantage, seulement une contrainte | §7 bis |

### 13.3 Arbitrage — le familier *(rendu le 2026-08-01)*

Le §13.1 posait le familier comme « un acteur qui joue à votre place », et le
classait **L** — le plus cher des huit. L'arbitrage porte sur un point unique :
**est-ce un acteur, ou un dépôt ?**

**Le test.** Retirez le ciblage — l'ennemi ne peut pas s'en prendre au familier.
Que reste-t-il ? Une chose qui inflige des dégâts à chaque tour de la rencontre,
pendant une durée, posée en un tour. C'est **exactement un dépôt** (§7 bis), et le
critère d'admission du §13.1 exige qu'une forme occupe une place qu'aucune autre
n'occupe.

**Décision : le familier est retenu, mais comme dépôt offensif. Ce n'est pas un
acteur.**

| | |
|---|---|
| **Ce qu'on garde** | il **agit sur les tours où son invocateur est absent** — sa raison d'être, et le seul défaut qu'il devait réparer |
| **Ce qu'on garde aussi** | la fiction entière : une créature invoquée, nommée, qui frappe. Le joueur ne lit pas « dépôt » |
| **Ce qu'on perd** | il ne peut pas être ciblé, ni tué, ni encaisser à la place de quelqu'un |
| **Ce qu'on économise** | aucun second acteur dans la boucle de tour, aucune IA, aucune cible supplémentaire. **Le coût passe de L à M** |

**Ce qu'il vaut, mesuré** — familier à 40 % du geste de son invocateur, 6 tours,
posé en un tour :

| | Sans familier | Avec | Gain |
|---|---:|---:|---:|
| Commun (100 PV) | 6,1 tours | 6 tours | **+2 %** |
| Élite (180 PV) | 11,0 tours | 10 tours | **+9 %** |

Rendement du tour investi : **×2,4** — le profil exact d'un dépôt. Et le même
enseignement que pour *la charge* : **le familier ne sert pas sur le tout-venant**,
il sert dans les longues rencontres. Deux formes qui poussent au même endroit, ce
qui est cohérent : elles récompensent toutes deux la durée.

> **Quatre garde-fous.** (1) Il **meurt avec la rencontre** — jamais de familier
> permanent, ce serait une ressource qui persiste (§13.3). (2) **Un seul à la
> fois**, comme la posture. (3) **Les passifs de l'arbre qualifient ses gestes** :
> c'est un prolongement de l'invocateur, donc la double borne s'applique — il ne
> contourne pas la case élément × registre. (4) **Il déplace la puissance, il ne la
> double pas** : son coût en ressource est celui d'un geste de son palier, et son
> action vaut une fraction de celle du joueur.

### 13.4 Arbitrage — l'aggro *(rendu le 2026-08-01)*

Le §13.3 la refusait : *« impossible sur une rencontre à PV partagés qui frappe le
joueur actif — il n'y a personne à provoquer. »* Le refus est **rouvert**, parce
qu'il reposait sur le modèle **actuel** du donjon, et que **DON-02/03 le change** :
la rencontre abstraite devient de vrais monstres, avec une **riposte**. Dès qu'une
riposte existe, la question « qui la prend ? » se pose, et c'est toute l'aggro.

#### Ce que l'aggro change vraiment

**Elle ne réduit rien. Elle déplace.** Le total des dégâts d'une rencontre est
fixé par sa durée ; l'aggro décide seulement de **qui** les encaisse. Son intérêt
est donc entier : les concentrer sur celui qui est équipé pour les recevoir.

| | Sans aggro | Avec aggro |
|---|---|---|
| Qui encaisse | chacun la riposte de **ses propres** actions | le joueur **le plus menaçant** |
| Ce que ça donne au tank | rien — il ne protège personne | **enfin un rôle**, et un rôle actif |
| Ce que ça donne aux fragiles | ils paient plein tarif | ils survivent parce que quelqu'un paie à leur place |

#### Le mur : notre échelle défensive n'est pas calibrée pour ça

| Build | PV effectifs *(arbre seul)* |
|---|---:|
| Soldat — le Mur *(plaque)* | 167 |
| Soldat — la Ligne mobile | 149 |
| Guérisseur *(tissu)* | 130 |
| Pyromancien *(tissu)* | 120 |

**L'écart tank / tissu est de ×1,39.** Pour qu'un tank encaisse la part de quatre
joueurs, il lui faudrait **×4**. Le budget d'arbre ne peut pas le financer : il
faudrait **47 points de budget rien qu'en `guard`**, sur les 50 que vaut un arbre
entier, et le plafond du levier est à 15.

> **La mitigation d'un tank ne peut pas venir de son arbre. Elle doit venir de son
> armure.** C'est cohérent avec le canon — *l'équipement est le build*
> (GAME_DOMAINS §3) — et ça déplace le sujet vers `GAME_ITEMS`, où il a sa place.

#### Ce qui passe, chiffré

Rencontre de groupe calibrée (480 PV, 22 tours, 480 dégâts au total) :

| | Arbre seul | Arbre + plaque à −28 % |
|---|---|---|
| **Sans aggro** *(part égale, 106)* | survit | survit |
| **Aggro bornée à 50 %** *(212)* | **mort** | **survit** *(144 sur 147)* |
| **Aggro totale** *(423)* | mort | mort |

#### Comment ça se joue, concrètement

Le transfert n'est **ni une statistique, ni un score** : c'est un **geste**, donc
une matéria, donc soumis aux mêmes règles que tout le reste. Et parce qu'il touche
les alliés, c'est un **dépôt** (§7 bis) — il se pose, il dure, et il agit même si
son lanceur est déconnecté.

> **Cri de ralliement** — `protection`, `le groupe`, 8 tours de rencontre.
> *Pendant sa durée, la moitié des dégâts qui frapperaient un allié vous
> reviennent.*

| Tour | Ce qui se passe |
|---|---|
| 1 | Terel *(le Mur)* pose le Cri. Il n'attaque pas — c'est son coût |
| 2 | Mira lance sa Nova. La rencontre riposte : 22. **Elle en prend 11, Terel 11** — dont il ne ressent que 7, grâce à sa plaque |
| 3-5 | Idem pour les deux autres |
| **6** | **Terel se déconnecte.** Son tour se résout en attaque de base |
| 6-9 | **Le Cri court toujours.** Les alliés restent couverts — c'est la loi du dépôt |
| 10 | Il expire. Chacun reprend ses propres ripostes |

#### Pourquoi la moitié, et pas plus

Mesuré sur la rencontre calibrée (480 dégâts, 22 tours, 4 joueurs) — le tank a
147 PV et −36 % de mitigation *(arbre + plaque)*, le porteur de tissu 120 PV et
rien :

| Part déplacée | Poses | Tank *(147 PV)* | Tissu *(120 PV)* |
|---:|---:|---|---|
| 30 % | 2 | 98 ✔ | 94 ✔ *(au bord)* |
| **50 %** | **1** | 104 ✔ | 98 ✔ |
| **50 %** | **2** | **132 ✔** | **76 ✔** |
| 50 % | 3 | **149 ✘ — il meurt** | 60 ✔ |
| 70 % | 2 | 165 ✘ | 59 ✔ |
| 100 % | 2 | 215 ✘ | 33 ✔ |

**Trois choses se lisent dans ce tableau.**

1. **Au-delà de la moitié, le tank meurt** — quoi qu'il fasse, et même en plaque.
   La borne n'est pas un choix de confort, c'est le point où l'arithmétique
   s'arrête.
2. **Il ne peut pas le maintenir en permanence** : trois poses le tuent. Il
   **choisit ses fenêtres** — et c'est du jeu, apparu tout seul, sans qu'on ait à
   inventer un temps de reprise.
3. **En dessous de 30 %, le porteur de tissu reste au bord.** L'intervalle utile
   est étroit, et 50 % est son centre.

#### Ce que ça change vraiment — c'est une assurance, pas une mitigation

| | Sans transfert | Avec *(50 %, 2 poses)* |
|---|---:|---:|
| Tank | 76 sur 147 | 132 sur 147 |
| Porteur de tissu | **120 sur 120 — mort** | **76 sur 120 — vivant** |
| Total encaissé par le groupe | 436 | 372 |

**Le groupe n'économise que 15 % de dégâts. Ce qu'il gagne, c'est que personne ne
tombe.** Le transfert ne se calibre donc pas comme un outil de réduction — il se
calibre comme une **assurance** : sa valeur est nulle quand tout va bien, et
totale quand quelqu'un allait mourir.

> **Le tank ne protège pas : il assure.** Exactement le pendant du guérisseur, qui
> *ne soigne pas mais provisionne* (§9 ter). Les deux fonctions de groupe posent à
> l'avance ce qu'elles ne pourront pas donner au moment voulu — parce que dans un
> donjon semi-synchrone, **le moment voulu arrive quand on n'est pas là.**

**Décision : l'aggro entre, bornée, et sous la forme du transfert.**

1. **Par défaut, chacun encaisse la riposte de ses propres actions.** Un groupe
   sans tank fonctionne — c'est ce qui préserve « aucun rôle n'est nécessaire »
   (§7 bis).
2. **Un geste de menace déplace au plus la moitié** de la riposte vers celui qui le
   pose, pour une durée. C'est la forme 3 (**le transfert**), qui cesse d'être un
   contournement pour devenir le mécanisme lui-même.
3. **La table de menace reste refusée** : pas de score cumulé, pas de course au
   sommet, pas de « perte d'aggro » à gérer. Un geste, une part, une durée — les
   trois choses que notre modèle sait déjà faire.
4. **Elle exige que la plaque porte ~−30 %.** Sans ce chiffre, l'aggro tue le tank
   au lieu de sauver le groupe. **C'est le prérequis, et il est dans GAME_ITEMS.**

> **Et ça ne casse pas l'équilibre solo**, ce qui n'allait pas de soi : une plaque
> à −28 % ferait du tank le meilleur solitaire… si sa lenteur ne le rattrapait pas.
> Il met 14 tours là où l'archer en met 6 — 14 × 0,72 = 10 tours-de-dégâts contre 6
> pour l'archer. L'écart reste à l'avantage de l'assaut. **La vitesse paie encore.**

#### Le défaut que l'exercice a trouvé au passage

En calibrant la rencontre de groupe, une **incohérence livrée** apparaît :

| `zone.dungeon.encounter_hp_per_member` | PV | Durée | Dégâts | Groupe de 4 *(pool 518 PV)* |
|---:|---:|---:|---:|---|
| **200** *(valeur livrée)* | 800 | 36 tours | 800 | **wipe sans soigneur** |
| 150 | 600 | 27 tours | 600 | wipe sans soigneur |
| 120 | 480 | 22 tours | 480 | tenable, mais **au fil du rasoir** (118 encaissés sur 120 PV) |
| **110** | 440 | 20 tours | 434 | **tenable avec une marge** (109 sur 120) |

**Le curseur livré est calibré pour une rencontre sans riposte** — c'est-à-dire
pour le donjon actuel, où rien ne peut être perdu (GAME_DUNGEONS). Le jour où
DON-03 branche la riposte, **200 PV par membre rend le soigneur obligatoire**, ce
que le §7 bis interdit. Il doit descendre à ~120.

> **La règle qui le remplace** : *une rencontre de groupe se calibre sur le **pool
> de points de vie du groupe**, jamais sur un multiple du nombre de joueurs.* Un
> multiple linéaire produit mécaniquement une rencontre dont la difficulté ne
> dépend pas de la composition — et donc qui exige la meilleure.

### 13.5 Ce qu'on refuse, et pourquoi

| Mécanique | D'où elle vient | Pourquoi non |
|---|---|---|
| **La table de menace (aggro)** | WoW, FFXIV, la trinité classique | **Le score cumulé** reste refusé : pas de course au sommet, pas de perte d'aggro à gérer. Mais **l'aggro elle-même entre**, bornée et sous la forme du **transfert** — arbitrage rendu au §13.4, une fois la riposte livrée (DON-03) |
| **La trinité obligatoire** | tous les MMO à raids | Aucun rôle n'est nécessaire (§7 bis). Un groupe sans soigneur met plus de tours ; il ne rencontre pas un mur. C'est déjà un refus acté (GAME_INSPIRATIONS §5) |
| **Les effets qui n'existent qu'en groupe** | Parangon (GW1), Chanteur (Aion) | Morts 95 % du temps à 1-2 joueurs simultanés. Tout geste collectif doit avoir une lecture en `scope: soi` |
| **Le changement d'arme en combat** | GW2, ESO | Contredit DOM-02 — le build se change **hors** combat, et c'est ce qui rend la borne matérielle honnête |
| **La ressource qui persiste entre les rencontres** | âmes du Démoniste, charges accumulées | Double la comptabilité de la journée (§9 septies) et transforme le combat en gestion de stock |
| **Le tour supplémentaire** | Final Fantasy Tactics, extra turns | Dans un modèle semi-synchrone à un joueur actif, un tour de plus est un tour **volé aux autres** |
| **La montée en puissance entre les combats** (stacks qui montent sur la journée) | Diablo, ARPG | Récompense le temps passé, la seule chose que ce jeu a décidé de ne jamais récompenser (GAME_PROGRESSION §5) |

---

## 14. Ce que ce document ne décide pas

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
- **Quelles formes de geste (§13) sont livrées, et dans quel ordre** — le
  vocabulaire est arrêté, la priorisation est un sujet de plan (ARC-18). Les deux
  arbitrages qui bloquaient sont rendus : le **familier** est un dépôt (§13.3),
  l'**aggro** entre bornée (§13.4).
- **La mitigation des lignes d'armure** — la **fourchette** est mesurée (§2.2 :
  30 % minimum pour que l'aggro passe, 50 % maximum avant que le solo ne casse,
  cible ~40 %), mais le chiffre retenu appartient à
  [GAME_ITEMS.md](GAME_ITEMS.md), pas au budget d'arbre.
- **Le nombre d'arbres qu'un joueur mènera réellement** : c'est une conséquence de
  l'énergie et du build, pas une règle à écrire.
- **La valeur de la vitesse de combat** (§9 sexies.4) — l'arbitrage est posé et
  documenté, la décision revient au jeu, pas aux arbres. Tant qu'il n'est pas rendu,
  **l'assaut n'a pas de raison d'exister** et le guerrier reste le meilleur choix
  dans tous les cas de figure.
