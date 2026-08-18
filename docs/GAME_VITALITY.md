# La vitalité — d'où vient la barre de vie, et pourquoi le tank en a plus

> **Statut : proposition instruite, 2026-08-18.** Compagnon de
> [GAME_ARCHETYPES.md](GAME_ARCHETYPES.md) (les archétypes et les leviers), de
> [GAME_BESTIARY.md](GAME_BESTIARY.md) (le gabarit `tier × rank`) et de
> [GAME_ITEMS.md](GAME_ITEMS.md) (les lignes d'armure).
>
> Il répond à une question que **personne n'avait posée** et dont tout le reste
> dépend : *le personnage n'a pas de niveau — alors qu'est-ce qui fait monter sa
> barre de vie, et qu'est-ce qui fait qu'un tank en a plus qu'un mage ?*
>
> Décliné en jalons dans [roadmap/PLAN_ARCHETYPES.md](roadmap/PLAN_ARCHETYPES.md)
> (**ARC-20**).

---

## 0. Le problème

### 0.1 Ce qui est livré, mesuré le 2026-08-18

| Source de PV | Ce qu'elle donne aujourd'hui |
|---|---|
| `PlayerFactory::BASE_LIFE` | **20 PV**, fixes à vie |
| `Skill::life` (~30 nœuds) | +3 / +5, **plat**, **cumulatif**, écrit en dur dans `Player::maxLife` par `SkillAcquiring` |
| Levier `life` (ARC-03) | ×1,5 % par point de budget, plafond 20 pb → **+30 %**, calculés sur la base de 20 |
| Enchantement, patronage | +10 PV, +X % de la base |
| Équipement | **rien.** `Item::protection` existe, s'affiche sur la fiche d'inventaire, et **n'est lu par aucune formule de combat** |
| Mitigation d'armure | **n'existe pas dans le moteur.** ARC-19 la réclame comme prérequis |

Un personnage qui a tout appris plafonne entre **26 et 40 PV**.

### 0.2 Ce qu'il y a en face

`MonsterStatTemplate` (BES-02, puis ARC-17a pour les dégâts) :

| | T1 | T2 | T3 | T4 |
|---|---|---|---|---|
| Vie d'un commun | 30 | 70 | 150 | 300 |
| **Frappe d'un commun** | 4 | 9 | 19 | **38** |
| **Frappe d'une élite** | 12 | 26 | 55 | **110** |

Une élite de palier 4 tue le personnage le plus solide du jeu **en un coup, trois
fois de suite**. Le contenu fait **×80** de T1 à T4 ; le joueur fait **×2**.

### 0.3 Le canon suppose une barre que rien ne produit

Ce n'est pas une omission d'équilibrage, c'est un **trou dans le modèle**.
GAME_ARCHETYPES raisonne depuis le premier jour avec « **joueur 120 PV** » au
palier 2 (§9 bis, §9 sexies, §9 octies) et un tank à 147. Les six builds comparés,
la journée simulée, les 102 à 129 % de barre qu'une élite retire : **tous ces
chiffres reposent sur une échelle de PV que le code ne produit nulle part**.

Le simulateur d'ARC-17 mesure donc, aujourd'hui, un personnage de 20 PV contre des
monstres calibrés pour 120. C'est ce qui rend quatre de ses cinq seuils
inatteignables — ils portent sur les dégâts subis.

### 0.4 Les deux questions, qu'il faut séparer

1. **L'échelle verticale** — qu'est-ce qui fait que la barre suive le contenu,
   sans niveau ?
2. **La différenciation** — qu'est-ce qui fait qu'un tank encaisse plus ?

La seconde est presque tranchée : GAME_ARCHETYPES §13.4 a mesuré que *la mitigation
d'un tank vient de son armure, pas de son arbre* (par l'arbre seul, l'écart
tank/tissu vaut **×1,39**). **La première n'a aucune réponse**, et c'est l'objet de
ce document.

---

## 1. L'étude — comment les autres jeux font monter une barre de vie

Même méthode que [GAME_INSPIRATIONS.md](GAME_INSPIRATIONS.md) : un jeu n'est pas
cité parce qu'il est bon, mais parce qu'il a **résolu un problème qu'on a**. Les
trois filtres du §1 s'appliquent (sans PvP, asynchrone et navigateur, coût
d'écriture amorti).

### 1.1 Deux familles

| Famille | Principe | Jeux | Ce que ça coûte |
|---|---|---|---|
| **Verticale** | la barre suit le contenu | la quasi-totalité | il faut recalibrer soins, régénération et consommables à chaque palier |
| **Horizontale** | la barre ne monte jamais | **Guild Wars 1** — 480 PV à vie, cap atteint en ~10 h | le contenu doit être horizontal lui aussi ; aucune robustesse acquise |

GW1 prouve que l'horizontal fonctionne, mais il l'a payé par un jeu entièrement
horizontal (800 compétences, zéro inflation de monstre). **Nous avons déjà tranché
l'inverse** : `MonsterStatTemplate` fait ×80. Le vertical est acquis par
construction ; la question est *par quel véhicule*.

### 1.2 Les quatre véhicules verticaux

**a. Une compétence de vie dédiée — Old School RuneScape.** Notre cousin
structurel le plus proche : pas de niveau global, des compétences indépendantes, et
*Hitpoints* est **une compétence en soi** qui monte en combattant, quel que soit le
style. PV = 10 + niveau.
→ **Refusé.** Elle récompense le **volume** de combats, donc taper des rats —
exactement ce qu'ARC-06b a fermé en indexant le gain de points sur le **palier** de
l'adversaire.

**b. La branche la plus avancée — Ryzom.** Déjà dans notre premier cercle. Aucun
niveau de personnage : quatre branches (combat, magie, artisanat, récolte), et les
**pools dérivent de la branche correspondante**, jamais de leur somme.
→ **Le précédent le plus directement transposable.** Il résout le problème que
posent nos 32 arbres et « le savoir n'est jamais borné » : on prend le **maximum**,
pas le total.

**c. L'équipement — Albion Online, EVE Online.** Albion : **aucun niveau de
personnage**, la vie vient des pièces (palier + enchantement), la plaque en donne
plus que le tissu, et le Destiny Board dit ce qu'on a le droit de porter. EVE :
aucun PV de personnage du tout — l'**EHP** vient du vaisseau et des modules de
résistance.
→ **À doser, pas à adopter en entier.** Albion assume que le marché décide de la
survie parce qu'il a le **full loot** pour l'équilibrer ; nous n'avons ni l'un ni
l'autre (règle 11). EVE apporte en revanche l'idée juste : *le tank choisit un
**type** de tank, pas un plus gros nombre*.

**d. Le niveau, plus un multiplicateur d'archétype — FFXIV, GW2, Dofus.** FFXIV :
une base dérivée du niveau, puis un **modificateur de vie par job** (les tanks
autour de 140, le reste autour de 100). GW2 : trois paliers de vie de base **par
profession** (guerrier ~19 k, élémentaliste ~11 k, rapport ×1,75), **décidés par la
classe, jamais achetés**.
→ **Le découpage est la bonne idée** : *la base vient du contenu, l'archétype vient
d'un multiplicateur qu'on ne choisit pas dans un arbre.* Nous n'avons pas de
classes — mais nous avons des **lignes d'armure**, qui jouent exactement ce rôle.

### 1.3 Les trois façons de différencier le tank, et celle qui échoue

| Façon | Jeux | Verdict |
|---|---|---|
| Un **multiplicateur d'archétype** posé, non acheté | GW2, FFXIV | marche, mais suppose des classes |
| La **mitigation / les résistances** — on choisit un *type* de tank | **EVE**, Star Wars Galaxies, Albion | **marche, et notre canon l'a déjà actée** (plaque 40 / cuir 20 / tissu 0) |
| **Acheter de la vie dans un arbre partagé** | Path of Exile, Dofus, Diablo II | **échoue systématiquement** |

Le troisième cas est le cœur de l'étude. **Path of Exile** : les nœuds
`% increased maximum Life` vivent dans l'arbre commun, et il en résulte la « taxe
de vie » — tout le monde prend les mêmes nœuds parce que ne pas les prendre, c'est
mourir. Ce n'est pas un choix, c'est un péage, et il appauvrit l'arbre d'autant.
**Dofus** : la Vitalité coûte 1 point pour 1 PV, c'est la caractéristique la moins
chère, donc tout le monde en prend. **Diablo II** : le « vitality build », pour
toutes les classes.

> **La leçon, et c'est la plus importante de l'étude :** dès qu'une mécanique fait
> la différence entre survivre et mourir face au contenu du moment, elle **cesse
> d'être un choix**. La rendre payante ne crée pas un arbitrage — elle crée une
> taxe que tout le monde paie.
>
> **Corollaire, et c'est la décision de ce document :** *la progression verticale
> ne doit jamais être un choix ; seule la différenciation l'est.*

### 1.4 Ce qu'on prend, ce qu'on refuse

| | |
|---|---|
| ✅ **Ryzom** | la branche la plus avancée porte le pool — **le maximum, jamais la somme** |
| ✅ **FFXIV / GW2** | la base vient du contenu, l'archétype d'un multiplicateur non acheté |
| ✅ **EVE / SWG** | la survie du tank est une **mitigation**, et c'est un *type* de défense |
| ✅ **FF (Soin / Soin+ / Méga-Soin)** | des soins à **valeur fixe par palier**, et l'obsolescence assumée |
| ❌ **OSRS** | une compétence de vie qui monte en combattant — récompense les rats |
| ❌ **PoE, Dofus, D2** | des nœuds de vie additifs — la taxe, et ×32 arbres chez nous |
| ❌ **Albion** | la vie majoritairement dans l'équipement — le marché déciderait de la survie |
| ❌ **GW2, OSRS** | le *downscaling* — il supprime « une élite tue un joueur seul », qui est chez nous une propriété **absolue** |

---

## 2. Décision 1 — Trois étages, trois rôles, et pas un de plus

| Étage | Ce qu'il fait | D'où il vient | Amplitude |
|---|---|---|---|
| **Le Socle** | suit le contenu — c'est le remplaçant du niveau | un **palier**, porté par un nœud d'arbre et calculé par une loi | **×9** sur la vie du jeu |
| **L'armure** | la mitigation — **c'est là que vit le tank** | la ligne de la pièce portée (GAME_ITEMS) | ×1 à ×1,67 de PV effectifs |
| **Les leviers** | le choix — `life`, `guard`, `dodge` dans les 50 pb | l'arbre | ±30 % |
| *(l'équipement)* | l'écart horizontal entre deux joueurs du même palier | pièces, enchantements | ±15 % |

**Chaque étage fait une chose et une seule.** C'est ce qui permet d'en recalibrer un
sans toucher aux autres, et c'est ce qui manque au modèle actuel, où le levier
`life` porte à la fois la différenciation et (très mal) la verticalité.

> **Ce que le Socle rend possible, et qui est sa vraie justification :** en
> garantissant la survie de base, il rend le levier `life` **facultatif**. Un mage
> qui ne l'achète pas est fragile, pas mort. Sans Socle, `life` deviendrait
> obligatoire dans les 24 arbres et le budget de 50 pb n'en compterait plus que 30.
> *C'est le Socle qui empêche la taxe de Path of Exile.*

---

## 3. Décision 2 — Le Socle est un nœud visible **et** une loi

**Les deux, et pas l'un ou l'autre.** Le nœud existe pour être vu — un palier de
vie est un moment de la progression, pas une variable cachée ; la valeur est
calculée par une loi — parce qu'une valeur écrite dans 24 arbres diverge au premier
ajustement du bestiaire.

### 3.1 Sa forme

| | |
|---|---|
| **Nature** | une **porte**, jamais un levier |
| **Ce qu'il donne** | le **palier de vitalité** *n* — un état, pas une addition |
| **Coût en budget** | **0 pb** (il ne donne aucun levier, donc rien à peser) |
| **Coût en points** | **0 point** — on ne l'achète pas, on l'atteint |
| **Où** | un par palier d'arbre (1, 2, 3) dans **chacun des 24 arbres de combat** |
| **Prérequis** | un nœud du palier — c'est la chaîne de prérequis qui le date, pas son prix |
| **Entre arbres** | **maximum, jamais somme.** Le Socle II d'un deuxième arbre ne donne rien |

**Pourquoi gratuit.** C'est la conséquence directe de la leçon du §1.3 : la
progression verticale ne doit pas être un choix. Le faire payer en points en
ferait un péage ; le faire payer en budget en ferait la taxe de PoE. Il ne coûte
rien parce qu'il n'est pas une récompense — il **constate** que le personnage a
franchi un palier.

**Pourquoi le maximum et jamais la somme.** C'est la seule forme qui survive à
« le savoir n'est jamais borné » (GAME_DOMAINS §1). Un nœud additif à +100 PV donne
+3 200 PV au joueur qui a mené les 32 arbres, et **rien ne le borne** — c'est très
exactement le défaut de `Skill::life` aujourd'hui. Un palier est **idempotent** :
l'apprendre deux fois ne fait rien.

### 3.2 Ce que le Socle n'est pas

- **jamais un levier** — il n'entre dans aucune des quinze places de la formule, et
  il ne consomme pas de budget ;
- **jamais une source de puissance** — il donne la survie, **jamais la capacité de
  nuire**. Un joueur qui se précipiterait sur le Socle IV se retrouverait avec 880
  PV et des gestes de palier 1 : il survivrait à un monstre T4 sans pouvoir le
  tuer. *Le Socle n'ouvre aucun contenu* ;
- **jamais un niveau global** — il est **par arbre**, il ne s'affiche pas comme un
  rang de personnage, et deux personnages au même palier de vitalité peuvent ne
  partager aucune compétence.

### 3.3 L'amendement qu'il impose

GAME_TREE_ANATOMY §2 énumère **six natures de nœud, « et pas une septième »**. Le
Socle en est une septième, et il faut le dire plutôt que de le déguiser.

La liste des six répondait à la question *« qu'est-ce qu'un nœud donne à un
build ? »* — accord, passif, capstone, échelon de port, pacte, accord dormant. Le
Socle ne donne rien à un build : il enregistre **ce que le personnage est devenu
capable d'encaisser**. C'est une question que la liste ne posait pas.

> **La règle qui la referme :** *une septième nature n'est admise que si elle ne
> donne ni geste, ni levier, ni droit de port — sinon c'est l'une des six sous un
> autre nom.*

### 3.4 Le plancher, et ceux qui n'ont pas d'arbre de combat

Le **palier 1 est le plancher du jeu**, porté par `PlayerFactory` et non par un
arbre : un personnage qui sort du tunnel de création, ou qui ne mène que des arbres
de métier, l'a sans rien avoir appris. C'est le même principe que l'outil de
palier 1 offert à l'ouverture d'un arbre de récolte (OBJ-06) et que le plancher du
jour 1 de GAME_MATERIA §3. **On ne peut pas se retrouver sans barre de vie.**

---

## 4. Décision 3 — La barre se dérive du bestiaire

Comme les filons ont des profils, les matéria une dérivation depuis leur sort et
les monstres un gabarit `tier × rank`, **la barre du joueur ne s'écrit pas**.

> **La loi :** *la barre d'un joueur de palier n vaut ce qu'une élite de son palier
> lui prend en une rencontre entière.*
>
> `barre(n) = tours_médians(élite) × frappe(n, élite)`, soit **8 × la frappe d'une
> élite de palier n**.

Les 8 tours ne sont pas un chiffre de goût : c'est le **centre de la bande de durée
d'une élite** déjà livrée dans `EncounterAnchor::TURN_BANDS` (`elite => [6, 10]`).
La barre est donc définie par *le format d'une rencontre*, jamais par une table.

| Palier | Frappe de l'élite | **Barre** | Un commun, par tour | Un commun, sur 4 tours | Une élite, sur 8 tours |
|---|---|---|---|---|---|
| **1** | 12 | **96** | 4,2 % | 17 % | **100 %** |
| **2** | 26 | **208** | 4,3 % | 17 % | **100 %** |
| **3** | 55 | **440** | 4,3 % | 17 % | **100 %** |
| **4** | 110 | **880** | 4,3 % | 17 % | **100 %** |

### 4.1 Ce que la dérivation retrouve toute seule

Les trois dernières colonnes ne sont pas choisies, elles **tombent** — et elles
tombent sur les nombres que le canon avait mesurés à la main :

- **17 % pour un commun**, à tous les paliers, dans la bande « 16 à 26 % » du
  §9 octies ;
- **100 % pour une élite**, contre les « 102 à 129 % » mesurés — *une élite tue un
  joueur seul* devient une propriété de la définition, pas un réglage ;
- **le rapport ne dépend pas du palier**, parce que les deux membres dérivent de la
  même vie de commun. Monter de palier ne rend donc ni plus ni moins fragile : il
  rend les rencontres **plus longues et plus chères**, ce qui est la promesse du
  bestiaire.

C'est le même critère qu'ARC-17a s'était donné : *une dérivation qui rate sa propre
référence ne dérive rien.*

### 4.2 Et un boss ?

Un boss de palier 2 frappe 41, soit **19,7 % de la barre par tour** : il tue en
5 tours quand sa bande de durée en demande 12 à 20. **Un boss n'est pas un contenu
solo, et la barre le dit sans qu'on ait à l'interdire** — il faut la mitigation
d'un tank, les dépôts d'un guérisseur et un groupe pour tenir la fenêtre. C'est ce
que GAME_DUNGEONS suppose déjà.

### 4.3 Ce qui est figé, et ce qui ne l'est pas

Conformément à GAME_ARCHETYPES §0.2 : **aucun nombre de ce tableau n'est une valeur
de jeu définitive**. Ce qui survivra à la recalibration :

| Ce qui est figé | Pourquoi |
|---|---|
| La barre se **dérive** du gabarit du bestiaire | sinon les deux divergent en silence |
| Une élite prend **une rencontre entière** de barre | c'est la définition, pas une mesure |
| Le rapport **ne dépend pas du palier** | il découle de la dérivation |
| Le Socle est **un palier**, jamais un cumul | sinon 32 arbres |
| La progression verticale n'est **pas un choix** | la leçon du §1.3 |

Ce qui sera recalculé : les 8 tours médians, les quatre valeurs de barre, et le
partage entre Socle, armure et leviers — par **`app:balance:simulate`** (ARC-17), et
jamais à la main.

---

## 5. Décision 4 — Les soins à valeur fixe sont viables, sous une ancre symétrique

**Oui, et c'est déjà le modèle du jeu pour les dégâts.**
`EncounterAnchor::targetDamageFor()` dit *un geste de palier n retire 25 % des PV
d'un commun de palier n* : 8, 18, 38, 75. Des valeurs **fixes, par palier** — la
ligne Brasier → Brasier+ → Méga-Brasier de FF, et exactement ce que la dérivation
de matéria par palier sait déjà produire.

Il manque juste l'**ancre symétrique**, qui n'existe pas :

| Geste | Règle | T1 | T2 | T3 | T4 |
|---|---|---|---|---|---|
| Dégât direct | 25 % de la vie d'un commun *(livré)* | 8 | 18 | 38 | 75 |
| **Soin direct** | **25 % de la barre du palier** | **24** | **52** | **110** | **220** |
| **Dépôt de soin** | **8 % de la barre, par tour** | **8** | **17** | **35** | **70** |

Trois conditions, et une conséquence agréable.

1. **La grille se dérive, elle ne s'écrit pas** — un `MendingAnchor` frère
   d'`EncounterAnchor`, sinon les deux tables divergent au premier ajustement.
2. **L'obsolescence est une fonctionnalité.** Un soin de palier 1 sur une barre de
   palier 4 rend 2,7 % : c'est ce qui donne un sens à la progression de matéria.
   La seule chose à garantir est le **plancher du jour 1** — l'accord d'entrée
   gratuit doit ouvrir un soin de **son** palier, jamais un soin figé au palier 1.
3. **Ce qui est en pourcentage doit le rester.** Le levier `recovery` est déjà en
   `percent_of_max` : c'est le bon modèle, ne pas y toucher.

**La conséquence agréable** : les potions deviennent une échelle de paliers, comme
les outils (OBJ-06). L'alchimiste a alors un produit **à chaque palier** au lieu
d'un seul qui se périme — du contenu économique gratuit pour MET et ECO.

---

## 6. Ce que ça déplace ailleurs

Une barre qui fait ×9 ne se pose pas sans conséquences. Les six trouvées :

| # | Ce qui casse | Pourquoi | Ce qu'il faut |
|---|---|---|---|
| 1 | **`LifeRegenManager`** | 12 secondes **par point**, en absolu : revenir à pleine vie passe de 19 min au palier 1 à **2 h 56** au palier 4, et l'ancre en minutes d'attente de `DailyAnchor` explose | la régénération devient **un pourcentage de la barre** — le temps de retour à plein ne doit pas dépendre du palier |
| 2 | **`Skill::life`** | plat, cumulatif, hors budget, **écrit en dur** dans `Player::maxLife` par `SkillAcquiring` — la même fuite que les échelons de port de l'écart n° 5 | le retirer, et le convertir en levier `life` là où l'intention était un bonus de build |
| 3 | **`Item::protection`** | lu par `EquipmentSetResolver`, affiché sur la fiche d'inventaire, **et par aucune formule de combat** | le brancher comme mitigation (ARC-19) ou le retirer — un chiffre affiché sans effet est un mensonge d'interface |
| 4 | **Les soins et potions à valeur fixe** | calibrés sur une barre de 20 | la grille du §5 |
| 5 | **`SkillRespecManager`** | il défait `maxLife` en soustrayant les bonus plats accumulés — il n'aura plus rien à défaire, mais le **palier** ne doit jamais redescendre en dessous du plancher | le respec rend des points, jamais un palier de vitalité |
| 6 | **Les builds de référence d'ARC-17** | `ReferenceCharacterFactory::maxLifeOf()` lit `PlayerFactory::BASE_LIFE` | il lit la loi, et les cinq seuils du simulateur redeviennent mesurables |

Le point 6 est la raison pour laquelle ce chantier passe **avant** la suite
d'ARC-17 et **avant** ARC-19 : les deux mesurent des dégâts subis sur une barre qui
n'existe pas.

---

## 7. Le déroulé — ce que le joueur en voit

**Jour 1.** Il sort du tunnel avec **96 PV** et aucun arbre. Dans les Vallons, un
commun T1 lui prend 4 PV par tour ; une rencontre de quatre tours lui coûte 17 % de
sa barre, il en enchaîne cinq avant de devoir souffler. Il ne sait pas qu'il a un
« palier de vitalité » — il sait qu'il tient.

**Semaine 2.** Il a ouvert le Pyromancien et acheté deux nœuds de palier 1. Le
**Socle II** s'allume : *« Vous encaissez ce que le second palier envoie. »* Sa
barre passe à 208. Il n'a rien payé — et c'est le moment où les Dunes deviennent
jouables sans que rien ne les ait déverrouillées.

**Semaine 5.** Il hésite entre un troisième nœud de `power` et le premier de
`life`. **C'est un vrai choix** : sa survie de base est acquise, `life` n'achète que
du confort contre du dégât. Un an plus tôt, dans un jeu à taxe de vie, il n'aurait
pas hésité.

**Mois 3.** Il a le Socle IV (880 PV) et une cotte de mailles. Face à une élite T4,
il encaisse 110 par tour, ramenés à 66 par sa plaque : il tient 13 tours au lieu de
8. Il la tue quand même de justesse, **parce qu'il frappe moins vite** — la plaque
lui a rendu exactement le temps qu'elle lui a coûté.

**Ce que son voisin en tissu voit du même combat** : 110 par tour, huit tours, il
meurt. Il ne lui manque pas des points de vie, il lui manque **une raison de rester
au contact** — et c'est très bien ainsi.

---

## 8. Invariants testables

1. **Le Socle ne se cumule jamais.** Un joueur qui a appris le Socle III dans huit
   arbres a le palier 3, pas le palier 24.
2. **Le Socle ne coûte rien** — ni point, ni budget. Un Socle payant est refusé à la
   lecture.
3. **Le Socle ne donne aucun levier, aucun geste, aucun droit de port.**
4. **Tout arbre de combat porte exactement un Socle par palier 1, 2 et 3** — cliquet
   de couverture, comme la liste d'attente d'ARC-02.
5. **Aucun personnage n'a une barre inférieure au plancher**, quel qu'ait été son
   parcours (aucun arbre, respec complet, arbres de métier seulement).
6. **La barre se dérive du bestiaire** : changer la vie d'un commun déplace la barre
   du joueur, et aucun nombre n'est écrit à deux endroits.
7. **Un commun de palier n retire 16 à 26 % de la barre d'un joueur de palier n**, à
   tous les paliers.
8. **Une élite de palier n reste mortelle en solo** : elle retire au moins 60 % de
   sa barre à chacun des builds de référence, et en tue au moins un sur deux.
9. **Le temps de retour à pleine vie ne dépend pas du palier** — l'invariant qui
   protège `DailyAnchor`.
10. **Un soin de palier n rend la même part de barre à tous les paliers** — l'ancre
    symétrique du §5.
11. **Aucune source de PV ne s'indexe sur le nombre d'arbres appris.**
12. **Plus de la moitié de l'écart de PV effectifs entre le build le plus solide et
    le plus fragile vient de l'armure**, jamais de l'arbre — l'invariant qui tient
    la décision 21 du canon.

---

## 9. Ce que ce document ne décide pas

- **Le chiffre de mitigation de chaque ligne d'armure** — il appartient à
  GAME_ITEMS §2.2 (fourchette mesurée : 30 % minimum, 50 % maximum, cible ~40 %) et
  se livre avec ARC-19.
- **Les quatre valeurs de barre** — elles se recalculeront avec le bestiaire, par
  `app:balance:simulate`. Ce qui est figé, c'est la **loi**.
- **La forme visuelle du Socle dans l'arbre** — design d'écran, pas design de
  système. Il doit seulement être **visible** : c'est la moitié de la décision.
- **La mort et la résurrection** — ce qu'on perd en tombant est un autre sujet.
- **Une éventuelle matéria de vitalité** (le *HP Plus* de FF7, qui ferait payer les
  PV en **emplacement** plutôt qu'en points). C'est le plus élégant des modèles
  étudiés et il est dans notre ADN, mais il contredit la dérivation de
  GAME_MATERIA §2.1 — *une matéria, un sort*. À rouvrir seulement si la
  différenciation par l'armure se révèle insuffisante.

---

## Sources

Jeux étudiés pour la question « faire monter une barre de vie sans niveau » :
Old School RuneScape (Hitpoints comme compétence), Ryzom (pools dérivés de la
branche), Albion Online (progression par l'équipement, Destiny Board), EVE Online
(EHP et profils de résistance), Star Wars Galaxies (mitigation par type),
Final Fantasy XIV (modificateur de vie par job), Guild Wars 2 (paliers de vie par
profession), Guild Wars 1 (barre constante), Path of Exile et Dofus (la taxe de
vie), Diablo II (le *vitality build*), Final Fantasy VII (HP Plus, et l'échelle
Soin / Soin+ / Méga-Soin).
