# Plan — Compte, personnage et arrivée en jeu

> **Numérotation :** les jalons de **ce** document sont préfixés **ONB-** (Onboarding).
> Ils n'entrent **pas** en conflit avec les numéros de la roadmap globale
> (`SPRINT_*.md`) ni avec les jalons **NAR-** / **ZON-** / **ECO-** / **FOY-** / **DOM-** /
> **FAC-** / **RET-** / **REP-** / **WIK-**.

> Décline [../GAME_ONBOARDING.md](../GAME_ONBOARDING.md) — la source de vérité de tout ce
> qui se passe **avant qu'un joueur soit un joueur**. Trois décisions y sont actées et
> commandent ce plan : la vérification d'e-mail est **différée** derrière une porte
> économique et sociale (A1), le compte et le personnage forment un **tunnel unique en
> quatre pas** (A2), l'interface s'apprend par un **coach par écran** et non par une visite
> guidée (A3).
>
> Le plan s'appuie sur ce qui existe et n'invente aucun moteur : `CharacterController`,
> `PlayerFactory`, `TutorialManager`, l'arc de quêtes `intro` (NAR-03/04), `PlayerHubDigest`,
> `ForbiddenNameChecker`, le design system Parchemin.

## Vue d'ensemble

**15 jalons** (**ONB-01** à **ONB-15**) organisés en 5 pistes.

| Code | Sujet (résumé) | Taille | Priorité |
|------|----------------|--------|----------|
| ONB-01 | Inscription — le compte peut naître (ferme D1) | M | ★★★ |
| ONB-02 | Mailer + mot de passe oublié (ferme D2) | M | ★★★ |
| ONB-03 | Durcissement de la connexion (ferme D3) | S | ★★★ |
| ONB-04 | Vérification d'e-mail différée et sa porte | M | ★★★ |
| ONB-05 | Le tunnel en 4 pas — coquille et fil narratif | M | ★★★ |
| ONB-06 | Le nom : unicité robuste et immédiate (ferme D9) | S | ★★★ |
| ONB-07 | Le peuple porte le foyer d'attache (ferme D8) | M | ★★★ |
| ONB-08 | Le foyer d'attache filtre l'écran des arbres | S | ★★★ |
| ONB-09 | Réordonner le tutoriel, y mettre la matéria (ferme D6) | M | ★★★ |
| ONB-10 | Une seule source d'état d'onboarding (ferme D7) | S | ★★ |
| ONB-11 | Réparer les quêtes `explore` de l'arc (ferme D4) | S | ★★★ |
| ONB-12 | Une seule population de PNJ au Fanal (ferme D5) | M | ★★ |
| ONB-13 | Le coach par écran (ferme D10) | M | ★★ |
| ONB-14 | Écrans d'entrée au design system (ferme la dette visuelle) | S | ★★ |
| ONB-15 | Instrumentation du tunnel + tests de contrat | M | ★★ |

```
Piste A — Le compte existe   : ONB-01 → ONB-02 → ONB-03 → ONB-04
Piste B — Le tunnel          : ONB-05 → ONB-06 → ONB-07 → ONB-08
Piste C — L'acte I répare    : ONB-09 → ONB-10, ONB-11, ONB-12   (parallélisables)
Piste D — L'apprentissage    : ONB-13
Piste E — Finition & preuve  : ONB-14, ONB-15
```

**Ordre de valeur/effort** : `Piste A → Piste B → Piste C → Piste D → Piste E`.

La piste A n'est pas prioritaire par confort : **tant qu'elle n'est pas livrée, le jeu n'a
littéralement aucun joueur possible**. Les trois dettes rouges de GAME_ONBOARDING §2 (D1,
D2, D3) y sont concentrées, et ONB-02 est la plus urgente des trois après ONB-01 — un jeu
où perdre son mot de passe signifie perdre son personnage ne retient personne.

**Coupe minimale jouable** : ONB-01 + ONB-02 + ONB-03 + ONB-11. À ce point, un inconnu peut
créer un compte, le récupérer, et traverser l'acte I sans buter sur une quête morte. Tout le
reste améliore ; ces quatre-là débloquent.

**Croisements avec les autres plans** — à tenir, sous peine de livrer un tunnel neuf déjà
hors canon :

| Jalon voisin | Lien |
|---|---|
| **NAR-20** (le réveil au Fanal) | ONB-07, ONB-09, ONB-11 et ONB-12 touchent les mêmes textes et les mêmes PNJ. **À faire dans la même vague**, sinon les écrans neufs naissent avec les anciens noms |
| **WIK-02** (contrôleur `/wiki`) | ONB-13 : « relire cette explication » pointe vers le wiki. Sans WIK-02, le lien va nulle part — dégradation acceptable, à noter |
| **RET-08→10** (le tableau du lundi) | ONB-13 : le coach du hub arrive **après** l'acte I, sur un hub qui aura son bloc « La semaine » |
| **FAC-01** (une seule faction portée) | ONB-07 : la réputation de départ d'un cran doit respecter la règle de faction portée unique |

---

## Piste A — Le compte existe (séquentiel)

### ONB-01 — Inscription : le compte peut naître (M | ★★★ | CRITIQUE)
> Ferme **D1**. `RegistrationController::__invoke()` lève aujourd'hui un `NotFoundHttpException` :
> aucun compte ne peut être créé hors fixtures. C'est le jalon qui rend le jeu accessible.
- [ ] `RegistrationFormType` : **trois champs** — e-mail, mot de passe, acceptation des règles.
      Pas de pseudo de compte (`User::username` reste nullable et inutilisé), pas de
      confirmation de mot de passe (bouton « afficher » à la place) — cf. GAME_ONBOARDING §3.1
- [ ] Contraintes : e-mail valide et unique, mot de passe ≥ 10 caractères, hachage `auto`
- [ ] `emailVerifiedAt` nullable sur `User` (+ migration idempotente) — le compte naît **non
      vérifié et pleinement jouable**
- [ ] Authentification automatique après inscription, puis redirection vers le tunnel de
      personnage (ONB-05 ; d'ici là, vers `app_character_create`)
- [ ] Limiteur de débit à l'inscription : 5 comptes / heure / IP
- [ ] Lien « créer un compte » depuis l'écran de connexion et depuis l'accueil public
- [ ] Tests : création nominale, e-mail déjà pris, mot de passe trop court, limiteur

### ONB-02 — Mailer et mot de passe oublié (M | ★★★ | CRITIQUE)
> Ferme **D2**, la dette la plus dangereuse du dossier : aujourd'hui, perdre son mot de passe
> revient à perdre son personnage, son inventaire, sa guilde et sa place dans un foyer.
> Prérequis : ← ONB-01
- [ ] Installer `symfony/mailer` **dans Docker** (règle 1) ; `MAILER_DSN` en variable
      d'environnement, transport `null://` en test
- [ ] Demande de réinitialisation : **réponse identique** que le compte existe ou non ;
      limiteur de débit sur la demande
- [ ] Jeton à usage unique, **1 heure**, un seul actif par compte, stocké haché
- [ ] Réinitialisation : nouveau mot de passe + **invalidation de toutes les sessions**
- [ ] Gabarits d'e-mail au ton du jeu (et non au ton d'un SaaS), en français, avec repli texte
- [ ] Tests : jeton expiré, jeton rejoué, compte inexistant (réponse constante), invalidation

### ONB-03 — Durcissement de la connexion (S | ★★★ | CRITIQUE)
> Ferme **D3**. Le firewall n'a aucun garde-fou, et `User::isBanned` existe sans être lu nulle
> part : un compte banni se connecte normalement.
- [ ] `login_throttling` : 5 essais / 15 min par identifiant, 30 / 15 min par IP
- [ ] Lecture de `isBanned` au login **et** en session courante, avec un message clair
      (jamais un 500, jamais un accès partiel)
- [ ] Message d'erreur **unique** (« Identifiants invalides ») — ne jamais révéler qu'un
      e-mail existe
- [ ] `remember_me.lifetime` : 7 j → **30 j** (GAME_ONBOARDING §3.3)
- [ ] Redirection post-login **selon l'état** : aucun personnage → tunnel ; un personnage →
      zone si l'arc `intro` est en cours, hub sinon ; plusieurs → sélection
- [ ] Tests : throttling, banni refusé, redirection par état

### ONB-04 — La vérification différée et sa porte (M | ★★★ | HAUTE)
> Le cœur de la décision A1 : la vérification ne barre pas le jeu, elle barre **tout ce qui
> sort du joueur vers les autres** (GAME_ONBOARDING §3.2).
> Prérequis : ← ONB-02 (mailer)
- [ ] E-mail de vérification à l'inscription, jeton renvoyable ; `emailVerifiedAt` posé au clic
- [ ] **Un seul point de décision** — un `EmailVerificationGate` (ou un voter) consulté par
      toutes les portes, jamais un `if` recopié dans chaque contrôleur
- [ ] Portes fermées : chat de zone et global, hôtel des ventes (achat **et** vente), échoppe
      joueur, don/commerce direct, guilde, groupe, donjon, messages privés, amis, et
      **livraison d'une commission à un foyer**
- [ ] Écran de porte : dit ce qui est verrouillé, pourquoi, et propose « renvoyer le lien ».
      Jamais un message d'erreur générique
- [ ] Rappel : une ligne discrète au hub (jamais une modale), e-mail à J+1 et J+3, puis silence
- [ ] **Aucun blocage rétroactif** : ce qui a été gagné avant vérification reste acquis
- [ ] Tests : chaque porte fermée puis ouverte, jeton rejoué, absence d'effet rétroactif,
      et un test de contrat qui échoue si une porte contourne le point de décision unique

---

## Piste B — Le tunnel (séquentiel)

### ONB-05 — Le tunnel en quatre pas (M | ★★★ | HAUTE)
> Décision A2. Aujourd'hui, inscription et création de personnage sont deux formulaires
> administratifs d'affilée. Le joueur ne doit jamais sentir qu'il franchit deux systèmes.
> Prérequis : ← ONB-01
- [ ] Séquence continue : **compte → nom → peuple → visage**, un écran par pas, une décision
      par écran, une phrase de fiction par écran
- [ ] Barre de progression, retour arrière partout, aucune saisie perdue
- [ ] Écran d'éveil final : un paragraphe, **un seul bouton**, qui mène à **l'écran de zone**
      — jamais au hub (décision A4, GAME_ONBOARDING §0)
- [ ] Mention explicite de ce qui est réversible et de ce qui ne l'est pas (§4.3)
- [ ] Les écrans séparés existants (`app_character_create`, `select`, `customize`) restent
      la voie du **2ᵉ personnage** et de l'apparence
- [ ] `limit_reached` : dit **quoi faire**, pas seulement « non » (état vide du design system)
- [ ] Tests : parcours complet, retour arrière, reprise d'un tunnel interrompu

### ONB-06 — Le nom : unicité robuste et immédiate (S | ★★★ | HAUTE)
> Ferme **D9**. `findOneBy(['name' => $name])` est sensible à la casse et aveugle aux
> homoglyphes ; et l'erreur ne tombe qu'après que le joueur a tout rempli — le point
> d'abandon le plus prévisible du tunnel.
> Prérequis : ← ONB-05
- [ ] Unicité **insensible à la casse** et normalisation des homoglyphes (`Aldric` / `aldric` /
      `Аldric` cyrillique sont le même nom) — colonne normalisée + index unique
- [ ] Vérification **au fil de la frappe** (indicateur sous le champ), sans révéler autre chose
      que « libre / pris »
- [ ] `ForbiddenNameChecker` appliqué à la forme normalisée, pas seulement à la saisie brute
- [ ] Bouton « proposer un nom », tirant dans une table par peuple (contenu ← NAR-20)
- [ ] Tests : casse, homoglyphes, nom interdit, course entre deux créations simultanées

### ONB-07 — Le peuple porte le foyer d'attache (M | ★★★ | HAUTE)
> Ferme **D8** et rend enfin réel GAME_WORLD §13.1 : aujourd'hui la race n'est qu'un
> modificateur de statistiques, et le foyer d'attache n'existe **nulle part** dans le code.
> Prérequis : ← ONB-05 ; croise NAR-20 (textes) et FAC-01 (faction portée unique)
- [ ] `Race` porte le foyer d'attache : destination, kit, faction de départ, arbres mis en avant
- [ ] Écran du peuple **inversé** : qui vous recueille et où vous irez d'abord ; les
      modificateurs de stats en second, en une ligne factuelle (GAME_ONBOARDING §4.2)
- [ ] `PlayerFactory` applique le kit et le cran de réputation de départ
- [ ] **Le foyer d'attache n'ouvre ni ne ferme aucun contenu** — un Nain peut devenir
      herboriste. À verrouiller par un test, c'est la garantie qui rend le choix sûr
- [ ] Tests : les quatre peuples, kit appliqué, réputation appliquée, aucun contenu gaté

### ONB-08 — Le foyer d'attache filtre l'écran des arbres (S | ★★★ | HAUTE)
> La seule parade sérieuse au risque n° 1 de l'acte I : *32 arbres visibles au jour 1, c'est
> un mur* (GAME_PROGRESSION §3).
> Prérequis : ← ONB-07
- [ ] `/game/skills` s'ouvre par défaut sur les **3-4 arbres du foyer d'attache**
- [ ] « Voir les 32 » toujours accessible, jamais mis en avant, et **mémorisé** une fois choisi
- [ ] Aucun arbre n'est masqué au sens des droits — c'est un filtre d'affichage, pas une porte
- [ ] Tests : filtre par peuple, bascule mémorisée, aucun arbre rendu inaccessible

---

## Piste C — L'acte I répare (ONB-09 en tête, le reste parallélisable)

### ONB-09 — Réordonner le tutoriel et y mettre la matéria (M | ★★★ | HAUTE)
> Ferme **D6** et le trou le plus grave du tutoriel actuel : il **ne mentionne jamais la
> matéria**, c'est-à-dire le cœur du jeu (règle 10) et le build du personnage. Et il commence
> par le voyage, le seul geste qui fait attendre.
> Prérequis : ← ONB-11 recommandé (quêtes réparées) ; croise NAR-20
- [ ] Sept étapes dans l'ordre acté (GAME_ONBOARDING §5.2) : récolte → explorer/combattre →
      butin et sac → **matéria (trouver, accorder, sertir, lancer)** → arbre → atelier → voyage
- [ ] Le voyage **en dernier** : c'est la porte de sortie de l'acte I, pas son entrée
- [ ] L'étape matéria est **non négociable** : la chaîne doit garantir une matéria en main,
      son accord à 0 point expliqué, et un sort effectivement lancé en combat
- [ ] Une étape de tutoriel = **une** quête de l'arc `intro` (sept pour sept)
- [ ] Tests : la matéria est garantie ; aucune étape avant le voyage n'est time-gatée

### ONB-10 — Une seule source d'état d'onboarding (S | ★★ | MOYENNE)
> Ferme **D7** : 5 `TutorialStep` d'un côté, 7 quêtes d'arc de l'autre, sans correspondance.
> Deux sources de vérité pour « où en est le nouveau ».
> Prérequis : ← ONB-09
- [ ] L'arc `intro` devient la **source** ; `TutorialStep` devient une **projection** de son
      avancement, utilisée pour l'affichage et le surlignage
- [ ] « Passer le tutoriel » et « abandonner l'arc » deviennent le même geste
- [ ] Le succès `tutorial-complete` reste attaché à la clôture de l'arc
- [ ] Tests : aucun état d'onboarding écrit à deux endroits (test de contrat)

### ONB-11 — Réparer les quêtes `explore` de l'arc (S | ★★★ | HAUTE)
> Ferme **D4**. Trois des sept quêtes d'`intro` valident un `explore` sur `map_id => 1` et des
> coordonnées. Post-ZON-21, `PlayerQuestUpdater::updateExplored()` résout par **zone** : les
> trois pointent la même zone, et ne se déclenchent qu'au voyage — donc jamais pour un joueur
> qui n'a pas bougé. **L'arc d'introduction est bloqué dès sa première étape.**
- [ ] Convertir les trois objectifs en objectifs du pivot : parler à un PNJ, réussir une
      récolte, mener un combat
- [ ] **Aucune quête d'introduction ne dépend de `map_id` ni de coordonnées**
- [ ] Balayer les autres arcs pour la même faute (`acte4`, quêtes de zone, saisonnières)
- [ ] Tests : l'arc `intro` se termine de bout en bout sans quitter la zone de départ, et un
      test de contrat qui échoue si une quête d'`intro` réintroduit un `explore` par carte

### ONB-12 — Une seule population de PNJ au Fanal (M | ★★ | MOYENNE)
> Ferme **D5** : `PnjFixtures` (Gérard le Forgeron, Marie la Herboriste, Claire la Sage —
> porteurs de l'arc) et `VillageHubPnjFixtures` (Aldric le Forgeron, Iris l'Alchimiste, Lyra
> la Guide…) coexistent. **Le nouveau venu rencontre deux forgerons dans le même village.**
> Prérequis : ← à faire avec NAR-20 (renommage canon), sinon on renomme deux fois
- [ ] Une seule population au Fanal ; les porteurs de l'arc en font partie
- [ ] Les rôles en double (forgeron, herboriste/alchimiste) fusionnés, dialogues reportés
- [ ] Les slugs deviennent la clé d'idempotence (convention ZON-26b-b)
- [ ] Tests : aucun rôle en double dans la zone de départ

---

## Piste D — L'apprentissage

### ONB-13 — Le coach par écran (M | ★★ | MOYENNE)
> Ferme **D10**. Décision A3 : pas de visite guidée — **un écran jamais ouvert se présente
> lui-même, une fois** (GAME_ONBOARDING §6).
> Prérequis : ← ONB-05 ; croise WIK-02 (le lien « relire ») et RET-08→10 (le hub)
- [ ] `Player.seenCoachMarks` (tableau JSON de slugs) — pas de nouvelle entité, pas de table
- [ ] Composant Twig + contrôleur Stimulus : deux phrases, le geste proposé, **son coût en
      énergie**, une croix. Ne revient jamais seul
- [ ] Les huit écrans d'ouverture + les deux différés (§6.2)
- [ ] **C1** : ne jamais parler d'un système inutilisable — le marché ne se présente pas avant
      que le joueur ait quelque chose à vendre **et** son e-mail vérifié
- [ ] **C2** : chaque encart d'action affiche le coût en énergie
- [ ] **C3** : déclenchement à l'arrivée, jamais au temps écoulé ; aucune séquence enchaînée
- [ ] Le coach du **hub** n'apparaît qu'après l'acte I (avant, le hub n'a rien à montrer)
- [ ] Relecture depuis l'aide (lien wiki ; dégradation acceptable tant que WIK-02 n'est pas là)
- [ ] Le coach est **par personnage** — un second personnage d'un autre peuple le rejoue
- [ ] Tests : affichage unique, persistance, C1 respectée, aucun coach au retour d'absence

---

## Piste E — Finition et preuve

### ONB-14 — Écrans d'entrée au design system (S | ★★ | MOYENNE)
> L'écran de connexion est le **tout premier écran du jeu**, et c'est le seul qui ne ressemble
> pas au jeu : rampes `gray-*` / `purple-*` héritées, aucun composant `.ds-*`.
> Prérequis : ← ONB-05
- [ ] Connexion, inscription, mot de passe oublié, les quatre pas du tunnel et l'écran d'éveil
      repris avec les composants du design system Parchemin
- [ ] Une seule action primaire par écran ; tout chiffre en monospace ; états vides qui disent
      quoi faire
- [ ] Aucun nom Tailwind d'avant la v4 (`LegacyTailwindScanner` doit rester vert)
- [ ] Vérification mobile : le tunnel se traverse au pouce, sans zoom

### ONB-15 — Instrumentation du tunnel et tests de contrat (M | ★★ | MOYENNE)
> Sans mesure, on répare à l'aveugle (GAME_ONBOARDING §8).
> Prérequis : ← toute la piste A et la piste B
- [ ] Cinq indicateurs : inscriptions → personnages, **pas d'abandon dans le tunnel**,
      personnages → acte I terminé, % vérifiés à J+7, **retour à J+1 et J+7**
- [ ] Exposition dans l'admin (pas un tableau de bord neuf : une section de l'existant)
- [ ] `OnboardingPlanContractTest` — les invariants qui ne doivent jamais se perdre :
      aucune quête d'`intro` par coordonnées (ONB-11), la matéria garantie dans l'arc (ONB-09),
      un seul point de décision pour la porte de vérification (ONB-04), aucun contenu gaté par
      le foyer d'attache (ONB-07), un seul état d'onboarding (ONB-10)

---

## Ce que ce plan ne couvre pas

- **La deuxième semaine.** L'acte I s'arrête à j7 ; le passage qui décide de la rétention est
  s3→s6 (GAME_PROGRESSION §3). C'est le domaine de [PLAN_RETENTION.md](PLAN_RETENTION.md), et
  la couture entre les deux reste à vérifier une fois les deux vagues livrées.
- **Le renommage payant**, l'**OAuth**, le **compte invité** et l'**éparpillement des points
  de réveil** : arbitrages laissés ouverts en GAME_ONBOARDING §9.
- **Les textes eux-mêmes** (dialogues, lettre du foyer d'attache, tables de noms) : ils
  appartiennent à **NAR-20**. Ce plan pose les structures qui les accueillent.
