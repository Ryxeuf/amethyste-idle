# Arbres de domaine — doctrine, gabarits, équipement-build

> **Statut : acté le 2026-07-28.** Source de vérité du chantier des arbres de compétences.
> En amont : [GAME_PRINCIPLES.md](GAME_PRINCIPLES.md) §0 (le principe fondateur),
> [GAME_WORLD.md](GAME_WORLD.md) §2.1 (doctrine matéria — trois verbes) et §2.2 (la roue
> des domaines), [GAME_PROGRESSION.md](GAME_PROGRESSION.md) §3 bis (le build par acte) et
> §7.1 (~16 domaines nourris). Règle absolue n° 9 de CLAUDE.md : les compétences sont
> **passives uniquement** — jamais un sort actif.
> Jalons d'exécution : [roadmap/PLAN_DOMAINS.md](roadmap/PLAN_DOMAINS.md) (DOM-01+).

## 0. L'état des lieux (mesuré le 2026-07-28, remesuré le 2026-07-29)

Le système existe et il est grand : **575 compétences** (551 écrites à la main dans
`src/DataFixtures/Game/SkillFixtures.php`, ~6 000 lignes, plus 24 accords d'hybride
**générés** — DOM-07), les **36 domaines** servis (24 combat + 5 récolte + 7 artisanat),
la **Pyromancie comme domaine modèle** (14 nœuds : 13 écrits + 1 accord dormant).
Échelle de coût 0 → 55 points, nœuds
d'entrée à 0 point (la doctrine « matéria jour 1 » est respectée). Un nœud fait l'une de
trois choses : **accorder une matéria** (`actions.materia.unlock`), donner un **passif**
(`damage`/`heal`/`hit`/`critical`/`life`), **débloquer de l'équipement**.

Le moteur est livré : `SkillAcquiring` (points + prérequis), `SkillRespecManager` (le
respec existe et se compte — `Player.respecCount`), `BuildPresetManager` (les presets
existent), `CrossDomainSkillResolver`, `SynergyCalculator` (synergies inter-domaines),
`CombatSkillResolver` (agrégation des passifs — et le chemin du sort actif par compétence
y est **fermé et commenté**). `Domain.randomSeed`/`graphHeight` sont un héritage de
layout visuel, pas de la génération de contenu.

## 1. La doctrine des trois couches *(validée)*

> **Le savoir n'est jamais borné. Le faire est borné par l'instant. L'être est borné par
> les choix.**

| Couche | Règle | Le borneur |
|---|---|---|
| **Savoir** (les arbres) | Tout geste répété progresse — aucun arbre n'en exclut un autre, jamais | le budget d'énergie (~80 actions/jour, la seule monnaie) |
| **Faire** (l'instant) | On n'exprime qu'une partie de ce qu'on sait | le **build** en combat ; l'atelier et le temps en artisanat |
| **Être** (l'identité) | Certains choix se paient d'un renoncement | la **spécialisation** d'artisanat, le **patronage** de faction, la tension doctrinale |

> **Amendement (2026-07-31, [GAME_ARCHETYPES.md](GAME_ARCHETYPES.md) §11.2) — le plafond
> global de points est supprimé.** `PlayerSkillHelper::MAX_TOTAL_SKILL_POINTS = 500` était
> un verrou de savoir, c'est-à-dire exactement ce que cette section interdit — et il était
> serré au point qu'un seul arbre (465 points) le consommait presque entièrement, rendant
> impossible le « deux à quatre domaines » de GAME_PROGRESSION §1. Les trois borneurs du
> tableau ci-dessus suffisent, et ils sont les bons : l'**énergie** borne le rythme, le
> **build** borne l'expression (DOM-02), la **spécialisation** et le **patronage** bornent
> l'identité. Un plafond de points ne bornait que le **temps de jeu** — la seule chose que
> ce jeu a décidé de ne jamais punir (GAME_PROGRESSION §5). C'est consigné ici pour qu'on
> ne le réintroduise pas par commodité. Jalon : **ARC-10**.

Formule canonique : **« on peut virtuellement savoir tout faire, mais on ne fait qu'une
seule chose à la fois. »** Les seules exclusions du jeu sont sociales et réversibles à
coût (spécialisation, patronage) — jamais des verrous de savoir. Interdire un arbre
serait interdire un geste : contradiction frontale avec le principe fondateur, et retour
des « classes » par la fenêtre.

> **Précision (2026-07-29, [GAME_ONBOARDING.md](GAME_ONBOARDING.md) §6)** — *le champ est
> infini, l'entrée est un acte.* Cette section dit ce qui est **ouvert** à un personnage, pas
> ce qu'il a **appris** : elle interdit qu'un geste soit **fermé**, elle n'affirme pas qu'il
> est **déjà acquis**. Personne ne sait tenir une pioche, forger ou lancer un sort sans
> l'avoir appris — tout le monde sait seulement **que ça existe**, et c'est le rôle du
> catalogue public (les 32 arbres, leur case élément × registre, ce qu'on y apprend, où
> trouver leur parchemin ; jamais les nœuds ni les valeurs).
>
> L'accès à un arbre passe donc par un **parchemin de registre** — acheté à un PNJ de métier
> ou reçu en récompense. Il n'exclut rien : c'est l'acte d'apprendre lui-même, et on peut les
> accumuler tous les 32 et les mener de front. Quatre conditions non négociables le
> garantissent : (1) tout parchemin est accessible à tout le monde, sans prérequis de peuple,
> de faction, de progression ni de choix antérieur ; (2) en posséder un n'en interdit aucun
> autre ; (3) aucun n'est unique ni limité — un PNJ le vend toujours à prix fixe ; (4) aucun
> parchemin payant sur le chemin critique de l'acte I. Le borneur réel de la progression reste
> **l'énergie**, comme ce tableau le dit déjà.
>
> **Portée** : le parchemin ouvre un **métier ou une famille d'arme**, jamais un verbe
> élémentaire du jeu — marcher, voyager, explorer, parler, ramasser et se battre **à mains
> nues** restent libres pour tous, sans condition. Sur les armes, le garde-fou « jamais
> d'interdit de port » (§3) réserve déjà le cas : *seul un prérequis de compétence peut gater
> une pièce*. Le parchemin d'arme est ce prérequis.
>
> **Les arbres retrouvés** (GAME_ONBOARDING §6.4, jalon DOM-10) sont l'exception assumée : des
> arbres **hors catalogue**, ouverts par une rencontre que l'accomplissement déclenche. Ils
> obéissent aux deux lois du Répertoire (GAME_WORLD §12.3) — **latéral jamais vertical**,
> **cumulatif jamais manqué** — plus deux propres : jamais nécessaires, et leur parchemin est
> **lié** (ce qui circule entre joueurs est l'information, pas l'objet).

## 2. La double borne des passifs *(validée)*

Tout passif de combat est borné **deux fois** : par l'**élément** de son domaine et par
son **registre**. Chaque domaine de combat est une case *élément × registre* — le
pyromancien est *feu × sorts* : son « critique +1 % » ne s'applique qu'aux **sorts de
feu**, jamais au CaC ni à un sort d'eau. Sur une action donnée, un seul arbre s'applique
donc pleinement.

**Registres** : sorts / mêlée / distance. **Impact modèle** : le format actuel des
passifs (`damage`, `critical`… plats) doit porter l'élément et le registre — c'est le
refactor central du chantier (**DOM-01 ✅ livré le 2026-07-29** : le registre est porté par
`Domain`, jamais par le nœud ; `CombatSkillResolver` filtre sur la case de l'action).

> **Deux précisions actées à la livraison.** (1) `life` **échappe à la borne** : les points
> de vie maximum ne sont pas un geste, et les borner ferait varier la barre de vie d'un tour
> à l'autre selon le sort choisi. Les quatre autres statistiques qualifient une action et se
> bornent avec elle. (2) **La grille 8 × 3 n'est pas pleine** : trois éléments (feu, air,
> bête) occupent leurs trois cases, l'eau a trois domaines de sorts, le métal deux de mêlée.
> Étiqueter le « Guérisseur » en mêlée pour faire tenir la grille aurait menti sur ce qu'est
> le domaine ; le remplissage est un sujet de contenu, pas de moteur. Les passifs de **récolte** et d'**artisanat**
sont bornés à leur **métier** (le rendement du mineur ne sert pas l'herboriste) ; pas de
registre pour eux.

## 3. L'équipement est le build *(validé)*

Le build (préset livré) n'est pas un menu : c'est **ce qu'on porte**.

- **Les emplacements de matéria sont typés et portés par l'équipement** : emplacement de
  **sort**, de **technique** (arme), ou **libre**. La robe porte des emplacements de sort
  et des bonus de magie ; la plaque, des emplacements de technique et de l'armure ; le
  cuir, l'entre-deux et la distance. **L'arme fixe le registre** des attaques de base (le
  bâton canalise les sorts et frappe mal ; l'épée frappe vraiment).
- **Un domaine n'est actif en combat que si le build porte une de ses sources** — une
  matéria de son élément (écoles de sort), une arme de son registre (écoles d'arme). La
  borne est **matérielle, jamais réglementaire** : une arme + 3-4 emplacements ⇒ 2-3
  domaines actifs, mécaniquement. Changement de build hors combat uniquement.
- **L'auto-limitation est émergente** : le mage en plaque avec son bâton existe — il est
  moyen partout, et c'est son choix. Personne ne lit un « interdit » ; le monde répond à
  ce qu'on porte.

**Trois garde-fous** :

1. **Jamais d'interdit de port** (pas de classes) : tout le monde peut **apprendre** à tout
   porter — la limitation vient des emplacements et des bonus. Seul un prérequis de
   *compétence* peut gater une pièce (règle 9, déjà en place).
   > **Amendement (2026-07-29, [GAME_ONBOARDING.md](GAME_ONBOARDING.md) §6.0 bis)** — ce
   > prérequis de compétence n'est plus l'exception mais **la règle générale** : armes,
   > armures et outils se portent après avoir appris à les porter. Le garde-fou tient parce
   > que ces nœuds sont des **points d'entrée gratuits** (0 point de domaine) des arbres, et
   > que tout arbre s'ouvre avec un parchemin accessible à tous : *le mage en plaque existe
   > toujours*, il a seulement dû l'apprendre. On ne lit jamais un interdit — on lit **ce
   > qu'il manque et où l'apprendre**. Le port s'apprend **par famille d'arme, ligne d'armure
   > ou outil de métier**, sur une **échelle** : échelon 1 gratuit (le port de base, palier
   > T1), échelons suivants **paliés et chaînés** pour les pièces évoluées — *on ne se sert
   > pas d'un arc à poulie sans maîtriser l'arc, ni du marteau de précision sans avoir usé le
   > marteau ordinaire*. Les compétences d'arme déjà en base (`*_weapon_t2` → `t3`) **sont**
   > cette échelle et restent inchangées ; il manque l'échelon 1 et les échelles d'armures et
   > d'outils. Chaque échelon est **partagé** par tous les arbres qui l'enseignent : plusieurs
   > chemins mènent à la hache de guerre.
2. **Le plancher jour 1** : les kits T1 portent au moins un emplacement **libre** — la
   première matéria se sertit toujours, quelle que soit la tenue (GAME_WORLD §2.1).
3. **La progression du support reste actée** : plus d'emplacements, de meilleurs bonus,
   pour **la même** matéria. Le typage ne change rien à ça.

Cohérences gagnées : le **tailleur** (ECO-31) cesse d'être « une armure de plus » — la
ligne tissu est *le support des sorts* ; les bâtons et baguettes du **charpentier**
(ECO-30) sont les canaux de sort. Côté code, c'est une **donnée** (type d'emplacement
par pièce), pas un moteur — 56 items portent déjà `elemental_damage_boost`.

> **DOM-02 et DOM-03 ✅ livrés le 2026-07-29, avec un report explicite.** Seule la ligne
> tissu est typée `spell` (7 pièces, palier 2+) ; plaque → technique et cuir → entre-deux
> sont **reportés** tant qu'aucune matéria de technique n'existe — un test l'interdit
> même activement : un emplacement qui refuserait tout ce qu'on peut lui présenter est
> un mur sans porte, pire qu'un emplacement libre. Par ailleurs, « l'arme fixe le
> registre des attaques de base » n'est **pas encore implémenté** côté attaque de base
> (elle ne lit toujours pas les passifs — voir DOM-01).

## 4. Les caractéristiques du personnage *(état consigné)*

**Il n'y a pas d'attributs primaires** (pas de Force/Intelligence/Dextérité distribuées)
— et c'est une décision, pas un manque : on est ce qu'on *fait*, pas ce qu'on a coché à
la création. Toutes les stats sont **dérivées** de quatre sources : arbres, équipement,
matéria — et bientôt patronage (§6.4 de GAME_WORLD) et nourriture (ECO-29).

| Caractéristique | Où elle vit | Rôle |
|---|---|---|
| Vie `life`/`maxLife` | Player (+ `diedAt`, régén ancrée `lifeUpdatedAt`) | survie ; la régén est un régulateur du pivot |
| Énergie d'action `actionEnergy`/240 | Player | **le budget PBBG** (~80 actions/jour) |
| Énergie de combat `energy`/`maxEnergy` | Player | paie les sorts (les PM, de fait) |
| Précision `hit` | Player + passifs | `FightCalculator::hasAttackHit()` |
| Vitesse `speed` | Player | initiative / fuite |
| Passifs d'arbre `damage`/`heal`/`hit`/`critical`/`life` | Skill → `CombatSkillResolver` | la seule chose qu'un nœud donne en stats |

Couches modificatrices : effets d'équipement (JSON par pièce ; attaque de base gratuite),
bonus de set (`EquipmentSetResolver`), synergies inter-domaines et élémentaires, statuts,
XP de matéria (+25 % si l'élément correspond). Hors combat : `gils`, `renownScore`
(vitrine), `respecCount`, `discoveredRecipes`, `unlockedToolSlots`, `lastActivityAt` +
`actionEnergySpentTotal` (FOY-17a).

## 5. Le gabarit par famille

Trois gabarits, opposables comme les lois de zone. Profondeur cible **~15 nœuds** (le
modèle Pyromancie), échelle 0 → 55 points, **2 nœuds d'entrée à 0 point**. Le test du
principe fondateur s'applique nœud par nœud : *quel geste répété m'a mené ici ?*

### 5.1 Combat (élément × registre)

| Part | Nœuds | Contenu |
|---|---|---|
| **Accords** | ~5 | matéria de l'élément, du palier d'entrée (0 pt) au palier rare ; les accords d'entrée ne coûtent rien, **jamais** |
| **Passifs doublement bornés** | ~6 | dégâts, critique, précision, coût d'énergie de combat — *élément × registre* uniquement |
| **Déblocages d'équipement** | ~2 | prérequis de port des pièces hautes de son registre |
| **Accord d'hybride dormant** | 1 | voir §8 |
| **Capstone** | 1 | un passif signature du domaine (jamais un sort) |

### 5.2 Récolte (par métier)

| Part | Nœuds | Contenu |
|---|---|---|
| **Rendement & fatigue** | ~4 | mieux récolter, se fatiguer moins (GAME_ZONE_ACTIONS : la fatigue module le rendement, jamais l'accès) |
| **Repérage** | ~3 | découverte des filons cachés de son métier, lecture de la vitalité — l'information du prospecteur (GAME_ZONE_ACTIONS) |
| **Pureté** | ~3 | la compétence du récolteur est un facteur du tirage de bande (ECO-22) — ligne du cristal seulement (mineur) ; les autres métiers portent des passifs de qualité équivalents |
| **Outils** | ~2 | `unlockedToolSlots`, paliers d'outils |
| **Déblocages de palier** | ~3 | accès aux filons T3/T4 par gate de compétence (la rareté se règle par le palier et le gate, jamais en étranglant la capacité) |

### 5.3 Artisanat (par métier)

| Part | Nœuds | Contenu |
|---|---|---|
| **Qualité & temps** | ~4 | chance de qualité supérieure, réduction de `craftingTime` — bornées au métier |
| **Paliers de recettes** | ~4 | `requiredLevel` des recettes hautes |
| **Économie du geste** | ~2 | rendement de matière (moins d'intrants perdus), taille de lot |
| **Spécialisation terminale** | ~3 | voir §6 — la branche exclusive |
| **Capstone** | 1 | le geste de maître (signature d'artisan sur l'objet, lien réputation d'artisan) |

## 6. Les spécialisations d'artisanat

**Une branche terminale exclusive par arbre d'artisanat** — le forgeron d'armes *ou*
d'armures, l'alchimiste des remèdes *ou* des toxines. Exclusive **au sein de l'arbre**
(on ne prend qu'une branche), changeable par **respec coûteux** (le seul respec payant du
jeu — le respec de points ordinaire reste doux). Aucune exclusivité *entre* arbres
(doctrine §1).

**Impact modèle — migré (DOM-04 ✅, 2026-07-29)** : `Player.craftSpecialization` était au
**singulier** (une pour tout le personnage) ; la migration a livré
`PlayerCraftSpecialization` — une spécialisation **par arbre**, l'unicité portée par le
schéma, changeable par le respec payant (`Recipe.requiredSpecialization` la consomme).
La colonne héritée subsiste, mais le jeu ne la lit plus. C'est le nœud « on compte sur
moi » de l'Acte III : *le* forgeron d'armes de la région est une personne, pas une case.

## 7. Les quatre arbres neufs

Déclinés des gabarits — les métiers n'ayant **pas de matéria** (règle 9 : les sorts sont
le combat), leurs arbres sont passifs + déblocages + spécialisation, ce qui est conforme.

| Arbre | Gabarit | Spécialisation terminale | Nœuds signatures |
|---|---|---|---|
| **Bûcheron** (récolte) | §5.2 | — | repérage des essences exclusives (chêne murmurant, bois tourbé, pétrifié) ; gate T3/T4 |
| **Cuisinier** | §5.3 | table de fête *ou* vivres de route | durée des effets de nourriture ; lots de voyage |
| **Charpentier** | §5.3 | armes de trait *ou* mobilier | canaux de sort (qualité des bâtons) ; flèches en lot |
| **Tailleur** | §5.3 | robes de sort *ou* tenues de travail | emplacements de sort de qualité ; doublures (confort de récolte) |

> **DOM-05 et DOM-06 ✅ livrés le 2026-07-29** : les quatre arbres sont au gabarit
> (15 nœuds, deux entrées à 0 point), la hache du bûcheron existe, et la branche
> terminale est devenue une porte d'arbre (motif de refus `other_branch`).

## 8. L'accord d'hybride dormant

Chaque arbre de combat porte **un nœud d'accord réservé**, inactif au lancement, qui
s'activera quand la **fusion** ouvrira (extension — GAME_WORLD §2.1/§2.2) : l'accord de
l'hybride dont son élément est parent (le pyromancien pourra accorder Magma ou Inferno).
Poser le nœud maintenant coûte une ligne de données et évite un refactor d'arbre le jour
venu ; l'enum `Element` devra tolérer les éléments composés — **pas encore fait dans
l'enum** (contrairement à ce qu'une version antérieure de ce paragraphe affirmait) :
l'arbitrage est porté par **DOM-09** (PLAN_DOMAINS). Les **gestes
retrouvés** du Répertoire, eux, n'exigent pas de nouveau nœud : un geste retrouvé produit
une matéria du **catalogue standard** — l'accord existant suffit.

> **DOM-07 ✅ livré le 2026-07-29** : les 24 accords dormants sont posés, un par arbre de
> combat, **générés** depuis une table déclarative. L'hybride n'est pas nommé — le nœud
> déclare son élément parent, la seule chose que la doctrine fixe.

## 9. Ce que ce document ne décide pas

- **Aucune valeur d'équilibrage** (pourcentages des passifs, coûts exacts des nœuds) —
  BALANCE au moment des jalons.
- **Le détail nœud par nœud des 36 arbres** : les gabarits sont la loi, les fixtures
  sont l'exécution (DOM-05/06 pour les neufs ; la mise en conformité des 32 existants est
  progressive, domaine fréquenté d'abord — §7.1 de GAME_PROGRESSION).
- **La forme visuelle** de l'écran d'arbre (le `graphHeight` hérité) — design UI, pas
  design système.
