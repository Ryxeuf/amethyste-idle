# Le compte, le personnage, l'arrivée — cadrage de l'entrée dans le jeu

> **Statut : proposé** · 2026-07-29 (révisé le même jour — cf. §10, révision R1)
> Source de vérité de **tout ce qui se passe avant qu'un joueur soit un joueur** : la
> création du compte, la connexion, la création du personnage, les dix premières minutes
> et l'apprentissage de l'interface.
> Complète (ne remplace pas) : [GAME_PROGRESSION.md](GAME_PROGRESSION.md) §3 « Acte I »,
> [GAME_DOMAINS.md](GAME_DOMAINS.md) (la doctrine des trois couches, dont ce document est
> une application directe), [GAME_WORLD.md](GAME_WORLD.md) §13.1 (**amendé ici**, cf. §4.4),
> §1 (loi de nommage), [GAME_ZONE_ACTIONS.md](GAME_ZONE_ACTIONS.md) (l'écran où le joueur
> atterrit), [GAME_DASHBOARD.md](GAME_DASHBOARD.md) (le hub, qui n'est **pas** le premier
> écran).
> Les règles absolues de [CLAUDE.md](../CLAUDE.md) priment sur tout ce qui suit.
> Décliné en jalons dans [roadmap/PLAN_ONBOARDING.md](roadmap/PLAN_ONBOARDING.md).

---

## 0. L'idée en une page

Le jeu a un monde, une économie, une narration, 32 arbres de domaine et quinze sprints de
systèmes livrés. **Il n'a pas de porte d'entrée.** La route `/register` lève un 404 : à ce
jour, aucun joueur ne peut créer un compte autrement qu'en passant par les fixtures.

Ce document ferme ce trou, et il le ferme avec une thèse : dans un PBBG, **l'entrée n'est pas
un formulaire, c'est le premier acte de jeu**. Chaque champ demandé avant la première action
est un joueur perdu ; chaque décision demandée avant qu'il ait compris le jeu est une décision
qu'il prendra mal, et regrettera.

Quatre principes en découlent, et tout le reste s'y ramène.

> **P1 — On joue d'abord, on prouve ensuite.** L'e-mail est demandé à l'inscription mais sa
> vérification est **différée** : elle ne barre pas le jeu, elle barre l'entrée dans
> l'économie et le social. On ne perd personne dans une boîte mail, et les faux comptes
> n'atteignent jamais le marché, les guildes ni les foyers.

> **P2 — Le tunnel est une scène, pas un formulaire.** Compte, nom, peuple, visage :
> quatre pas continus, portés par une seule fiction — l'éveil au Fanal. Le joueur ne doit
> jamais sentir qu'il vient de franchir deux systèmes techniques distincts.

> **P3 — Aucune décision de build ne se prend dans le tunnel.** Améthyste n'a ni classe, ni
> attributs, ni orientation de départ. Le tunnel ne demande donc que **qui vous êtes**,
> jamais **ce que vous ferez**. Les vrais choix — le métier, l'élément, la destination — se
> prennent pendant l'acte I, une fois que les mots veulent dire quelque chose.

> **P4 — Le tutoriel enseigne les verbes ; le joueur choisit les compléments.** Chaque pan
> du jeu est traversé une fois, et à chaque fois le *quoi* appartient au joueur : quelle
> récolte, quel élément, quelle destination.

Et une contrainte de forme qui vaut décision : **le premier écran après la création n'est pas
le hub, c'est la zone.** Le hub est un tableau de bord (GAME_DASHBOARD) ; le tableau de bord
d'un joueur qui vient de naître est vide. On n'ouvre pas un jeu sur un écran vide.

---

## 1. Garde-fous — ce qu'on n'invente pas

| # | Contrainte | Origine | Ce qu'elle interdit ici |
|---|---|---|---|
| E1 | **Pas de niveau global** | CLAUDE.md §6 | Afficher « niveau 1 » à la création, une jauge de puissance, un « niveau recommandé » dans le tutoriel |
| E2 | **Pas de classe** | CLAUDE.md §9/§10 | Demander un métier, une classe ou une spécialisation dans le tunnel — **y compris de façon déguisée**, par un kit, une destination imposée ou un filtre d'écran |
| E3 | **Pas d'attributs primaires** | GAME_DOMAINS §2 | Une répartition de points à la création |
| E4 | **Point de réveil unique** | GAME_WORLD §13.1 | Choisir sa zone de départ |
| E5 | **Sorts = matéria uniquement** | CLAUDE.md §10 | Offrir un sort à la création autrement que par une matéria + son accord |
| E6 | **Pas de PvP** | CLAUDE.md §11 | Tout apprentissage du duel, de l'arène, du vol |
| E7 | **1 personnage par compte** (configurable) | CLAUDE.md §12 | Un tunnel qui suppose plusieurs personnages, ou un mur sans issue |
| E8 | **Loi de nommage** | GAME_WORLD §1 | « Village de Lumière ». Le lieu du réveil est **le Fanal**, le cristal est sous **la Voûte**, le personnage est un **Limpide** |
| E9 | **Le budget d'énergie est égalitaire** | GAME_ZONE_ACTIONS G2 | Un kit ou un bonus de départ qui donne plus d'actions par jour qu'un autre joueur |
| E10 | **Le savoir n'est jamais borné** | GAME_DOMAINS (doctrine des trois couches) | Cacher un domaine, une recette ou une possibilité au motif que le joueur « n'en est pas là ». On borne le **faire**, jamais le **savoir** |

**Corollaire de E2 et E10, central pour ce document** : Améthyste n'a pas de classe, et c'est
sa force à l'entrée — il n'y a **aucune décision irréversible de gameplay** à prendre dans le
tunnel. Le seul choix définitif est le **nom**. Tout le reste s'apprend, se cumule et se
répare. Le tunnel doit le dire, parce qu'un joueur qui craint de se tromper lit trois fois
chaque option et abandonne une fois sur cinq.

---

## 2. L'existant, sans complaisance

État constaté dans le code au 2026-07-29. Douze dettes, dont trois bloquantes.

| # | Dette | Gravité | Constat |
|---|---|---|---|
| **D1** | **Aucune inscription** | 🔴 bloquante | `RegistrationController::__invoke()` lève `NotFoundHttpException`. Aucun compte ne peut naître |
| **D2** | **Aucune récupération de compte** | 🔴 bloquante | Pas de `symfony/mailer` dans `composer.json`. Un joueur qui perd son mot de passe perd son personnage **définitivement** |
| **D3** | **Login sans protection** | 🔴 bloquante | Aucun `login_throttling`, aucun limiteur. `User::isBanned` existe et **n'est lu nulle part** |
| **D4** | **Arc d'introduction cassé par le pivot** | 🟠 forte | Trois des sept quêtes d'`intro` valident un `explore` sur `map_id => 1` + coordonnées. Post-ZON-21, `PlayerQuestUpdater::updateExplored()` résout par **zone** et ne se déclenche qu'**au voyage** — donc jamais pour un joueur qui n'a pas bougé |
| **D5** | **Deux populations de PNJ au même hub** | 🟠 forte | `PnjFixtures` (Gérard le Forgeron, Marie la Herboriste, Claire la Sage) et `VillageHubPnjFixtures` (Aldric le Forgeron, Iris l'Alchimiste…) coexistent. Le nouveau venu rencontre **deux forgerons** |
| **D6** | **Le tutoriel commence par le voyage** | 🟠 forte | `TutorialStep::Movement` est la première étape, et le voyage est **time-gaté en temps réel**. Le premier geste demandé est celui qui fait attendre |
| **D7** | **Deux états d'onboarding concurrents** | 🟡 moyenne | 5 `TutorialStep` d'un côté, 7 quêtes d'arc `intro` de l'autre, sans correspondance |
| **D8** | **La création n'a ni fiction ni conséquence** | 🟡 moyenne | Formulaire administratif. Le **foyer d'attache** promis par GAME_WORLD §13.1 n'existe **nulle part** dans le code |
| **D9** | **Unicité du nom naïve et tardive** | 🟡 moyenne | `findOneBy(['name' => $name])` : sensible à la casse, aveugle aux homoglyphes. Et l'erreur ne tombe qu'**après** que le joueur a tout rempli |
| **D10** | **Aucun apprentissage d'interface** | 🟡 moyenne | Un bandeau d'objectif en bas du hub, et rien d'autre |
| **D11** | **La zone de départ ne permet ni de choisir sa récolte ni de combattre** | 🟠 forte | Le Fanal n'expose que **deux filons, tous deux d'herboristerie** (thym, lavande). Et il est `safe: true` → `ExploreService` force `mob: 0` : **aucun combat n'y est possible**. Un acte I « au choix » y est matériellement impossible ; tout le monde devient herboriste faute d'alternative |
| **D12** | **Les peuples ne sont pas équilibrés — et l'Humain est strictement dominé** | 🟡 moyenne | Humain `0/0/0/0` ; Nain `+5 vie, +5 énergie, −1 vitesse` ; Orc `+8 vie, −3 précision` ; Elfe `+2 vitesse, +3 précision`. L'Humain n'a **aucun avantage**, seulement l'absence de malus. Et +8 vie sur une base de 20, c'est **+40 % de points de vie** — un arbitrage de puissance majeur demandé au pas 3 |

Trois observations de plus, qui ne sont pas des dettes mais des pièges :

- **`Race` n'est lue nulle part** hors de la création (sprite par défaut, modificateurs). Elle
  ne gate aucun contenu aujourd'hui, et §4.5 recommande que cela reste vrai.
- **Deux notions d'énergie cohabitent.** `PlayerFactory` initialise `energy`/`maxEnergy` à
  80/100 (stat héritée) ; l'énergie d'action du pivot est `DEFAULT_MAX_ACTION_ENERGY = 240`,
  **pleine à la création**. Tout texte d'onboarding doit citer la seconde, jamais la première.
- **L'écran de login est hors design system.** C'est le premier écran du jeu, et le seul qui
  ne ressemble pas au jeu.

---

## 3. Le compte

### 3.1 L'inscription

**Trois champs, pas quatre.** E-mail, mot de passe, acceptation des règles. Pas de pseudo de
compte : `User::username` reste nullable et inutilisé — un identifiant de plus serait un champ
à remplir pour rien et une seconde identité à modérer. **Le nom du personnage est la seule
identité publique.**

Pas de confirmation de mot de passe : un bouton « afficher » remplace avantageusement la
double saisie, et la récupération par e-mail existe (§3.4) pour rattraper une faute de frappe.

**Le compte naît non vérifié et pleinement jouable.**

### 3.2 La porte de vérification

> **Tranché** : la vérification d'e-mail ne barre pas le jeu, elle barre **tout ce qui sort
> du joueur vers les autres**.

| Ouvert sans vérification | Fermé jusqu'à vérification |
|---|---|
| Explorer, chasser, récolter, combattre | Chat de zone et chat global |
| L'arc d'introduction **en entier** | Hôtel des ventes (achat **et** vente) |
| Arbres de domaine, matéria, artisanat | Échoppe joueur, commerce direct, don d'objet |
| Quêtes, quotidiennes, bestiaire, Codex | Rejoindre ou fonder une guilde, groupe, donjon |
| Voyage, expéditions, boutiques PNJ | Messages privés, amis |
| Le journal, le hub, la carte du monde | **Livraison d'une commission à un foyer** |

La dernière ligne est la plus importante et la moins évidente : une livraison de commission
dépose du **sédiment** dans un foyer (PLAN_RETENTION S1, PLAN_SETTLEMENTS). C'est donc un
vecteur direct de multicomptage sur le pilier territorial — et la Crue indexe le quota de
grandes cités sur la population **active**. Une ferme de comptes non vérifiés ne doit pas
pouvoir faire monter une ville.

Trois conséquences de conception :

1. **La porte tombe au bon moment toute seule.** L'arc d'introduction se clôt sur « L'appel
   des guildes » : le joueur atteint la porte exactement quand la fiction le pousse vers les
   autres.
2. **Le blocage se lit comme une porte, pas comme une panne.** L'écran dit ce qui est
   verrouillé, pourquoi, et propose « renvoyer le lien ».
3. **Aucun blocage rétroactif.** Ce qui a été gagné avant vérification reste acquis.

Relance : une ligne discrète dans le hub (jamais une modale), plus un rappel e-mail à J+1 et
J+3. Après quoi, silence.

### 3.3 La connexion

| Point | État | Cible |
|---|---|---|
| Bruteforce | aucun | `login_throttling` : 5 essais / 15 min par identifiant, 30 / 15 min par IP |
| Compte banni | `isBanned` jamais lu | Refus au login avec un message clair (jamais un 500, jamais un accès partiel) |
| Divulgation | — | Message d'erreur **unique** : « Identifiants invalides. » |
| « Rester connecté » | 7 jours | **30 jours.** Un PBBG se joue tous les jours depuis le même appareil |
| Après login | toujours `/game` | **Selon l'état** : aucun personnage → tunnel ; un personnage → zone si l'acte I est en cours, hub sinon ; plusieurs → sélection |

Tant que l'arc `intro` n'est pas clos, **la zone est la maison** : envoyer un joueur en plein
acte I sur le hub, c'est lui montrer un tableau de bord vide au lieu de l'endroit où il jouait.

### 3.4 Le mot de passe oublié

C'est **D2**, la dette la plus dangereuse du document : aujourd'hui, perdre son mot de passe,
c'est perdre son personnage, son inventaire, sa guilde et sa place dans un foyer.

Exigences : `symfony/mailer` installé (via Docker, règle 1) ; jeton à usage unique, **1
heure**, un seul actif par compte ; **réponse identique** que le compte existe ou non ;
limiteur de débit ; invalidation de toutes les sessions à la réinitialisation.

### 3.5 Ce qu'on ne fait pas

Pas d'**OAuth** au lancement (dépendance externe sur la porte d'entrée, pour un gain qui
n'existe que si le trafic existe). Pas de **compte invité** (personnages orphelins, purge à
écrire, et une question insoluble : que devient un invité qui a fait monter un foyer ?). Pas
de **captcha** tant que le limiteur de débit suffit — un captcha est une taxe sur les honnêtes.

---

## 4. La création de personnage

### 4.1 Ce que le tunnel ne demande pas

Pas de classe (E2), pas de zone de départ (E4), pas de stats à répartir (E3), pas de sexe en
champ séparé — il est porté par le corps choisi. **Et pas de métier, pas d'élément, pas de
destination** : ce sont les vrais choix du jeu, ils appartiennent à l'acte I (§5).

### 4.2 Le tunnel en quatre pas

Un pas = un écran, **une** décision, une phrase de fiction. Barre de progression, retour
arrière partout, rien de perdu.

**Pas 1 — Le compte.** E-mail, mot de passe. *« On ne se souvient de personne avant le
Fanal. »*

**Pas 2 — Le nom.** 3 à 16 caractères, lettres, espaces et tirets. Deux corrections à
`CharacterCreateType` :
- **vérification d'unicité au fil de la frappe**, parce que découvrir que « Aldric » est pris
  **après** avoir tout rempli est le point d'abandon le plus prévisible du tunnel ;
- unicité **insensible à la casse et aux homoglyphes** : `Aldric`, `aldric` et `Аldric` (А
  cyrillique) sont le même nom, sans quoi l'usurpation est ouverte dans un jeu où le nom est
  l'identité économique.

Un bouton **« proposer un nom »** tirant dans une table par peuple.

**Pas 3 — Le peuple.** Qui vous êtes, d'où vous venez. **Rien d'autre** — voir §4.5.

**Pas 4 — Le visage.** Corps, coiffure, couleur, tenue, avec aperçu vivant. Mention explicite :
*« Tout ceci se change quand vous voulez. »* C'est vrai (`/game/character/customize` existe),
et le dire accélère le pas le plus long du tunnel en supprimant l'angoisse du choix.

### 4.3 Réversible ou non — à afficher, honnêtement

| Choix | Réversible ? | Pourquoi |
|---|---|---|
| Apparence | **Oui**, à tout moment | Aucune conséquence de jeu |
| Nom | Non | Identité publique : marché, guilde, journal de monde |
| Peuple | Non — **et sans conséquence de puissance** | §4.5 |

Un tunnel qui dit clairement ce qui engage est un tunnel qu'on traverse vite. Ici, la liste
des engagements tient en une ligne : **le nom**.

### 4.4 Le foyer d'attache — il ne se choisit pas, il se gagne

> **Amendement à GAME_WORLD §13.1** *(proposé le 2026-07-29)*. Le canon dit : « la race donne
> un foyer d'attache — qui détermine sa première destination, son kit, sa réputation de départ
> et sa première chaîne de quêtes ». **Ce document conserve l'intention et change le
> mécanisme.**

L'intention de §13.1 est double, et elle reste entièrement valable :
1. **ne pas éparpiller la population** au lancement — un monde vide est le vrai danger, et
   l'éparpillement combat le pilier des foyers, qui a besoin de concentration ;
2. **différencier par la destination, pas par l'origine**, pour que le joueur se sente distinct.

Le problème n'est pas là. Il est dans le fait de **dériver le foyer de la race** : cela
revient à demander au pas 3, avant toute expérience de jeu, une orientation de carrière — un
kit, une première destination, une chaîne de quêtes. C'est une classe déguisée, donc une
violation de E2. Et concrètement : un joueur qui choisit Elfe parce qu'il aime les elfes se
retrouve poussé vers l'herboristerie, qu'il n'a pas demandée.

> **Le foyer d'attache est la zone où le joueur a réellement travaillé pendant l'acte I.**
> Il ne l'a pas choisi dans un menu : le jeu le constate à la clôture de l'acte, et le lui
> annonce. *« Les mineurs des Mines profondes ont remarqué votre travail. »*

Ce que ça règle, en une décision :

| | Foyer dérivé de la race (canon) | Foyer gagné par les gestes (ici) |
|---|---|---|
| L'Elfe qui veut miner | poussé ailleurs | adopté par les mineurs |
| Cohérence avec le pilier des foyers | l'origine fait le lieu | **l'activité fait le lieu** — exactement la loi des foyers |
| Décision demandée avant de comprendre | oui | non |
| Concentration de la population | tenue | **tenue de la même façon** : le réveil reste unique, le foyer n'est qu'une adresse constatée après coup |
| Sentiment d'être distinct | à la création | **à la sortie de l'acte I**, et mérité |

**Ce que le foyer d'attache apporte, une fois gagné** — quatre choses, toutes latérales :
- **une lettre**, qui suggère une première destination et donne une raison d'y aller ;
- **un PNJ qui vous connaît** au foyer, porte d'entrée des quêtes de zone ;
- **un cran de réputation** chez la faction correspondante (dans le respect de la règle de
  faction portée unique, FAC-01) ;
- **une ligne au journal**.

**Ce qu'il n'apporte pas, et ne doit jamais apporter** : aucun contenu ouvert, aucun contenu
fermé, aucun arbre mis en avant, aucun bonus de rendement. Il **enregistre** une orientation
que le joueur a déjà prise par ses gestes ; il ne l'oriente pas.

**Et le joueur qui n'a rien fait de distinctif ?** Il reste attaché **au Fanal**. Ce qui est
exactement le foyer d'attache que le canon prévoyait pour l'Humain — « libre, le hub est sa
maison ». Le cas humain devient le **cas par défaut de tout le monde**, au lieu d'être une
exception de race. Une simplification, pas une perte.

*(Ce que §13.1 prévoit par ailleurs — l'éparpillement des points de réveil indexé sur la
population quand le serveur grossit — est inchangé et hors scope.)*

### 4.5 Ce qu'apporte le peuple

**État réel** : `Race` porte un sprite par défaut (que le joueur écrase au pas 4) et des
modificateurs de statistiques. Elle n'est lue **nulle part ailleurs** dans le code : elle ne
gate aucun contenu. Et les modificateurs ne sont pas équilibrés — l'Humain est strictement
dominé (**D12**).

Le problème de fond n'est pas l'équilibrage : c'est que **+8 points de vie sur une base de 20,
c'est +40 % de survie, demandés au pas 3**, c'est-à-dire au moment où le joueur en sait le
moins. Dans un jeu qui a explicitement renoncé aux classes (E2) et aux attributs primaires
(E3), c'est le seul endroit où survit une décision de puissance prise à l'aveugle.

> **Recommandation — à confirmer** : **le peuple ne porte aucune statistique.** Il porte
> l'apparence de départ (modifiable), et de la **reconnaissance sociale** : des PNJ qui vous
> saluent différemment, des noms proposés, une entrée de Codex, quelques répliques. Du texte,
> jamais de la puissance.

Ce n'est pas un appauvrissement, c'est ce qui rend le choix **gratuit** — donc pris pour de
bonnes raisons. On choisit Orc parce qu'on veut être un Orc, pas parce que +8 points de vie.

**L'alternative, si une différence mécanique est souhaitée** : un **trait qualitatif**, jamais
quantitatif — le Nain distingue une bande de pureté de plus sur un filon, l'Elfe repère une
plante de plus au reperage. De l'**information**, jamais du débit ni des dégâts (garde-fou
G2 : le budget d'énergie est égalitaire). C'est jouable, mais c'est un arbitrage de conception
à poser sciemment, pas un héritage à conserver par inertie.

**Dans les deux cas, une règle tient** : le peuple ne détermine ni le métier, ni l'élément, ni
la destination, ni les arbres visibles.

### 4.6 La limite d'un personnage

`app.max_players_per_user: 1`. L'écran de limite atteinte doit dire **quoi faire** — règle des
états vides du design system : « Un seul Limpide par compte pour l'instant », plus un lien
vers le personnage existant.

Le second personnage rejoue l'arc d'introduction intégralement (règle 12) : rien à changer.
Le coach par écran (§6) est **par personnage** et se rejoue aussi — c'est voulu, un second
personnage explore d'autres gestes, donc d'autres écrans.

---

## 5. L'acte I — les dix premières minutes, puis la première semaine

### 5.1 Le principe

> **Le tutoriel enseigne les verbes ; le joueur choisit les compléments.** (P4)

Chaque pan du jeu est traversé **une fois**, et à chaque fois le *quoi* appartient au joueur.
Le tutoriel ne dit jamais « deviens herboriste » : il dit « récolte quelque chose », et pose
devant le joueur quatre ou cinq choses différentes à récolter.

Le joueur sort du tunnel avec 240 points d'énergie — **une journée entière**. Il n'a aucun
budget à ménager, et l'onboarding ne doit pas le lui faire croire. À l'inverse, il ne doit
jamais attendre : **rien de time-gaté avant la dernière étape**.

Un écran d'éveil, un paragraphe, un seul bouton (« Ouvrir les yeux ») → **l'écran de zone**.

### 5.2 Les neuf gestes

| # | Pan du jeu | Le geste imposé | **Le choix laissé au joueur** |
|---|---|---|---|
| 1 | **Récolte** | Récolter une fois (⚡3) | **Quel métier.** Quatre ou cinq filons de professions différentes sont posés côte à côte. Le métier qui démarre est celui qu'il a touché |
| 2 | **Exploration** | Explorer une fois (⚡5) | Ce qu'il fait de ce qu'il trouve |
| 3 | **Combat** | Gagner un combat à l'arme | Son arme, et quand il y va |
| 4 | **Butin & sac** | Ramasser, équiper | Quoi équiper |
| 5 | **Matéria** | La choisir, l'accorder, la sertir, **la lancer** | **Son élément.** Huit matérias de départ, une par élément. C'est le seul moment où le jeu demande « qui es-tu ? » — et il arrive **après** un combat, quand le mot « feu » veut dire quelque chose |
| 6 | **Arbres** | Dépenser son premier point | **Dans lequel** des arbres qu'il a ouverts par ses gestes |
| 7 | **Artisanat** | Fabriquer un objet | Quoi fabriquer |
| 8 | **Voyage** | Partir du Fanal | **Où.** Trois destinations proposées, aucune imposée. C'est la première fois qu'il attend, et on le lui dit |
| 9 | **Expédition** | Lancer une expédition avant de partir | Laquelle, et pour combien de temps |

Trois remarques, dans l'ordre d'importance :

**L'étape 5 est le cœur du jeu, et elle manque aujourd'hui.** Le tutoriel actuel ne mentionne
jamais la matéria — c'est-à-dire la seule source d'actions de combat (règle 10) et le build du
personnage (GAME_PROGRESSION §3 bis). Un joueur qui termine l'acte I sans avoir serti une
matéria n'a pas rencontré Améthyste. Et le choix d'élément **n'engage à rien** : la matéria est
abondante à la base (GAME_WORLD §2.1), on en trouve d'autres, on en portera plusieurs. Le
tunnel doit le dire, sans quoi le joueur le lira comme un choix de classe et hésitera dix
minutes.

**L'étape 9 clôt l'acte I sur la leçon la plus PBBG qui soit : comment quitter le jeu en le
laissant travailler.** C'est la dernière chose qu'on apprend au jour 1, et c'est précisément
ce qui fait revenir au jour 2. Aucun autre geste du jeu ne dit aussi bien ce qu'est le genre.

**Le voyage passe de la première à la huitième place** (**D6**). Il reste enseigné, mais à sa
vraie place : comme le geste qui coûte du **temps réel**, et donc comme la porte de sortie de
l'acte I — pas comme son entrée.

### 5.3 Ce que le périmètre de l'acte I doit contenir (contrainte de contenu)

C'est **D11**, et c'est la condition matérielle de tout ce qui précède : aujourd'hui, le Fanal
n'expose que **deux filons d'herboristerie** et il est `safe: true` (donc `mob: 0` : aucun
combat possible). « Récolte au choix » et « premier combat » y sont **matériellement
impossibles**.

Deux exigences, à porter dans les données de zone :

1. **Les cinq récoltes doivent être représentées dans le périmètre de l'acte I**, au palier
   T0 : herboristerie (existe), minerai, bois, pêche, dépeçage. Sans quoi « au choix » est un
   mensonge et tout le monde devient herboriste par défaut d'alternative.
2. **Le combat doit être atteignable sans attendre.** Le Fanal reste sûr — c'est sa
   définition (« ici, rien ne mord »), et on n'y touche pas. Donc l'acte I se joue sur
   **deux zones** : le Fanal et un voisin immédiat, avec **le premier voyage offert** (durée
   nulle, une seule fois, narrativement accompagné).

Ce dernier point donne d'ailleurs une progression propre sur le voyage : le joueur apprend
d'abord **le voyage comme geste** (gratuit, immédiat, accompagné), et découvre à l'étape 8
**le voyage comme temps réel**. Deux leçons distinctes, dans le bon ordre.

### 5.4 Une seule source de vérité

**D7** doit se refermer : `TutorialStep` et l'arc `intro` disent tous deux « où en est le
nouveau », sans se parler. L'arc de quêtes est la **source** (il porte le texte, les PNJ, les
récompenses, et il est déjà rejouable par personnage) ; `TutorialStep` devient une
**projection** de son avancement, utilisée pour l'affichage et le surlignage.

Conséquence : « passer le tutoriel » et « abandonner l'arc » deviennent le même geste, et le
succès `tutorial-complete` reste attaché à la clôture de l'arc.

### 5.5 Ce que l'acte I doit laisser

Contrat vérifiable à la sortie de la semaine 1 (reprise de GAME_PROGRESSION §3, complétée) :

- une **arme dotée d'un sort** et un soin (kit T1 échangeable) ;
- **un domaine de combat** commencé et **un métier de récolte** commencé — **tous deux
  choisis par le joueur**, jamais attribués ;
- **sa première matéria en main**, accordée, sertie, et lancée au moins une fois ;
- un **foyer d'attache constaté** (§4.4) et une première destination *suggérée* ;
- une **expédition en cours** au moment de la déconnexion ;
- un e-mail vérifié, ou une porte clairement identifiée.

### 5.6 Les corrections de contenu à faire au passage

- **D4** : les trois quêtes `explore` de l'arc ciblent des coordonnées sur une carte morte.
  Elles doivent devenir des objectifs du pivot. **Aucune quête d'introduction ne doit dépendre
  de `map_id`.**
- **D5** : une seule population de PNJ au Fanal, dont les porteurs de l'arc font partie.
- **E8** : le Fanal, la Voûte, le Limpide (NAR-20) — **en même temps** que la refonte du
  tunnel, sinon les écrans neufs naissent hors canon.

---

## 6. Les 32 arbres — et l'écran qui les montre

### 6.1 La question

Faut-il montrer les 32 domaines dès l'arrivée ? Aujourd'hui, `PlayerDomainHelper::getDomains()`
les renvoie tous et l'écran les empile : c'est un mur, et GAME_PROGRESSION §3 le désigne comme
**le risque n° 1 de l'acte I**.

Le réflexe — filtrer sur trois ou quatre selon le peuple — est mauvais deux fois : il cache du
jeu (E10 : le savoir n'est jamais borné) et il oriente (E2 : pas de classe déguisée).

### 6.2 La réponse : deux couches, celles de GAME_DOMAINS

La doctrine des trois couches donne la solution telle quelle — **le savoir n'est jamais borné,
le faire est borné par le build** :

> **La carte des domaines** *(le savoir)* — consultable dès la première minute, elle contient
> **les 32**, sans exception et sans condition. Elle n'est pas une liste : c'est la roue
> élément × registre (8 éléments × 3 registres pour le combat, plus 5 récoltes et 4 métiers
> d'artisanat). Lue comme une grille, elle tient sur un écran et **se comprend**. Rien n'est
> caché, jamais.

> **Mes arbres** *(le faire)* — l'écran par défaut. Il ne contient que les arbres **où le
> joueur a posé un geste**. Il mine → l'arbre Mineur s'ouvre. Il lance une matéria de feu →
> les trois arbres de feu s'ouvrent. Il part de zéro et il grossit avec lui.

Le mur disparaît sans qu'on ait rien caché ni rien orienté. Mieux : **l'apparition d'un arbre
devient une récompense de découverte** — le joueur voit son personnage se dessiner par ce
qu'il fait, ce qui est exactement la promesse d'un jeu sans classe. Un filtre par peuple
n'aurait rien produit de tel.

Et le joueur qui veut viser : il ouvre la carte, voit les 32, choisit son cap, et va poser le
geste qui ouvre l'arbre. C'est de l'intention, pas du hasard.

*(Le même principe vaut ailleurs et n'est pas nouveau dans le projet : GAME_ZONE_ACTIONS pose
déjà que « la zone montre ce que le personnage sait ».)*

---

## 7. Le coach par écran

### 7.1 Le principe

Pas de visite guidée. **Un écran jamais ouvert se présente lui-même, une fois.** Un encart en
haut : deux phrases, le geste proposé, son coût en énergie, une croix. Il ne revient jamais
seul et se relit depuis l'aide (crochet vers `/wiki`, WIK-02).

> **C1 — Le coach ne parle jamais d'un système que le joueur ne peut pas encore utiliser.**

> **C2 — Le coach dit toujours ce que ça coûte.** Le coût en énergie de chaque action est
> l'information la plus utile du jeu et la moins expliquée aujourd'hui.

> **C3 — Le coach se déclenche à l'arrivée, jamais au temps écoulé.**

### 7.2 Les écrans coachés

| Écran | Ce que l'encart dit | Déclenchement |
|---|---|---|
| Zone | Ce qu'on peut tenter ici, et ce que ça coûte | 1re ouverture |
| Combat | Attaque gratuite, matéria, fuite | 1er combat |
| Inventaire | Sac, équipement, **emplacements de matéria** | 1re ouverture |
| Arbres | La carte des 32 d'un côté, mes arbres de l'autre (§6.2) | 1re ouverture |
| Quêtes | Arc en cours, quotidiennes | 1re ouverture |
| Artisanat | Recettes, ce qui ne coûte pas d'énergie | 1re ouverture |
| Carte du monde | Le graphe, les durées de voyage | 1re ouverture |
| Hub | Ce qui attend, la semaine, la reprise | 1re ouverture **après** l'acte I |
| *Marché* | Vendre son surplus | 1er objet vendable **et** e-mail vérifié (C1) |
| *Guilde* | Pourquoi on en rejoint une | Fin de l'acte I |

Le hub arrive **après** l'acte I : avant, il n'a rien à montrer (§0).

### 7.3 Stockage

Un champ `Player.seenCoachMarks` (tableau JSON de slugs). Pas de nouvelle entité, pas de
nouvelle table : la donnée est un ensemble de drapeaux par personnage.

### 7.4 Ce qui n'est pas du coach : le retour après absence

Un joueur qui revient après sept jours ne veut pas apprendre l'interface, il veut savoir **ce
qui a changé**. `PlayerHubDigest::recap()` fait déjà une partie du travail. Réafficher un coach
à un joueur qui revient est la meilleure façon de le faire repartir.

---

## 8. Sécurité et abus

| Surface | Mesure |
|---|---|
| Inscription | Limiteur par IP (5 comptes / heure) ; e-mail unique ; pas de captcha tant que le limiteur suffit |
| Connexion | `login_throttling`, message d'erreur unique, lecture de `isBanned` |
| Mot de passe oublié | Jeton 1 h à usage unique, réponse constante, limiteur de débit |
| Nom de personnage | `ForbiddenNameChecker` **plus** unicité insensible à la casse et normalisation des homoglyphes (**D9**) |
| Multicomptage | La porte de vérification (§3.2) : marché, guildes, chat et **livraison aux foyers** hors de portée d'une ferme de comptes |
| Ban | Effet au login **et** en session courante |

---

## 9. Ce qui se mesure

| Indicateur | Ce qu'il révèle |
|---|---|
| Inscriptions → personnages créés | La friction du tunnel |
| **Pas d'abandon dans le tunnel** | Le nom déjà pris est le suspect n° 1 (**D9**) |
| Personnages créés → acte I terminé | La qualité de l'arc et du coach |
| **Répartition des métiers et des éléments choisis à l'acte I** | Si les choix de §5.2 sont réels. Une distribution écrasée sur une seule récolte est le symptôme de **D11** |
| % d'e-mails vérifiés à J+7 | Si la porte est placée au bon endroit |
| **Retour à J+1 et à J+7** | Tout le reste |

---

## 10. Arbitrages

### Tranchés (2026-07-29)

| # | Question | Décision |
|---|---|---|
| A1 | Vérification d'e-mail | **Différée**, porte sur l'économie et le social (§3.2) |
| A2 | Compte et personnage | **Tunnel unique en 4 pas** ; écrans séparés pour le 2ᵉ personnage |
| A3 | Apprentissage | **Coach par écran** à la première visite, pas de visite guidée (§7) |
| A4 | Premier écran après création | **La zone**, jamais le hub (§0) |
| A5 | Ordre du tutoriel | Neuf gestes, **matéria en 5ᵉ**, voyage en 8ᵉ, expédition en clôture (§5.2) |
| A6 | État d'onboarding | L'arc `intro` est la source ; `TutorialStep` en est la projection (§5.4) |
| A7 | Durée de « rester connecté » | **30 jours** (§3.3) |
| A8 | OAuth, compte invité, captcha | **Non au lancement** (§3.5) |

### Révision R1 (2026-07-29) — ce que la première version disait, et pourquoi elle a changé

La première rédaction faisait porter au peuple un **foyer d'attache** déterminant la première
destination, le kit, la réputation et **les trois arbres montrés en premier**. C'était une
classe déguisée : un joueur choisissant Elfe se trouvait poussé vers l'herboristerie sans
l'avoir demandé — violation de E2, et contradiction avec un jeu qui a renoncé aux classes.

| # | Question | Décision révisée |
|---|---|---|
| **A9** | Rôle du peuple | Il ne détermine **ni le métier, ni l'élément, ni la destination, ni les arbres visibles**. Recommandation : **aucune statistique** non plus (§4.5) |
| **A10** | Foyer d'attache | **Il se gagne, il ne se choisit pas** : c'est la zone où le joueur a travaillé pendant l'acte I. Amendement à GAME_WORLD §13.1 (§4.4) |
| **A11** | Les 32 arbres | **Deux couches** : la carte des 32 toujours consultable *(le savoir)*, « mes arbres » ouverts par les gestes *(le faire)*. Aucun filtre par peuple (§6.2) |
| **A12** | Le choix structurant | C'est **l'élément de la première matéria**, pris à l'étape 5, après un premier combat — jamais dans le tunnel (§5.2) |
| **A13** | Périmètre de l'acte I | **Deux zones** (le Fanal reste sûr + un voisin), **premier voyage offert**, et **les cinq récoltes représentées** au palier T0 — sans quoi « au choix » est un mensonge (§5.3) |

### Restés ouverts

- **Les statistiques de peuple.** §4.5 recommande de les retirer et propose une alternative
  (un trait qualitatif, jamais quantitatif). C'est un arbitrage de conception à poser
  sciemment. En l'état, **D12** subsiste : l'Humain est strictement dominé.
- **Le renommage payant.** Le nom est le seul choix irréversible du tunnel. Un renommage tardif
  contre monnaie est un gold sink honnête, mais il touche l'identité économique et le journal
  de monde. À instruire avec les gold sinks.
- **L'éparpillement des points de réveil** (GAME_WORLD §13.1) : rien à faire au lancement ; à
  écrire quand ce sera un problème de succès.
- **Les tables de noms proposés par peuple** (§4.2) et les textes de la lettre de foyer :
  contenu pur, à écrire avec NAR-20.
- **Le tutoriel de la deuxième semaine.** L'acte I s'arrête à j7 ; le passage critique est
  s3→s6 (GAME_PROGRESSION §3). C'est le domaine de PLAN_RETENTION, et la couture reste à
  vérifier.
