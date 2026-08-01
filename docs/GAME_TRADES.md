# Les métiers — récolte, artisanat, et ce qu'un arbre de métier décide

> **Statut : proposition instruite**, 2026-08-01. Second volet du chantier des
> arbres, annoncé par [GAME_ARCHETYPES.md](GAME_ARCHETYPES.md) §14 : *« l'équivalent
> des fonctions et des leviers pour la récolte et l'artisanat est un second
> chantier, à instruire après celui-ci. »*
>
> En amont, et jamais contredit : **règle 9** (les compétences sont passives),
> [GAME_DOMAINS.md](GAME_DOMAINS.md) §5.2/§5.3 (les gabarits de récolte et
> d'artisanat), [GAME_ZONE_ACTIONS.md](GAME_ZONE_ACTIONS.md) (ce qu'on fait dans
> une zone, l'information du prospecteur), [GAME_PROGRESSION.md](GAME_PROGRESSION.md)
> §1 et §5 (le budget d'énergie), [GAME_PRINCIPLES.md](GAME_PRINCIPLES.md)
> (l'économie joueur), [BALANCE.md](BALANCE.md) §8 et §22.
>
> Décliné en jalons : [roadmap/PLAN_TRADES.md](roadmap/PLAN_TRADES.md).

---

## 0. Le problème que ce document résout

### 0.1 Le constat, mesuré le 2026-08-01

Les douze arbres de métier — 5 récoltes, 7 artisanats — comptent **190 nœuds**.
Voici ce qu'ils contiennent :

| Ce qu'un nœud fait | Nombre | Part |
|---|---:|---:|
| **Débloquer une recette** (`craft`) | 73 | 38 % |
| **Débloquer un filon** (`harvest`) | 56 | 29 % |
| **Débloquer un outil** (`equip.tool`, `tool_slot.unlock`) | 45 | 24 % |
| Choisir une **branche** (`specialization.branch`) | 6 | 3 % |
| **Un bonus de rendement** (`yield`) | **10** | **5 %** |

> **175 nœuds sur 190 sont des portes.** Un arbre de métier ne dit pas *comment on
> travaille* — il dit *à quoi on a droit*, et dans quel ordre. C'est un catalogue,
> pas un arbre.

**Et les cinq autres constats vont dans le même sens :**

1. **Un seul levier existe** — `gather_percent`, plafonné à **+100 %**
   (`ActionYieldResolver::MAX_BONUS_PERCENT`). Il est présent sur **10 nœuds sur
   190**, tous en récolte. **L'artisanat n'a aucun levier** : un forgeron qui a fini
   son arbre fabrique exactement comme un débutant — il fabrique seulement **plus de
   choses**.
2. **Ce levier fait deux choses à la fois.** `PurityDrawer` lit
   `CATEGORY_GATHER` pour décaler le tirage de bande : **la quantité récoltée et la
   qualité récoltée sont le même curseur**. Il n'y a donc aucun arbitrage possible —
   investir dans l'un, c'est investir dans l'autre.
3. **La qualité d'artisanat ne passe pas par l'arbre** : `QualityCalculator` la
   dérive du **niveau de domaine** (2 % par niveau), plus la branche, plus l'atelier
   du foyer. Elle progresse toute seule en pratiquant — ce qui n'est pas un défaut,
   mais qui laisse l'arbre sans rien à décider.
4. **Le renoncement de DOM-04/06 n'a presque aucun contenu** : **6 recettes sur
   398** portent un `required_specialization`. Choisir une branche ne ferme donc
   quasiment rien, et le respec payant (2 500 gils) protège un choix qui n'en est
   pas encore un.
5. **Le gabarit décrit des arbres qui n'ont jamais été écrits.** GAME_DOMAINS §5.2
   promet « rendement & fatigue », « repérage », « pureté » ; §5.3 promet « qualité
   & temps », « économie du geste ». **Zéro nœud** porte une fatigue, un repérage,
   une pureté propre, une réduction de temps ou une économie de matière.

### 0.2 Le statut des chiffres

**Le même qu'en combat**, et il vaut la peine d'être répété :
[GAME_ARCHETYPES §0.2](GAME_ARCHETYPES.md) — aucun nombre de ce document n'est une
valeur de jeu. Les repères y sont calculés sur le budget d'énergie réel
(240 points/jour, récolte à 3, exploration et chasse à 5 — BALANCE §8) et sur les
prix de référence, mais **la recalibration passera par le simulateur** (ARC-17),
étendu aux métiers.

---

## 1. Ce qui change par rapport au combat

Le combat a un adversaire, donc des **tours**. Un métier n'en a pas. Trois
différences en découlent, et elles commandent tout le reste.

### 1.1 Deux ancres, une par famille

| Famille | L'unité de mesure | Ce qu'un levier convertit |
|---|---|---|
| **Récolte** | **le point d'énergie** — 240 par jour, 3 par récolte, soit 80 gestes | ce qu'**un point d'énergie** rapporte, en quantité *et* en qualité |
| **Artisanat** | **l'heure d'atelier** et **l'unité de matière** — le craft ne coûte pas d'énergie, il coûte du **temps réel** et des **intrants** | ce qu'**une heure** et **une unité de matière** rendent |

C'est déjà la doctrine d'`ActionYieldResolver`, écrite dans son en-tête : *« le
budget d'énergie est égalitaire ; le levier de progression n'est donc pas le
**nombre** d'actions mais ce que **chaque** action rapporte. »* Ce document ne fait
que la prolonger — et constater qu'un seul levier l'applique.

### 1.2 Le borneur n'est pas le combat, c'est le marché

C'est la différence qui change tout :

> **En combat, un arbre trop fort déséquilibre un joueur. En métier, un arbre trop
> fort déséquilibre le serveur.**

Un pyromancien à +50 % de dégâts joue mieux, et cela ne concerne que lui. Un
mineur à **+100 % de rendement** — le plafond actuel — fait **baisser le prix du
minerai pour tout le monde**, y compris pour ceux qui n'ont pas l'arbre. Le budget
d'un arbre de métier est donc un **budget d'inflation**, et chaque levier doit être
jugé sur sa conséquence économique avant de l'être sur sa sensation.

### 1.3 La règle qui en découle

> **La progression d'un métier ne doit pas augmenter la quantité produite. Elle
> doit déplacer la composition de ce qu'on produit, et l'information qu'on
> possède.**

Trois familles d'effets, et leur risque économique :

| Ce qu'un levier touche | Effet sur le marché | Verdict |
|---|---|---|
| **La quantité** *(rendement, coût en énergie, temps de craft)* | il **gonfle le stock mondial** — chaque point est de l'offre en plus | à borner sévèrement |
| **La composition** *(pureté, qualité, taille de lot)* | il **déplace** l'offre vers le haut de gamme sans en créer davantage | **sûr, et c'est là que doit vivre la progression** |
| **L'information** *(repérage, lecture de vitalité)* | il ne crée **rien** — il redistribue l'avantage entre joueurs | **le plus sûr, et le plus intéressant** : c'est le métier du prospecteur (GAME_ZONE_ACTIONS) |

Le plafond actuel de **+100 % de rendement** est donc exactement à l'envers : le
seul levier du jeu est celui de la famille la plus inflationniste, et il est
plafonné au doublement.

---

## 2. Le vocabulaire fermé des leviers de métier

Neuf leviers, **cinq pour la récolte, quatre pour l'artisanat**. Même règle
d'admission qu'en combat ([GAME_ARCHETYPES §4](GAME_ARCHETYPES.md)) : *un levier
occupe une place dans la formule qu'aucun autre n'occupe.*

### 2.1 Récolte

| Levier | Effet | 1 point de budget vaut | Plafond / arbre | Risque |
|---|---|---:|---:|---|
| **`yield`** | quantité récoltée | **+1,0 %** | **12 pb** | quantité — **borné bas** |
| **`purity`** | poids de la bande haute dans le tirage | **+1,0 pt** | 18 pb | composition |
| **`stride`** | coût en énergie du geste | **−0,5 %** | 12 pb | quantité *(il agrandit la journée)* |
| **`sight`** | chance de repérer un filon caché, lecture de la vitalité | **+2,0 pt** | 18 pb | information — **le plus sûr** |
| **`care`** | vitalité consommée par la récolte | **−1,2 %** | 15 pb | **anti-inflationniste** : il préserve le filon |

> **`purity` se sépare de `yield`, et c'est la correction la plus importante de ce
> document.** Aujourd'hui `PurityDrawer` lit le bonus de **rendement** pour décaler
> le tirage : un mineur qui investit dans la quantité obtient gratuitement la
> qualité. Deux leviers, deux places dans la formule — sinon il n'y a **rien à
> arbitrer**, et donc pas de build.

### 2.2 Artisanat

| Levier | Effet | 1 point de budget vaut | Plafond / arbre | Risque |
|---|---|---:|---:|---|
| **`finesse`** | chance de monter d'un palier de qualité | **+1,0 pt** | 15 pb | composition |
| **`tempo`** | `craftingTime` | **−1,0 %** | 15 pb | quantité *(le temps est la seule borne du craft)* |
| **`thrift`** | chance de ne pas consommer un intrant | **+0,8 pt** | 12 pb | quantité — **il crée de la matière** |
| **`batch`** | taille de lot par cycle | **+2,0 %** | 12 pb | composition *(il ne change pas le rendement horaire)* |

> **`finesse` ne remplace pas la pratique, elle s'y ajoute.** `QualityCalculator`
> dérive déjà la qualité du niveau de domaine (2 %/niveau) : c'est bien, et ça
> reste. Ce que l'arbre ajoute, c'est le **choix** — un artisan qui a mis son
> budget dans la finesse produit mieux qu'un autre du même niveau qui l'a mis
> dans le temps.

### 2.3 Le budget

**Un arbre de métier vaut 50 points de budget**, comme un arbre de combat, et il
réutilise la même grille — **3 / 6 / 9 / 14** par palier. Deux raisons de ne pas
inventer une seconde échelle : le respec, l'affichage et les tests sont les mêmes ;
et un joueur qui mène un arbre de combat et un arbre de métier doit pouvoir
comparer ce qu'il y gagne.

Ce qui diffère, ce sont les **plafonds par levier**, calés non sur la puissance
mais sur le **risque économique** (colonne de droite des tableaux ci-dessus).
---

## 3. La fourche des métiers

En combat, la règle est écrite ([GAME_ARCHETYPES §9 septies](GAME_ARCHETYPES.md)) :
**toute fourche oppose deux contextes** — une branche jouable seul, une branche
qui sert le groupe. Un métier n'a pas de groupe. L'équivalent existe pourtant, et
il est même plus net :

> **En combat, la fourche oppose le solo et le donjon. En métier, elle oppose
> la journée et la saison.**

### 3.1 Récolte : Extraire / Préserver

C'est **l'axe politique du jeu ramené à l'échelle d'une personne**
([GAME_WORLD](GAME_WORLD.md) §6 : la Concorde, l'axe Extraire/Préserver, la
Fonderie). Jusqu'ici cet axe ne se jouait qu'au niveau du foyer, par une doctrine
d'atelier votée collectivement. La fourche de récolte le rend **individuel et
quotidien**.

| | **Extraire** | **Préserver** |
|---|---|---|
| Ce qu'on optimise | ce que rapporte **la journée** | ce que rapporte **le filon, sur la durée** |
| Leviers | `yield`, `stride` | `purity`, `care` |
| L'accord | **la Percée** — un filon sous 0,33 de vitalité rend davantage | **le Repos** — un filon au-dessus de 0,66 rend une bande de plus |
| Ce qu'on vend | **du volume en bande basse** | **du peu en bande haute** |
| Ce qu'on laisse derrière soi | de la **pâleur**, que quelqu'un paiera | un filon qui rendra encore la semaine prochaine |

**Les dents existent déjà dans le code**, et c'est ce qui rend cette fourche
jouable sans rien inventer :

- `purity.yaml` → `draw.vitality_ceilings` : un filon sous **0,66** de vitalité
  ne rend **plus jamais de parfait**, sous **0,33** plus jamais de pur. La
  vitalité n'est pas un compteur décoratif, c'est un **plafond dur** sur la
  composition.
- `settlements.yaml` → `paleness.rise_per_pressure` **0,08** contre
  `daily_recovery` **0,04** : *abîmer va deux fois plus vite que réparer* — la
  contrainte est écrite dans le validateur lui-même
  (`SettlementDefinitionLoader`, qui refuse une configuration où la reprise
  dépasse la montée).
- `PurityDrawer::dullsPurity()` : au-delà de `dulls_purity_from`, la pâleur pose
  un **second plafond**, plus bas.
- `VeinRestoration` : la trace laissée par l'extracteur **a un prix**, payé au
  trésor du foyer.

D'où la boucle, qui est la raison d'être de la fourche :

> **Extraire gagne la journée. Préserver gagne la saison. Et ce que l'extracteur
> gagne aujourd'hui, quelqu'un le repaiera au trésor demain — y compris lui.**

C'est un dilemme du bien commun, ce qui est exactement l'intention de
GAME_WORLD. Ce n'est pas du PvP : personne n'empêche personne, personne ne perd
un objet. On se dispute un **rythme**, pas un territoire.

### 3.2 Le défaut qui interdit cette fourche aujourd'hui

Mesuré le 2026-08-01, et c'est **bloquant** :

> **La bande de pureté n'a aucune valeur marchande.** Aucun multiplicateur de
> prix, nulle part : ni sur `Item::price`, ni dans la boutique PNJ, ni à l'hôtel
> des ventes. `PurityChain` la **propage** (une chaîne ne vaut pas mieux que son
> maillon le plus trouble) et `CraftOrderManager` **l'exige** sur les commandes —
> la bande a donc une valeur d'**usage**, jamais une valeur d'**échange**.

Conséquence directe : un joueur qui investit dans `purity` produit **moins
d'objets pour le même argent**. `Préserver` n'est pas une branche faible, c'est
une branche **strictement dominée**. Aucun équilibrage de levier ne rattrape ça.

**Décision.** La bande porte un multiplicateur de prix, appliqué au prix de
référence de la matière :

| Bande | Multiplicateur | Ce que ça veut dire |
|---|---:|---|
| Trouble | **×1** | le plancher, ce que produit une journée ordinaire |
| Clair | **×1,8** | |
| Pur | **×3,5** | le seuil où la composition bat la quantité |
| Parfait | **×9** | il éveille une materia (GAME_WORLD §5.4) — son prix doit dire qu'il est un événement |

L'échelle est **super-linéaire** volontairement : elle doit rendre le pur
*intéressant* et le parfait *déraisonnable à ignorer*, sans quoi la bande haute
resterait une curiosité. Le ×9 n'est pas un chiffre de puissance — c'est la
rareté de base (1 % des tirages) traduite en prix.

### 3.3 Artisanat : les branches existent, elles ne décident presque rien

`config/game/craft_branches.yaml` livre **7 métiers × 2 branches**, écrites avec
soin (« le forgeron d'armes *ou* d'armures », « la table de fête *ou* les vivres
de route »). Le respec coûte 2 500 gils. Et pourtant :

> **6 recettes sur 398 portent un `required_specialization`.** Choisir une
> branche ne ferme rien, donc ne signifie rien.

La correction évidente serait d'ajouter des `required_specialization` en masse.
**C'est la mauvaise correction**, et pour une raison économique :

> Une recette fermée par branche est une recette qui **disparaît du serveur** si
> personne ne prend la branche. En combat, un renoncement ne coûte qu'à celui qui
> renonce. En métier, il coûte à **tous les acheteurs**.

**Décision.** La branche d'artisanat ne ferme pas l'accès, elle **déplace la
qualité** :

| | Sur sa moitié | Sur l'autre moitié |
|---|---|---|
| **`finesse`** | **+8 pt** | **−4 pt** |
| **`tempo`** | −8 % | +4 % |
| Recettes | les **6 pièces de signature** existantes restent fermées | tout le reste ouvert |

Un forgeron d'armures fabrique donc encore des épées — moins bien, plus
lentement, et il n'aura jamais la pièce de signature. Le marché reste
approvisionné ; l'identité reste lisible ; et le respec à 2 500 gils protège
enfin quelque chose.

**Les 6 pièces de signature sont conservées telles quelles** : elles sont
exactement ce qu'une branche exclusive doit produire — peu nombreuses,
identifiables, et jamais sur le chemin critique d'un débutant.

---

## 4. Le gabarit revu

GAME_DOMAINS §5.2/§5.3 décrit un gabarit qui n'a jamais été écrit. Le voici,
chiffré, et aligné sur celui du combat (~15 nœuds, 50 points de budget).

### 4.1 Ce qu'un arbre de métier contient

| Type de nœud | Nombre | Ce qu'il fait |
|---|---:|---|
| **Ouverture** | 1 | gratuite. Elle donne **l'outil de palier 1** ([GAME_ITEMS](GAME_ITEMS.md)) et la première porte. C'est le `rung1.free` des arbres de combat |
| **Portes** | **6** | chacune ouvre **un palier du métier** — pas un filon, pas une recette : *tout ce que le palier contient* |
| **Leviers** | **3** portés | 1 dans le **tronc** (18 pb — toujours celui de la famille « information », le plus sûr), 2 dans la **branche** (22 à 25 pb) |
| **Fourche** | 1 (2 branches) | §3. Chaque branche **écrit 32 pb** — 2 leviers plus un **accord** (7 à 10 pb). Tronc + une branche = **exactement 50 pb** ; l'arbre en écrit **82** |
| **Capstone** | 1 | conditionnel, comme en combat |

**Le changement de fond est la porte.** Aujourd'hui, 73 nœuds `craft` ouvrent
398 recettes et 56 nœuds `harvest` ouvrent 55 filons — soit, en moyenne, un nœud
pour **une** ressource côté récolte. C'est un catalogue déguisé en arbre.

> **Une porte ouvre un palier, jamais un objet.** « Le travail du fer » ouvre
> tout ce qui est en fer. Six portes couvrent T0→T4 plus l'exclusivité de zone.

Cela ramène 175 portes à **72** (6 × 12 arbres) et libère la place pour les
leviers — qui n'existent pas aujourd'hui.

### 4.2 Ce que l'outil fait, et ce qu'il ne fait pas

Reprise directe de [GAME_ITEMS](GAME_ITEMS.md) : *la récolte en exige un ; le
palier module le rendement, jamais l'accès ; le palier 1 est gratuit à
l'ouverture de l'arbre.*

Les **45 nœuds d'outil** actuels (24 % de l'arbre) ne sont donc pas des portes :
ce sont des **leviers déguisés**, et mal placés — ils occupent un quart de
l'arbre pour un effet que personne n'arbitre, puisqu'on prend toujours l'outil
supérieur. Ils fusionnent avec les portes de palier : *ouvrir le palier du fer,
c'est pouvoir forger la pioche de fer.*

### 4.3 Ce que le capstone d'un métier peut être

Même règle qu'en combat : **conditionnel**, jamais un pourcentage de plus.
Trois formes admises, une par famille d'effet (§1.3) :

| Forme | Exemple | Famille |
|---|---|---|
| **Le second regard** | quand un filon est au-dessus de 0,66 de vitalité, la bande tirée est **la meilleure de deux tirages** | composition |
| **La main sûre** | quand la recette consomme un intrant en bande **pure ou mieux**, elle ne peut pas descendre d'un palier de qualité | composition |
| **Le relevé** | à l'entrée dans une zone, **la vitalité et la pâleur** de tous ses filons connus sont lues d'un coup | information |

Aucun capstone de métier n'augmente une quantité. C'est la règle §1.3 appliquée
à l'endroit où la tentation est la plus forte.

---

## 5. Les invariants testables

1. **Aucun nœud de métier ne porte une action.** Règle 9 : un arbre de métier
   ouvre, borne ou module — il ne fait jamais un geste.
2. **`yield` et `purity` sont deux leviers distincts**, lus séparément :
   `PurityDrawer` ne lit plus `CATEGORY_GATHER` pour décaler sa bande.
3. **Tronc + une branche = exactement 50 pb**, et **les deux branches réunies en
   écrivent au moins 60** (sinon la fourche ne force aucun choix). Un arbre en
   écrit donc ~82 pour 50 portés.
4. **Aucun levier ne dépasse son plafond** du tableau §2.
5. **La somme des leviers de la famille « quantité »** (`yield`, `stride`,
   `tempo`, `thrift`) **ne dépasse pas 24 pb** dans un arbre — soit **moins de la
   moitié du budget**. C'est la traduction testable de §1.3.
6. **Toute matière du périmètre de pureté a un prix qui dépend de sa bande**, et
   le rapport parfait/trouble est le même pour toutes.
7. **Aucune recette n'est fermée par une branche sans qu'une recette
   équivalente reste ouverte**, sauf pour les pièces de signature explicitement
   listées.
8. **Chaque arbre de métier compte au maximum 8 portes** et au moins 5 leviers.
9. **Aucun capstone de métier n'augmente une quantité produite.**
10. **Le simulateur** (ARC-17, étendu) tient l'écart de chiffre d'affaires
    journalier entre les deux branches d'un même métier **sous 10 %**, et
    l'écart de **composition** au-dessus de 2× sur la part de bande haute.

L'invariant 10 est le seul qui compte vraiment : deux branches doivent gagner
**autant**, en vendant **autre chose**.

---

## 6. Un exemple déroulé — le mineur, une journée

Repères illustratifs (§0.2), calculés sur le budget réel : 240 points d'énergie,
récolte à 3, exploration à 5, 2 unités par récolte, prix de référence 10 gils en
trouble. Les deux mineurs ont fini leur arbre et 50 points de budget.

**Le cycle commun.** Un filon s'épuise ; les deux doivent explorer pour en
trouver un frais. On compte **1 exploration pour 12 récoltes**. Les deux portent
`sight` **18 pb** dans le tronc — c'est ce qui rend leur cadence d'exploration
identique, et donc leur comparaison honnête.

### 6.1 Bréna, Extraire

Branche : `yield` **12 pb** (+12 %) · `stride` **10 pb** (−5 %) · accord
**la Percée** (10 pb).

- Coût d'une récolte : 3 × 0,95 = **2,85**
- Un cycle : 12 × 2,85 + 5 = **39,2** points → **6,12 cycles** → **73 récoltes**
- Unités : 73 × 2 × 1,12 = **164**
- La Percée (+12 % sur filon pressé) : **184 unités**
- Elle travaille des filons pressés → vitalité < 0,33 → **plafond `pur`**, jamais
  de parfait. Poids : 60 / 30 / 10 / 0
- Valeur moyenne : 0,60×10 + 0,30×18 + 0,10×35 = **14,9 gils/unité**
- **Journée : ≈ 2 740 gils**, dont **0 unité parfaite** et 18 unités pures

### 6.2 Iven, Préserver

Branche : `purity` **15 pb** (+15 pts de décalage) · `care` **10 pb** (−12 % de
vitalité consommée) · accord **le Repos** (7 pb).

- Coût d'une récolte : **3**
- Un cycle : 12 × 3 + 5 = **41** points → **5,85 cycles** → **70 récoltes**
- Unités : 70 × 2 = **140**
- Ses filons restent au-dessus de 0,66 → **plafond `parfait`**. Poids décalés de
  +15 : 45 / 30 / 24 / 1
- Valeur moyenne : 0,45×10 + 0,30×18 + 0,24×35 + 0,01×90 = **19,2 gils/unité**
- **Journée : ≈ 2 690 gils**, dont **1,4 unité parfaite** et 34 unités pures

### 6.3 Ce que la comparaison montre

| | Bréna (Extraire) | Iven (Préserver) | Rapport |
|---|---:|---:|---:|
| Unités produites | 184 | 140 | **×1,31** |
| Chiffre d'affaires | 2 740 | 2 690 | **×1,02** |
| Unités en bande **pure ou mieux** | 18 | **35** | **×1,94** |
| Unités **parfaites** | 0 | **1,4** | **∞** |
| Pâleur ajoutée au filon | **+0,08/jour** | ≈ 0 | |

**Trois enseignements.**

1. **Elles gagnent la même chose et ne vendent pas la même marchandise.** Bréna
   alimente le plancher T1 et l'artisanat de masse ; Iven alimente la chaîne
   haute — et elle est **la seule source de parfait**, donc de materia éveillée.
   Ce sont deux marchés, pas deux niveaux de performance.
2. **Ce n'est pas le levier qui fait l'écart, c'est l'accord.** Sans la Percée,
   Bréna finit à **2 440 gils** — soit **−11 %** de sa propre journée, et 9 % de
   moins qu'Iven : elle perd l'égalité. Ses 22 points de pourcentages lui
   rapportent donc moins que la seule condition qui récompense son style.
   **C'est exactement le constat du combat**
   ([GAME_ARCHETYPES §9 bis](GAME_ARCHETYPES.md) : *+9 % sur un levier ne retire
   pas un seul tour*), retrouvé sur un terrain sans tours.
3. **Le troisième joueur est absent du tableau, et c'est lui l'enjeu.** Si Bréna
   et Iven travaillent le **même** filon, la pâleur de Bréna finit par abaisser le
   plafond d'Iven : Iven perd son parfait — ×9 — pour une trace qu'elle n'a pas
   laissée. Elle n'a aucun moyen de l'en empêcher, et c'est voulu : **la seule
   réponse est le trésor du foyer**, qui paie la restauration
   (`VeinRestoration`). La fourche est donc un argument politique avant d'être un
   build, ce qui la branche sur FOY et sur les doctrines d'atelier.

### 6.4 Ce que ça implique pour la Pâleur

Un mineur Extraire ajoute ~0,08 de pâleur par jour à pression 2, contre 0,04
récupérés. Il atteint donc `dulls_purity_from` en quelques jours **s'il est
seul** — et bien plus vite à plusieurs. Le plafond dur de **0,60** garantit qu'il
ne stérilise rien.

> **La conclusion à porter dans FOY** : le coût de restauration d'un filon doit
> se comparer à ce que l'extraction a rapporté. Si restaurer coûte moins qu'une
> journée d'Extraire, personne n'arbitre ; si ça coûte beaucoup plus, le foyer
> laisse pâlir et la fourche meurt d'un côté.

---

## 7. Ce que ce document ne décide pas

- **Les valeurs.** Tous les nombres sont illustratifs (§0.2). La recalibration
  passe par `app:balance:simulate` (ARC-17), **étendu aux métiers** : mêmes
  scénarios, mais l'unité de sortie est le gil par jour et la composition de la
  production, pas le tour de combat.
- **La liste des 6 portes par arbre.** Elle se dérive des paliers de zone
  (GAME_ZONES §2) et des paliers d'objet (GAME_ITEMS), donc après eux.
- **Le prix de restauration d'un filon.** §6.4 pose la contrainte, FOY tranche.
- **Les 12 accords** (2 par arbre de récolte, 2 par arbre d'artisanat). Ce
  document en écrit 2 comme patrons ; les 22 autres suivent la même règle
  d'admission qu'en combat.
- **La qualité d'artisanat côté joueur.** `QualityCalculator` reste la source ;
  `finesse` s'y **ajoute**, il ne la remplace pas. La formule exacte de
  composition est une décision d'implémentation.
- **Le rapport entre métiers.** Un mineur et un pêcheur doivent-ils gagner
  autant par jour ? Le document suppose que oui (ancre commune : le point
  d'énergie) mais ne le vérifie pas — c'est le premier scénario à écrire dans le
  simulateur.
