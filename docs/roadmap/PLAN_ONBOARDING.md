# Plan — Compte, personnage et arrivée en jeu

> **Numérotation :** les jalons de **ce** document sont préfixés **ONB-** (Onboarding).
> Ils n'entrent **pas** en conflit avec les numéros de la roadmap globale
> (`SPRINT_*.md`) ni avec les jalons **NAR-** / **ZON-** / **ECO-** / **FOY-** / **DOM-** /
> **FAC-** / **RET-** / **REP-** / **WIK-**.

> Décline [../GAME_ONBOARDING.md](../GAME_ONBOARDING.md) — la source de vérité de tout ce
> qui se passe **avant qu'un joueur soit un joueur**.
>
> Décisions qui commandent ce plan (§10 du cadrage) : vérification d'e-mail **différée** (A1) ;
> **tunnel unique en quatre pas** (A2) ; **coach par écran** (A3) ; **aucune décision de build
> dans le tunnel** (A8) ; **le foyer d'attache se gagne** (A9) ; **le peuple porte une capacité,
> jamais des statistiques** (A11) ; **l'accès aux arbres passe par un parchemin — un coût,
> jamais un verrou** (A12) ; **le combat s'enseigne sur deux mannequins** (A13) ; et l'acte I
> est **trois tours de la même boucle** *parchemin → arbre → geste* (A14).
>
> Le plan s'appuie sur ce qui existe : `CharacterController`, `PlayerFactory`, `TutorialManager`,
> l'arc `intro` (NAR-03/04), les **parchemins de domaine déjà en fixtures**,
> `PlayerDomainHelper`, `DomainInfoController`, `PlayerHubDigest`, `ForbiddenNameChecker`.

## Vue d'ensemble

**19 jalons** (**ONB-01** à **ONB-19**) organisés en 5 pistes.

| Code | Sujet (résumé) | Taille | Priorité |
|------|----------------|--------|----------|
| ONB-01 | Inscription — le compte peut naître (ferme D1) | M | ★★★ |
| ONB-02 | Mailer + mot de passe oublié (ferme D2) | M | ★★★ |
| ONB-03 | Durcissement de la connexion (ferme D3) | S | ★★★ |
| ONB-04 | Vérification d'e-mail différée et sa porte | M | ★★★ |
| ONB-05 | Le tunnel en 4 pas — coquille et fil narratif | M | ★★★ |
| ONB-06 | Le nom : unicité robuste et immédiate (ferme D9) | S | ★★★ |
| ONB-07 | La capacité de peuple remplace les statistiques (ferme D12) | M | ★★ |
| ONB-08 | L'accès à un arbre : le parchemin l'ouvre (modèle) | M | ★★★ |
| ONB-09 | Le catalogue des 32 arbres, et l'arbre ouvert (écran) | M | ★★★ |
| ONB-10 | Les cinq récoltes dans le périmètre de l'acte I (ferme D11) | M | ★★★ |
| ONB-11 | Les mannequins d'entraînement (combat scripté au Fanal) | M | ★★★ |
| ONB-12 | La chaîne de l'acte I — dix quêtes, trois tours de boucle | L | ★★★ |
| ONB-13 | Le foyer d'attache constaté à la clôture (ferme D8) | M | ★★ |
| ONB-14 | Une seule source d'état d'onboarding (ferme D7) | S | ★★ |
| ONB-15 | Réparer les quêtes `explore` de l'arc (ferme D4) | S | ★★★ |
| ONB-16 | Une population de PNJ au Fanal, dont le maître d'armes (ferme D5) | M | ★★ |
| ONB-17 | Le coach par écran (ferme D10) | M | ★★ |
| ONB-18 | Écrans d'entrée au design system | S | ★★ |
| ONB-19 | Instrumentation du tunnel + tests de contrat | M | ★★ |

```
Piste A — Le compte existe    : ONB-01 → ONB-02 → ONB-03 → ONB-04
Piste B — Le tunnel           : ONB-05 → ONB-06, ONB-07        (06 et 07 paralleles)
Piste C — La boucle du jeu    : ONB-08 → ONB-09
                                ONB-10, ONB-11  (paralleles)  → ONB-12 → ONB-13
Piste D — L'acte I repare     : ONB-14, ONB-15, ONB-16        (paralleles)
Piste E — Apprentissage & preuve : ONB-17, ONB-18, ONB-19
```

**Ordre de valeur/effort** : `Piste A → ONB-15 → Piste C → Piste B → Piste D → Piste E`.

La piste A n'est pas prioritaire par confort : **tant qu'elle n'est pas livrée, le jeu n'a
littéralement aucun joueur possible**. ONB-02 suit immédiatement ONB-01 — un jeu où perdre son
mot de passe signifie perdre son personnage ne retient personne.

**ONB-15 remonte juste après**, malgré sa taille S : l'arc d'introduction est **bloqué dès sa
première étape**. Ouvrir l'inscription sur un tutoriel mort n'a pas de sens.

**Puis la piste C avant la piste B**, ce qui peut surprendre : c'est elle qui contient la
boucle du jeu (**ONB-08/09**, le parchemin et le catalogue) et les conditions matérielles des
choix de l'acte I (**ONB-10**, **ONB-11**). Tant qu'elles manquent, le tunnel devrait reprendre
le rôle d'orienter — c'est-à-dire redevenir ce que R1 et R2 ont écarté.

**ONB-08 est le pivot technique du plan.** Il introduit une notion que le modèle n'a pas :
**un arbre ouvert pour un personnage**. Tout ce qui suit en dépend — le catalogue (09), la
chaîne (12), et le coach de l'écran des arbres (17).

**Coupe minimale jouable** : ONB-01 + ONB-02 + ONB-03 + ONB-15. Un inconnu peut alors créer un
compte, le récupérer, et traverser l'acte I sans buter sur une quête morte.

**Croisements avec les autres plans** :

| Jalon voisin | Lien |
|---|---|
| **NAR-20** (le réveil au Fanal) | ONB-12, ONB-13, ONB-15, ONB-16 touchent les mêmes textes et PNJ. **Même vague**, sinon on renomme deux fois. ⚠️ NAR-20 prévoit une « lettre du foyer d'attache **selon la race** » — ONB-13 la dérive des **gestes** (A9) |
| **DOM-01→09** | ONB-08/09 s'appuient sur la borne `element × registre` déjà portée par `Domain`. ⚠️ **ONB-08 précise GAME_DOMAINS §1** (le parchemin est un coût, pas un verrou — cadrage §6.3) : à relire ensemble |
| **PLAN_ZONES** | ONB-10 est une exigence de **données de zone** |
| **PLAN_PLAYER_ECONOMY** | ONB-08 : le barème des 29 parchemins non offerts est un **gold sink** à poser avec l'économie |
| **WIK-02** (`/wiki`) | ONB-17 : « relire cette explication » pointe vers le wiki |
| **RET-08→10** | ONB-17 : le coach du hub arrive **après** l'acte I |
| **FAC-01** | ONB-13 : le cran de réputation respecte la faction portée unique |

---

## Piste A — Le compte existe (séquentiel)

### ONB-01 — Inscription : le compte peut naître (M | ★★★ | CRITIQUE)
> Ferme **D1**. `RegistrationController::__invoke()` lève un `NotFoundHttpException` : aucun
> compte ne peut être créé hors fixtures.
- [ ] `RegistrationFormType` : **trois champs** — e-mail, mot de passe, acceptation des règles.
      Pas de pseudo de compte, pas de confirmation (bouton « afficher »)
- [ ] Contraintes : e-mail valide et unique, mot de passe ≥ 10 caractères, hachage `auto`
- [ ] `emailVerifiedAt` nullable sur `User` (+ migration idempotente) — le compte naît **non
      vérifié et pleinement jouable**
- [ ] Authentification automatique puis redirection vers le tunnel (ONB-05)
- [ ] Limiteur : 5 comptes / heure / IP
- [ ] Lien « créer un compte » depuis la connexion et l'accueil public
- [ ] Tests : création nominale, e-mail pris, mot de passe trop court, limiteur

### ONB-02 — Mailer et mot de passe oublié (M | ★★★ | CRITIQUE)
> Ferme **D2** : perdre son mot de passe revient à perdre son personnage, son inventaire, sa
> guilde et sa place dans un foyer.
> Prérequis : ← ONB-01
- [ ] Installer `symfony/mailer` **dans Docker** (règle 1) ; `MAILER_DSN` en env, `null://` en test
- [ ] Demande : **réponse identique** que le compte existe ou non ; limiteur de débit
- [ ] Jeton à usage unique, **1 heure**, un seul actif par compte, stocké haché
- [ ] Réinitialisation + **invalidation de toutes les sessions**
- [ ] Gabarits d'e-mail au ton du jeu, en français, avec repli texte
- [ ] Tests : jeton expiré, rejoué, compte inexistant (réponse constante), invalidation

### ONB-03 — Durcissement de la connexion (S | ★★★ | CRITIQUE)
> Ferme **D3**. Aucun garde-fou sur le firewall, et `isBanned` n'est lu nulle part.
- [ ] `login_throttling` : 5 essais / 15 min par identifiant, 30 / 15 min par IP
- [ ] Lecture de `isBanned` au login **et** en session courante
- [ ] Message d'erreur **unique**
- [ ] `remember_me.lifetime` : 7 j → **30 j**
- [ ] Redirection post-login **selon l'état** (aucun personnage / acte I en cours / plusieurs)
- [ ] Tests : throttling, banni refusé, redirection par état

### ONB-04 — La vérification différée et sa porte (M | ★★★ | HAUTE)
> Décision A1 (cadrage §3.2).
> Prérequis : ← ONB-02
- [ ] E-mail de vérification, jeton renvoyable ; `emailVerifiedAt` posé au clic
- [ ] **Un seul point de décision** — un `EmailVerificationGate` consulté par toutes les portes
- [ ] Portes fermées : chat, hôtel des ventes (achat **et** vente), échoppe, don, guilde,
      groupe, donjon, messages privés, amis, **livraison d'une commission à un foyer**
- [ ] Écran de porte : ce qui est verrouillé, pourquoi, « renvoyer le lien »
- [ ] Rappel : une ligne au hub, e-mail à J+1 et J+3, puis silence
- [ ] **Aucun blocage rétroactif**
- [ ] Tests : chaque porte, jeton rejoué, absence d'effet rétroactif, test de contrat sur le
      point de décision unique

---

## Piste B — Le tunnel

### ONB-05 — Le tunnel en quatre pas (M | ★★★ | HAUTE)
> Décision A2. Deux formulaires administratifs d'affilée aujourd'hui.
> Prérequis : ← ONB-01
- [ ] **Compte → nom → peuple → visage**, un écran par pas, une décision par écran, une phrase
      de fiction par écran
- [ ] **Aucune décision de build** : ni métier, ni élément, ni arme, ni destination (P3, A8)
- [ ] Barre de progression, retour arrière partout, aucune saisie perdue
- [ ] Écran d'éveil : un paragraphe, **un bouton**, vers **l'écran de zone** (A4)
- [ ] Afficher ce qui engage : **le nom et le peuple**, rien d'autre (§4.3)
- [ ] Les écrans existants restent la voie du 2ᵉ personnage
- [ ] `limit_reached` : dit **quoi faire**
- [ ] Tests : parcours complet, retour arrière, reprise d'un tunnel interrompu

### ONB-06 — Le nom : unicité robuste et immédiate (S | ★★★ | HAUTE)
> Ferme **D9**.
> Prérequis : ← ONB-05
- [ ] Unicité **insensible à la casse** + normalisation des homoglyphes — colonne normalisée,
      index unique
- [ ] Vérification **au fil de la frappe** (« libre / pris », rien de plus)
- [ ] `ForbiddenNameChecker` appliqué à la forme normalisée
- [ ] Bouton « proposer un nom » par peuple (contenu ← NAR-20)
- [ ] Tests : casse, homoglyphes, nom interdit, course entre deux créations simultanées

### ONB-07 — La capacité de peuple remplace les statistiques (M | ★★ | MOYENNE)
> Ferme **D12** et applique **A11**. Les modificateurs actuels ne sont pas équilibrés — Humain
> `0/0/0/0` face à Orc `+8 vie`, soit **+40 % de survie** sur une base de 20 — et surtout, ce
> sont des arbitrages de puissance demandés au pas 3. `Race` n'étant lue **nulle part** hors
> création, ce jalon ne casse rien.
> Cadrage : GAME_ONBOARDING §4.5
- [ ] **Retirer les modificateurs de statistiques** ; vérifier qu'aucun calcul n'en dépendait
- [ ] Implémenter les quatre capacités, sous la règle « elle touche ce qu'on **sait**, jamais
      ce qu'on **produit** » :
  - [ ] **Nain — Lire la pierre** : la bande de pureté d'un filon est lisible **avant** la
        récolte. ⚠️ Ne pas marcher sur le prospecteur : le Nain lit **le filon devant lui**,
        le prospecteur sait **où et pour combien de temps** (RET-06)
  - [ ] **Elfe — L'œil des lisières** : une exploration « rien » rend **un repérage**.
        ⚠️ **Jamais de butin, jamais de réduction de coût** — sinon E9 tombe
  - [ ] **Orc — Le flair** : élément et faiblesse d'un monstre lisibles **dès la première
        rencontre**, sans attendre le palier de bestiaire
  - [ ] **Humain — Les usages** : sur tout objet, les recettes qui le consomment et les PNJ qui
        l'achètent, sans l'avoir découvert (s'appuie sur `PlayerResourceCatalog`)
- [ ] Écran du peuple : qui vous êtes, d'où vous venez, **et ce que vous voyez**. Jamais un
      métier, une destination ni des arbres (A8)
- [ ] Tests : aucune capacité ne modifie dégâts, PV, rendement, coût, nombre d'actions ni prix ;
      aucun contenu gaté par la race

---

## Piste C — La boucle du jeu

### ONB-08 — L'accès à un arbre : le parchemin l'ouvre (M | ★★★ | HAUTE)
> **Le pivot technique du plan** (A12). Les parchemins existent déjà en fixtures
> (`life-domain-parchment`, `miner-domain-parchment`, `herbalist-domain-parchment`, 100 gils,
> deux déjà donnés en récompense de quête) mais leur effet est
> `{"action":"learn_skill","slug":"miner-copper-xs"}` : ils accordent **une compétence
> précise**, pas l'accès à un arbre. Le geste joueur est le bon ; la sémantique est à hisser.
> ⚠️ **Ce jalon précise GAME_DOMAINS §1**, qui écrit « interdire un arbre serait interdire un
> geste ». La réconciliation est en GAME_ONBOARDING §6.3 : **le parchemin est un coût, jamais
> un verrou** — à relire avec DOM avant de coder.
- [ ] Notion d'**arbre ouvert pour un personnage** (le modèle ne l'a pas)
- [ ] Nouvel effet d'objet **« ouvrir un domaine »**, distinct de `learn_skill`
- [ ] Un parchemin par domaine — **les 32**, avec le PNJ vendeur de chacun
- [ ] **Les quatre conditions non négociables**, chacune verrouillée par un test :
  - [ ] tout parchemin est accessible à **tout le monde** (aucun prérequis de peuple, de
        faction, de progression ou de choix antérieur)
  - [ ] en posséder un **n'en interdit aucun autre** — les 32 sont cumulables
  - [ ] aucun n'est **unique ni limité** : un PNJ le vend, toujours, à prix fixe
  - [ ] **aucun parchemin payant sur le chemin critique de l'acte I** — les trois premiers sont
        donnés en récompense (ONB-12)
- [ ] Migration des trois parchemins existants vers la nouvelle sémantique
- [ ] Barème de prix des 29 autres → à poser avec **PLAN_PLAYER_ECONOMY** (gold sink)
- [ ] Tests : les quatre conditions ; ouverture idempotente ; un arbre fermé n'accorde aucun nœud

### ONB-09 — Le catalogue des 32 arbres, et l'arbre ouvert (M | ★★★ | HAUTE)
> Les trois états de GAME_ONBOARDING §6.1. Aujourd'hui `DomainInfoController` montre **tous les
> nœuds de n'importe quel domaine à n'importe qui**, et l'écran des arbres empile les 32 : c'est
> le **risque n° 1 de l'acte I** (GAME_PROGRESSION §3).
> Prérequis : ← ONB-08
- [ ] **Le catalogue** *(public, complet, dès la première minute)* : les 32 arbres, présentés
      comme la **roue élément × registre** (8 × 3, + 5 récoltes, + 4 artisanats). Pour chacun :
      ce qu'on y apprend **en une phrase**, ce qu'il permet d'équiper **en famille**, et **où
      trouver son parchemin**
- [ ] **Ce que le catalogue ne dit pas** : la liste des nœuds, les valeurs, les prérequis
      internes, **ni même le premier nœud**, ni la spécialisation terminale
- [ ] **L'arbre ouvert** : le détail complet, après parchemin
- [ ] `DomainInfoController` sert le catalogue pour un arbre fermé, l'arbre pour un arbre ouvert
- [ ] L'ouverture d'un arbre est **notifiée** — c'est un moment, pas un changement d'état muet
- [ ] Tests : le catalogue contient toujours les 32 ; aucun nœud d'un arbre fermé n'est exposé
      (y compris via l'API et le rendu Twig) ; aucun arbre n'est rendu inatteignable

### ONB-10 — Les cinq récoltes dans le périmètre de l'acte I (M | ★★★ | HAUTE)
> Ferme **D11**. Le Fanal n'expose que **deux filons, tous deux d'herboristerie** (thym,
> lavande). Un choix parmi cinq parchemins de récolte qui débouche sur une seule récolte
> possible est un **faux choix** — et tout le monde deviendrait herboriste.
> Prérequis : ∅ (données de zone) ; à instruire avec **PLAN_ZONES**
- [ ] **Les cinq récoltes atteignables** dans le périmètre de l'acte I, au palier T0 :
      herboristerie (existe), minerai, bois, pêche, dépeçage — au Fanal ou dans un voisin immédiat
- [ ] **Le premier voyage est offert** : durée nulle, une seule fois, narrativement accompagné.
      Le joueur apprend le voyage **comme geste** ici, et **comme temps réel** à l'étape 9
- [ ] Cohérence avec le plancher T1 PNJ et les lois de zone (GAME_ZONES)
- [ ] Tests : les cinq professions atteignables sans attendre ; le Fanal reste `safe`

### ONB-11 — Les mannequins d'entraînement (M | ★★★ | HAUTE)
> Décision A13. Le Fanal est `safe: true`, donc `ExploreService` force `mob: 0` : **aucun
> combat n'y est possible**. Un combat **scripté par une quête** n'est pas un tirage de
> rencontre — le mannequin enseigne donc le combat au Fanal **sans lever sa sûreté**, et évite
> de faire voyager (donc attendre) un joueur avant son premier combat.
> Cadrage : GAME_ONBOARDING §5.3
- [ ] Combat scripté déclenché par une quête, hors tirage de rencontre — `safe: true` intact
- [ ] **Mannequin n° 1** : son action est « tourne sur lui-même », **zéro dégât**. **Perdre est
      impossible** — c'est ce qui permet d'afficher toute l'interface sans qu'un joueur qui lit
      lentement se fasse tuer
- [ ] **Mannequin n° 2** : il riposte faiblement et **ne peut pas tuer** (plancher à 1 PV). Le
      joueur doit voir sa barre descendre pour comprendre à quoi servent les soins
- [ ] Diégétique : ce sont des mannequins, **pas des monstres affaiblis** — le monde ne raconte
      jamais que ses monstres sont inoffensifs
- [ ] Tests : le mannequin 1 n'inflige jamais de dégât ; le mannequin 2 ne descend jamais sous
      1 PV ; ni l'un ni l'autre n'apparaît dans un tirage de rencontre ; le Fanal reste `safe`

### ONB-12 — La chaîne de l'acte I : dix quêtes, trois tours de boucle (L | ★★★ | HAUTE)
> Décision A14, et le cœur du dossier. Le tutoriel actuel commence par le voyage (**D6**, seul
> geste time-gaté) et **ne mentionne jamais la matéria** — la seule source d'actions de combat
> (règle 10) et le build du personnage.
> Prérequis : ← ONB-08, ONB-09, ONB-10, ONB-11, ONB-15, ONB-16 ; croise NAR-20
- [ ] La chaîne de GAME_ONBOARDING §5.2, en dix quêtes :
  - [ ] **1 — Le maître d'armes.** Récompense : **une arme au choix** + **le parchemin de
        l'arbre qui l'autorise**
  - [ ] **2 — Apprendre.** Parchemin → l'arbre s'ouvre → nœud d'équipement → arme équipée
        *(tour 1 de la boucle)*
  - [ ] **3 — Le mannequin.** Combat n° 1. Récompense : **une matéria de l'élément du domaine
        choisi** + **les points de domaine pour en prendre l'accord** — on ne montre jamais une
        matéria qu'on ne peut pas utiliser
  - [ ] **4 — L'accord.** Nœud d'accord → matéria sertie *(tour 2)*
  - [ ] **5 — Le second mannequin.** Lancer le sort
  - [ ] **6 — Le métier.** Un parchemin de récolte **parmi les cinq** *(tour 3)*
  - [ ] **7 — La récolte.** Aller dans la zone, récolter (l'exploration est le moyen, pas une
        étape : on explore pour trouver où récolter)
  - [ ] **8 — L'atelier.** Fabriquer — la première fois qu'un geste ne coûte pas d'énergie
  - [ ] **9 — Le départ.** Voyager vers une vraie zone : **le voyage coûte du temps réel**, et
        on le dit
  - [ ] **10 — L'expédition.** En lancer une avant de fermer — **comment quitter le jeu en le
        laissant travailler**, la leçon qui fait revenir au jour 2
- [ ] **Le choix est réel à chaque tour** : quelle arme, quel élément, quel métier, où partir
- [ ] La récompense de l'étape 3 est **dérivée du domaine choisi à l'étape 1** — pas un objet fixe
- [ ] Une étape = **une** quête de l'arc `intro`
- [ ] Tests : la matéria est garantie, accordée et lancée ; les cinq métiers et les armes sont
      réellement proposés ; **aucune étape avant la 9ᵉ n'est time-gatée** ; la matéria reçue
      correspond à l'élément du domaine choisi

### ONB-13 — Le foyer d'attache constaté à la clôture (M | ★★ | MOYENNE)
> Ferme **D8**, applique l'**amendement à GAME_WORLD §13.1** (A9) : le foyer d'attache ne se
> choisit pas, **il se gagne**.
> Prérequis : ← ONB-12 ; croise NAR-20 (la lettre) et FAC-01
- [ ] Mesure de l'activité par zone pendant l'acte I ; foyer constaté à la clôture
- [ ] Défaut sans activité distinctive : **le Fanal**
- [ ] Ce qu'il apporte : une **lettre** suggérant une destination, **un PNJ qui vous connaît**,
      **un cran de réputation**, **une ligne au journal**
- [ ] **Aucun contenu ouvert ou fermé, aucun arbre mis en avant, aucun bonus de rendement** —
      verrouillé par un test
- [ ] Tests : foyer dérivé des gestes et non de la race ; défaut au Fanal ; aucun contenu gaté

---

## Piste D — L'acte I répare (parallélisable)

### ONB-14 — Une seule source d'état d'onboarding (S | ★★ | MOYENNE)
> Ferme **D7**.
> Prérequis : ← ONB-12
- [ ] L'arc `intro` devient la **source** ; `TutorialStep` devient une **projection**
- [ ] « Passer le tutoriel » et « abandonner l'arc » deviennent le même geste
- [ ] `tutorial-complete` reste attaché à la clôture de l'arc
- [ ] Tests : aucun état d'onboarding écrit à deux endroits (test de contrat)

### ONB-15 — Réparer les quêtes `explore` de l'arc (S | ★★★ | HAUTE)
> Ferme **D4**. Trois des sept quêtes d'`intro` valident un `explore` sur `map_id => 1` et des
> coordonnées ; `updateExplored()` résout par **zone** et ne se déclenche qu'au voyage : elles
> ne tombent **jamais** pour un joueur qui n'a pas bougé. **L'arc est bloqué dès sa première
> étape.**
- [ ] Convertir les trois objectifs en objectifs du pivot : parler à un PNJ, récolter, combattre
- [ ] **Aucune quête d'introduction ne dépend de `map_id` ni de coordonnées**
- [ ] Balayer les autres arcs pour la même faute (`acte4`, zones, saisons)
- [ ] Tests : l'arc `intro` se termine de bout en bout ; test de contrat contre la récidive

### ONB-16 — Une population de PNJ au Fanal, dont le maître d'armes (M | ★★ | MOYENNE)
> Ferme **D5** : `PnjFixtures` (Gérard le Forgeron, Marie la Herboriste, Claire la Sage) et
> `VillageHubPnjFixtures` (Aldric le Forgeron, Iris l'Alchimiste, Lyra la Guide…) coexistent —
> **deux forgerons**, et **aucun maître d'armes** alors que la chaîne (ONB-12) commence chez lui.
> Prérequis : à faire **avec NAR-20**, sinon on renomme deux fois
- [ ] Une seule population au Fanal ; les porteurs de l'arc en font partie
- [ ] Rôles en double fusionnés, dialogues reportés
- [ ] **Le maître d'armes** existe, et il vend des parchemins d'arbres de combat (ONB-08)
- [ ] Chaque PNJ de métier du Fanal vend le parchemin de son arbre
- [ ] Slugs comme clé d'idempotence (convention ZON-26b-b)
- [ ] Tests : aucun rôle en double ; chaque parchemin de l'acte I a un vendeur identifié

---

## Piste E — Apprentissage et preuve

### ONB-17 — Le coach par écran (M | ★★ | MOYENNE)
> Ferme **D10**. Décision A3.
> Prérequis : ← ONB-05, ONB-09 ; croise WIK-02 et RET-08→10
- [ ] `Player.seenCoachMarks` (tableau JSON de slugs) — pas de nouvelle entité
- [ ] Composant Twig + contrôleur Stimulus : deux phrases, le geste, **son coût en énergie**,
      une croix. Ne revient jamais seul
- [ ] Les huit écrans d'ouverture + les deux différés (cadrage §7.2)
- [ ] Le coach de combat s'affiche sur **le premier mannequin** — le seul combat où lire ne tue
      pas (ONB-11)
- [ ] **C1** : jamais un système inutilisable · **C2** : toujours le coût · **C3** : à
      l'arrivée, jamais au temps écoulé
- [ ] Le coach du **hub** n'apparaît qu'après l'acte I
- [ ] Relecture depuis l'aide (lien wiki ; dégradation acceptable sans WIK-02)
- [ ] Le coach est **par personnage**
- [ ] Tests : affichage unique, persistance, C1 respectée, aucun coach au retour d'absence

### ONB-18 — Écrans d'entrée au design system (S | ★★ | MOYENNE)
> L'écran de connexion est le **premier écran du jeu**, et le seul qui ne ressemble pas au jeu.
> Prérequis : ← ONB-05
- [ ] Connexion, inscription, mot de passe oublié, les quatre pas, l'écran d'éveil — composants
      Parchemin
- [ ] Une seule action primaire par écran ; chiffres en monospace ; états vides utiles
- [ ] Aucun nom Tailwind d'avant la v4 (`LegacyTailwindScanner` vert)
- [ ] Le tunnel se traverse au pouce, sans zoom

### ONB-19 — Instrumentation du tunnel et tests de contrat (M | ★★ | MOYENNE)
> Sans mesure, on répare à l'aveugle (cadrage §9).
> Prérequis : ← pistes A, B, C
- [ ] Sept indicateurs : inscriptions → personnages ; **pas d'abandon dans le tunnel** ;
      personnages → acte I terminé ; **répartition des armes, éléments et métiers choisis** ;
      **répartition des peuples** ; % vérifiés à J+7 ; **retour à J+1 et J+7**
- [ ] La répartition des métiers est l'indicateur de santé de **D11** ; celle des peuples,
      l'indicateur d'équilibre de **ONB-07**
- [ ] Exposition dans l'admin (une section de l'existant)
- [ ] `OnboardingPlanContractTest` — les invariants à ne jamais perdre : aucune quête d'`intro`
      par coordonnées (15) ; la matéria garantie et accordée (12) ; un seul point de décision
      pour la porte (04) ; **aucun contenu gaté par la race ni par le foyer d'attache** (07, 13) ;
      **les quatre conditions du parchemin** (08) ; le catalogue contient toujours les 32 et
      n'expose aucun nœud d'un arbre fermé (09) ; un seul état d'onboarding (14)

---

## Ce que ce plan ne couvre pas

- **La deuxième semaine.** L'acte I s'arrête à j7 ; le passage qui décide de la rétention est
  s3→s6 (GAME_PROGRESSION §3). Domaine de [PLAN_RETENTION.md](PLAN_RETENTION.md) ; la couture
  entre les deux vagues reste à vérifier.
- **Le barème des 29 parchemins non offerts** : ONB-08 pose la règle, l'économie pose les prix.
- **Le renommage payant**, l'**OAuth**, le **compte invité**, l'**éparpillement des points de
  réveil** : arbitrages ouverts en GAME_ONBOARDING §12.
- **Les textes** (dialogues du maître d'armes, lettre de foyer, tables de noms) : ils
  appartiennent à **NAR-20**. Ce plan pose les structures qui les accueillent.
