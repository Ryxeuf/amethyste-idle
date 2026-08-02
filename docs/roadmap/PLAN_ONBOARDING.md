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

**20 jalons** (**ONB-01** à **ONB-20**) organisés en 5 pistes. **17/20 livrés + ONB-12a + ONB-20a + ONB-20b-a + ONB-07a.**

| Code | Sujet (résumé) | Taille | Priorité |
|------|----------------|--------|----------|
| ONB-01 ✅ | Inscription — le compte peut naître (ferme D1) | M | ★★★ |
| ONB-02 | Mailer + mot de passe oublié (ferme D2) | M | ★★★ |
| ONB-03 ✅ | Durcissement de la connexion (ferme D3) | S | ★★★ |
| ONB-04 | Vérification d'e-mail différée et sa porte | M | ★★★ |
| ONB-05 ✅ | Le tunnel en 4 pas — coquille et fil narratif | M | ★★★ |
| ONB-06 ✅ | Le nom : unicité robuste et immédiate (ferme D9) | S | ★★★ |
| ONB-07a ✅ | Les statistiques de peuple disparaissent, la capacité est déclarée (ferme D12) | S | ★★ |
| ONB-07b | Les quatre capacités branchées (1/4 livré ; **3 bloquées**, voir le jalon) | M | ★★ |
| ONB-08 ✅ | L'accès à un arbre : le parchemin l'ouvre (modèle) | M | ★★★ |
| ONB-09 ✅ | Le catalogue des 32 arbres, et l'arbre ouvert (écran) | M | ★★★ |
| ONB-10 ✅ | Les cinq récoltes dans le périmètre de l'acte I (ferme D11) | M | ★★★ |
| ONB-11 ✅ | Les mannequins d'entraînement (combat scripté au Fanal) | M | ★★★ |
| ONB-12a ✅ | Les quatre gestes que la chaîne doit constater (moteur) | M | ★★★ |
| ONB-12b ✅ | La chaîne de l'acte I — dix quêtes, trois tours de boucle | L | ★★★ |
| ONB-13 ✅ | Le foyer d'attache constaté à la clôture (ferme D8) | M | ★★ |
| ONB-14 ✅ | Une seule source d'état d'onboarding (ferme D7) | S | ★★ |
| ONB-15 ✅ | Réparer les quêtes `explore` de l'arc (ferme D4) | S | ★★★ |
| ONB-16 ✅ | Une population de PNJ au Fanal, dont le maître d'armes (ferme D5) | M | ★★ |
| ONB-17 ✅ | Le coach par écran, les dix encarts (ferme D10) | M | ★★ |
| ONB-18 ✅ | Écrans d'entrée au design system | S | ★★ |
| ONB-19a ✅ | `OnboardingPlanContractTest` — les invariants du plan | S | ★★ |
| ONB-19b ✅ | Les sept indicateurs du tunnel + exposition admin | M | ★★ |
| ONB-20a ✅ | Mains nues (ferme la moitie de D13) | S | ★★★ |
| ONB-20b-a ✅ | Le port des **armes** par nœuds d'entree (echelon 1) | M | ★★★ |
| ONB-20b-b | Le port des **armures et outils** (echelles restantes) | M | ★★ |

```
Piste A — Le compte existe    : ONB-01 → ONB-02 → ONB-03 → ONB-04
Piste B — Le tunnel           : ONB-05 → ONB-06, ONB-07        (06 et 07 paralleles)
Piste C — La boucle du jeu    : ONB-20 → ONB-08 → ONB-09
                                ONB-10, ONB-11  (paralleles)  → ONB-12a → ONB-12b → ONB-13
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

**Mais ONB-20 passe avant lui**, et c'est non négociable : le combat à mains nues **n'existe
pas** (`PlayerAttackHandler` lève une exception sans arme équipée). Livrer la doctrine du
parchemin avant ce repli enfermerait un personnage sans arme apprise au lieu de l'orienter.

**Coupe minimale jouable** : ONB-01 + ONB-02 + ONB-03 + ONB-15. Un inconnu peut alors créer un
compte, le récupérer, et traverser l'acte I sans buter sur une quête morte.

**Croisements avec les autres plans** :

| Jalon voisin | Lien |
|---|---|
| **NAR-20** (le réveil au Fanal) | ONB-12, ONB-13, ONB-15, ONB-16 touchent les mêmes textes et PNJ. **Même vague**, sinon on renomme deux fois. ⚠️ NAR-20 prévoit une « lettre du foyer d'attache **selon la race** » — ONB-13 la dérive des **gestes** (A9) |
| **DOM-01→09** | ONB-08/09 s'appuient sur la borne `element × registre` déjà portée par `Domain`. ⚠️ **ONB-08 précise GAME_DOMAINS §1** (*le champ est infini, l'entrée est un acte* — cadrage §6.0/§6.3) : à relire ensemble |
| **DOM-10** (arbres retrouvés) | **Ouvert par ONB-08** : le parchemin posé comme mécanisme rend possibles les arbres hors catalogue. Le jalon vit dans [PLAN_DOMAINS.md](PLAN_DOMAINS.md) — c'est du contenu de progression, pas de l'onboarding |
| **DOM-02** (jamais d'interdit de port) | ⚠️ **ONB-20 amende son garde-fou 1** : le prérequis de compétence, jusqu'ici un cas réservé, devient **la règle générale** (armes, armures, outils). *« Tout le monde peut tout porter »* devient *« tout le monde peut **apprendre à** tout porter »* — le mage en plaque existe toujours, il a dû l'apprendre. À relire avec DOM |
| **PLAN_ZONES** | ONB-10 est une exigence de **données de zone** |
| **PLAN_PLAYER_ECONOMY** | ONB-08 : le barème des 29 parchemins non offerts est un **gold sink** à poser avec l'économie |
| **WIK-02** (`/wiki`) | ONB-17 : « relire cette explication » pointe vers le wiki |
| **RET-08→10** | ONB-17 : le coach du hub arrive **après** l'acte I |
| **FAC-01** | ONB-13 : le cran de réputation respecte la faction portée unique |

---

## Piste A — Le compte existe (séquentiel)

### ONB-01 — Inscription : le compte peut naître (M | ★★★ | CRITIQUE) — ✅ LIVRÉ 2026-07-29
> Ferme **D1**. `RegistrationController::__invoke()` levait un `NotFoundHttpException` : aucun
> compte ne pouvait être créé hors fixtures.
- [x] `RegistrationFormType` : **trois champs** — e-mail, mot de passe, acceptation des règles.
      Pas de pseudo de compte, pas de confirmation (bouton « afficher »)
- [x] Contraintes : e-mail valide et unique, mot de passe ≥ 10 caractères, hachage `auto`
- [x] `emailVerifiedAt` nullable sur `User` (+ migration idempotente) — le compte naît **non
      vérifié et pleinement jouable**
- [x] Authentification automatique puis redirection vers la création de personnage — le tunnel
      (ONB-05) reprendra cette redirection sans la déplacer
- [x] Limiteur : 5 comptes / heure / IP
- [x] Lien « créer un compte » depuis la connexion, l'accueil public et la barre de navigation
- [x] Tests : création nominale, e-mail pris (y compris à la casse près), mot de passe trop
      court, règles refusées, limiteur
- [x] **Au passage** : `User::setEmail()` normalise l'adresse (minuscule + trim), sans quoi
      l'unicité ne tient pas et ONB-02 viserait le mauvais compte

### ONB-02 — Mailer et mot de passe oublié (M | ★★★ | CRITIQUE)
> Ferme **D2** : perdre son mot de passe revient à perdre son personnage, son inventaire, sa
> guilde et sa place dans un foyer.
> Prérequis : ← ONB-01
>
> **Infrastructure décidée le 2026-08-02** : fournisseur **Brevo** (français, RGPD,
> 300 e-mails/jour gratuits, bridge `symfony/brevo-mailer`) ; adresse d'envoi
> **`no-reply@amethyste.best`** (SPF/DKIM du fournisseur à poser dans la zone DNS
> d'amethyste.best — à la main de l'opérateur). **Séquencement : le code d'abord** —
> le jalon se livre complet et testé avec `MAILER_DSN` en env (`null://` en test),
> le branchement prod se fait quand le compte Brevo et le DNS sont opérationnels.
- [ ] Installer `symfony/mailer` **dans Docker** (règle 1) ; `MAILER_DSN` en env, `null://` en test
- [ ] Demande : **réponse identique** que le compte existe ou non ; limiteur de débit
- [ ] Jeton à usage unique, **1 heure**, un seul actif par compte, stocké haché
- [ ] Réinitialisation + **invalidation de toutes les sessions**
- [ ] Gabarits d'e-mail au ton du jeu, en français, avec repli texte
- [ ] Tests : jeton expiré, rejoué, compte inexistant (réponse constante), invalidation

### ONB-03 — Durcissement de la connexion (S | ★★★ | CRITIQUE) — ✅ LIVRÉ 2026-07-29
> Ferme **D3**. Aucun garde-fou sur le firewall, et `isBanned` n'était lu nulle part.
- [x] `login_throttling` : 5 essais / 15 min par identifiant, 30 / 15 min par IP.
      Les deux limiteurs sont **déclarés séparément** (`app.login_rate_limiter`) : l'option
      `max_attempts` de Symfony dérive la borne par IP de la borne par identifiant, ce qui
      interdit de régler l'une sans l'autre
- [x] Lecture de `isBanned` au login (`UserChecker`) **et** en session courante
      (`BannedUserSubscriber`) — sans quoi une session ouverte survivait au bannissement
      jusqu'à un mois avec le « se souvenir de moi »
- [x] Message d'erreur **unique** (`LoginFormAuthenticator::GENERIC_FAILURE_MESSAGE`) —
      seul le throttling garde le sien, il ne dit rien du compte visé et le taire ferait
      réessayer sans fin
- [x] `remember_me.lifetime` : 7 j → **30 j**
- [x] Redirection post-login **selon l'état** : aucun personnage → le tunnel ; plusieurs →
      le choix ; un seul avec l'acte I inachevé → **l'écran de zone**, pas le hub
- [x] Tests : message unique par cause d'échec, banni refusé, session bannie fermée,
      chemins de sortie laissés ouverts, redirection par état

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

### ONB-05 — Le tunnel en quatre pas ✅ (M | ★★★ | HAUTE)
> Décision A2. Deux formulaires administratifs d'affilée aujourd'hui.
> Prérequis : ← ONB-01
- [x] **Compte → nom → peuple → visage**, un écran par pas, une décision par écran, une phrase
      de fiction par écran
- [x] **Aucune décision de build** : ni métier, ni élément, ni arme, ni destination (P3, A8)
- [x] Barre de progression, retour arrière partout, aucune saisie perdue
- [x] Écran d'éveil : un paragraphe, **un bouton**, vers **l'écran de zone** (A4)
- [x] Afficher ce qui engage : **le nom et le peuple**, rien d'autre (§4.3)
- [x] Les écrans existants restent la voie du 2ᵉ personnage
- [x] `limit_reached` : dit **quoi faire**
- [x] Tests : parcours complet, retour arrière, reprise d'un tunnel interrompu

### ONB-06 — Le nom : unicité robuste et immédiate (S | ★★★ | HAUTE) — ✅ LIVRÉ 2026-07-29
> Ferme **D9**.
> Prérequis annoncé : ← ONB-05. **Livré avant** : le tunnel réutilisera l'écran de création
> existant, et l'usurpation d'identité n'a pas de raison d'attendre une refonte d'écran.
- [x] Unicité **insensible à la casse** + normalisation des homoglyphes — colonne
      `player.normalized_name`, index unique, migration avec repli SQL et désambiguïsation des
      doublons déjà en base
- [x] Vérification **au fil de la frappe** (« libre / pris », rien de plus — jamais **qui**
      porte le nom, ce serait un annuaire de personnages ouvert à tous)
- [x] `ForbiddenNameChecker` appliqué à la forme normalisée — « аdmin » avec un « а »
      cyrillique passait au travers. Deux lectures sont testées, parce qu'un chiffre est
      ambigu : `1` vaut `l` pour l'unicité et peut se lire `i` pour la détection
- [ ] Bouton « proposer un nom » par peuple — **laissé à NAR-20**, qui porte les tables de noms
- [x] Tests : casse, espaces, ponctuation, accents, ligatures, homoglyphes cyrilliques et grecs,
      noms distincts qui le restent, nom interdit ; **la course entre deux créations
      simultanées est tranchée par l'index**, et le contrôleur rattrape la violation par une
      redirection (Doctrine ferme le gestionnaire d'entités après une violation de contrainte)

### ONB-07 — La capacité de peuple remplace les statistiques (M | ★★ | MOYENNE)
> Ferme **D12** et applique **A11**. Les modificateurs actuels ne sont pas équilibrés — Humain
> `0/0/0/0` face à Orc `+8 vie`, soit **+40 % de survie** sur une base de 20 — et surtout, ce
> sont des arbitrages de puissance demandés au pas 3. `Race` n'étant lue **nulle part** hors
> création, ce jalon ne casse rien.
> Cadrage : GAME_ONBOARDING §4.5
- [x] **ONB-07a ✅ LIVRÉ 2026-07-29 — Retirer les modificateurs de statistiques** ; vérifié
      qu'aucun calcul n'en dépendait (seul `PlayerFactory` les lisait). La colonne
      `race.stat_modifiers` est **supprimée**, pas remise à zéro : une donnée que plus rien ne
      lit finit par être relue un jour par quelqu'un qui la croit vivante. Les personnages déjà
      créés gardent leurs statistiques — les recalculer retirerait des points de vie à des
      joueurs pour corriger une décision qu'ils n'ont pas prise
  - [x] `RaceCapability` (les quatre capacités déclarées) + `RaceCapabilityResolver` (le point
        unique d'où l'on demande « ce personnage voit-il ceci ? »)
  - [x] Écran du peuple : la capacité remplace les puces de statistiques
  - [x] Tests : chaque peuple porte **exactement une** capacité ; une capacité n'expose
        **aucun chiffre** ; les quatre peuples naissent avec des statistiques identiques
- **ONB-07b — reste à faire** : brancher les quatre capacités sur leurs écrans (zone,
  exploration, bestiaire, catalogue de ressources)
- [ ] Implémenter les quatre capacités, sous la règle « elle touche ce qu'on **sait**, jamais
      ce qu'on **produit** » :
  - [ ] **Nain — Lire la pierre** : la bande de pureté d'un filon est lisible **avant** la
        récolte. ⚠️ Ne pas marcher sur le prospecteur : le Nain lit **le filon devant lui**,
        le prospecteur sait **où et pour combien de temps** (RET-06)
        🚧 **Bloqué — décision de conception, pas d'implémentation.** La pureté est **tirée au
        moment de la récolte** (`PurityDrawer::draw()`), à partir de la vitalité du filon et
        d'un jet. Il n'existe donc **aucune bande « du filon »** à lire d'avance : la rendre
        lisible exige de décider si le tirage devient déterministe par état de filon — ce qui
        touche ECO-21/22 et la façon dont la pureté se négocie. À trancher avant de coder.
  - [ ] **Elfe — L'œil des lisières** : une exploration « rien » rend **un repérage**.
        ⚠️ **Jamais de butin, jamais de réduction de coût** — sinon E9 tombe
        🚧 **Bloqué — le repérage n'existe pas.** GAME_ZONE_ACTIONS décrit les trois états de
        découverte (rumeur → repérée → cartographiée) et le repérage cumulatif, mais **aucune
        entité par joueur ne les porte** : il n'y a ni `PlayerVein`, ni découverte de monstre
        par joueur. La capacité rendrait quelque chose qui n'a nulle part où être rangé. Elle
        suit la reprise de l'écran de zone, elle ne la précède pas.
  - [x] **Orc — Le flair** ✅ : élément et faiblesse d'un monstre lisibles **dès la première
        rencontre**, sans attendre le palier de bestiaire
  - [ ] **Humain — Les usages** : sur tout objet, les recettes qui le consomment et les PNJ qui
        l'achètent, sans l'avoir découvert (s'appuie sur `PlayerResourceCatalog`)
        🚧 **Bloqué — l'écran ne montre pas ce qu'il faudrait avancer.** Le palier
        `TIER_RECIPES` (25 récoltes) existe et son **badge** s'affiche, mais
        `templates/game/catalog/index.html.twig` n'a **aucun bloc de détail** pour les recettes
        ni pour les acheteurs — seuls l'élément/valeur (palier 1) et le titre de spécialiste
        (palier 3) sont rendus. Avancer la lecture d'un contenu qui n'est jamais affiché ne
        changerait qu'une couleur de pastille. Construire le bloc d'abord.
- [ ] Écran du peuple : qui vous êtes, d'où vous venez, **et ce que vous voyez**. Jamais un
      métier, une destination ni des arbres (A8)
- [ ] Tests : aucune capacité ne modifie dégâts, PV, rendement, coût, nombre d'actions ni prix ;
      aucun contenu gaté par la race

---

## Piste C — La boucle du jeu

### ONB-08 — L'accès à un arbre : le parchemin l'ouvre (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-07-30
> **Le pivot technique du plan** (A12). Les parchemins existent déjà en fixtures
> (`life-domain-parchment`, `miner-domain-parchment`, `herbalist-domain-parchment`, 100 gils,
> deux déjà donnés en récompense de quête) mais leur effet est
> `{"action":"learn_skill","slug":"miner-copper-xs"}` : ils accordent **une compétence
> précise**, pas l'accès à un arbre. Le geste joueur est le bon ; la sémantique est à hisser.
> ⚠️ **Ce jalon précise GAME_DOMAINS §1**, qui écrit « interdire un arbre serait interdire un
> geste ». La réconciliation est en GAME_ONBOARDING §6.3 : **le parchemin est un coût, jamais
> un verrou** — à relire avec DOM avant de coder.
- [x] Notion d'**arbre ouvert pour un personnage** — `PlayerDomainAccess`, unicité
      `(player, domain)` portée par le schéma : l'ouverture est idempotente par construction
- [x] Nouvel effet d'objet **« ouvrir un domaine »** (`open_domain`), distinct de `learn_skill`
- [x] Un parchemin par domaine — **les 36** (le cadrage en annonçait 32 : la Piste H a livré
      depuis le cuisinier, le charpentier et le tailleur, ZON-34 le bûcheron). Le **vendeur**
      de chacun reste à poser : c'est ONB-16
- [x] **Les actions de base sont concernées** (A16) : sans parchemin, on ne mine ni ne forge.
      Il n'a **rien fallu généraliser sur les filons** — les actions de récolte sont portées par
      des **nœuds d'arbre** (`PlayerActionHelper::getHarvestSpots()` les dérive des compétences),
      donc fermer l'arbre ferme le geste, sans toucher à `requires_skill`
  - [x] **migration : les personnages existants sont grand-périsés** — tout arbre dont ils
        portent une compétence **ou** une expérience de domaine. La règle sur-ouvre
        volontairement : ouvrir un arbre de trop coûte un parchemin non vendu, en fermer un de
        trop bloque un joueur
  - [ ] l'acte I **donne** les trois premiers parchemins — reste à **ONB-12**
- [x] **La frontière, testée** : une compétence sans domaine reste libre pour tous, et un nœud
      partagé suffit à **un seul** arbre ouvert
- [x] **Les quatre conditions non négociables**, verrouillées par `DomainParchmentContractTest`
      — tenues par la **forme des données**, pas par du code
- [x] Migration des trois parchemins existants vers la nouvelle sémantique (slugs conservés :
      `life-domain-parchment` désigne l'arbre du Guérisseur, le renommer casserait les
      récompenses de quête qui le visent)
- [ ] Barème de prix des 33 autres → à poser avec **PLAN_PLAYER_ECONOMY** (gold sink). 100 gils
      partout est provisoire ; c'est **l'uniformité** du prix qui est la règle, pas sa valeur
- [x] Tests : les quatre conditions ; ouverture idempotente ; un arbre fermé n'accorde aucun nœud

### ONB-09 — Le catalogue des 32 arbres, et l'arbre ouvert (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-07-30
> Les trois états de GAME_ONBOARDING §6.1. Aujourd'hui `DomainInfoController` montre **tous les
> nœuds de n'importe quel domaine à n'importe qui**, et l'écran des arbres empile les 32 : c'est
> le **risque n° 1 de l'acte I** (GAME_PROGRESSION §3).
> Prérequis : ← ONB-08
- [x] **Le catalogue** *(public, complet, dès la première minute)* : `/game/skills/catalog`,
      groupé par élément (les 36 arbres livrés). Pour chacun : ce qu'on y apprend **en une
      phrase**, ce qu'il permet d'équiper **en famille**, et **son parchemin avec son prix** —
      résolu par l'**effet** de l'objet, jamais par une table parallèle, donc impossible à
      désynchroniser
- [x] **Ce que le catalogue ne dit pas** : `DomainCatalogCard` n'a **aucune propriété** pour
      un nœud, une valeur ou un prérequis, et le loader YAML **refuse** un champ inconnu au
      lieu de l'ignorer. Une donnée qui n'existe pas ne fuit pas
- [x] **Le catalogue omet, il ne ment pas** : aucun compte total n'est affiché, précisément
      pour qu'un arbre retrouvé (DOM-10) puisse apparaître hors liste sans démentir l'écran
- [x] **L'arbre ouvert** : le détail complet, après parchemin
- [x] `DomainInfoController` sert le catalogue pour un arbre fermé, l'arbre pour un arbre
      ouvert — et **tranche avant** de lire la moindre compétence, pour qu'aucune variable de
      nœud n'existe dans ce contexte
- [x] L'ouverture d'un arbre est **notifiée** (`domain_opened`) — et une annonce ratée n'annule
      jamais l'ouverture : le parchemin est déjà consommé
- [x] **Au passage** : l'écran des arbres listait les domaines où le joueur avait de
      l'**expérience**. Deux défauts opposés — un arbre tout juste ouvert n'y apparaissait pas,
      et un arbre **fermé** pouvait y apparaître (`CrossDomainSkillResolver` crédite tous les
      domaines d'un nœud partagé). Twig et JSON listent désormais les arbres **ouverts**
- [x] **Au passage (2)** : l'élément `bois` livré par ZON-34 n'avait pas de pastille dans le
      système de design. Invisible tant qu'aucun écran ne rangeait les domaines par élément
- [x] Tests : le catalogue couvre exactement les arbres livrés (dans les deux sens) ; aucun
      nœud d'un arbre fermé n'est exposé — type, gabarits, écran Twig **et** payload JSON

### ONB-10 — Les cinq récoltes dans le périmètre de l'acte I (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-07-30
> Ferme **D11**. Le Fanal n'expose que **deux filons, tous deux d'herboristerie** (thym,
> lavande). Un choix parmi cinq parchemins de récolte qui débouche sur une seule récolte
> possible est un **faux choix** — et tout le monde deviendrait herboriste.
> Prérequis : ∅ (données de zone) ; à instruire avec **PLAN_ZONES**
- [x] **Les cinq récoltes atteignables** dans le périmètre de l'acte I. Quatre l'étaient déjà
      chez les voisins immédiats ; le minerai est à 10 min (les Mines), et **une carrière au
      Fanal n'était pas la réponse** : toute la ligne du cristal porte le préfixe `ore-`, et le
      Cristal sous la Voûte est un cœur, pas un gisement (loi tenue depuis ZON-32). C'est le
      **voyage offert** qui rend le choix réel, pas un filon de plus
- [x] **Le premier voyage est offert** : durée nulle, une seule fois. La faveur se consomme
      même si le voyage était déjà instantané — ce qui est offert est **le premier voyage**,
      pas la première attente, sinon elle devient une monnaie à optimiser. L'écran l'annonce
      **avant** le départ : un trajet de dix minutes qui n'en prend aucune, non annoncé, se lit
      comme un bug
- [x] Cohérence avec les lois de zone : le hub ne rend **rien de la ligne du cristal**, et
      `ActOnePerimeterTest` redit la règle depuis le périmètre de l'acte I — là où l'envie de
      densifier le hub reviendra
- [x] Tests : les quatre professions de filon et l'école du dépeceur atteignables dans le
      périmètre ; le hub ne rend aucun minerai ; le Fanal reste `safe` et sans population

### ONB-11 — Les mannequins d'entraînement (M | ★★★ | HAUTE) — ✅ LIVRÉ 2026-07-30
> Décision A13. Le Fanal est `safe: true`, donc `ExploreService` force `mob: 0` : **aucun
> combat n'y est possible**. Un combat **scripté par une quête** n'est pas un tirage de
> rencontre — le mannequin enseigne donc le combat au Fanal **sans lever sa sûreté**, et évite
> de faire voyager (donc attendre) un joueur avant son premier combat.
> Cadrage : GAME_ONBOARDING §5.3
- [x] Combat scripté hors tirage de rencontre — `TrainingFightLauncher` dresse le mannequin
      **le temps du combat**, rattaché à aucune zone. `safe: true` intact
- [x] **Mannequin n° 1** (`training_dummy_still`) : il tourne sur lui-même et **ne frappe
      jamais**. Le retour est placé *avant* la résolution du sort, et non après avec un dégât
      forcé à zéro : un mannequin inerte ne doit pas non plus appliquer d'effet de statut, ni
      déclencher d'alerte de danger, ni consommer un temps de recharge
- [x] **Mannequin n° 2** (`training_dummy_sparring`) : il riposte et **ne peut pas tuer**. Le
      plancher est posé dans `SpellApplicator`, là où la vie s'écrit — donc il vaut quel que
      soit le chemin (attaque, statut, riposte), et non seulement pour l'attaque de base
- [x] **Le plancher regarde l'agresseur, jamais la cible.** C'est ce qui empêche la clémence de
      fuir : un joueur qui sort du Fanal meurt comme tout le monde, dès le premier loup
- [x] Diégétique : `training_mode` à `null` désigne **un vrai monstre**, et c'est la valeur de
      tout ce qui vit dans le monde
- [x] **L'exclusion du tirage est posée au dépôt**, pas chez les appelants : explorer et chasser
      partent tous deux de `MobRepository`, et tout appelant à venir en héritera
- [x] Tests : les deux garanties, l'exclusion des deux requêtes de rencontre, le refus du
      lanceur sur un vrai monstre, et le mannequin sans zone
- [ ] **Reste à ONB-12** : la quête qui déclenche les deux combats

### ONB-12a — Les quatre gestes que la chaîne doit constater ✅ (M | ★★★ | HAUTE)
> Découpé d'ONB-12 (règle #8). Le moteur de quête savait compter des monstres, des objets, des
> zones et des PNJ ; il ne savait rien dire du **troisième temps** de la boucle. La moitié des dix
> étapes n'avait donc aucune condition de fin.
> Prérequis : ← ONB-11, ONB-20b
- [x] Une famille d'objectif unique, `gesture` : un **acte ponctuel dont la preuve est l'acte
      lui-même** — pas de cible structurée, donc pas quatre types
- [x] Quatre gestes, chacun prouvant **un tour de boucle entier** : porter une pièce (le nœud de
      port exige l'arbre, qui exige le parchemin), sertir une matéria (le nœud d'accord),
      lancer un sort, lancer une expédition
- [x] Le **lancer** compte, jamais le coup au but : rater n'est pas ne pas avoir appris
- [x] La cible est annoncée en **plusieurs lectures** (slug *et* famille d'arme ; slug *et*
      élément) — c'est ce qui permettra à l'étape 3 de demander « une matéria de votre élément »
- [x] Contrat vérifié **dans les deux sens** : un geste écrit dans une fixture existe, et un geste
      déclaré est réellement émis par un appelant
- [x] Piège fermé : `getPlayerQuestProgress()` rend **100** quand le total nécessaire vaut zéro —
      un type de suivi oublié produit une quête terminée dès son acceptation, pas une quête bloquée

### ONB-12b — La chaîne de l'acte I : dix quêtes, trois tours de boucle ✅ (L | ★★★ | HAUTE)
> Décision A14, et le cœur du dossier. Le tutoriel actuel commence par le voyage (**D6**, seul
> geste time-gaté) et **ne mentionne jamais la matéria** — la seule source d'actions de combat
> (règle 10) et le build du personnage.
> Prérequis : ← ONB-08, ONB-09, ONB-10, ONB-11, ONB-12a, ONB-15, ONB-16 ; croise NAR-20
- [x] La chaîne de GAME_ONBOARDING §5.2, en dix quêtes :
  - [x] **1 — Le maître d'armes.** Récompense : **une arme au choix** + **le parchemin de
        l'arbre qui l'autorise**
  - [x] **2 — Apprendre.** Parchemin → l'arbre s'ouvre → nœud d'équipement → arme équipée
        *(tour 1 de la boucle)*
  - [x] **3 — Le mannequin.** Combat n° 1. Récompense : **une matéria de l'élément du domaine
        choisi** + **les points de domaine pour en prendre l'accord** — on ne montre jamais une
        matéria qu'on ne peut pas utiliser
  - [x] **4 — L'accord.** Nœud d'accord → matéria sertie *(tour 2)*
  - [x] **5 — Le second mannequin.** Lancer le sort
  - [x] **6 — Le métier.** Un parchemin de récolte **parmi les cinq** *(tour 3)*
  - [x] **7 — La récolte.** Aller dans la zone, récolter (l'exploration est le moyen, pas une
        étape : on explore pour trouver où récolter)
  - [x] **8 — L'atelier.** Fabriquer — la première fois qu'un geste ne coûte pas d'énergie
  - [x] **9 — Le départ.** Voyager vers une vraie zone : **le voyage coûte du temps réel**, et
        on le dit
  - [x] **10 — L'expédition.** En lancer une avant de fermer — **comment quitter le jeu en le
        laissant travailler**, la leçon qui fait revenir au jour 2
- [x] **Le choix est réel à chaque tour** : quelle arme, quel élément, quel métier, où partir
- [x] La récompense de l'étape 3 est **dérivée du domaine choisi à l'étape 1** — pas un objet fixe
- [x] Une étape = **une** quête de l'arc `intro`
- [x] Tests : la matéria est garantie, accordée et lancée ; les cinq métiers et les armes sont
      réellement proposés ; **aucune étape avant la 9ᵉ n'est time-gatée** ; la matéria reçue
      correspond à l'élément du domaine choisi

### ONB-13 — Le foyer d'attache constaté à la clôture ✅ (M | ★★ | MOYENNE)
> Ferme **D8**, applique l'**amendement à GAME_WORLD §13.1** (A9) : le foyer d'attache ne se
> choisit pas, **il se gagne**.
> Prérequis : ← ONB-12 ; croise NAR-20 (la lettre) et FAC-01
- [x] Mesure de l'activité par zone pendant l'acte I ; foyer constaté à la clôture
- [x] Défaut sans activité distinctive : **le Fanal**
- [x] Ce qu'il apporte : une **lettre** suggérant une destination, **un PNJ qui vous connaît**,
      **un cran de réputation**, **une ligne au journal**
- [x] **Aucun contenu ouvert ou fermé, aucun arbre mis en avant, aucun bonus de rendement** —
      verrouillé par un test
- [x] Tests : foyer dérivé des gestes et non de la race ; défaut au Fanal ; aucun contenu gaté

---

## Piste D — L'acte I répare (parallélisable)

### ONB-14 — Une seule source d'état d'onboarding ✅ (S | ★★ | MOYENNE)
> Ferme **D7**.
> Prérequis : ← ONB-12
- [x] L'arc `intro` devient la **source** ; `TutorialStep` devient une **projection**
- [x] « Passer le tutoriel » et « abandonner l'arc » deviennent le même geste
- [x] `tutorial-complete` reste attaché à la clôture de l'arc
- [x] Tests : aucun état d'onboarding écrit à deux endroits (test de contrat)

### ONB-15 — Réparer les quêtes `explore` de l'arc (S | ★★★ | HAUTE) — ✅ LIVRÉ 2026-07-29
> Ferme **D4**. Le diagnostic était **en dessous de la réalité** : `map_id => 1` ne désigne pas
> le village mais la **« Carte de test »**, que `MapFixtures` crée en premier. Ces objectifs ne
> visaient pas un mécanisme mort, ils visaient une zone qui n'existe pas.
- [x] Convertir les trois objectifs en objectifs du pivot — les trois deviennent des `talk_to`,
      chacun vers **un autre PNJ que le donneur** (les trois sont données par Claire la Sage :
      on ne demande pas de retourner voir celui qu'on vient de quitter)
- [x] **Aucune quête d'introduction ne dépend de `map_id` ni de coordonnées**
- [x] Balayer les autres arcs : saison 1, quêtes de découverte (10), quête cachée de la
      clairière, chaîne de fond de la Forêt, les 4 fragments de l'acte 2, les deux quêtes
      morales, l'exploration de la forêt, le choix d'allégeance et les deux escortes
- [x] **Deux dettes trouvées au passage, hors énoncé** —
  - [x] l'**épilogue de l'acte 3** visait « le cœur du Nexus » recalé sur une carte de **donjon**,
        qu'aucune zone ne prend pour origine : l'arc principal se terminait sur une étape
        impossible. Il se termine chez Claire la Sage
  - [x] les porteurs de l'arc (Claire, Gérard, Marie, Antoine) habitaient `map_1`, donc **aucune
        zone** — or l'écran de zone liste par zone depuis ZON-27. Ils étaient injoignables, ce
        qui condamnait déjà l'étape « guilde » avant même les trois `explore`. Les quatre
        emménagent au village ; la fusion des deux populations reste à **ONB-16**
- [x] Tests : `QuestCoordinateContractTest` — aucune coordonnée dans `intro`, aucun verbe
      hors pivot, tout `talk_to` désigne quelqu'un **et** ce quelqu'un habite une zone,
      et le balayage global (aucune quête, nulle part)
- **Hors périmètre, assumé** : `defend` porte bien un `map_id`, mais il se résout par
  `Mob::map` et fonctionne — ce n'est pas la même faute

### ONB-16 — Une population de PNJ au Fanal, dont le maître d'armes (M | ★★ | MOYENNE) — ✅ LIVRÉ 2026-07-30
> Ferme **D5** : `PnjFixtures` (Gérard le Forgeron, Marie la Herboriste, Claire la Sage) et
> `VillageHubPnjFixtures` (Aldric le Forgeron, Iris l'Alchimiste, Lyra la Guide…) coexistent —
> **deux forgerons**, et **aucun maître d'armes** alors que la chaîne (ONB-12) commence chez lui.
> Prérequis : à faire **avec NAR-20**, sinon on renomme deux fois
- [x] Rôle en double fusionné : le Fanal comptait **deux forgerons**. Gérard reste — les quêtes
      le désignent par sa référence de fixture (`pnj_0`), on ne débranche pas un donneur — et
      l'autre poste **devient le maître d'armes**. Le transformer plutôt que d'en ajouter un
      neuvième règle les deux dettes d'un coup, et l'étal d'armes déjà écrit sert exactement le
      bon personnage
- [x] **Le maître d'armes** existe (Ysold), et il vend les parchemins des six arbres de combat
      qui apprennent à tenir une arme de palier 1 (ONB-08/ONB-20b)
- [x] Chaque PNJ de métier du Fanal vend le parchemin de son arbre — alchimie chez Iris, forge
      et herboristerie chez leurs porteurs d'arc. **C'est la moitié d'ONB-08 qui restait
      ouverte** : les 36 parchemins existaient, aucun n'avait de marchand
- [x] Slugs comme clé d'idempotence (convention ZON-26b-b) — sans eux, la seule façon de
      retrouver un habitant est son **nom affiché**, ce qui interdit de le renommer. ZON-39, qui
      réécrit précisément les libellés, arrive juste après
- [x] **Au passage** : « Aldric » cesse d'être porté par deux personnages. **Aldric l'Ancien**
      est l'ermite de la Crête, donneur de quête de l'acte 2 — deux Aldric à trois zones
      d'écart, dont un seul compte pour une quête
- [x] Tests : aucun métier tenu deux fois ; chaque parchemin de l'acte I a un vendeur, et chaque
      parchemin en rayon ouvre un arbre qui existe
- [ ] **Reste** : les soixante habitants historiques de `PnjFixtures` restent sur la carte de
      test. Les basculer noierait l'écran du Fanal (plafonné à 20) — c'est un sujet de contenu,
      pas de dette

---

## Piste E — Apprentissage et preuve

### ONB-17 — Le coach par écran ✅ (M | ★★ | MOYENNE)
> Ferme **D10**. Décision A3.
> Prérequis : ← ONB-05, ONB-09 ; croise WIK-02 et RET-08→10
- [x] `Player.seenCoachMarks` (tableau JSON de slugs) — pas de nouvelle entité
- [x] Composant Twig + contrôleur Stimulus : deux phrases, le geste, **son coût en énergie**,
      une croix. Ne revient jamais seul
- [x] Les **dix** encarts déclarés dans `CoachMark` ; **trois branchés** (zone, combat,
      inventaire) — les sept autres sont une ligne de gabarit chacun (**ONB-17b**)
- [x] **ONB-17b ✅ LIVRÉ 2026-08-02** — les sept écrans restants branchés : catalogue des
      arbres, quêtes, artisanat, carte du monde, hub, marché, guilde (constaté au code :
      les dix `coach_mark(...)` sont dans leurs gabarits, traductions comprises)
- [x] Le coach de combat s'affiche sur **le premier mannequin** — le seul combat où lire ne tue
      pas (ONB-11)
- [x] **C1** : jamais un système inutilisable · **C2** : toujours le coût · **C3** : à
      l'arrivée, jamais au temps écoulé
- [x] Le coach du **hub** n'apparaît qu'après l'acte I
- [x] Relecture depuis l'aide — chaque encart pointe vers **sa** page du wiki
      (`CoachMark::helpPage()`, WIK-02 livré), et un test refuse toute page morte
- [x] Le coach est **par personnage**
- [x] Tests : affichage unique, persistance, C1/C2/C3 respectées, idempotence de la fermeture

### ONB-18 — Écrans d'entrée au design system ✅ (S | ★★ | MOYENNE)
> L'écran de connexion est le **premier écran du jeu**, et le seul qui ne ressemble pas au jeu.
> Prérequis : ← ONB-05
- [x] Connexion, limite de personnages, les quatre pas, l'écran d'éveil — composants Parchemin.
      **Le mot de passe oublié n'existe pas** (ONB-02, bloqué faute de `symfony/mailer`) ;
      l'inscription était déjà propre
- [x] Une seule action primaire par écran ; chiffres en monospace (`.ds-field--num`) ; l'état
      « limite atteinte » dit désormais **quoi faire**, au lieu d'annoncer un refus
- [x] Aucun nom Tailwind d'avant la v4, et plus aucune rampe `gray-*`/`purple-*` sur ces écrans
- [x] Le tunnel se traverse au pouce : `.ds-field` fait 44 px, comme `.ds-btn` — un champ plus
      court qu'un bouton se rate une fois sur trois

### ONB-19a — Les invariants du plan, tenus par un test ✅ (S | ★★ | MOYENNE)
> Sans mesure, on répare à l'aveugle (cadrage §9).
> Prérequis : ← pistes A, B, C
- [x] `OnboardingPlanContractTest` — **huit** invariants tenus au même endroit : aucune quête
      d'`intro` par carte (15) ; la matéria garantie, accordée et lancée (12) ; le parchemin
      reste un coût — prix unique, aucun prérequis, les trois premiers donnés (08) ; le
      catalogue n'expose aucun nœud d'arbre fermé (09) ; aucun contenu gaté par la race ni par
      le foyer (07, 13) ; un seul état d'onboarding (14) ; aucune décision de build dans le
      tunnel (05) ; le coach n'explique jamais un système fermé (17)
- [x] **ONB-19b livré le 2026-07-31** — sept indicateurs : inscriptions → personnages ;
      abandon dans le tunnel ; personnages → acte I terminé ; répartition des armes, éléments
      et métiers choisis ; répartition des peuples ; % vérifiés à J+7 ; retour à J+1 et J+7
- [x] La répartition des métiers est l'indicateur de santé de **D11** ; celle des peuples,
      l'indicateur d'équilibre de **ONB-07**
- [x] Exposition dans l'admin : `/admin/onboarding`, sous « Joueurs »
> **Aucun indicateur ne mesure une intention** : chacun se dérive d'un état déjà en base — un
> compte sans personnage *est* un abandon, un foyer réclamé *est* un acte I terminé, un arbre
> ouvert *est* un choix fait. Rien n'a été instrumenté pour ce jalon, ce qui garantit que la
> mesure ne dérive pas de ce qu'elle mesure.
>
> **Les trois répartitions sortent d'une seule table.** `PlayerDomainAccess` porte les arbres
> ouverts et `Domain` porte déjà les deux bornes de DOM-01 : le registre nomme l'arme, son
> absence nomme le métier (*« un `null` sur `Domain::register` dit hors combat, jamais
> registre inconnu »*). Ajouter un élément ou un registre étend la mesure le jour même.
>
> **Un écart de mesure, nommé** : `Player::lastActivityAt` ne garde que la *dernière*
> activité, pas un historique. L'écran ne dit donc pas « revenu le lendemain » mais **« encore
> actif à J+1 »**, ce qui est exactement ce que la donnée sait. Un indicateur qui promet plus
> que sa donnée est pire que pas d'indicateur.
>
> ⚠️ **Le % vérifiés à J+7 restera à 0 %** tant qu'ONB-02/04 ne sont pas livrés : rien
> n'écrit `emailVerifiedAt`. L'écran le dit en toutes lettres, et un test tient l'accord
> **dans les deux sens** — le jour où un chemin de code renseignera la colonne, il tombera et
> l'avertissement devra partir. Une note qui survit à sa raison d'être est un mensonge de plus.
> L'invariant « un seul point de décision pour la porte » (**04**) rejoindra
> `OnboardingPlanContractTest` avec eux.

### ONB-20 — Mains nues, et le port de l'équipement par nœuds d'entrée (L | ★★★ | HAUTE)
> Ferme **D13** et rend applicables **A18** et **A19**. Deux choses à la fois, indissociables :
> le repli qui empêche la doctrine d'enfermer un joueur, et la règle de port qu'elle suppose.
> `PlayerAttackHandler::getItem()` lève aujourd'hui `EntityNotFoundException('Player attack
> impossible')` dès qu'aucune arme n'est équipée. **À livrer avant ONB-08.**
> ⚠️ **Amende DOM-02 garde-fou 1** — à relire avec DOM avant de coder (cadrage §6.0 bis).
- [x] **ONB-20a ✅ LIVRÉ 2026-07-29 — Les mains nues existent** : attaque faible, sans
      emplacement de matéria, **toujours disponible**. Aucun chemin de combat ne peut échouer
      faute d'arme. Découpé en sous-jalon au titre de la règle #8 de `CLAUDE.md` : c'est le
      repli qui débloque ONB-08, et il n'a pas à attendre l'échelle de port
  - [x] `BareHandsAttack` — le sort le plus faible du jeu, sans élément, sans effet de statut,
        sans critique, porté par aucun objet donc sans emplacement de matéria
  - [x] Chances de toucher d'un **geste sans entraînement** : le facteur qu'`ItemHitResolver`
        applique déjà à un objet dont le joueur ne connaît pas le domaine. On ne frappe pas
        mieux en ne sachant rien
  - [x] Un sort manquant en base **rate le coup**, il ne lève pas — une exception ici
        recréerait exactement le défaut réparé
  - [x] Une arme sans sort vaut une main vide
- **ONB-20b-a ✅ LIVRÉ 2026-07-30 — l'échelle de port des armes.** Découpé au titre de la
  règle #8 : les armes portent l'échelle héritée et le défaut le plus net (porter une hache
  imposait le feu, porter un bâton imposait un arbre de mêlée). Les armures et les outils de
  métier suivent en **ONB-20b-b**
- [ ] Le prérequis d'équipement par compétence est **déjà en place** (`Item::requirements`
      ManyToMany vers `Skill`, `PlayerItemHelper::canBeEquipped()` qui exige *toutes* les
      compétences) — rien à construire, seulement à généraliser
- [ ] **Les nœuds de port sont les points d'entrée gratuits des arbres** (0 point de domaine).
      Ouvrir un arbre livre immédiatement son kit de port — un maître mage ouvre *port du
      bâton*, *port de la baguette*, *port du tissu*. **Aucun « parchemin de port » à créer** :
      le compte reste à 32 parchemins, un par arbre
- [ ] **Le port est une échelle, par famille / ligne / outil** : échelon 1 = le port de base
      (palier T1), **gratuit**, nœud d'entrée de l'arbre ; échelons suivants **paliés et
      chaînés** pour les pièces évoluées — l'arc à poulie exige l'arc, le marteau de précision
      exige le marteau ordinaire
- [ ] **Les compétences d'arme existantes SONT cette échelle et ne bougent pas.**
      `soldier_weapon_t2` → `soldier_weapon_t3`, `berserk_weapon_t2` → `t3` : chaînées,
      conservées telles quelles. Ce qui manque est **l'échelon 1** (les armes T1 n'ont aucun
      prérequis aujourd'hui) et **les échelles d'armures et d'outils**, inexistantes
- [ ] **L'échelle suit les paliers d'objets déjà en place** (T1/T2/T3) — n'inventer aucun cran
- [ ] **Un échelon de port n'est jamais seul sur un palier d'arbre** : il accompagne des
      passifs, sinon le rang est un péage
- [ ] Bénéfice à exploiter : l'échelle donne aux **arbres d'artisanat** une raison de monter
      qui n'est pas la liste des recettes — de meilleurs outils
- [ ] **Nœuds partagés : plusieurs chemins pour la même chose.** « Port de la hache de
      guerre » existe dans tous les arbres qui l'enseignent ; **en ouvrir un seul suffit**.
      `Skill::domains` est déjà un ManyToMany
- [ ] **Jamais borné par l'élément.** Les prérequis actuels sont nommés par domaine
      (`berserk_weapon_t2` = feu × mêlée, `knight_weapon_t2` = métal × mêlée) et `steel-axe`
      porte `'domain' => 'soldier'` : pris tels quels, porter une hache imposerait un élément,
      ce que DOM-01 a séparé
- [ ] **L'arme de métier est un nœud entièrement séparé.** Hache de guerre (arbres de mêlée)
      et hache de bûcheron (arbre du bûcheron, ZON-34/DOM-05) : deux nœuds, deux buts. Idem
      pioche, canne, couteau à dépecer — nœuds d'entrée de leurs métiers
- [ ] **L'affordance (A19)** : une pièce non portable dit **ce qui manque et où l'apprendre**,
      jamais un grisé muet. Le crochet existe — `EquipmentController` renvoie `'locked_skill'`
- [ ] Migration : les personnages existants gardent ce qu'ils peuvent déjà équiper
- [ ] Tests : attaque possible sans arme ; aucun `EntityNotFoundException` sur un chemin de
      combat ; un échelon ouvert dans **un** arbre autorise la pièce partout ; aucune pièce
      n'exige un élément précis ; **l'échelon 1 de toute échelle est gratuit** ; **aucun palier
      d'arbre ne contient qu'un échelon de port** ; le kit T1 de l'acte I est portable avec le
      seul parchemin donné ; toute pièce non portable expose son manque et son lieu
      d'apprentissage

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
