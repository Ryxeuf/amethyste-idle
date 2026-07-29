# Plan — Compte, personnage et arrivée en jeu

> **Numérotation :** les jalons de **ce** document sont préfixés **ONB-** (Onboarding).
> Ils n'entrent **pas** en conflit avec les numéros de la roadmap globale
> (`SPRINT_*.md`) ni avec les jalons **NAR-** / **ZON-** / **ECO-** / **FOY-** / **DOM-** /
> **FAC-** / **RET-** / **REP-** / **WIK-**.

> Décline [../GAME_ONBOARDING.md](../GAME_ONBOARDING.md) — la source de vérité de tout ce
> qui se passe **avant qu'un joueur soit un joueur**.
>
> Les décisions qui commandent ce plan : vérification d'e-mail **différée** derrière une porte
> économique et sociale (A1) ; **tunnel unique en quatre pas** (A2) ; **coach par écran** et
> non visite guidée (A3) ; et surtout, depuis la révision R1 — **aucune décision de build dans
> le tunnel** : le peuple ne détermine ni le métier, ni l'élément, ni la destination, ni les
> arbres visibles (A9) ; le foyer d'attache **se gagne par les gestes** (A10) ; les 32 arbres
> se lisent en deux couches, la carte *(le savoir)* et mes arbres *(le faire)* (A11).
>
> Le plan s'appuie sur ce qui existe et n'invente aucun moteur : `CharacterController`,
> `PlayerFactory`, `TutorialManager`, l'arc `intro` (NAR-03/04), `PlayerDomainHelper`,
> `PlayerHubDigest`, `ForbiddenNameChecker`, le design system Parchemin.

## Vue d'ensemble

**17 jalons** (**ONB-01** à **ONB-17**) organisés en 5 pistes.

| Code | Sujet (résumé) | Taille | Priorité |
|------|----------------|--------|----------|
| ONB-01 | Inscription — le compte peut naître (ferme D1) | M | ★★★ |
| ONB-02 | Mailer + mot de passe oublié (ferme D2) | M | ★★★ |
| ONB-03 | Durcissement de la connexion (ferme D3) | S | ★★★ |
| ONB-04 | Vérification d'e-mail différée et sa porte | M | ★★★ |
| ONB-05 | Le tunnel en 4 pas — coquille et fil narratif | M | ★★★ |
| ONB-06 | Le nom : unicité robuste et immédiate (ferme D9) | S | ★★★ |
| ONB-07 | Ce que le peuple apporte — et ce qu'il cesse d'apporter (ferme D12) | S | ★★ |
| ONB-08 | Les 32 arbres en deux couches : la carte et mes arbres | M | ★★★ |
| ONB-09 | Le périmètre de l'acte I : cinq récoltes et un combat (ferme D11) | M | ★★★ |
| ONB-10 | Les neuf gestes, la matéria au choix (ferme D6) | M | ★★★ |
| ONB-11 | Le foyer d'attache constaté à la clôture de l'acte I (ferme D8) | M | ★★ |
| ONB-12 | Une seule source d'état d'onboarding (ferme D7) | S | ★★ |
| ONB-13 | Réparer les quêtes `explore` de l'arc (ferme D4) | S | ★★★ |
| ONB-14 | Une seule population de PNJ au Fanal (ferme D5) | M | ★★ |
| ONB-15 | Le coach par écran (ferme D10) | M | ★★ |
| ONB-16 | Écrans d'entrée au design system | S | ★★ |
| ONB-17 | Instrumentation du tunnel + tests de contrat | M | ★★ |

```
Piste A — Le compte existe   : ONB-01 → ONB-02 → ONB-03 → ONB-04
Piste B — Le tunnel          : ONB-05 → ONB-06, ONB-07        (06 et 07 parallelisables)
Piste C — Le choix est reel  : ONB-08, ONB-09 → ONB-10 → ONB-11
Piste D — L'acte I repare    : ONB-12, ONB-13, ONB-14         (parallelisables)
Piste E — Apprentissage & preuve : ONB-15, ONB-16, ONB-17
```

**Ordre de valeur/effort** : `Piste A → ONB-13 → Piste C → Piste B → Piste D → Piste E`.

La piste A n'est pas prioritaire par confort : **tant qu'elle n'est pas livrée, le jeu n'a
littéralement aucun joueur possible**. Les trois dettes rouges y sont concentrées, et ONB-02
suit immédiatement ONB-01 — un jeu où perdre son mot de passe signifie perdre son personnage
ne retient personne.

**ONB-13 remonte juste après**, malgré sa taille S : l'arc d'introduction est **bloqué dès sa
première étape** (trois quêtes `explore` par `map_id` que le pivot a rendues indéclenchables).
Ouvrir l'inscription sur un tutoriel mort n'a pas de sens.

**Puis la piste C avant la piste B**, ce qui peut surprendre : les choix de l'acte I (quelle
récolte, quel élément, quel arbre) sont ce qui rend le tunnel court et léger. Tant que la zone
de départ n'expose qu'une seule récolte (**D11**), « au choix » est un mensonge et le tunnel
devra reprendre le rôle d'orienter — c'est-à-dire redevenir ce que la révision R1 a écarté.

**Coupe minimale jouable** : ONB-01 + ONB-02 + ONB-03 + ONB-13. À ce point, un inconnu peut
créer un compte, le récupérer, et traverser l'acte I sans buter sur une quête morte.

**Croisements avec les autres plans** :

| Jalon voisin | Lien |
|---|---|
| **NAR-20** (le réveil au Fanal) | ONB-10, ONB-11, ONB-13 et ONB-14 touchent les mêmes textes et les mêmes PNJ. **À faire dans la même vague**, sinon les écrans neufs naissent avec les anciens noms. ⚠️ NAR-20 prévoit une « lettre du foyer d'attache **selon la race** » — ONB-11 la déplace à la clôture de l'acte I et la dérive des **gestes**, pas de la race (A10) |
| **PLAN_ZONES** | ONB-09 est une exigence de **données de zone** (cinq récoltes T0 dans le périmètre de l'acte I, zone voisine non sûre). À instruire avec ZON |
| **DOM-01→09** | ONB-08 s'appuie sur la borne `element × registre` déjà portée par `Domain` : la roue existe en données, il reste à la montrer |
| **WIK-02** (`/wiki`) | ONB-15 : « relire cette explication » pointe vers le wiki. Sans WIK-02, dégradation acceptable |
| **RET-08→10** (le tableau du lundi) | ONB-15 : le coach du hub arrive **après** l'acte I, sur un hub qui aura son bloc « La semaine » |
| **FAC-01** (faction portée unique) | ONB-11 : le cran de réputation du foyer d'attache doit respecter la règle |

---

## Piste A — Le compte existe (séquentiel)

### ONB-01 — Inscription : le compte peut naître (M | ★★★ | CRITIQUE)
> Ferme **D1**. `RegistrationController::__invoke()` lève aujourd'hui un `NotFoundHttpException` :
> aucun compte ne peut être créé hors fixtures.
- [ ] `RegistrationFormType` : **trois champs** — e-mail, mot de passe, acceptation des règles.
      Pas de pseudo de compte, pas de confirmation de mot de passe (bouton « afficher »)
- [ ] Contraintes : e-mail valide et unique, mot de passe ≥ 10 caractères, hachage `auto`
- [ ] `emailVerifiedAt` nullable sur `User` (+ migration idempotente) — le compte naît **non
      vérifié et pleinement jouable**
- [ ] Authentification automatique puis redirection vers le tunnel (ONB-05 ; d'ici là,
      `app_character_create`)
- [ ] Limiteur : 5 comptes / heure / IP
- [ ] Lien « créer un compte » depuis la connexion et depuis l'accueil public
- [ ] Tests : création nominale, e-mail pris, mot de passe trop court, limiteur

### ONB-02 — Mailer et mot de passe oublié (M | ★★★ | CRITIQUE)
> Ferme **D2** : aujourd'hui, perdre son mot de passe revient à perdre son personnage, son
> inventaire, sa guilde et sa place dans un foyer.
> Prérequis : ← ONB-01
- [ ] Installer `symfony/mailer` **dans Docker** (règle 1) ; `MAILER_DSN` en variable
      d'environnement, transport `null://` en test
- [ ] Demande : **réponse identique** que le compte existe ou non ; limiteur de débit
- [ ] Jeton à usage unique, **1 heure**, un seul actif par compte, stocké haché
- [ ] Réinitialisation + **invalidation de toutes les sessions**
- [ ] Gabarits d'e-mail au ton du jeu, en français, avec repli texte
- [ ] Tests : jeton expiré, rejoué, compte inexistant (réponse constante), invalidation

### ONB-03 — Durcissement de la connexion (S | ★★★ | CRITIQUE)
> Ferme **D3**. Le firewall n'a aucun garde-fou et `User::isBanned` n'est lu nulle part.
- [ ] `login_throttling` : 5 essais / 15 min par identifiant, 30 / 15 min par IP
- [ ] Lecture de `isBanned` au login **et** en session courante, message clair
- [ ] Message d'erreur **unique** (« Identifiants invalides »)
- [ ] `remember_me.lifetime` : 7 j → **30 j**
- [ ] Redirection post-login **selon l'état** : aucun personnage → tunnel ; un personnage →
      zone si l'arc `intro` est en cours, hub sinon ; plusieurs → sélection
- [ ] Tests : throttling, banni refusé, redirection par état

### ONB-04 — La vérification différée et sa porte (M | ★★★ | HAUTE)
> Décision A1 : la vérification ne barre pas le jeu, elle barre **ce qui sort du joueur vers
> les autres** (GAME_ONBOARDING §3.2).
> Prérequis : ← ONB-02
- [ ] E-mail de vérification, jeton renvoyable ; `emailVerifiedAt` posé au clic
- [ ] **Un seul point de décision** — un `EmailVerificationGate` consulté par toutes les
      portes, jamais un `if` recopié dans chaque contrôleur
- [ ] Portes fermées : chat, hôtel des ventes (achat **et** vente), échoppe joueur, don,
      guilde, groupe, donjon, messages privés, amis, **livraison d'une commission à un foyer**
- [ ] Écran de porte : ce qui est verrouillé, pourquoi, « renvoyer le lien »
- [ ] Rappel : une ligne au hub (jamais une modale), e-mail à J+1 et J+3, puis silence
- [ ] **Aucun blocage rétroactif**
- [ ] Tests : chaque porte, jeton rejoué, absence d'effet rétroactif, et un test de contrat
      qui échoue si une porte contourne le point de décision unique

---

## Piste B — Le tunnel

### ONB-05 — Le tunnel en quatre pas (M | ★★★ | HAUTE)
> Décision A2. Inscription et création sont aujourd'hui deux formulaires administratifs
> d'affilée. Le joueur ne doit jamais sentir qu'il franchit deux systèmes.
> Prérequis : ← ONB-01
- [ ] Séquence continue : **compte → nom → peuple → visage**, un écran par pas, une décision
      par écran, une phrase de fiction par écran
- [ ] **Aucune décision de build** : ni métier, ni élément, ni destination (P3, A9)
- [ ] Barre de progression, retour arrière partout, aucune saisie perdue
- [ ] Écran d'éveil final : un paragraphe, **un seul bouton**, vers **l'écran de zone** —
      jamais le hub (A4)
- [ ] Afficher ce qui engage : **le nom, et rien d'autre** (§4.3)
- [ ] Les écrans existants (`create`, `select`, `customize`) restent la voie du 2ᵉ personnage
- [ ] `limit_reached` : dit **quoi faire**, pas seulement « non »
- [ ] Tests : parcours complet, retour arrière, reprise d'un tunnel interrompu

### ONB-06 — Le nom : unicité robuste et immédiate (S | ★★★ | HAUTE)
> Ferme **D9**. `findOneBy(['name' => $name])` est sensible à la casse et aveugle aux
> homoglyphes ; et l'erreur ne tombe qu'après que le joueur a tout rempli.
> Prérequis : ← ONB-05
- [ ] Unicité **insensible à la casse** et normalisation des homoglyphes — colonne normalisée
      + index unique
- [ ] Vérification **au fil de la frappe**, sans révéler autre chose que « libre / pris »
- [ ] `ForbiddenNameChecker` appliqué à la forme normalisée
- [ ] Bouton « proposer un nom » par peuple (contenu ← NAR-20)
- [ ] Tests : casse, homoglyphes, nom interdit, course entre deux créations simultanées

### ONB-07 — Ce que le peuple apporte, et ce qu'il cesse d'apporter (S | ★★ | MOYENNE)
> Ferme **D12**. Les modificateurs actuels ne sont pas équilibrés — Humain `0/0/0/0` face à
> Orc `+8 vie` (soit **+40 % de survie** sur une base de 20) : l'Humain est **strictement
> dominé**. Et surtout, c'est un arbitrage de puissance demandé au pas 3, au moment où le
> joueur en sait le moins, dans un jeu qui a renoncé aux classes (E2) et aux attributs (E3).
> ⚠️ **Décision de conception à confirmer avant de coder** — cf. GAME_ONBOARDING §4.5.
- [ ] Trancher : **(a) aucune statistique** — le peuple porte l'apparence et de la
      reconnaissance sociale (PNJ qui saluent, noms proposés, entrée de Codex, répliques) —
      **recommandé** ; ou **(b) un trait qualitatif**, jamais quantitatif (une information de
      plus, jamais du débit ni des dégâts — garde-fou G2)
- [ ] Écran du peuple : qui vous êtes et d'où vous venez. **Jamais** un métier, une
      destination ni des arbres (A9)
- [ ] Si (a) : retirer les modificateurs, et vérifier qu'aucun calcul n'en dépendait
- [ ] Si (b) : rééquilibrer pour qu'aucun peuple ne soit dominé
- [ ] Tests : les quatre peuples équivalents en puissance ; aucun contenu gaté par la race

---

## Piste C — Le choix est réel

### ONB-08 — Les 32 arbres en deux couches (M | ★★★ | HAUTE)
> Décision A11, application directe de la doctrine des trois couches de GAME_DOMAINS.
> Aujourd'hui `PlayerDomainHelper::getDomains()` renvoie les 32 et l'écran les empile : c'est
> le **risque n° 1 de l'acte I** (GAME_PROGRESSION §3). Un filtre par peuple cacherait du jeu
> (E10) et orienterait (E2) — on fait l'inverse.
> Prérequis : ∅ (la borne `element × registre` est déjà en données, DOM-01)
- [ ] **La carte des domaines** *(le savoir, jamais borné)* : les **32**, présentés comme la
      roue élément × registre (8 × 3 en combat, + 5 récoltes, + 4 artisanats). Une grille
      lisible sur un écran, consultable dès la première minute, **sans condition**
- [ ] **Mes arbres** *(le faire)* : l'écran par défaut ne contient que les arbres **où le
      joueur a posé un geste**. Il mine → Mineur s'ouvre ; il lance une matéria de feu → les
      trois arbres de feu s'ouvrent
- [ ] L'ouverture d'un arbre est **notifiée comme une découverte**, pas subie comme un
      déblocage
- [ ] Aucun arbre n'est inaccessible : c'est une couche de lecture, jamais une porte
- [ ] Tests : la carte contient toujours les 32 ; « mes arbres » démarre à zéro et s'ouvre par
      les gestes ; aucun arbre rendu inatteignable

### ONB-09 — Le périmètre de l'acte I : cinq récoltes et un combat (M | ★★★ | HAUTE)
> Ferme **D11**, et c'est la **condition matérielle** de tout le reste. Le Fanal n'expose
> aujourd'hui que **deux filons, tous deux d'herboristerie** (thym, lavande), et il est
> `safe: true` → `ExploreService` force `mob: 0` : **aucun combat n'y est possible**. Sans ce
> jalon, « récolte au choix » est un mensonge et tout le monde devient herboriste.
> Prérequis : ∅ (données de zone) ; à instruire avec **PLAN_ZONES**
- [ ] **Les cinq récoltes représentées** dans le périmètre de l'acte I, au palier T0 :
      herboristerie (existe), minerai, bois, pêche, dépeçage
- [ ] **Le Fanal reste sûr** — « ici, rien ne mord » est sa définition, on n'y touche pas
- [ ] L'acte I se joue sur **deux zones** : le Fanal et un voisin immédiat non sûr
- [ ] **Le premier voyage est offert** : durée nulle, une seule fois, narrativement accompagné.
      Le joueur apprend d'abord le voyage **comme geste**, puis (étape 8) comme **temps réel**
- [ ] Vérifier la cohérence avec le plancher T1 PNJ et les lois de zone (GAME_ZONES)
- [ ] Tests : les cinq professions atteignables sans attendre ; un combat atteignable sans
      attendre ; le Fanal reste `safe`

### ONB-10 — Les neuf gestes, et la matéria au choix (M | ★★★ | HAUTE)
> Ferme **D6** et le trou le plus grave du tutoriel actuel : il **ne mentionne jamais la
> matéria** — la seule source d'actions de combat (règle 10) et le build du personnage. Et il
> commence par le voyage, seul geste time-gaté.
> Prérequis : ← ONB-08, ONB-09, ONB-13 ; croise NAR-20
- [ ] Les neuf gestes dans l'ordre acté (GAME_ONBOARDING §5.2) : récolte → exploration →
      combat → butin & sac → **matéria** → arbre → artisanat → voyage → **expédition**
- [ ] **Le joueur choisit le complément à chaque geste** (P4) : quelle récolte, quel élément,
      quel arbre, quelle destination, quelle expédition
- [ ] Étape 5 : **huit matérias de départ, une par élément**, présentées après le premier
      combat. Accord à 0 point expliqué. Le texte doit dire que **ce choix n'engage à rien** —
      la matéria est abondante à la base (GAME_WORLD §2.1)
- [ ] Étape 8 : le voyage annoncé comme coûtant du **temps réel** — première attente du jeu
- [ ] Étape 9 : l'expédition **clôt** l'acte I — « comment quitter le jeu en le laissant
      travailler », la leçon qui fait revenir au jour 2
- [ ] Une étape = **une** quête de l'arc `intro`
- [ ] Tests : la matéria est garantie et lancée au moins une fois ; aucune étape avant la 8ᵉ
      n'est time-gatée ; les cinq récoltes et les huit éléments sont réellement proposés

### ONB-11 — Le foyer d'attache constaté à la clôture de l'acte I (M | ★★ | MOYENNE)
> Ferme **D8** et applique l'**amendement à GAME_WORLD §13.1** (A10) : le foyer d'attache ne
> se choisit pas, **il se gagne**. C'est la zone où le joueur a réellement travaillé.
> Prérequis : ← ONB-10 ; croise NAR-20 (la lettre) et FAC-01 (faction portée unique)
- [ ] Mesure de l'activité par zone pendant l'acte I ; le foyer est constaté à la clôture
- [ ] Défaut sans activité distinctive : **le Fanal** (ce que le canon réservait à l'Humain
      devient le cas général)
- [ ] Ce qu'il apporte, et **rien d'autre** : une **lettre** suggérant une première
      destination, **un PNJ qui vous connaît**, **un cran de réputation**, **une ligne au
      journal**
- [ ] **Aucun contenu ouvert, aucun contenu fermé, aucun arbre mis en avant, aucun bonus de
      rendement** — à verrouiller par un test : c'est la garantie qui rend le mécanisme sûr
- [ ] Tests : foyer dérivé des gestes et non de la race ; défaut au Fanal ; aucun contenu gaté

---

## Piste D — L'acte I répare (parallélisable)

### ONB-12 — Une seule source d'état d'onboarding (S | ★★ | MOYENNE)
> Ferme **D7** : 5 `TutorialStep` d'un côté, 7 quêtes d'arc de l'autre, sans correspondance.
> Prérequis : ← ONB-10
- [ ] L'arc `intro` devient la **source** ; `TutorialStep` devient une **projection**
- [ ] « Passer le tutoriel » et « abandonner l'arc » deviennent le même geste
- [ ] Le succès `tutorial-complete` reste attaché à la clôture de l'arc
- [ ] Tests : aucun état d'onboarding écrit à deux endroits (test de contrat)

### ONB-13 — Réparer les quêtes `explore` de l'arc (S | ★★★ | HAUTE)
> Ferme **D4**. Trois des sept quêtes d'`intro` valident un `explore` sur `map_id => 1` et des
> coordonnées. Post-ZON-21, `PlayerQuestUpdater::updateExplored()` résout par **zone** et ne se
> déclenche qu'au voyage : elles ne tombent **jamais** pour un joueur qui n'a pas bougé.
> **L'arc d'introduction est bloqué dès sa première étape.**
- [ ] Convertir les trois objectifs en objectifs du pivot : parler à un PNJ, réussir une
      récolte, mener un combat
- [ ] **Aucune quête d'introduction ne dépend de `map_id` ni de coordonnées**
- [ ] Balayer les autres arcs pour la même faute (`acte4`, quêtes de zone, saisonnières)
- [ ] Tests : l'arc `intro` se termine de bout en bout ; test de contrat contre la récidive

### ONB-14 — Une seule population de PNJ au Fanal (M | ★★ | MOYENNE)
> Ferme **D5** : `PnjFixtures` (Gérard le Forgeron, Marie la Herboriste, Claire la Sage) et
> `VillageHubPnjFixtures` (Aldric le Forgeron, Iris l'Alchimiste, Lyra la Guide…) coexistent.
> **Le nouveau venu rencontre deux forgerons.**
> Prérequis : à faire **avec NAR-20**, sinon on renomme deux fois
- [ ] Une seule population au Fanal ; les porteurs de l'arc en font partie
- [ ] Rôles en double fusionnés (forgeron, herboriste/alchimiste), dialogues reportés
- [ ] Les slugs deviennent la clé d'idempotence (convention ZON-26b-b)
- [ ] Tests : aucun rôle en double dans la zone de départ

---

## Piste E — Apprentissage et preuve

### ONB-15 — Le coach par écran (M | ★★ | MOYENNE)
> Ferme **D10**. Décision A3 : **un écran jamais ouvert se présente lui-même, une fois**.
> Prérequis : ← ONB-05 ; croise WIK-02 et RET-08→10
- [ ] `Player.seenCoachMarks` (tableau JSON de slugs) — pas de nouvelle entité
- [ ] Composant Twig + contrôleur Stimulus : deux phrases, le geste proposé, **son coût en
      énergie**, une croix. Ne revient jamais seul
- [ ] Les huit écrans d'ouverture + les deux différés (GAME_ONBOARDING §7.2)
- [ ] **C1** : ne jamais parler d'un système inutilisable
- [ ] **C2** : chaque encart d'action affiche le coût en énergie
- [ ] **C3** : déclenchement à l'arrivée, jamais au temps écoulé
- [ ] Le coach du **hub** n'apparaît qu'après l'acte I
- [ ] Relecture depuis l'aide (lien wiki ; dégradation acceptable sans WIK-02)
- [ ] Le coach est **par personnage**
- [ ] Tests : affichage unique, persistance, C1 respectée, aucun coach au retour d'absence

### ONB-16 — Écrans d'entrée au design system (S | ★★ | MOYENNE)
> L'écran de connexion est le **premier écran du jeu**, et le seul qui ne ressemble pas au jeu.
> Prérequis : ← ONB-05
- [ ] Connexion, inscription, mot de passe oublié, les quatre pas et l'écran d'éveil repris
      avec les composants du design system Parchemin
- [ ] Une seule action primaire par écran ; chiffres en monospace ; états vides qui disent
      quoi faire
- [ ] Aucun nom Tailwind d'avant la v4 (`LegacyTailwindScanner` vert)
- [ ] Le tunnel se traverse au pouce, sans zoom

### ONB-17 — Instrumentation du tunnel et tests de contrat (M | ★★ | MOYENNE)
> Sans mesure, on répare à l'aveugle (GAME_ONBOARDING §9).
> Prérequis : ← pistes A, B, C
- [ ] Six indicateurs : inscriptions → personnages, **pas d'abandon dans le tunnel**,
      personnages → acte I terminé, **répartition des métiers et des éléments choisis**,
      % vérifiés à J+7, **retour à J+1 et J+7**
- [ ] La répartition des métiers est l'indicateur de santé de **D11** : une distribution
      écrasée sur une seule récolte veut dire que le choix n'est pas réel
- [ ] Exposition dans l'admin (une section de l'existant, pas un tableau de bord neuf)
- [ ] `OnboardingPlanContractTest` — les invariants qui ne doivent jamais se perdre :
      aucune quête d'`intro` par coordonnées (ONB-13) ; la matéria garantie dans l'arc
      (ONB-10) ; un seul point de décision pour la porte de vérification (ONB-04) ; **aucun
      contenu gaté par la race ni par le foyer d'attache** (ONB-07, ONB-11) ; la carte des
      domaines contient toujours les 32 (ONB-08) ; un seul état d'onboarding (ONB-12)

---

## Ce que ce plan ne couvre pas

- **La deuxième semaine.** L'acte I s'arrête à j7 ; le passage qui décide de la rétention est
  s3→s6 (GAME_PROGRESSION §3). C'est le domaine de [PLAN_RETENTION.md](PLAN_RETENTION.md), et
  la couture entre les deux vagues reste à vérifier.
- **Les statistiques de peuple** : ONB-07 pose l'arbitrage, il ne le tranche pas seul.
- **Le renommage payant**, l'**OAuth**, le **compte invité** et l'**éparpillement des points
  de réveil** : arbitrages laissés ouverts en GAME_ONBOARDING §10.
- **Les textes eux-mêmes** (dialogues, lettre de foyer, tables de noms) : ils appartiennent à
  **NAR-20**. Ce plan pose les structures qui les accueillent.
