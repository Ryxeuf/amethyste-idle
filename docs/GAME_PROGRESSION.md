# Colonne vertébrale de progression

> **Statut : proposition**, juillet 2026.
> Ce document répond à la seule question qu'aucun autre ne posait : **que fait un joueur au
> jour 1, au jour 40, au mois 6 ?**
>
> Le projet avait des piliers systémiques ([GAME_PRINCIPLES.md](GAME_PRINCIPLES.md)), un monde
> ([GAME_WORLD.md](GAME_WORLD.md)), une narration ([roadmap/PLAN_NARRATIVE.md](roadmap/PLAN_NARRATIVE.md))
> et une économie — mais aucune **forme de vie de joueur**. C'est le manque signalé en
> GAME_WORLD §14. Sans lui, on ne peut pas décider quelles zones ouvrir ni quelles ressources
> y mettre : on ne sait pas à quoi elles servent.

---

## 1. Le cadre imposé

Trois contraintes viennent d'ailleurs et ne se négocient pas ici.

**L'énergie.** 240 points au maximum, un point toutes les 6 minutes — soit **240 points par
jour, et une barre pleine qui représente exactement une journée**. Une action de zone en coûte
~3. Un joueur dispose donc d'environ **80 actions par jour**, consommables en une session de
dix minutes ou étalées. C'est le budget réel de toute progression.

**Pas de niveau global** (règle 6). La progression se fait par **32 arbres de domaine** :
24 de combat (3 par élément), 4 de récolte (mineur, herboriste, pêcheur, dépeceur), 4
d'artisanat (forgeron, tanneur, alchimiste, joaillier). Un joueur n'en développera jamais 32 —
il en travaillera **deux à quatre**.

**Asynchrone.** À ~50 joueurs quotidiens, on tourne autour d'1 à 2 joueurs simultanés
(GAME_WORLD §13.4). Aucun jalon de progression ne peut exiger une présence simultanée.

---

## 2. Les cinq horizons

Un PBBG ne retient pas par une seule boucle mais par des boucles **emboîtées**, chacune avec
sa cadence, son objectif et sa récompense. Le joueur doit pouvoir répondre « à quoi je joue
là ? » à cinq échelles à la fois.

| Horizon | Cadence | Ce que le joueur poursuit | Ce qui le clôt |
|---|---|---|---|
| **La session** | 10-15 min | Dépenser sa barre | La barre est vide |
| **Le jour** | 24 h | Les quotidiennes, la barre qui revient | La barre est pleine à nouveau |
| **La semaine** | ~7 j | Un palier de métier, un plan, une commande livrée | Le palier tombe |
| **La marée** | 28 j | L'arc saisonnier, le classement, le foyer qui monte | La résolution de saison |
| **L'an** | 3-12 mois | Une matéria éveillée, une Métropole, le Codex | Un fait inscrit au journal de monde |

### La règle qui les tient

> **À chaque horizon, il faut quelque chose qui *finit* et quelque chose qui *reste*.**

Un horizon sans clôture est un tapis roulant ; un horizon sans trace est du temps perdu. La
session finit (la barre se vide) et laisse de la matière. La marée finit (l'arc se résout) et
laisse un fait au journal. C'est le test à appliquer à toute nouvelle fonctionnalité : *à quel
horizon appartient-elle, qu'est-ce qu'elle clôt, qu'est-ce qu'elle laisse ?*

---

## 3. La forme d'une vie de joueur

Quatre actes. Ce ne sont pas des paliers de contenu mais des **changements de rapport au
jeu** — ce qui fait revenir n'est pas le même à la semaine 1 et au mois 6.

### Acte I — Les premiers jours (j1 → j7) : « je comprends »

Le Limpide s'éveille à Lumière, l'arc d'introduction (livré, NAR-03) l'emmène de la première
exploration à la première potion, puis vers une guilde.

**Ce qu'il doit avoir à la fin de la semaine 1 :**
- une **arme dotée d'un sort** et un soin (kit T1 échangeable, livré NAR-04) ;
- **un domaine de combat** commencé et **un métier de récolte** commencé ;
- **sa première matéria en main**, et l'accord qui la rend utilisable ;
- son **foyer d'attache** identifié (GAME_WORLD §13.1) et une première destination.

**Ce qui le fait revenir :** la découverte, et le fait mécanique que la barre se recharge.
C'est le seul acte où la nouveauté suffit.

**Le risque :** noyer. 32 arbres visibles au jour 1, c'est un mur. L'onboarding doit en
présenter **trois ou quatre**, ceux de son foyer d'attache.

### Acte II — Le premier mois (s2 → s4) : « je me spécialise »

Le joueur choisit ce qu'il est. Il monte un domaine de combat, un métier de récolte, souvent
un métier d'artisanat, et découvre qu'il **produit plus que ce qu'il consomme**.

**Les jalons de l'acte :**
- premier **surplus vendu** au marché — le moment où l'économie cesse d'être décorative ;
- premier **plan trouvé** (découverte, pas achat — D2) ;
- première **marée vécue en entier** : amorce, montée, climax, résolution ;
- première contribution visible au **foyer** de sa zone de prédilection.

**Ce qui le fait revenir :** le palier suivant, à portée d'une semaine ; et la marée en cours,
qui a une fin datée.

### Acte III — Mois 2 à 6 : « on compte sur moi »

**C'est le passage critique**, et le seul qui décide de la survie d'un serveur.

Le joueur cesse de jouer pour progresser et commence à jouer parce que **d'autres ont besoin
de lui**. Il devient le fournisseur de quelque chose : un plan que peu possèdent, une bande de
pureté qu'il sait où trouver, un métier que sa guilde n'a pas.

**Les jalons de l'acte :**
- il prend des **commandes de craft** et se fait une réputation d'artisan ;
- il connaît des filons que les autres ignorent (information exclusive du prospecteur) ;
- sa guilde vise un **foyer**, et ses récoltes y comptent ;
- il a une **opinion** sur la doctrine — fondre ou lire.

**Ce qui le fait revenir : les autres.** Aucun autre moteur ne tient au-delà de trois mois.
Toute la conception économique (interdépendance des métiers, commandes, marchés régionaux)
existe pour rendre cet acte possible ; si un joueur peut tout faire seul, l'acte III n'arrive
jamais et il part au mois 2.

### Acte IV — Au-delà : « j'ai une place dans le monde »

Le joueur a une fonction reconnue : il gouverne une cité, il est l'artisan qu'on va chercher,
il complète le Codex, il explore l'Étale.

**Les jalons de l'acte :** une **matéria éveillée** (exige une Métropole et de l'améthystite
Parfaite — donc un serveur qui a réussi *et* un savoir de prospecteur) ; un **nom au journal
de monde** ; un plan de fin de jeu.

**Ce qui le fait revenir :** le statut, la trace laissée, et la marée suivante — qui remet un
enjeu neuf sur la table tous les 28 jours sans rien effacer de ce qui a été acquis.

---

## 3 bis. Le build : matéria et emplacements

La matéria est la **seule source d'actions de combat** (règle 10). Elle n'est donc pas une
récompense parmi d'autres : c'est **le build du personnage**, et son rythme d'acquisition est
un jalon de progression à part entière. La règle complète est en
[GAME_WORLD.md §2.1](GAME_WORLD.md) ; ce qu'elle impose à la colonne :

| Acte | Ce que le joueur doit avoir |
|---|---|
| **I** (j1-j7) | **Ses premières matéria de son élément dès les premiers jours.** Un pyromancien qui attend une semaine sa première matéria de feu ne joue pas — il regarde. Les nœuds d'entrée d'un domaine sont à 0 point requis, et c'est voulu |
| **II** (s2-s4) | Deux ou trois matéria accordées, un premier équipement à plusieurs emplacements |
| **III** (m2-m6) | Des matéria de palier supérieur, et surtout **du meilleur support** : plus d'emplacements, de meilleurs bonus |
| **IV** | L'**éveil** — créer une matéria neuve. Exceptionnel, tardif, et *jamais* nécessaire pour jouer |

> **On ne progresse pas en changeant de sort, on progresse en le portant mieux.**

La boule de feu du jour 1 sert encore au mois 6. La progression passe par les
**emplacements de sertissage** et les bonus qui les entourent, pas par le remplacement du
sort. C'est ce qui évite d'obsolescer la matéria fétiche d'un joueur — et c'est cohérent avec
le refus de l'obsolescence d'équipement à chaque saison.

---

## 3 ter. Les tâches système — la rétention qu'on n'écrit pas à la main

Les quêtes de PNJ coûtent cher à écrire et se consomment une fois. Les **tâches système**
(quotidiennes, hebdomadaires) sont générées, répétables, et récompensent l'assiduité : c'est
le meilleur rapport rétention/coût d'écriture du jeu, et elles remplissent deux horizons
entiers.

**État réel du jeu :**

| Horizon | Ce qui existe | Verdict |
|---|---|---|
| **Jour** | `PlayerDailyQuest` + `app:daily-quest:rotate` (00h01) | ✅ vivant et **personnel** |
| **Semaine** | `WeeklyChallenge` | ⚠️ existe, mais **rattaché à `InfluenceSeason` et scoré en points d'influence de guilde** — et **aucune rotation planifiée** dans `DefaultScheduleProvider` |

**Le trou est donc précis** : un joueur **solo** n'a rien à l'horizon de la semaine. Le défi
hebdomadaire existant sert la guilde, pas la personne, et il ne tourne pas.

**Ce qu'il faudrait :** un objet de désir hebdomadaire **personnel**, répétable, généré —
par exemple une tâche tirée des tables de zone du joueur, avec une récompense qui compte à son
horizon (un plan, une matéria de palier, un lot de pureté garantie). Et une récompense
d'**assiduité** (série de semaines tenues) qui ne punisse jamais la rupture : on récompense
la présence, on ne sanctionne pas l'absence — sinon le jeu devient une corvée, ce qui est le
contraire d'un PBBG.

**Mais les tâches système ne suffiront pas.** Elles occupent l'horizon, elles ne créent pas
d'attachement : une tâche générée ne fait pas qu'on compte sur toi. L'horizon de la semaine a
besoin d'**au moins un objet social** — une commande de craft à honorer, un palier de foyer à
faire tomber avec sa guilde. La tâche système est le plancher, pas le plafond.

---

## 4. Le passage critique, et comment on le rate

Entre la **semaine 3 et la semaine 6**, le joueur a fait le tour de la nouveauté et n'a pas
encore de rôle social. C'est là qu'on perd tout le monde.

Trois choses doivent arriver **avant la fin de la semaine 6**, sans quoi il part :

1. **Quelqu'un lui a demandé quelque chose.** Une commande de craft, une matière pour la
   guilde, un coup de main sur un climax de marée. Une seule fois suffit.
2. **Il a produit quelque chose que lui seul, ou presque, pouvait produire.** Pas la meilleure
   pièce du serveur — juste une pièce que le voisin ne pouvait pas faire.
3. **Il a vu une trace de son passage** : un foyer qui a monté d'un rang, son nom sur une
   contribution, une entrée de Codex débloquée.

C'est le meilleur test de conception qu'on ait : **toute fonctionnalité qui rapproche ces
trois événements de la semaine 3 vaut mieux que celle qui ajoute du contenu au mois 6.**

---

## 5. Ce que le budget d'énergie autorise

240 points par jour, ~3 par action, soit **~80 actions quotidiennes**, ~560 par semaine.

| Objectif | Budget raisonnable | Lecture |
|---|---|---|
| Un palier de métier de récolte | ~1 à 2 semaines d'une partie du budget | Le joueur travaille 2-3 domaines à la fois, jamais un seul |
| Faire monter un foyer d'un rang | plusieurs joueurs × une marée | Personne ne fait monter une ville seul — c'est voulu (épreuve collective, cf. A Tale in the Desert) |
| Un lot d'améthystite *Parfaite* | rare, et dépend du lieu et de la vitalité | Ce n'est pas un coût d'énergie, c'est un savoir |

**La conséquence à ne pas manquer :** l'énergie étant plafonnée et régénérée, **on ne peut pas
rattraper son retard en jouant plus**. C'est une propriété précieuse — elle protège le joueur
occasionnel — mais elle interdit toute conception où « il suffit de farmer ». Les écarts entre
joueurs viennent du **savoir** et des **relations**, jamais du temps passé.

---

## 6. Ce que cette colonne impose au contenu

C'est la raison d'être du document : ces contraintes commandent les zones et les ressources.

**a) À portée du hub, dès la semaine 1**, il faut au minimum : les quatre lignes de récolte
(minage, herboristerie, pêche, dépeçage), de la faune de niveau d'entrée, un atelier, et une
première matéria. Sans ça, l'Acte I ne tient pas.

**b) Les 32 domaines ne doivent pas être servis également.** Un joueur en travaille 2 à 4 ; à
50 joueurs quotidiens, un domaine exotique n'aura aucun praticien. Le contenu (matéria,
recettes, plans) se concentre sur les domaines **fréquentés**, et les autres restent des
arbres praticables sans être des chantiers de contenu.

**c) L'interdépendance doit mordre dès l'Acte II.** Si un joueur peut monter récolte + craft
et se suffire, l'Acte III n'arrive jamais. C'est ce que vérifie ECO-14 (aucun métier
autosuffisant) et ce que renforce la chaîne de production par paliers (ECO-25).

**d) Il faut un objet de désir à chaque horizon.** Un plan à la semaine, un rang de foyer à la
marée, une matéria éveillée à l'année. Une zone qui n'offre d'objet de désir à aucun horizon
est une zone morte, quelle que soit sa beauté.

---

## 7. Ce qui reste ouvert

1. **Le nombre de domaines réellement servis.** 32 arbres existent ; combien méritent du
   contenu neuf ? Ma recommandation : concentrer sur ~12 (les 4 récoltes, les 4 artisanats, et
   4 combats couvrant des rôles distincts), les autres restant jouables sans être nourris.
2. **La rejouabilité de l'Acte I** sur un second personnage (cf. CLAUDE.md règle 12) :
   intégralement rejoué aujourd'hui (NAR-04). À reconsidérer si l'Acte I s'allonge.
3. **L'horizon hebdomadaire reste le plus fragile** (§3 ter). Le défi hebdomadaire existant
   est collectif et ne tourne pas ; il manque une tâche **personnelle** répétable *et* un
   objet **social** à cet horizon. C'est le premier chantier de design à ouvrir.
4. **Les paliers de métier ne sont pas calibrés** contre le budget d'énergie réel. À faire en
   même temps que le recalibrage des filons (BALANCE § 22.3).
