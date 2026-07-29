# Le compte, le personnage, l'arrivée — cadrage de l'entrée dans le jeu

> **Statut : proposé** · 2026-07-29 (révisions R1, R2, R3 et R3b le même jour — cf. §11)
> Source de vérité de **tout ce qui se passe avant qu'un joueur soit un joueur** : la
> création du compte, la connexion, la création du personnage, les dix premières minutes
> et l'apprentissage de l'interface.
> Complète (ne remplace pas) : [GAME_PROGRESSION.md](GAME_PROGRESSION.md) §3 « Acte I »,
> [GAME_DOMAINS.md](GAME_DOMAINS.md) §1 (la doctrine des trois couches — **précisée ici**,
> cf. §6.3), [GAME_WORLD.md](GAME_WORLD.md) §13.1 (**amendé ici**, cf. §4.4) et §1 (loi de
> nommage), [GAME_ZONE_ACTIONS.md](GAME_ZONE_ACTIONS.md), [GAME_DASHBOARD.md](GAME_DASHBOARD.md).
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

Cinq principes en découlent.

> **P1 — On joue d'abord, on prouve ensuite.** L'e-mail est demandé à l'inscription mais sa
> vérification est **différée** : elle ne barre pas le jeu, elle barre l'entrée dans
> l'économie et le social.

> **P2 — Le tunnel est une scène, pas un formulaire.** Compte, nom, peuple, visage : quatre
> pas continus, portés par une seule fiction — l'éveil au Fanal.

> **P3 — Aucune décision de build ne se prend dans le tunnel.** Améthyste n'a ni classe, ni
> attributs, ni orientation de départ. Le tunnel ne demande que **qui vous êtes**, jamais
> **ce que vous ferez**.

> **P4 — Le tutoriel enseigne les verbes ; le joueur choisit les compléments.** Quelle arme,
> quel élément, quelle récolte, quelle destination : le *quoi* appartient toujours au joueur.

> **P5 — L'acte I n'enseigne pas dix gestes disparates : il enseigne trois fois la même
> boucle.** *Choisir un parchemin → l'apprendre, l'arbre s'ouvre → poser le geste qu'il
> autorise.* Cette boucle est celle du jeu entier ; la répéter trois fois au jour 1 (l'arme,
> la matéria, la récolte) vaut mieux que l'expliquer une fois.

Et une contrainte de forme qui vaut décision : **le premier écran après la création n'est pas
le hub, c'est la zone.** Le hub est un tableau de bord ; celui d'un joueur qui vient de naître
est vide. On n'ouvre pas un jeu sur un écran vide.

---

## 1. Garde-fous — ce qu'on n'invente pas

| # | Contrainte | Origine | Ce qu'elle interdit ici |
|---|---|---|---|
| E1 | **Pas de niveau global** | CLAUDE.md §6 | « niveau 1 » à la création, une jauge de puissance, un « niveau recommandé » |
| E2 | **Pas de classe** | CLAUDE.md §9/§10 | Demander un métier ou une spécialisation dans le tunnel — **y compris déguisé**, par un kit, une destination imposée ou un filtre d'écran |
| E3 | **Pas d'attributs primaires** | GAME_DOMAINS §2 | Une répartition de points à la création |
| E4 | **Point de réveil unique** | GAME_WORLD §13.1 | Choisir sa zone de départ |
| E5 | **Sorts = matéria uniquement** | CLAUDE.md §10 | Offrir un sort autrement que par une matéria + son accord |
| E6 | **Pas de PvP** | CLAUDE.md §11 | Tout apprentissage du duel |
| E7 | **1 personnage par compte** | CLAUDE.md §12 | Un tunnel supposant plusieurs personnages, ou un mur sans issue |
| E8 | **Loi de nommage** | GAME_WORLD §1 | « Village de Lumière ». C'est **le Fanal**, **la Voûte**, un **Limpide** |
| E9 | **Le budget d'énergie est égalitaire** | GAME_ZONE_ACTIONS G2 | Un kit, un passif ou un bonus qui donne plus d'actions par jour |
| E10 | **Aucun arbre n'en exclut un autre** | GAME_DOMAINS §1 | Un accès aux arbres qui serait exclusif, unique, ou conditionné à un choix antérieur. Un **coût** d'accès est licite ; un **verrou** ne l'est pas (§6.3) |

**Corollaire de E2 et E10** : le seul choix définitif du tunnel est le **nom**. Tout le reste
s'apprend, se cumule et se répare. Le tunnel doit le dire — un joueur qui craint de se tromper
lit trois fois chaque option et abandonne une fois sur cinq.

---

## 2. L'existant, sans complaisance

État constaté dans le code au 2026-07-29. Treize dettes, dont trois bloquantes.

| # | Dette | Gravité | Constat |
|---|---|---|---|
| **D1** | **Aucune inscription** | 🔴 | `RegistrationController::__invoke()` lève `NotFoundHttpException` |
| **D2** | **Aucune récupération de compte** | 🔴 | Pas de `symfony/mailer`. Perdre son mot de passe = perdre son personnage **définitivement** |
| **D3** | **Login sans protection** | 🔴 | Aucun `login_throttling`. `User::isBanned` existe et **n'est lu nulle part** |
| **D4** | **Arc d'introduction cassé par le pivot** | 🟠 | Trois des sept quêtes d'`intro` valident un `explore` par `map_id` + coordonnées. `PlayerQuestUpdater::updateExplored()` résout par **zone** et ne se déclenche qu'**au voyage** — donc jamais pour un joueur qui n'a pas bougé |
| **D5** | **Deux populations de PNJ au même hub** | 🟠 | `PnjFixtures` (Gérard le Forgeron, Marie la Herboriste, Claire la Sage) et `VillageHubPnjFixtures` (Aldric le Forgeron, Iris l'Alchimiste…) coexistent : **deux forgerons** |
| **D6** | **Le tutoriel commence par le voyage** | 🟠 | `TutorialStep::Movement` d'abord, et le voyage est **time-gaté**. Le premier geste demandé est celui qui fait attendre |
| **D7** | **Deux états d'onboarding concurrents** | 🟡 | 5 `TutorialStep` et 7 quêtes d'arc, sans correspondance |
| **D8** | **La création n'a ni fiction ni conséquence** | 🟡 | Le **foyer d'attache** promis par GAME_WORLD §13.1 n'existe **nulle part** dans le code |
| **D9** | **Unicité du nom naïve et tardive** | 🟡 | `findOneBy(['name' => $name])` : casse-sensible, aveugle aux homoglyphes, et l'erreur tombe après que tout est rempli |
| **D10** | **Aucun apprentissage d'interface** | 🟡 | Un bandeau d'objectif au hub, rien d'autre |
| **D11** | **La zone de départ n'expose qu'une seule récolte** | 🟠 | Le Fanal n'a que **deux filons, tous deux d'herboristerie** (thym, lavande). Un choix de métier « au choix » y est impossible : tout le monde devient herboriste faute d'alternative *(le volet « combat » de cette dette est réglé autrement — cf. §5.4)* |
| **D12** | **Les peuples ne sont pas équilibrés — et l'Humain est strictement dominé** | 🟡 | Humain `0/0/0/0` ; Nain `+5 vie, +5 énergie, −1 vitesse` ; Orc `+8 vie, −3 précision` ; Elfe `+2 vitesse, +3 précision`. L'Humain n'a **aucun avantage**. Et +8 vie sur une base de 20, c'est **+40 % de survie** demandés au pas 3 |
| **D13** | **Le combat à mains nues n'existe pas** | 🟠 | `PlayerAttackHandler::getItem()` lève `EntityNotFoundException('Player attack impossible')` dès qu'aucune arme n'est équipée (ou qu'elle n'a pas de `spell`). Le repli posé comme garde-fou anti-blocage en §6.0 **est aujourd'hui un plantage** : sans lui, la doctrine du parchemin enferme un personnage sans arme apprise |

**Deux systèmes à moitié construits**, découverts en instruisant ce dossier — ils changent le
plan plus qu'ils ne l'alourdissent :

- **Les parchemins de domaine existent déjà.** `life-domain-parchment`, `miner-domain-parchment`,
  `herbalist-domain-parchment` sont en fixtures, vendus 100 gils, et deux sont déjà donnés en
  récompense de quête. Mais leur effet est
  `{"action":"learn_skill","slug":"miner-copper-xs"}` : ils accordent **une compétence
  précise**, pas l'accès à un arbre. Le geste joueur est le bon ; la sémantique est à hisser
  (§6).
- **`Race` n'est lue nulle part** hors création (sprite par défaut, modificateurs). Le peuple
  n'a aujourd'hui **aucune existence en jeu** — ce qui rend §4.5 possible sans rien casser.

Trois observations mineures : **deux notions d'énergie cohabitent** (`energy`/`maxEnergy` à
80/100, héritée, contre `DEFAULT_MAX_ACTION_ENERGY = 240`, celle du pivot, pleine à la
création — tout texte d'onboarding cite la seconde) ; **l'écran de login est hors design
system** ; **`DomainInfoController` montre tous les nœuds de n'importe quel domaine à
n'importe qui**, ce que §6 change.

---

## 3. Le compte

### 3.1 L'inscription

**Trois champs.** E-mail, mot de passe, acceptation des règles. Pas de pseudo de compte
(`User::username` reste inutilisé) : **le nom du personnage est la seule identité publique**.
Pas de confirmation de mot de passe — un bouton « afficher » vaut mieux, et §3.4 rattrape les
fautes de frappe. **Le compte naît non vérifié et pleinement jouable.**

### 3.2 La porte de vérification

> **Tranché** : la vérification d'e-mail ne barre pas le jeu, elle barre **tout ce qui sort
> du joueur vers les autres**.

| Ouvert sans vérification | Fermé jusqu'à vérification |
|---|---|
| Explorer, chasser, récolter, combattre | Chat de zone et chat global |
| L'arc d'introduction **en entier** | Hôtel des ventes (achat **et** vente) |
| Arbres, parchemins, matéria, artisanat | Échoppe joueur, commerce direct, don d'objet |
| Quêtes, quotidiennes, bestiaire, Codex | Guilde, groupe, donjon |
| Voyage, expéditions, boutiques PNJ | Messages privés, amis |
| Le journal, le hub, la carte du monde | **Livraison d'une commission à un foyer** |

La dernière ligne est la moins évidente et la plus importante : une livraison de commission
dépose du **sédiment** dans un foyer, et la Crue indexe le quota de grandes cités sur la
population **active**. Une ferme de comptes non vérifiés ne doit pas pouvoir faire monter une
ville.

Trois conséquences : la porte **tombe au bon moment toute seule** (l'arc se clôt sur « L'appel
des guildes ») ; le blocage **se lit comme une porte, pas comme une panne** (ce qui est
verrouillé, pourquoi, « renvoyer le lien ») ; **aucun blocage rétroactif**. Relance : une ligne
au hub (jamais une modale), e-mail à J+1 et J+3, puis silence.

### 3.3 La connexion

| Point | État | Cible |
|---|---|---|
| Bruteforce | aucun | `login_throttling` : 5 essais / 15 min par identifiant, 30 / 15 min par IP |
| Compte banni | `isBanned` jamais lu | Refus au login, message clair (jamais un 500) |
| Divulgation | — | Message **unique** : « Identifiants invalides » |
| « Rester connecté » | 7 jours | **30 jours** — un PBBG se joue tous les jours depuis le même appareil |
| Après login | toujours `/game` | **Selon l'état** : aucun personnage → tunnel ; un personnage → zone si l'acte I est en cours, hub sinon ; plusieurs → sélection |

Tant que l'arc `intro` n'est pas clos, **la zone est la maison**.

### 3.4 Le mot de passe oublié

C'est **D2**, la dette la plus dangereuse : aujourd'hui, perdre son mot de passe, c'est perdre
son personnage, son inventaire, sa guilde et sa place dans un foyer.

`symfony/mailer` installé (via Docker, règle 1) ; jeton à usage unique, **1 heure**, un seul
actif par compte ; **réponse identique** que le compte existe ou non ; limiteur de débit ;
invalidation de toutes les sessions à la réinitialisation.

### 3.5 Ce qu'on ne fait pas

Pas d'**OAuth** au lancement. Pas de **compte invité** (personnages orphelins, purge à écrire,
et une question insoluble : que devient un invité qui a fait monter un foyer ?). Pas de
**captcha** tant que le limiteur suffit — c'est une taxe sur les honnêtes.

---

## 4. La création de personnage

### 4.1 Ce que le tunnel ne demande pas

Pas de classe (E2), pas de zone de départ (E4), pas de stats à répartir (E3), pas de sexe en
champ séparé. **Et pas de métier, pas d'élément, pas d'arme, pas de destination** : ce sont
les vrais choix du jeu, et ils appartiennent à l'acte I (§5).

### 4.2 Le tunnel en quatre pas

**Pas 1 — Le compte.** E-mail, mot de passe. *« On ne se souvient de personne avant le
Fanal. »*

**Pas 2 — Le nom.** 3 à 16 caractères. Deux corrections : **unicité vérifiée au fil de la
frappe** (découvrir que « Aldric » est pris après avoir tout rempli est le point d'abandon le
plus prévisible du tunnel) et **unicité insensible à la casse et aux homoglyphes** (`Aldric`,
`aldric` et `Аldric` cyrillique sont le même nom). Plus un bouton **« proposer un nom »**.

**Pas 3 — Le peuple.** Qui vous êtes, d'où vous venez, et **une capacité** — voir §4.5.

**Pas 4 — Le visage.** Corps, coiffure, couleur, tenue, avec aperçu. Mention explicite :
*« Tout ceci se change quand vous voulez. »* C'est vrai, et le dire accélère le pas le plus
long du tunnel.

### 4.3 Réversible ou non — à afficher, honnêtement

| Choix | Réversible ? | Pourquoi |
|---|---|---|
| Apparence | **Oui**, à tout moment | Aucune conséquence de jeu |
| Nom | Non | Identité publique : marché, guilde, journal de monde |
| Peuple | Non | Il porte une capacité (§4.5) |

La liste des engagements tient en deux lignes. Un tunnel qui le dit est un tunnel qu'on
traverse vite.

### 4.4 Le foyer d'attache — il ne se choisit pas, il se gagne

> **Amendement à GAME_WORLD §13.1** *(proposé le 2026-07-29)*. Le canon dit : « la race donne
> un foyer d'attache — qui détermine sa première destination, son kit, sa réputation de départ
> et sa première chaîne de quêtes ». **L'intention est conservée, le mécanisme change.**

L'intention de §13.1 reste valable : **ne pas éparpiller la population** (un monde vide est le
vrai danger, et l'éparpillement combat le pilier des foyers) et **différencier par la
destination**. Le problème est ailleurs : **dériver le foyer de la race** revient à demander
une orientation de carrière — kit, destination, chaîne de quêtes — avant toute expérience de
jeu. C'est une classe déguisée, donc E2.

> **Le foyer d'attache est la zone où le joueur a réellement travaillé pendant l'acte I.**
> Le jeu le constate à la clôture et le lui annonce. *« Les mineurs des Mines profondes ont
> remarqué votre travail. »*

Ce que ça règle :

| | Foyer dérivé de la race | Foyer gagné par les gestes |
|---|---|---|
| L'Elfe qui veut miner | poussé ailleurs | adopté par les mineurs |
| Cohérence avec le pilier des foyers | l'origine fait le lieu | **l'activité fait le lieu** — la loi des foyers |
| Décision demandée avant de comprendre | oui | non |
| Concentration de la population | tenue | **tenue de la même façon** — le réveil reste unique |
| Sentiment d'être distinct | à la création | à la sortie de l'acte I, **et mérité** |

**Ce qu'il apporte, une fois gagné** : une **lettre** suggérant une première destination, un
**PNJ qui vous connaît**, un **cran de réputation** (dans le respect de FAC-01), une **ligne au
journal**. **Aucun contenu ouvert, aucun contenu fermé, aucun bonus de rendement.** Il
enregistre une orientation déjà prise ; il ne l'oriente pas.

Sans activité distinctive, le joueur reste attaché **au Fanal** — exactement ce que le canon
réservait à l'Humain. Le cas humain devient le cas par défaut de tout le monde.

### 4.5 Ce qu'apporte le peuple : une capacité, jamais de la puissance

**État réel** : `Race` porte un sprite (écrasé au pas 4) et des modificateurs non équilibrés
(**D12**). Elle n'est lue nulle part ailleurs : le peuple n'a **aucune existence en jeu**.

Le problème n'est pas l'équilibrage mais la nature : **+8 points de vie sur une base de 20,
c'est +40 % de survie demandés au pas 3**, quand le joueur en sait le moins. Dans un jeu qui a
renoncé aux classes (E2) et aux attributs (E3), c'est le dernier endroit où survit une décision
de puissance prise à l'aveugle.

> **Décision** : les modificateurs de statistiques disparaissent. Le peuple porte **une
> capacité passive unique**, et la règle suivante la rend sûre :

> **La règle du passif de peuple** — il ne touche jamais ce que le joueur **produit**,
> seulement ce qu'il **sait**. Ni dégâts, ni points de vie, ni rendement, ni coût, ni nombre
> d'actions par jour (E9), ni prix. Il change **ce que l'écran affiche** : donc la qualité des
> décisions, jamais leur puissance.

> **Le corollaire qui garantit l'équilibre** : *le passif de peuple rattrape l'expérience, il
> ne remplace pas le talent.* Un vétéran qui connaît le bestiaire par cœur ne tire rien du
> passif orc ; un joueur qui lit le wiki obtient la même information. **L'écart se referme avec
> les heures de jeu** — c'est l'exact contraire d'un déséquilibre compétitif, et c'est ce qui
> permet d'en donner un à tout le monde, humains compris.

**Les quatre capacités proposées :**

| Peuple | Capacité | Ce qu'elle montre | Fort pour | Nul pour |
|---|---|---|---|---|
| **Nain** — *Lire la pierre* | La **bande de pureté** d'un filon est lisible **avant** de récolter | un fait local, sur le filon devant soi | mineur, prospecteur, artisan qui vise le Parfait | un joueur qui ne récolte pas |
| **Elfe** — *L'œil des lisières* | Une exploration qui ne donne « rien » rend quand même **un repérage** (un filon, un PNJ ou un monstre de la zone) | une information de zone, jamais du butin | qui découvre une zone neuve | qui connaît déjà sa zone par cœur |
| **Orc** — *Le flair* | L'**élément et la faiblesse** d'un monstre sont lisibles **dès la première rencontre**, sans attendre le palier de bestiaire | une information de combat | qui affronte du nouveau | qui a déjà le bestiaire plein |
| **Humain** — *Les usages* | Sur un objet **qu'il a ou a eu en sac ou en banque**, il voit **à quoi il sert** : les recettes qui le consomment, les PNJ qui l'achètent | une information économique, **sur ce qui lui est passé entre les mains** | artisan, marchand, débutant noyé | qui connaît le livre de recettes |

**Le test qui valide le jeu de quatre** : *chaque capacité a une population pour qui elle ne
sert à rien.* C'est la garantie qu'aucune n'est strictement supérieure — et c'est le test à
repasser si l'une d'elles est réécrite.

Deux garde-fous à tenir à l'implémentation :

- **Le Nain ne marche pas sur les plates-bandes du prospecteur.** Le Nain lit **le filon devant
  lui** ; le prospecteur sait **où chercher et pour combien de temps** (l'Affleurement de la
  semaine, RET-06). Deux échelles différentes, aucun doublon.
- **Le repérage de l'Elfe ne donne jamais de butin** et ne réduit aucun coût. Il transforme du
  vide en connaissance, jamais en ressource — sans quoi E9 tombe.
- **L'Humain ne lit pas une base de données, il lit son propre passé.** La capacité ne porte
  que sur ce qui lui est **passé entre les mains** (sac ou banque, présent ou passé — elle
  s'appuie sur `PlayerResourceCatalog`) : jamais sur un objet vu à l'hôtel des ventes, dans une
  échoppe ou sur un autre joueur. C'est ce qui l'empêche de devenir un outil d'arbitrage de
  marché, et ce qui la fait **se gagner en jouant** comme les trois autres.

Et dans tous les cas : **le peuple ne détermine ni le métier, ni l'élément, ni la destination,
ni les arbres accessibles.**

### 4.6 La limite d'un personnage

`app.max_players_per_user: 1`. L'écran de limite doit dire **quoi faire** — règle des états
vides du design system. Le second personnage rejoue l'arc intégralement (règle 12) ; le coach
par écran (§7) est **par personnage** et se rejoue aussi.

---

## 5. L'acte I — la boucle, trois fois

### 5.1 Le principe

> **L'acte I n'enseigne pas dix gestes : il enseigne trois fois la même boucle** (P5).
>
> **Choisir un parchemin → l'apprendre, l'arbre s'ouvre → poser le geste qu'il autorise.**

C'est la boucle du jeu entier : voir un arbre au catalogue, obtenir son parchemin, l'apprendre,
jouer ce qu'il ouvre. La répéter trois fois au jour 1 — **l'arme, la matéria, la récolte** —
vaut mieux que l'expliquer une fois. À la troisième, le joueur n'a plus besoin qu'on la lui
explique : il sait comment on apprend quelque chose dans Améthyste.

Et à chaque tour de boucle, **le choix est réel** : quelle arme, quel élément, quel métier.

Le joueur sort du tunnel avec 240 points d'énergie — une journée entière. Il n'a aucun budget à
ménager, et **rien n'est time-gaté avant la fin**. Un écran d'éveil, un paragraphe, un bouton
(« Ouvrir les yeux ») → **l'écran de zone**.

### 5.2 La chaîne

| # | Quête | Le geste | **Le choix du joueur** | Ce que ça enseigne |
|---|---|---|---|---|
| 1 | **Le maître d'armes** | Aller le voir, parler | — | Le PNJ, le dialogue, la quête. Coût nul, échec impossible |
| | *récompense* | **une arme au choix** + **le parchemin de l'arbre qui l'autorise** | **quelle arme** | Le premier vrai choix, et il est indolore |
| 2 | **Apprendre** | Utiliser le parchemin → l'arbre s'ouvre → prendre le nœud d'équipement → équiper l'arme | — | **La boucle, tour 1.** L'inventaire, l'arbre, l'équipement |
| 3 | **Le mannequin** | Combat n° 1 | quand il y va | **L'interface de combat**, l'attaque d'arme gratuite. Le mannequin **n'attaque pas** — son action est « tourne sur lui-même », zéro dégât. **Perdre est impossible** |
| | *récompense* | **une matéria de l'élément du domaine choisi** + **les points de domaine pour en prendre l'accord** | — | On ne montre jamais une matéria qu'on ne peut pas utiliser |
| 4 | **L'accord** | Prendre le nœud d'accord → sertir la matéria | quel emplacement | **La boucle, tour 2.** Les emplacements de matéria, le build |
| 5 | **Le second mannequin** | Combat n° 2, lancer le sort | — | La matéria en combat. Celui-ci **riposte**, faiblement — il frappe, **il ne tue pas** |
| 6 | **Le métier** | Choisir un parchemin de récolte parmi les cinq | **quel métier** | **La boucle, tour 3.** Et le choix le plus structurant de la semaine 1 |
| 7 | **La récolte** | Aller dans la zone et récolter | où, combien | Le coût en énergie, le filon partagé, le voyage court |
| 8 | **L'atelier** | Fabriquer avec ce qu'on a récolté | quoi | L'artisanat ne coûte pas d'énergie — la première fois qu'un geste est « gratuit » |
| 9 | **Le départ** | Voyager vers une vraie zone | **où** — trois destinations, aucune imposée | Le voyage **coûte du temps réel**. Première attente du jeu, et on le dit |
| 10 | **L'expédition** | En lancer une avant de fermer | laquelle, combien de temps | **Comment quitter le jeu en le laissant travailler.** La leçon qui fait revenir au jour 2 |

*(L'exploration n'a pas sa quête propre : elle est le moyen naturel de l'étape 7 — on explore
pour trouver où récolter. Lui donner une étape en ferait une corvée ; l'y intégrer en fait un
outil.)*

### 5.3 Pourquoi deux mannequins plutôt qu'un monstre

Trois raisons, et la troisième est la moins évidente.

**Le premier combat ne peut pas être perdu.** On peut donc y afficher toute l'interface — les
encarts de tutoriel, l'ordre des tours, les points de vie, la fuite — sans qu'un joueur qui
lit lentement se fasse tuer pendant qu'il lit. C'est la seule façon d'enseigner une interface
de combat sans mentir sur la difficulté ni frustrer.

**Le mannequin est diégétique.** Ce n'est pas un monstre affaibli pour les débutants : c'est
un mannequin d'entraînement, et « tourne sur lui-même » est ce que fait un mannequin. Le monde
ne raconte donc jamais que ses monstres sont inoffensifs — le premier vrai monstre gardera tout
son mordant.

**Et il résout un blocage matériel sans rien casser.** Le Fanal est `safe: true`, ce qui force
`mob: 0` dans `ExploreService` : **aucun combat n'y est possible**. Un combat **scripté par une
quête** n'est pas un tirage de rencontre : le mannequin permet donc d'enseigner le combat au
Fanal **sans toucher à sa sûreté** — « ici, rien ne mord » reste vrai, et rien ne mord
effectivement. C'est ce qui évite d'avoir à faire voyager le joueur avant même son premier
combat, c'est-à-dire à le faire attendre.

Le second, lui, **riposte faiblement et ne peut pas tuer** (plancher à 1 point de vie) : le
joueur doit voir sa barre descendre pour comprendre à quoi servent les soins, sans que
l'apprentissage se solde par une mort.

### 5.4 Ce que le périmètre de l'acte I doit contenir

**Le combat est réglé** par §5.3 : les mannequins, au Fanal, sans toucher au `safe: true`.

**La récolte ne l'est pas** (**D11**) : le Fanal n'expose que **deux filons, tous deux
d'herboristerie**. Un choix parmi cinq parchemins de récolte (étape 6) qui débouche sur une
seule récolte possible (étape 7) est un faux choix — et tout le monde deviendrait herboriste.

> **Exigence de données** : les **cinq récoltes** doivent être atteignables dans le périmètre
> de l'acte I — au Fanal ou dans une zone voisine immédiate — au palier T0.

Et puisque l'étape 7 envoie le joueur récolter, **c'est là que le voyage s'apprend**, dans sa
forme courte et indolore : un premier trajet **offert** (durée nulle, une seule fois,
narrativement accompagné). Le joueur découvre alors le voyage **comme geste** ; il découvrira
à l'étape 9 le voyage **comme temps réel**. Deux leçons distinctes, dans le bon ordre.

### 5.5 Une seule source de vérité

**D7** doit se refermer. L'arc `intro` est la **source** (il porte le texte, les PNJ, les
récompenses, et il est rejouable par personnage) ; `TutorialStep` devient une **projection**
de son avancement. « Passer le tutoriel » et « abandonner l'arc » deviennent le même geste, et
le succès `tutorial-complete` reste attaché à la clôture de l'arc.

### 5.6 Ce que l'acte I doit laisser

- une **arme équipée** et l'arbre qui l'autorise, **tous deux choisis** ;
- **une matéria accordée, sertie et lancée** au moins une fois ;
- **un métier de récolte** commencé, choisi parmi cinq ;
- **trois arbres ouverts** par trois parchemins — et la boucle comprise ;
- un **foyer d'attache constaté** (§4.4) et une première destination suggérée ;
- une **expédition en cours** au moment de la déconnexion ;
- un e-mail vérifié, ou une porte clairement identifiée.

### 5.7 Les corrections de contenu à faire au passage

- **D4** : les trois quêtes `explore` de l'arc ciblent des coordonnées sur une carte morte.
  **Aucune quête d'introduction ne doit dépendre de `map_id`.**
- **D5** : une seule population de PNJ au Fanal — et le **maître d'armes** de l'étape 1 en fait
  partie (aujourd'hui, il y a deux forgerons et pas de maître d'armes).
- **E8** : le Fanal, la Voûte, le Limpide (NAR-20), **en même temps** que la refonte.

---

## 6. Les arbres : le catalogue, le parchemin, l'arbre

### 6.0 Le principe : tout le monde sait qu'on peut miner, personne ne sait miner

> **Le champ est infini ; l'entrée est un acte.**

C'est le juste milieu entre « pouvoir tout faire » et « interdire un geste », et il tient dans
une distinction que la doctrine ne faisait pas :

| | |
|---|---|
| **Ce qui n'est jamais borné** | *Le champ.* Aucun geste n'est fermé à personne. Aucun arbre n'en exclut un autre. Un joueur peut tout apprendre, et mener les 32 de front s'il le veut |
| **Ce qui doit s'apprendre** | *Le geste lui-même.* On ne tient pas une pioche parce qu'on sait que les pioches existent |

Personne ne sait lancer un sort ni tenir une épée sans l'avoir appris. Tout le monde **sait
qu'on peut** se battre à l'épée, cueillir des plantes, miner, forger — c'est le catalogue,
public et complet. Mais **savoir comment** demande de s'y être intéressé : un maître, ou un
texte. C'est le parchemin.

Deux conséquences, et la seconde est une décision de portée large.

**Le joueur n'est jamais limité en nombre.** Il apprend autant de choses qu'il en croise, et
les mène toutes de front. Le seul borneur reste l'énergie — ce que la doctrine des trois
couches désigne déjà comme *la seule monnaie*.

**Les actions de base sont concernées.** Un personnage qui n'a rien appris ne mine pas, ne
cueille pas, ne forge pas. Ce n'est pas une punition, c'est la même phrase lue à l'endroit :
il ne sait pas comment. *(Le mécanisme existe déjà en données — `requires_skill` sur les
filons, aujourd'hui posé seulement au haut palier : `miner-darksteel-xs`,
`lumber-whisperoak-xs`. Le généraliser au palier T0 est un changement de comportement, avec
deux garde-fous obligatoires : les personnages existants sont **grand-périsés** sur ce qu'ils
pratiquent déjà, et l'acte I **donne** les trois premiers parchemins.)*

**Ce qui ne s'apprend jamais**, et qui reste libre pour tous, sans condition : marcher,
voyager, explorer, parler, ramasser, se battre **à mains nues**. On n'apprend pas à marcher, et
un personnage sans aucun apprentissage d'arme doit pouvoir se défendre — mal, mais toujours.
La frontière est nette : **le parchemin ouvre un métier ou une famille d'arme, jamais un verbe
élémentaire du jeu.** Sans cette ligne, le jeu devient une parade de verrous.

*(Sur les armes, aucune contradiction avec GAME_DOMAINS : son garde-fou « jamais d'interdit de
port » réserve explicitement le cas — « seul un prérequis de **compétence** peut gater une
pièce ». Le parchemin d'arme est ce prérequis, et il est atteignable par tout le monde.)*

#### 6.0 bis — Le cas de l'arme, tranché

**Oui : équiper une arme exige d'avoir appris son maniement dans un arbre. Sans quoi on se bat
à mains nues.** Le mécanisme est **déjà en place** — `Item::requirements` (ManyToMany vers
`Skill`) et `PlayerItemHelper::canBeEquipped()`, qui exige *toutes* les compétences requises —
et il est déjà utilisé sur les armes de palier 2 et 3 (`berserk_weapon_t2`, `knight_weapon_t2`,
`archer_weapon_t2`…). Trois précisions le rendent utilisable sans casser le reste.

**a) C'est l'inverse aujourd'hui.** Les armes **T1 n'ont aucun prérequis** ; seules les T2/T3
en portent. On peut donc manier une hachette rouillée sans rien avoir appris, mais pas une
hache de guerre. Avec la doctrine, **c'est le T1 qui porte le nœud de maniement** — c'est là
qu'on apprend à tenir l'arme ; les paliers supérieurs continuent d'exiger des nœuds plus
avancés du **même** arbre.

**b) Le nœud de maniement est borné par le registre, jamais par l'élément.** Les prérequis
existants sont nommés **par domaine** (`berserk_weapon_t2` = feu × mêlée, `knight_weapon_t2` =
métal × mêlée) et `steel-axe` porte `'domain' => 'soldier'`. Pris tel quel, cela signifie qu'un
Berserker devrait ouvrir l'arbre du Soldat pour porter une hache : **l'arme redeviendrait
couplée à l'élément**, exactement ce que DOM-01 a séparé (le domaine est élément × registre ;
c'est l'**arme** qui fixe le registre).

> **« Maniement de la hache » est un nœud partagé entre les huit arbres de mêlée. En ouvrir un
> seul suffit. On n'achète jamais un élément pour porter une arme.**

Le mécanisme existe : `Skill::domains` est un ManyToMany, et les nœuds partagés sont déjà une
pratique du projet (DOM-09).

**c) La hache d'arme n'est pas la hache de bûcheron.** Le mot est le même, la porte ne l'est
pas : la hache de guerre passe par le nœud de maniement (registre mêlée), la hache de bûcheron
(ZON-34, DOM-05) par l'arbre du bûcheron. Même remarque pour la pioche — outil, jamais arme.

**d) Et le repli doit d'abord exister** (**D13**). `PlayerAttackHandler::getItem()` lève
aujourd'hui `EntityNotFoundException('Player attack impossible')` dès qu'aucune arme n'est
équipée. Le combat à mains nues — faible, sans emplacement de matéria, mais **toujours
disponible** — est la condition sans laquelle cette doctrine enferme un personnage au lieu de
l'orienter. C'est le premier jalon à livrer de tout le bloc.

### 6.1 Trois états, pas deux

La question « montre-t-on les 32 arbres dès l'arrivée ? » n'a pas une réponse binaire. Elle en
a trois.

> **1. Le catalogue** *(public, complet, dès la première minute)* — les **32 arbres existent
> et sont listés**, chacun avec **ce qu'on y apprend** et **où s'en procurer le parchemin**.
> Une description, pas une fiche technique : « on y apprend à travailler le métal, à réparer
> une lame, à sertir une monture » — et « le parchemin se trouve chez le forgeron du Fanal ».
> **Ni la liste des nœuds, ni les valeurs, ni même le premier nœud.**

> **2. Le parchemin** *(un objet)* — vendu par le PNJ du métier ou donné en récompense de
> quête. L'apprendre **ouvre l'arbre**, définitivement.

> **3. L'arbre ouvert** *(le détail)* — nœuds, prérequis, coûts, valeurs. Visible seulement
> après le parchemin.

Le mur des 32 disparaît sans qu'on ait rien caché d'important et sans qu'on ait orienté qui que
ce soit : le joueur voit **la carte entière du savoir possible**, et choisit délibérément où
aller. Ce que le parchemin achète, c'est le **détail technique** — pas l'existence, pas la
vocation, pas la possibilité.

Trois bénéfices qui ne se voyaient pas au départ :

- **Le choix devient délibéré.** Un modèle où l'arbre s'ouvre en posant un geste ouvrirait des
  arbres par accident ; ici, on ouvre un arbre parce qu'on a décidé de l'ouvrir.
- **Les PNJ de métier retrouvent une raison d'exister.** Un forgeron qui vend le parchemin de
  forge est un forgeron qu'on va voir. Le maître d'armes de l'étape 1 en est le premier
  exemple.
- **C'est un gold sink honnête** — on paie pour du savoir, pas pour de la puissance.

### 6.2 Ce que le catalogue doit dire, et ne pas dire

| Le catalogue dit | Le catalogue ne dit pas |
|---|---|
| Que l'arbre existe | La liste de ses nœuds |
| Sa case élément × registre (Feu × sorts, Métal × mêlée…) | Les valeurs de ses passifs |
| Ce qu'on y apprend, en une phrase | Ses prérequis internes |
| Ce qu'il permet d'équiper ou d'utiliser, en famille | Son premier nœud |
| Où trouver son parchemin, et à quel prix | Sa spécialisation terminale |

Présenté comme la **roue élément × registre** (8 × 3 pour le combat, plus 5 récoltes et 4
métiers d'artisanat — la borne existe déjà en données depuis DOM-01), le catalogue tient sur un
écran et **se lit**. 32 en grille se comprend ; 32 en liste est un mur.

### 6.3 La tension avec GAME_DOMAINS §1, et pourquoi elle se résout

GAME_DOMAINS §1 énonce : *« le savoir n'est jamais borné »*, et ajoute — sans ambiguïté —
*« interdire un arbre serait interdire un geste : contradiction frontale avec le principe
fondateur, et retour des "classes" par la fenêtre. »* Le parchemin borne l'accès à un arbre. Il
faut donc trancher, pas contourner.

**La doctrine parle du champ, le parchemin parle de l'apprentissage** (§6.0). Sa formule
canonique — *« on peut virtuellement savoir tout faire, mais on ne fait qu'une seule chose à la
fois »* — dit **ce qui est possible**, pas ce qui est déjà su. Et son critère est explicite :
**aucun arbre n'en exclut un autre**. Le parchemin n'exclut rien : il est l'acte d'apprendre
lui-même, et on peut les accumuler tous.

Autrement dit, la doctrine interdit qu'un geste soit **fermé** ; elle n'a jamais dit qu'il
était **déjà acquis**. Confondre les deux, c'est poser qu'un personnage naît en sachant miner,
forger, tirer à l'arc et lancer des sorts — ce qu'aucun texte du projet n'affirme, et ce que
la fiction contredit.

> **Le parchemin de registre est un coût, jamais un verrou.** Quatre conditions le
> garantissent, et si l'une tombe, il devient un système de classes et la doctrine est
> réellement violée :
>
> 1. **Tout parchemin est accessible à tout le monde** — aucun prérequis de peuple, de faction,
>    de progression ou de choix antérieur.
> 2. **En posséder un n'en interdit aucun autre.** Les 32 sont cumulables.
> 3. **Aucun n'est unique ni limité en nombre.** Un PNJ le vend, toujours, à prix fixe.
> 4. **Aucun parchemin payant sur le chemin critique de l'acte I** — les trois premiers sont
>    **donnés** en récompense de quête (§5.2). Un joueur sans gils n'est jamais bloqué.

Sous ces conditions, le parchemin a exactement le statut de l'énergie : il borne le **rythme**
d'apprentissage, pas son **champ**. C'est le borneur que la doctrine désigne elle-même pour la
couche « savoir » — *le budget d'énergie, la seule monnaie* — auquel s'ajoute une seconde
monnaie, les gils. Ni l'une ni l'autre n'interdit un geste : elles l'échelonnent.

**Une conséquence à assumer** : la doctrine, prise à la lettre, rendait le mur des 32 arbres
*structurel* — si rien ne peut jamais être borné, tout est ouvert au jour 1, et
GAME_PROGRESSION §3 désigne ce mur comme le risque n° 1 de l'acte I. Le parchemin transforme le
mur en chemin. C'est un gain net pour la doctrine, pas une entorse.

**Les parchemins sont-ils échangeables ?** Sans importance, et donc oui : puisqu'un PNJ en vend
à prix fixe (condition 3), le marché joueur ne peut pas dépasser ce prix — c'est le principe du
plancher T1 PNJ de GAME_PRINCIPLES appliqué au savoir. Un joueur riche ouvrira ses arbres plus
tôt ; il ne les ouvrira pas *mieux*, et l'énergie reste la borne réelle de sa progression.

### 6.4 Les arbres retrouvés — ce que le catalogue ne contient pas

Le catalogue est complet **pour ce qui s'atteint par le jeu ordinaire**. Il existe une seconde
catégorie, qui n'y figure nulle part : **les arbres retrouvés**.

> Un joueur qui a mené l'arbre du mineur à son dernier palier — et qui n'a donc, en principe,
> plus rien à y faire — croise un vieux Nain à moitié changé en minerai. Le Nain lui confie un
> parchemin prêt à tomber en poussière : un arbre de prospection que **le registre ne mentionne
> pas**.

C'est un ajout de valeur nette, pour une raison qui n'était pas visible avant : **aujourd'hui,
terminer un arbre ne donne rien.** Le dernier palier est un cul-de-sac. Ici, il devient une
**condition de rencontre** — et la fin d'un arbre cesse d'être une fin.

**Le même patron vaut au-delà des arbres** : quêtes retrouvées, monstres uniques, recettes que
personne n'a listées. Ce document ne cadre que les arbres ; le reste appartient au contenu.

**Cinq lois.** Les deux premières sont importées telles quelles de GAME_WORLD §12.3 (le
Répertoire), qui a déjà résolu le même problème à l'échelle du serveur — on ne réinvente pas
une seconde jurisprudence pour le même sujet.

1. **Latéral, jamais vertical** *(§12.3c)*. Un arbre retrouvé apporte des **options** — une
   variante, un utilitaire, une lecture nouvelle du métier — jamais strictement plus de
   puissance. Sinon le joueur qui n'a pas croisé le vieux Nain est mécaniquement derrière, et
   le secret devient une obligation déguisée.
2. **Cumulatif, jamais manqué** *(§12.3d)*. La rencontre reste disponible **indéfiniment, pour
   quiconque remplit la même condition**. Il n'y a pas de premier arrivé, pas de fenêtre, pas
   de date. **Le secret est dans le savoir, jamais dans l'avoir-été-là.**
3. **Jamais nécessaire.** Aucune recette, aucun palier, aucune quête de progression normale
   n'en dépend. Un joueur qui ignore tout des arbres retrouvés joue un jeu complet.
4. **La condition est un accomplissement, pas un hasard.** Le vieux Nain se montre à qui a fini
   l'arbre, pas à qui a de la chance. Un secret tiré au sort est une loterie ; un secret mérité
   est une récompense.
5. **Le parchemin retrouvé est lié** — c'est **l'exception** aux quatre conditions du §6.3, et
   la seule. Ce qui circule entre joueurs, c'est **l'information** (« va voir le vieux Nain
   quand tu auras fini le mineur »), jamais l'objet. Sans cette exception, le premier
   découvreur met le secret à l'hôtel des ventes et il est mort en deux jours.

La cinquième loi est le vrai geste de conception : elle fait du secret un **objet social**
plutôt qu'une marchandise. C'est exactement le patron de l'information exclusive du prospecteur
(GAME_ZONE_ACTIONS) — le savoir se monnaie entre joueurs sans qu'aucun objet ne change de main.

> **À ne pas confondre avec le Répertoire** (GAME_WORLD §12.3, PLAN_REPERTOIRE). Le Répertoire
> est **collectif** (c'est le serveur qui retrouve un geste), il porte sur la **matéria**, et il
> s'oriente par les lectures. Les arbres retrouvés sont **individuels**, portent sur les
> **domaines**, et se gagnent par l'accomplissement. Deux systèmes cousins, deux échelles, mais
> **les mêmes deux lois** — ce qui est le signe qu'elles sont bonnes.

### 6.5 Ce que ça change dans le code existant

Les parchemins existent (`life-domain-parchment`, `miner-domain-parchment`,
`herbalist-domain-parchment`) mais leur effet est `learn_skill` sur **une compétence précise**.
Le geste joueur est le bon, la sémantique est à hisser : il faut un effet **« ouvrir un
domaine »** et une notion d'**arbre ouvert pour ce personnage**, que le modèle n'a pas
aujourd'hui. Et `DomainInfoController`, qui montre actuellement tous les nœuds de n'importe
quel domaine à n'importe qui, doit servir **le catalogue** pour un arbre fermé et **l'arbre**
pour un arbre ouvert.

---

## 7. Le coach par écran

### 7.1 Le principe

Pas de visite guidée. **Un écran jamais ouvert se présente lui-même, une fois.** Un encart en
haut : deux phrases, le geste proposé, son coût en énergie, une croix. Il ne revient jamais
seul et se relit depuis l'aide (crochet vers `/wiki`, WIK-02).

> **C1 — Le coach ne parle jamais d'un système que le joueur ne peut pas encore utiliser.**

> **C2 — Le coach dit toujours ce que ça coûte.**

> **C3 — Le coach se déclenche à l'arrivée, jamais au temps écoulé.**

### 7.2 Les écrans coachés

| Écran | Ce que l'encart dit | Déclenchement |
|---|---|---|
| Zone | Ce qu'on peut tenter ici, et ce que ça coûte | 1re ouverture |
| Combat | Attaque gratuite, matéria, fuite | **1er mannequin** — le seul combat où lire ne tue pas |
| Inventaire | Sac, équipement, **emplacements de matéria** | 1re ouverture |
| Catalogue des arbres | Ce qu'on y lit, et ce qu'un parchemin ouvre (§6.1) | 1re ouverture |
| Quêtes | Arc en cours, quotidiennes | 1re ouverture |
| Artisanat | Recettes, ce qui ne coûte pas d'énergie | 1re ouverture |
| Carte du monde | Le graphe, les durées de voyage | 1re ouverture |
| Hub | Ce qui attend, la semaine, la reprise | 1re ouverture **après** l'acte I |
| *Marché* | Vendre son surplus | 1er objet vendable **et** e-mail vérifié (C1) |
| *Guilde* | Pourquoi on en rejoint une | Fin de l'acte I |

### 7.3 Stockage

Un champ `Player.seenCoachMarks` (tableau JSON de slugs). Pas de nouvelle entité.

### 7.4 Ce qui n'est pas du coach : le retour après absence

Un joueur qui revient après sept jours veut savoir **ce qui a changé**, pas réapprendre
l'interface. `PlayerHubDigest::recap()` fait déjà une partie du travail.

---

## 8. Sécurité et abus

| Surface | Mesure |
|---|---|
| Inscription | Limiteur par IP (5 comptes / heure) ; e-mail unique ; pas de captcha tant que le limiteur suffit |
| Connexion | `login_throttling`, message unique, lecture de `isBanned` |
| Mot de passe oublié | Jeton 1 h à usage unique, réponse constante, limiteur |
| Nom de personnage | `ForbiddenNameChecker` **plus** unicité insensible à la casse et aux homoglyphes (**D9**) |
| Multicomptage | La porte de vérification (§3.2) |
| Ban | Effet au login **et** en session courante |

---

## 9. Ce qui se mesure

| Indicateur | Ce qu'il révèle |
|---|---|
| Inscriptions → personnages créés | La friction du tunnel |
| **Pas d'abandon dans le tunnel** | Le nom déjà pris est le suspect n° 1 (**D9**) |
| Personnages créés → acte I terminé | La qualité de l'arc et du coach |
| **Répartition des armes, éléments et métiers choisis** | Si les choix de §5.2 sont réels. Une distribution écrasée sur un métier est le symptôme de **D11** |
| **Répartition des peuples** | Si une capacité de §4.5 est perçue comme supérieure |
| % d'e-mails vérifiés à J+7 | Si la porte est au bon endroit |
| **Retour à J+1 et à J+7** | Tout le reste |

---

## 10. Arbitrages tranchés

| # | Question | Décision |
|---|---|---|
| A1 | Vérification d'e-mail | **Différée**, porte sur l'économie et le social (§3.2) |
| A2 | Compte et personnage | **Tunnel unique en 4 pas** |
| A3 | Apprentissage | **Coach par écran**, pas de visite guidée (§7) |
| A4 | Premier écran après création | **La zone**, jamais le hub |
| A5 | État d'onboarding | L'arc `intro` est la source ; `TutorialStep` en est la projection |
| A6 | « Rester connecté » | **30 jours** |
| A7 | OAuth, compte invité, captcha | **Non au lancement** |
| A8 | Rôle du peuple | Il ne détermine ni le métier, ni l'élément, ni la destination, ni les arbres accessibles (R1) |
| A9 | Foyer d'attache | **Il se gagne, il ne se choisit pas** — amendement à GAME_WORLD §13.1 (R1, §4.4) |
| A10 | Le choix structurant | **L'élément de la première matéria**, pris après un premier combat — jamais dans le tunnel |
| **A11** | **Statistiques de peuple** | **Supprimées.** Le peuple porte **une capacité passive** qui touche ce qu'on *sait*, jamais ce qu'on *produit* (R2, §4.5) |
| **A12** | **Accès aux arbres** | **Catalogue public complet → parchemin → arbre ouvert.** Le parchemin est **un coût, jamais un verrou** : quatre conditions le garantissent (R2, §6.3) |
| **A13** | **Le combat s'enseigne sur deux mannequins** | Le premier n'attaque pas (perdre est impossible), le second riposte sans pouvoir tuer. Combats scriptés : le Fanal reste `safe` (R2, §5.3) |
| **A14** | **La forme de l'acte I** | **Trois tours de la même boucle** — parchemin → arbre → geste — sur l'arme, la matéria et la récolte (R2, §5.1) |
| **A15** | **Le juste milieu** | **Le champ est infini, l'entrée est un acte.** Aucun geste n'est fermé, aucun arbre n'en exclut un autre, et un joueur peut tout mener de front — mais **rien n'est su avant d'avoir été appris** (R3, §6.0) |
| **A16** | **Les actions de base** | Elles sont **concernées** : sans parchemin, on ne mine ni ne forge. Avec deux garde-fous : les personnages existants sont **grand-périsés**, et l'acte I **donne** les trois premiers. **Restent libres sans condition** : marcher, voyager, explorer, parler, ramasser, se battre **à mains nues** (R3, §6.0) |
| **A18** | **L'arme** | **Équiper une arme exige le nœud de maniement de sa famille ; sans quoi, mains nues.** Le nœud est **partagé entre les arbres d'un même registre** (jamais borné par l'élément), il descend au **palier T1**, et l'arme de métier (hache de bûcheron, pioche) relève de l'arbre de métier, pas du combat. Prérequis absolu : **que les mains nues existent** (D13) — R3b, §6.0 bis |
| **A17** | **Les arbres retrouvés** | Des arbres **hors registre**, ouverts par une rencontre que **l'accomplissement** déclenche (finir un arbre). **Latéral jamais vertical**, **cumulatif jamais manqué**, **jamais nécessaire**, et le parchemin retrouvé est **lié** : ce qui circule est l'information, pas l'objet (R3, §6.4) |

---

## 11. Historique des révisions

**R1 (2026-07-29)** — la première rédaction faisait porter au peuple un foyer d'attache
déterminant la destination, le kit, la réputation et **les trois arbres montrés en premier**.
C'était une classe déguisée : un joueur choisissant Elfe se trouvait poussé vers
l'herboristerie sans l'avoir demandé. → A8, A9, A10, et le passage de sept à neuf gestes.

**R2 (2026-07-29)** — trois apports :
- **le peuple porte une capacité** au lieu de statistiques, sous une règle qui la rend sûre
  (elle touche le savoir, jamais la production) → A11 ;
- **l'accès aux arbres passe par un parchemin**, ce qui remplace le modèle « le geste ouvre
  l'arbre » de R1 : plus délibéré, et il rend leur rôle aux PNJ de métier. La tension avec
  GAME_DOMAINS §1 est traitée en §6.3, pas contournée → A12 ;
- **le combat s'enseigne sur deux mannequins**, ce qui règle au passage l'impossibilité de
  combattre au Fanal sans lever son `safe: true` → A13, et refonde l'acte I sur la boucle
  (§5.1) → A14.

**R3 (2026-07-29)** — la doctrine du parchemin trouve sa formulation juste, et gagne un
débouché :
- **le juste milieu est nommé** : *le champ est infini, l'entrée est un acte*. R2 défendait le
  parchemin comme « un coût qui séquence » ; c'était vrai mais faible. La vraie réponse est
  qu'un personnage ne naît pas en sachant miner, forger et lancer des sorts — la doctrine
  interdit qu'un geste soit **fermé**, elle n'a jamais dit qu'il était **déjà acquis** → A15 ;
- **les actions de base entrent dans le champ du parchemin**, avec la frontière qui empêche le
  jeu de devenir une parade de verrous (les verbes élémentaires restent libres) et les deux
  garde-fous de migration → A16 ;
- **les arbres retrouvés** : le parchemin, une fois posé comme mécanisme, ouvre une couche
  hors registre qui donne enfin une récompense au fait de **terminer** un arbre. Ses lois sont
  importées du Répertoire (GAME_WORLD §12.3), pas réinventées → A17 ;
**R3b (2026-07-29)** — le cas de l'arme tranché (§6.0 bis, A18), et une dette découverte en le
vérifiant : **le combat à mains nues n'existe pas** (**D13**) — le repli que la doctrine
suppose est aujourd'hui une exception non gérée. Trois précisions : le nœud de maniement
descend au T1 (c'est l'inverse aujourd'hui), il est **partagé par registre** et jamais borné
par l'élément (sinon porter une hache imposerait un élément), et l'arme de métier relève de
l'arbre de métier.

- **la capacité de l'Humain est resserrée** sur ce qui lui est passé entre les mains (sac ou
  banque), jamais sur l'hôtel des ventes : elle cesse d'être un outil d'arbitrage de marché et
  se gagne en jouant, comme les trois autres.

---

## 12. Restés ouverts

- **Le renommage payant.** Le nom est le seul choix irréversible du tunnel. Gold sink honnête,
  mais il touche l'identité économique et le journal de monde. À instruire avec les gold sinks.
- **Le prix des parchemins**, et leur place dans les boutiques PNJ. Les trois de l'acte I sont
  donnés (§6.3, condition 4) ; le barème des 29 autres reste à poser, avec l'économie.
- **L'éparpillement des points de réveil** (GAME_WORLD §13.1) : rien à faire au lancement.
- **Les tables de noms par peuple**, les textes de la lettre de foyer et les répliques du
  maître d'armes : contenu pur, à écrire avec NAR-20.
- **Le tutoriel de la deuxième semaine.** L'acte I s'arrête à j7 ; le passage critique est
  s3→s6 (GAME_PROGRESSION §3). Domaine de PLAN_RETENTION ; la couture reste à vérifier.
