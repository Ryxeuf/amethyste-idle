# Le compte, le personnage, l'arrivée — cadrage de l'entrée dans le jeu

> **Statut : proposé** · 2026-07-29
> Source de vérité de **tout ce qui se passe avant qu'un joueur soit un joueur** : la
> création du compte, la connexion, la création du personnage, les dix premières minutes
> et l'apprentissage de l'interface.
> Complète (ne remplace pas) : [GAME_PROGRESSION.md](GAME_PROGRESSION.md) §3 « Acte I »
> (ce que le joueur doit avoir au bout d'une semaine), [GAME_WORLD.md](GAME_WORLD.md) §13.1
> (le point de réveil unique et le foyer d'attache par race), §1 (loi de nommage),
> [GAME_ZONE_ACTIONS.md](GAME_ZONE_ACTIONS.md) (l'écran où le joueur atterrit),
> [GAME_DASHBOARD.md](GAME_DASHBOARD.md) (le hub, qui n'est **pas** le premier écran).
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

Trois principes en découlent, et tout le reste s'y ramène.

> **P1 — On joue d'abord, on prouve ensuite.** L'e-mail est demandé à l'inscription mais sa
> vérification est **différée** : elle ne barre pas le jeu, elle barre l'entrée dans
> l'économie et le social. On ne perd personne dans une boîte mail, et les faux comptes
> n'atteignent jamais le marché, les guildes ni les foyers.

> **P2 — Le tunnel est une scène, pas un formulaire.** Compte, nom, peuple, visage :
> quatre pas continus, portés par une seule fiction — l'éveil au Fanal. Le joueur ne doit
> jamais sentir qu'il vient de franchir deux systèmes techniques distincts.

> **P3 — Le jeu s'explique à l'endroit où il se joue.** Pas de visite guidée : chaque écran
> se présente lui-même, une fois, à sa première ouverture, en deux phrases et un geste
> proposé. Ce qui n'est pas encore utilisable ne s'explique pas.

Et une contrainte de forme qui vaut décision : **le premier écran après la création n'est pas
le hub, c'est la zone.** Le hub est un tableau de bord (GAME_DASHBOARD) ; le tableau de bord
d'un joueur qui vient de naître est vide. On n'ouvre pas un jeu sur un écran vide.

---

## 1. Garde-fous — ce qu'on n'invente pas

| # | Contrainte | Origine | Ce qu'elle interdit ici |
|---|---|---|---|
| E1 | **Pas de niveau global** | CLAUDE.md §6 | Afficher « niveau 1 » à la création, une jauge de puissance, un « niveau recommandé » dans le tutoriel |
| E2 | **Pas de classe** | CLAUDE.md §9/§10 | Demander un métier, une classe ou une spécialisation dans le tunnel. Le domaine se choisit **en jouant**, jamais avant |
| E3 | **Pas d'attributs primaires** | GAME_DOMAINS §2 | Une répartition de points à la création |
| E4 | **Point de réveil unique** | GAME_WORLD §13.1 | Choisir sa zone de départ. La race différencie la **destination**, jamais l'origine |
| E5 | **Sorts = matéria uniquement** | CLAUDE.md §10 | Offrir un sort à la création ou en récompense de tutoriel autrement que par une matéria + son accord |
| E6 | **Pas de PvP** | CLAUDE.md §11 | Tout apprentissage du duel, de l'arène, du vol |
| E7 | **1 personnage par compte** (configurable) | CLAUDE.md §12 | Un tunnel qui suppose plusieurs personnages, ou un mur sans issue quand la limite est atteinte |
| E8 | **Loi de nommage** | GAME_WORLD §1 | « Village de Lumière ». Le lieu du réveil est **le Fanal**, le cristal est sous **la Voûte**, le personnage est un **Limpide** |
| E9 | **Le budget d'énergie est égalitaire** | GAME_ZONE_ACTIONS G2 | Un kit ou un bonus de départ qui donne plus d'actions par jour qu'un autre joueur |

**Corollaire de E2, central pour ce document** : Améthyste n'a pas de classe, et c'est sa
force à l'entrée — il n'y a **aucune décision irréversible de gameplay** à prendre dans le
tunnel. Le seul choix définitif est le **nom**, et accessoirement le peuple. Tout le reste
s'apprend et se répare. Le tunnel doit le dire, parce qu'un joueur qui craint de se tromper
lit trois fois chaque option et abandonne une fois sur cinq.

---

## 2. L'existant, sans complaisance

État constaté dans le code au 2026-07-29. Dix dettes, dont trois bloquantes.

| # | Dette | Gravité | Constat |
|---|---|---|---|
| **D1** | **Aucune inscription** | 🔴 bloquante | `RegistrationController::__invoke()` lève `NotFoundHttpException`. Aucun compte ne peut naître |
| **D2** | **Aucune récupération de compte** | 🔴 bloquante | Pas de `symfony/mailer` dans `composer.json`. Un joueur qui perd son mot de passe perd son personnage **définitivement** |
| **D3** | **Login sans protection** | 🔴 bloquante | Aucun `login_throttling`, aucun limiteur dans `rate_limiter.yaml` (il n'en connaît que trois, tous en jeu). `User::isBanned` existe et **n'est lu nulle part** au login |
| **D4** | **Arc d'introduction cassé par le pivot** | 🟠 forte | Trois des sept quêtes d'`intro` valident un `explore` sur `map_id => 1` + coordonnées. Post-ZON-21, `PlayerQuestUpdater::updateExplored()` résout par **zone** : les trois pointent la même zone, et ne se déclenchent qu'**au voyage** — donc jamais pour un joueur qui n'a pas bougé |
| **D5** | **Deux populations de PNJ au même hub** | 🟠 forte | `PnjFixtures` (Gérard le Forgeron, Marie la Herboriste, Claire la Sage — porteurs de l'arc) et `VillageHubPnjFixtures` (Aldric le Forgeron, Iris l'Alchimiste, Lyra la Guide…) coexistent. Le nouveau venu rencontre **deux forgerons** dans le même village |
| **D6** | **Le tutoriel commence par le voyage** | 🟠 forte | `TutorialStep::Movement` est la première étape, et le voyage est **time-gated en temps réel**. Le tout premier geste demandé au joueur est celui qui le fait attendre |
| **D7** | **Deux états d'onboarding concurrents** | 🟡 moyenne | 5 `TutorialStep` d'un côté, 7 quêtes d'arc `intro` de l'autre, sans correspondance. Deux sources de vérité pour « où en est le nouveau » |
| **D8** | **Création de personnage sans fiction ni foyer** | 🟡 moyenne | Formulaire administratif. La race n'affiche que des modificateurs de stats ; le **foyer d'attache** promis par GAME_WORLD §13.1 n'existe nulle part dans le code |
| **D9** | **Unicité du nom naïve et tardive** | 🟡 moyenne | `findOneBy(['name' => $name])` : sensible à la casse, aveugle aux homoglyphes. Et l'erreur ne tombe qu'**après** que le joueur a rempli tout le formulaire |
| **D10** | **Aucun apprentissage d'interface** | 🟡 moyenne | Un bandeau d'objectif en bas du hub, et rien d'autre. Aucun écran ne se présente |

Deux observations de plus, qui ne sont pas des dettes mais des pièges pour la suite :

- **Deux notions d'énergie cohabitent.** `PlayerFactory` initialise `energy`/`maxEnergy` à
  80/100 (stat héritée), tandis que l'énergie d'action du pivot est
  `Player::DEFAULT_MAX_ACTION_ENERGY = 240`, pleine à la création. Un nouveau joueur naît donc
  avec **une journée entière d'énergie** — c'est le bon comportement, mais tout texte
  d'onboarding qui parle d'énergie doit citer la seconde, jamais la première.
- **L'écran de login est hors design system** (rampes `gray-*`/`purple-*` héritées). C'est le
  tout premier écran du jeu, et c'est le seul qui ne ressemble pas au jeu.

---

## 3. Le compte

### 3.1 L'inscription

**Trois champs, pas quatre.** E-mail, mot de passe, acceptation des règles. Pas de pseudo de
compte : `User::username` reste nullable et inutilisé au tunnel — un identifiant de plus
serait un champ à remplir pour rien et une seconde identité à modérer. **Le nom du
personnage est la seule identité publique.**

Pas de confirmation de mot de passe : un champ « afficher le mot de passe » remplace
avantageusement la double saisie, et la récupération par e-mail existe (§3.4) pour rattraper
une faute de frappe.

**Le compte naît non vérifié et pleinement jouable.** `emailVerifiedAt = null` n'empêche rien
de ce qui se joue seul.

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

1. **La porte tombe au bon moment tout seul.** L'arc d'introduction se clôt sur « L'appel des
   guildes » : le joueur atteint la porte exactement quand la fiction le pousse vers les
   autres. On ne demande jamais l'e-mail « parce qu'il le faut », on le demande parce qu'il
   veut entrer quelque part.
2. **Le blocage se lit comme une porte, pas comme une panne.** L'écran dit ce qui est
   verrouillé, pourquoi, et propose un bouton « renvoyer le lien » — jamais un message
   d'erreur générique.
3. **Aucun blocage rétroactif.** Ce qui a été gagné avant vérification reste acquis. On ne
   confisque rien.

Relance : une ligne discrète dans le hub (jamais une modale, jamais un blocage d'écran), plus
un rappel e-mail à J+1 et à J+3. Après quoi, silence.

### 3.3 La connexion

Cinq corrections à l'existant, toutes petites, toutes nécessaires.

| Point | État | Cible |
|---|---|---|
| Bruteforce | aucun | `login_throttling` : 5 essais / 15 min par identifiant, 30 / 15 min par IP |
| Compte banni | `isBanned` jamais lu | Refus au login avec un message clair (jamais un 500, jamais un accès partiel) |
| Divulgation | — | Message d'erreur **unique** : « Identifiants invalides. » Ne jamais révéler qu'un e-mail existe |
| « Rester connecté » | 7 jours | **30 jours.** Un PBBG se joue tous les jours depuis le même appareil ; redemander le mot de passe chaque semaine est une friction pure et sans gain |
| Après login | toujours `/game` | **Selon l'état** : aucun personnage → tunnel ; un personnage → zone si l'acte I est en cours, hub sinon ; plusieurs → écran de sélection |

Cette dernière ligne mérite son mot : envoyer un joueur en plein acte I sur le hub, c'est lui
montrer un tableau de bord vide au lieu de l'endroit où il jouait. Tant que l'arc `intro`
n'est pas clos, **la zone est la maison**.

### 3.4 Le mot de passe oublié

C'est **D2**, la dette la plus dangereuse du document : aujourd'hui, perdre son mot de passe,
c'est perdre son personnage, son inventaire, sa guilde et sa place dans un foyer. Aucune
rétention ne survit à ça.

Exigences : `symfony/mailer` installé (via Docker, règle 1) ; jeton à usage unique, valable
**1 heure**, un seul actif par compte ; **réponse identique** que le compte existe ou non ;
limiteur de débit sur la demande ; invalidation de toutes les sessions à la réinitialisation.

### 3.5 Ce qu'on ne fait pas

- **Pas d'OAuth au lancement** (Google/Discord). Une dépendance externe sur la porte
  d'entrée, pour un gain qui n'existe que si le trafic existe. À reconsidérer plus tard.
- **Pas de compte invité.** Séduisant sur le papier, mais il crée des personnages orphelins,
  une purge à écrire, et une question insoluble : que devient un invité qui a fait monter un
  foyer ?
- **Pas de captcha visible** tant que le limiteur de débit suffit. Un captcha est une taxe
  sur les honnêtes.

---

## 4. La création de personnage

### 4.1 Ce qu'on ne demande pas

Pas de classe (E2), pas de zone de départ (E4), pas de stats à répartir (E3), pas de sexe en
champ séparé — il est porté par le corps choisi, et un champ de moins est un pas de moins.

### 4.2 Le tunnel en quatre pas

Un pas = un écran, **une** décision, une phrase de fiction. Barre de progression visible,
retour arrière possible partout, rien de perdu.

**Pas 1 — Le compte.** E-mail, mot de passe. *« On ne se souvient de personne avant le
Fanal. »*

**Pas 2 — Le nom.** 3 à 16 caractères, lettres, espaces et tirets (contrainte existante).
Deux corrections à `CharacterCreateType` :
- **vérification d'unicité au fil de la frappe** (indicateur vert/rouge sous le champ), parce
  que découvrir que « Aldric » est pris **après** avoir choisi son peuple, sa coiffure et sa
  tenue est le point d'abandon le plus prévisible du tunnel ;
- unicité **insensible à la casse et aux homoglyphes**, sans quoi `Aldric`, `aldric` et
  `Аldric` (А cyrillique) coexistent — une porte ouverte à l'usurpation dans un jeu où le nom
  est l'identité économique.

Un bouton **« proposer un nom »** tirant dans une table par peuple : il fait gagner plus de
tunnels qu'il n'en coûte à écrire.

**Pas 3 — Le peuple.** C'est le pas qui porte tout le poids, et aujourd'hui il ne porte rien
qu'un modificateur de statistiques.

> **Inversion actée** : ce que l'écran montre en premier n'est pas le bonus, c'est **qui vous
> recueille et où vous irez**.

| Peuple | Qui vous recueille | Première destination | Les trois arbres montrés d'abord |
|---|---|---|---|
| **Nain** | les mineurs | Mines profondes | Mineur · Forgeron · Défenseur |
| **Elfe** | les herboristes | Forêt des murmures | Herboriste · Alchimiste · Archer |
| **Orc** | les chasseurs | Crête de Ventombre / Dunes d'Ambre | Dépeceur · Tanneur · Chasseur |
| **Humain** | les marchands | libre — le Fanal est sa maison | choisis au premier voyage |

Les modificateurs de statistiques restent affichés, **en second**, en une ligne factuelle
(ils ne sont pas anodins : +5 PV sur 20 est un quart de vie en plus). Mais ils cessent d'être
l'argument principal, parce qu'ils ne sont pas la vraie différence.

> **La règle qui rend ce choix sûr : le foyer d'attache n'ouvre ni ne ferme rien.** Un Nain
> peut devenir herboriste, un Elfe forgeron. Le foyer d'attache décide de quatre choses et
> d'aucune autre : la **première destination**, le **kit de départ**, une **réputation de
> départ** d'un cran chez la faction correspondante, et **les trois ou quatre arbres montrés
> en premier**.

Cette dernière est la seule parade sérieuse au risque n° 1 de l'acte I : *32 arbres visibles
au jour 1, c'est un mur* (GAME_PROGRESSION §3). Le foyer d'attache n'est pas qu'une couleur
narrative — c'est **le filtre par défaut de l'écran des arbres**, avec un « voir les 32 »
toujours accessible et jamais mis en avant.

**Pas 4 — Le visage.** Corps, coiffure, couleur, tenue, avec aperçu vivant. Et une mention
explicite : *« Tout ceci se change quand vous voulez. »* C'est vrai (`/game/character/customize`
existe), et le dire accélère le pas le plus long du tunnel en supprimant l'angoisse du choix.

### 4.3 Réversible ou non — à afficher, honnêtement

| Choix | Réversible ? | Pourquoi |
|---|---|---|
| Apparence | **Oui**, à tout moment | Aucune conséquence de jeu |
| Peuple | Non | Modificateurs de stats, réputation de départ |
| Nom | Non | Identité publique : marché, guilde, journal de monde |
| Foyer d'attache | Non — **et sans importance** | Il n'ouvre ni ne ferme rien (§4.2) |

Un tunnel qui dit clairement ce qui engage est un tunnel qu'on traverse vite.

### 4.4 La limite d'un personnage

`app.max_players_per_user: 1`. L'écran de limite atteinte existe et dit « non ». Il doit dire
**quoi faire** — c'est la règle des états vides du design system : « Un seul Limpide par
compte pour l'instant », plus un lien vers le personnage existant.

Le second personnage rejoue l'arc d'introduction intégralement (règle 12 ; progression portée
par `Player`) : rien à changer, mais le coach par écran (§6), lui, est **par personnage** et
doit donc se rejouer aussi. C'est voulu : un second personnage d'un autre peuple découvre
d'autres arbres.

---

## 5. Les dix premières minutes

### 5.1 Le fil

Le joueur sort du tunnel avec 240 points d'énergie — **une journée entière**. Il n'a donc
aucun budget à ménager, et l'onboarding ne doit surtout pas le lui faire croire. À l'inverse,
il ne doit jamais attendre : **rien de time-gaté avant la fin de l'acte I**.

Un écran d'éveil, un paragraphe, un seul bouton (« Ouvrir les yeux ») → **l'écran de zone**.

### 5.2 Le nouvel ordre des étapes

L'ordre actuel (déplacement → combat → inventaire → quêtes → craft) place en premier le seul
geste qui fait attendre (**D6**) et **ne mentionne jamais la matéria** — c'est-à-dire le cœur
du jeu (règle 10) et le build du personnage (GAME_PROGRESSION §3 bis).

Ordre proposé, sept étapes, une par quête de l'arc `intro` :

| # | Étape | Ce qu'elle enseigne | Pourquoi ici |
|---|---|---|---|
| 1 | **Récolter** | L'action de zone, le coût en énergie, le filon partagé | ⚡3, immédiat, et **une récolte n'échoue jamais** (GAME_ZONE_ACTIONS) : le premier geste du jeu doit réussir |
| 2 | **Explorer & combattre** | Le tour par tour, l'attaque d'arme gratuite | Le combat ne coûte pas d'énergie (G3) : on peut échouer sans rien perdre |
| 3 | **Le butin et le sac** | Ramasser, équiper l'arme | Enchaîne naturellement sur la victoire |
| 4 | **La matéria** | La trouver, l'accorder, la sertir, **lancer le sort** | **L'étape qui manque aujourd'hui.** Sans elle, le joueur ne sait pas qu'il a un build |
| 5 | **L'arbre** | Dépenser son premier point de domaine | Trois arbres montrés, pas trente-deux (§4.2) |
| 6 | **L'atelier** | Le craft, la première potion | Le craft ne coûte pas d'énergie : un geste « gratuit » qui montre l'économie |
| 7 | **Le voyage** | Partir vers le foyer d'attache | Time-gaté, donc **en dernier** : c'est la porte de sortie de l'acte I, pas son entrée |

L'étape 4 est non négociable. Un joueur qui termine l'acte I sans avoir serti une matéria n'a
pas rencontré le jeu — il a rencontré un clone de PBBG générique.

### 5.3 Une seule source de vérité

**D7** doit se refermer : `TutorialStep` et l'arc `intro` disent tous deux « où en est le
nouveau », sans se parler. L'arc de quêtes est la source (il porte le texte, les PNJ, les
récompenses, et il est déjà rejouable par personnage) ; `TutorialStep` devient une
**projection** de l'avancement de l'arc, utilisée pour l'affichage et le surlignage. Aucun
état d'onboarding ne s'écrit à deux endroits.

Conséquence : « passer le tutoriel » et « abandonner l'arc » deviennent le même geste, avec
la même conséquence — et le succès `tutorial-complete` reste attaché à la clôture de l'arc.

### 5.4 Ce que l'acte I doit laisser

Reprise stricte de GAME_PROGRESSION §3, comme contrat vérifiable à la sortie de la semaine 1 :

- une **arme dotée d'un sort** et un soin (kit T1 échangeable) ;
- **un domaine de combat** commencé et **un métier de récolte** commencé ;
- **sa première matéria en main**, accordée et sertie ;
- son **foyer d'attache** identifié et une première destination ;
- et — ajout de ce document — **un e-mail vérifié ou une porte clairement identifiée**.

### 5.5 Les corrections de contenu à faire au passage

Elles ne sont pas cosmétiques : ce sont les textes que 100 % des joueurs lisent.

- **D4** : les trois quêtes `explore` de l'arc ciblent des coordonnées sur une carte morte.
  Elles doivent devenir des objectifs du pivot : parler à un PNJ, réussir une récolte, mener
  un combat. Aucune quête d'introduction ne doit dépendre de `map_id`.
- **D5** : deux forgerons au même hub. Une seule population de PNJ au Fanal, et les porteurs
  de l'arc en font partie.
- **E8** : le Fanal, la Voûte, le Limpide (couvert par NAR-20, à faire **en même temps** que
  la refonte du tunnel — pas après, sinon le tunnel neuf naît hors canon).

---

## 6. Le coach par écran

### 6.1 Le principe

Pas de visite guidée. **Un écran jamais ouvert se présente lui-même, une fois.** Un encart en
haut de l'écran : deux phrases, le geste proposé, son coût en énergie, une croix. Il ne
revient jamais seul et se relit depuis l'aide (crochet naturel vers `/wiki`, WIK-02).

Trois règles font toute la différence entre un coach et une pop-up publicitaire :

> **C1 — Le coach ne parle jamais d'un système que le joueur ne peut pas encore utiliser.**
> L'encart du marché ne s'affiche pas avant qu'il ait quelque chose à vendre.

> **C2 — Le coach dit toujours ce que ça coûte.** Le coût en énergie de chaque action est
> l'information la plus utile du jeu et la moins expliquée aujourd'hui.

> **C3 — Le coach se déclenche à l'arrivée, jamais au temps écoulé.** Aucun compte à rebours,
> aucune relance, aucune séquence qui se poursuit pendant qu'on lit.

### 6.2 Les écrans coachés

Huit à l'ouverture, deux différés. Pas davantage : au-delà, ça redevient une visite guidée.

| Écran | Ce que l'encart dit | Déclenchement |
|---|---|---|
| Zone | Ce qu'on peut tenter ici, et ce que ça coûte | 1re ouverture |
| Combat | Attaque gratuite, matéria, fuite | 1er combat |
| Inventaire | Sac, équipement, **emplacements de matéria** | 1re ouverture |
| Arbres | Le filtre du foyer d'attache, et « voir les 32 » | 1re ouverture |
| Quêtes | Arc en cours, quotidiennes | 1re ouverture |
| Artisanat | Recettes, ce qui ne coûte pas d'énergie | 1re ouverture |
| Hub | Ce qui attend, la semaine, la reprise | 1re ouverture **après** l'acte I |
| Carte du monde | Le graphe, les durées de voyage | 1re ouverture |
| *Marché* | Vendre son surplus | 1re fois qu'il possède un objet vendable **et** l'e-mail vérifié (C1) |
| *Guilde* | Pourquoi on en rejoint une | Fin de l'acte I |

Le hub arrive **après** l'acte I et c'est délibéré : avant, il n'a rien à montrer (§0).

### 6.3 Stockage

Un champ `Player.seenCoachMarks` (tableau JSON de slugs). Pas de nouvelle entité, pas de
nouvelle table : la donnée est un ensemble de drapeaux par personnage, et rien d'autre.

### 6.4 Ce qui n'est pas du coach : le retour après absence

Un joueur qui revient après sept jours ne veut pas apprendre l'interface, il veut savoir **ce
qui a changé**. `PlayerHubDigest::recap()` fait déjà une partie du travail. À traiter comme
un sujet voisin mais distinct, et à ne jamais confondre : réafficher un coach à un joueur qui
revient est la meilleure façon de le faire repartir.

---

## 7. Sécurité et abus

| Surface | Mesure |
|---|---|
| Inscription | Limiteur par IP (5 comptes / heure) ; e-mail unique ; pas de captcha tant que le limiteur suffit |
| Connexion | `login_throttling` (§3.3), message d'erreur unique, lecture de `isBanned` |
| Mot de passe oublié | Jeton 1 h à usage unique, réponse constante, limiteur de débit |
| Nom de personnage | `ForbiddenNameChecker` (existant) **plus** unicité insensible à la casse et normalisation des homoglyphes (**D9**) |
| Multicomptage | La porte de vérification (§3.2) : marché, guildes, chat et **livraison aux foyers** hors de portée d'une ferme de comptes |
| Ban | Effet au login **et** en session courante |

---

## 8. Ce qui se mesure

Sans instrumentation, on répare à l'aveugle. Cinq chiffres suffisent, et le dernier décide de
tout.

| Indicateur | Ce qu'il révèle |
|---|---|
| Inscriptions → personnages créés | La friction du tunnel |
| **Pas d'abandon dans le tunnel** | Le nom déjà pris est le suspect n° 1 (**D9**) |
| Personnages créés → acte I terminé | La qualité de l'arc et du coach |
| % d'e-mails vérifiés à J+7 | Si la porte est placée au bon endroit |
| **Retour à J+1 et à J+7** | Tout le reste. Un tunnel parfait avec un J+7 nul n'a rien réglé |

---

## 9. Arbitrages

### Tranchés (2026-07-29)

| # | Question | Décision |
|---|---|---|
| A1 | Vérification d'e-mail | **Différée**, porte sur l'économie et le social (§3.2) |
| A2 | Compte et personnage | **Tunnel unique en 4 pas** ; écrans séparés conservés pour le 2ᵉ personnage |
| A3 | Apprentissage | **Coach par écran** à la première visite, pas de visite guidée (§6) |
| A4 | Premier écran après création | **La zone**, jamais le hub (§0) |
| A5 | Rôle du peuple | Porte le **foyer d'attache** ; n'ouvre ni ne ferme aucun contenu (§4.2) |
| A6 | Ordre du tutoriel | Récolte d'abord, **matéria en 4ᵉ**, voyage en dernier (§5.2) |
| A7 | État d'onboarding | L'arc `intro` est la source ; `TutorialStep` en est la projection (§5.3) |
| A8 | Durée de « rester connecté » | **30 jours** (§3.3) |
| A9 | OAuth, compte invité, captcha | **Non au lancement** (§3.5) |

### Restés ouverts

- **Le renommage payant.** Le nom est irréversible (§4.3). Un renommage tardif contre monnaie
  est un gold sink classique et honnête — mais il touche l'identité économique et le journal
  de monde. Hors scope, à instruire avec les gold sinks.
- **L'éparpillement des points de réveil.** GAME_WORLD §13.1 prévoit que des foyers d'attache
  deviennent de vrais points de réveil quand la population grossit, indexé comme le quota de
  Crue. Rien à faire au lancement ; à écrire quand le problème sera un problème de succès.
- **Les tables de noms proposés par peuple** (§4.2). Contenu pur, à écrire avec NAR-20.
- **Le tutoriel de la deuxième semaine.** L'acte I s'arrête à j7 ; le passage critique est
  s3→s6 (GAME_PROGRESSION §3). Rien dans ce document ne couvre ce moment — c'est le domaine de
  PLAN_RETENTION, et il faudra vérifier que la couture tient.
