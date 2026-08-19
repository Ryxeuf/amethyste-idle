# Plan — L'Autel d'éveil & le Répertoire

> **Numérotation :** jalons préfixés **REP-** (Répertoire). Pas de conflit avec les
> autres préfixes.

> Décline [../GAME_WORLD.md](../GAME_WORLD.md) **§12.3** (l'Autel d'éveil, le Répertoire
> des gestes retrouvés, l'équilibre — dossier tranché, y compris la nuance « le
> Répertoire est orienté par les lectures ») et §2.1 (les trois verbes de la matéria).
> C'était **le dernier système acté sans plan d'exécution**. Prérequis livrés : la
> pureté et le Parfait (ECO-21/22 ✅), le gate Métropole (FOY-05/06 ✅), le journal de
> monde (NAR ✅). ~~**Attention (audit 2026-07-29)** : fondre/lire (**FAC-04b**)
> n'est **pas** livré — le plan factions est à 1/10, et le crochet de versement des lectures
> n'existe donc pas encore.~~ **Levé le 2026-08-19** : le plan factions est à 9,5/10, FAC-04b
> est livré, et `MateriaReadEvent` a trouvé son premier abonné avec REP-01.

## Vue d'ensemble

**6 jalons** (**REP-01** à **REP-06**) en 2 pistes — **plan complet le 2026-08-19**.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| REP-01 ✅ | Les lectures contextées (le savoir du serveur) | S | ← FAC-04b (le crochet existe) |
| REP-02 ✅ | Le bassin des gestes retrouvés (contenu tagué) | M | ∅ (données pures) |
| REP-03 ✅ | Seuils, dominantes & déblocage orienté | M | ← REP-01, REP-02 |
| REP-04 ✅ | L'Autel d'éveil (le seul craft de matéria) | M | ← ECO-22 ✅, FOY-06 ✅ |
| REP-05 ✅ | La restitution : le Scriptorium & le journal | S | ← REP-03 ; converge FAC-09c |
| REP-06 ✅ | Tests du plan | S | ‖ |

```
Piste A — Le savoir     : REP-01 → REP-02 → REP-03
Piste B — Le geste      : REP-04 → REP-05 ; REP-06 ‖
```

**Quand.** Après FAC-04b (la lecture existe). REP-04 (l'Autel) est **sans urgence de
gameplay** — le gate Métropole le place à l'horizon de l'an (300 actifs) — mais il doit
exister *avant* qu'un serveur l'atteigne, et sa présence visible (« l'Autel attend »)
est un objet de désir dès maintenant. Le Programme du Cercle (FAC-09c) consomme REP-03.

---

## Piste A — Le savoir du serveur

### REP-01 ✅ — Les lectures contextées (S | ★★★ | HAUTE) — livré le 2026-08-19
> Chaque lecture est déjà versée au Répertoire (crochet FAC-04b). Ce jalon lui donne
> une mémoire : le **contexte** de la lecture est ce qui orientera tout.
> Prérequis : ← FAC-04b ✅
- [x] Entité de décompte contexté (`RepertoireReading`) : élément, provenance, lieu de
      lecture (zone + rang de foyer), semaine — **aucune colonne ne nomme un joueur**
- [x] Agrégats par élément / provenance / lieu / rang de foyer, calculés en base
- [x] Plafond anti-forçage journalier, **sur le joueur** (`config/game/repertoire.yaml`)
- [x] Tests : versement contexté, regroupement des inconnues, agrégats, plafond, remise à
      zéro sans cron, absence de joueur, et **le crochet a bien un abonné**

> **Livré (2026-08-19).** Trois décisions, dont deux corrigent le plan.
>
> **Une ligne n'est pas une lecture, c'est un bâton.** Le Répertoire tient un décompte par
> contexte, pas un journal — et **aucune de ses colonnes ne nomme un joueur** : *le Répertoire
> est la mémoire du serveur, pas un journal de joueurs*. Le canon ne demande jamais de savoir
> qui a lu quoi ; une table d'événements répondrait aux deux questions, dont une que personne
> n'a posée, et elle grossirait sans fin.
>
> La conséquence est structurelle : **le plafond ne peut pas vivre là**, puisqu'on n'y
> distingue pas les joueurs. Il vit sur le joueur, sous la forme déjà employée par le plafond
> des gestes de faction — *une clé de jour et un compteur, une clé différente = un autre jour =
> compteur à zéro*. Rien à purger, aucun cron. Le plan renvoyait à `InfluenceAntiExploit` ;
> ce service est typé sur `Region` et `InfluenceSeason` (l'influence de guilde) et ne se
> transpose pas — c'est sa **doctrine** qu'on reprend, pas son code. Et le plafond ne refuse
> jamais la lecture : réputation, Codex et accord continuent, seule la contribution au souvenir
> s'arrête. *Il n'est pas une borne de jeu, c'est une borne de mesure.*
>
> **Le repli du plan sur « sinon la zone de lecture » aurait détruit un axe.** La provenance
> n'existait nulle part : aucun `PlayerItem` ne savait d'où il venait. Remplir la provenance
> avec la zone de lecture aurait donné deux axes remplis par la même colonne, et la dominante
> de provenance aurait dit exactement ce que dit la dominante de lieu — *le Répertoire aurait
> cru en avoir trois*. La provenance se stampe donc **là où elle est vraie** (le butin d'un
> monstre sait de quelle zone il vient, seul chemin où le monde la donne) et **un inconnu reste
> inconnu**, regroupé avec les autres inconnus.
>
> **Le crochet portait ce que son auteur avait deviné.** `MateriaReadEvent` avait été déclaré
> sans abonné avec la promesse que REP s'y brancherait *« sans qu'on revienne toucher la
> lecture »*. Il portait exactement les deux choses qui **survivent** à la lecture — le joueur
> et la fiche de matéria. Ce qui ne survit pas, c'est la pièce, supprimée **une ligne avant le
> dispatch**, et c'est elle qui savait d'où elle venait. La provenance se capture avant la
> suppression et voyage dans l'événement. Un test vérifie désormais que le crochet **a** son
> abonné : *un événement dispatché que personne n'écoute est silencieux par nature*.

### REP-02 ✅ — Le bassin des gestes retrouvés (M | ★★ | HAUTE) — livré le 2026-08-19
> **Un seul bassin, écrit une fois** (§12.3) : le contenu est global et tagué, les
> serveurs le traversent dans des ordres différents. Jamais de contenu par serveur.
> Prérequis : ∅ — données pures (`config/game/repertoire.yaml`)
- [x] Format d'entrée : la **matéria éveillée** (et non un slug de recette — voir
      ci-dessous), tags (éléments, provenances, lieux), condition rare éventuelle, texte
      de révélation en deux langues
- [x] Premier lot : **14 gestes** couvrant les huit éléments qui marquent, dont **3 à
      conditions rares**
- [x] **La règle latérale vérifiable** : tenue en deux moitiés — la forme du fichier
      (aucun champ où écrire une valeur) et le contrat en base (la matéria existe au
      catalogue standard, et son élément est celui que le geste déclare)
- [x] Tests : six refus au chargement, couverture des éléments, part rare bornée des deux
      côtés, latéralité, zones taguées existantes

> **Livré (2026-08-19).** **Une entrée nomme la matéria, pas une recette** — et c'est une
> correction au plan. Celui-ci demandait un « slug de recette d'éveil » ; aucune n'existe :
> l'Autel est déclaré dans `settlements.yaml` comme une **promesse sans route**, et c'est
> REP-04 qui le livrera. Un bassin de références pendantes n'aurait rien pu vérifier du tout.
> Nommer la matéria rend au contraire vérifiable **dès aujourd'hui** ce que le plan appelle
> lui-même « la règle latérale vérifiable ». REP-04 dérivera la recette depuis l'entrée, comme
> MAT-03 dérive le catalogue des matéria depuis les nœuds.
>
> **Pourquoi ce n'est pas de la puissance, et comment on le vérifie.** Une matéria du catalogue
> a **déjà** un canal (MAT-08 : aucune n'est orpheline). Ce qu'un geste retrouvé ajoute n'est
> donc pas l'objet mais la **façon de l'obtenir** — l'éveil à l'Autel plutôt que l'attente d'un
> coffre. *Le serveur qui n'a pas retrouvé le geste n'est pas derrière, seulement moins souple.*
> Un test le vérifie par la négative, la seule tenable ici : aucune matéria du bassin n'est
> introuvable ailleurs.
>
> **La règle latérale tient en deux moitiés.** La forme du fichier en porte une : le chargeur
> **refuse une clé inconnue** au lieu de l'ignorer, donc il n'existe aucun endroit où écrire une
> statistique, un sort, un multiplicateur ou un palier — *ce qu'on ne peut pas écrire ne peut
> pas dériver*. L'autre moitié ne se lit qu'en base, et le contrat s'en charge.
>
> **L'élément déclaré doit être celui de la matéria**, sinon la dominante mentirait : un serveur
> qui a lu du feu toute l'année retrouverait un geste tagué feu qui éveille une matéria d'eau, et
> *ce qu'il a vécu ne serait plus ce dont il se souvient*.
>
> **Le vocabulaire des conditions rares est fermé**, et chacune désigne quelque chose qui existe
> déjà dans le code — `metropolis_exists`, `readers_doctrine` (la doctrine des Lecteurs de
> FOY-13 : 6 000 gils et un verrou d'une marée), `every_element_read`. Une condition qui
> nommerait un système absent rendrait son geste **inatteignable en silence**, ce qui est le pire
> état pour un contenu rare : *indiscernable d'un contenu qu'on n'a pas encore mérité*. REP-03
> les évaluera ; REP-02 ne fait que les admettre.
>
> La part rare est bornée **des deux côtés** : sans condition rare, le bassin est une liste que
> tout le monde épuise ; avec trop, la plupart des serveurs ne retrouvent presque rien.

### REP-03 ✅ — Seuils, dominantes & déblocage orienté (M | ★★★ | HAUTE) — livré le 2026-08-19
> La nuance actée : ce qu'un serveur lit est ce dont il se souvient. Le déblocage est
> tiré du bassin **selon la dominante des lectures** — deux serveurs d'un an n'ont pas
> le même Répertoire parce qu'ils n'ont pas vécu pareil.
> Prérequis : ← REP-01 ✅, REP-02 ✅
- [x] Seuil **indexé sur la population effective** (`WorldLoadService`, la mécanique de la
      Crue), avec un plancher, et le n-ième geste coûtant n crans
- [x] Au franchissement : choix **déterministe** dans le bassin, l'élément bornant et la
      provenance puis le lieu départageant ; les conditions rares évaluées en plus
- [x] Annonce au journal de monde (`WorldFactService`, idempotente par slug)
- [x] **Cumulatif et sans retrait** : `RepertoireGesture` n'a aucune colonne pour reprendre
- [x] Commande `app:repertoire:unlock` au calendrier, après le tick des foyers
- [x] Tests : plancher, rampe, dominante bornante, départage, condition rare ignorée,
      idempotence, et **la boucle entière** (seuil franchi → geste → journal → relance nulle)

> **Livré (2026-08-19).** **Le tirage n'en est pas un, et c'est la décision du jalon.** Le
> canon dit « tiré du bassin », et il aurait été facile de mettre un jet là. C'est refusé :
> *un tirage au sort ferait du souvenir une loterie*, quand toute la thèse du système est que
> ce qu'un monde retrouve se lit depuis ce qu'il a vécu. Le choix est **déterministe** — même
> histoire de lectures, même geste —, et c'est précisément ce qui permet à un serveur de faire
> campagne (« cette marée, lisez du feu ») en sachant ce qu'il obtient. Le canon appelle cela
> de la politique, et la qualifie explicitement de légitime.
>
> **L'élément borne, il ne classe pas.** Un monde qui n'a lu que du feu ne retrouve pas « le
> geste d'eau le mieux classé » : il ne retrouve **aucun** geste d'eau. La provenance puis le
> lieu départagent ce qui reste. Les traiter à égalité ferait qu'un serveur lisant du feu
> partout et de l'eau aux Mines retrouverait un geste d'eau parce que « Mines » l'emporte — et
> *ce qu'il a lu ne serait plus ce dont il se souvient*.
>
> **Le seuil s'indexe sur la charge, pas sur les têtes** (BALANCE §22.5) : dix comptes qui ne
> jouent pas ne rendent pas le Répertoire plus dur. Un plancher évite l'inverse — un monde
> naissant, à population quasi nulle, aurait un seuil quasi nul et viderait le bassin en
> quelques lectures.
>
> **La commande rattrape son retard.** Elle boucle tant que le seuil est franchi : la règle du
> planificateur est que *rien n'est rejoué*, donc un déclenchement manqué pendant un
> redémarrage n'est pas rattrapé — mais le suivant doit rendre tout ce qui était dû, sinon une
> panne du worker priverait le serveur de gestes qu'il a mérités.
>
> **Un seuil franchi sans candidat ne se consomme pas.** Si les gestes restants portent des
> conditions que le monde ne remplit pas, on ne retire rien : le seuil retombera le jour où la
> condition sera remplie. C'est la doctrine du sédiment de la Crue — *une attente ne coûte
> rien*.
>
> Les trois conditions rares que REP-02 avait déclarées sans les évaluer ont leur évaluateur.
> Le catalogue les refuse à la lecture, l'évaluateur **lève** sur une inconnue plutôt que de
> rendre `false` : rendre `false` rendrait son geste inatteignable en silence.

## Piste B — Le geste d'éveil

### REP-04 ✅ — L'Autel d'éveil (M | ★★★ | MOYENNE) — livré le 2026-08-19
> Le **seul craft de matéria** du jeu (§12.3) : un service de la ville, jamais un
> pouvoir de joueur. Exceptionnel, tardif, jamais nécessaire (§2.1).
> Prérequis : ← ECO-22 ✅ (le Parfait), FOY-06 ✅ (gate Métropole), accords d'arbre
- [x] Service de cité gaté **Métropole** — ouvert à tous, jamais fermé par la guilde,
      taxé par elle ; un foyer **Sanctuaire** réduit coût **et** délai, dans la même
      proportion
- [x] Entrées : lots d'améthystite **Parfaite** + gils + **temps réel** ; sortie : une
      matéria du catalogue standard dont le joueur possède l'**accord**
- [x] Les **gestes retrouvés** (REP-03) sont la liste — c'est leur débouché
- [x] Visible avant d'être atteignable : l'écran s'ouvre fermé et dit ce qui manque
- [x] Tests : gate, Sanctuaire, accord requis, Parfait seul, rien à recueillir sans rite
- [x] **Test de cohérence gate ↔ routeur** (`GatedServiceRoutingTest`)

> **Livré (2026-08-19).** **L'Autel n'éveille que ce que le monde a retrouvé.** Le §12.3
> annonce deux moitiés — *« le catalogue de base par provenance, **plus** les gestes
> retrouvés »* —, et ce jalon livre la seconde. C'est celle qui fait payer REP-03 : si la
> liste de base contenait déjà tout, retrouver un geste n'élargirait rien, et le débouché
> collectif de « lire » serait vide. **Deux conditions se croisent** — le monde a retrouvé le
> geste, le personnage en possède l'accord —, ce qui fait de l'éveil un aboutissement à la fois
> collectif et personnel.
>
> **La première moitié est bloquée par une mesure, pas par le temps.** Un catalogue « par
> provenance » suppose que les lots d'améthystite disent d'où ils viennent. Ils ne le disent
> pas : REP-01 n'a stampé la provenance que là où le monde la donne — le butin d'un monstre —,
> et l'améthystite se **récolte**. La remplir depuis la zone de récolte ferait revenir
> exactement l'erreur que REP-01 a refusée : *un axe rempli par la copie d'un autre*. Cliquet
> nommé.
>
> **Le rite est un contrat, pas un objet en attente** : lots et gils sont pris au lancement, et
> ce qui vit en base est la promesse de la matéria. Même choix que le ballot de contrebande
> (FAC-08), et même raison : *rien de ce qui n'existe pas encore ne peut être vendu, échangé ou
> volé*. Le prix payé est figé sur la ligne — le recalculer à la réclamation ferait payer à un
> joueur un prix qu'aucun écran ne lui a montré, si le rang du foyer a bougé entre-temps.
>
> La taxe suit la règle de l'hôtel des ventes et de l'échoppe : **sans guilde pour la recevoir,
> elle sort du jeu**. La rendre au joueur en ferait une remise déguisée sur les serveurs sans
> contrôle, c'est-à-dire l'inverse d'un gold sink.
>
> **Le contrat gate ↔ routeur a trouvé un second défaut du même genre.** L'audit avait nommé
> l'Autel ; le test, écrit pour lui, a aussi trouvé que **`zone_bank` pointait sur une route
> inexistante** — `IndexController` porte l'attribut sur la classe et Symfony suffixe celui de
> la méthode, si bien que la route s'appelle `app_game_inventory_index`. Le défaut était
> **latent** : `zone_bank` s'ouvre à la Cité, qu'aucun foyer du monde livré n'atteint. Il
> attendait le premier serveur assez vieux pour l'y conduire, et il aurait alors **cassé** le
> panneau de foyer plutôt que d'y manquer une ligne. Les promesses restantes sont nommées une à
> une, en cliquet : *une promesse assumée est une décision, une promesse oubliée est un bug*.

### REP-05 ✅ — La restitution : le Scriptorium & le journal (S | ★★ | MOYENNE) — livré le 2026-08-19
> Le Répertoire doit se **voir** pour être un projet collectif.
> Prérequis : ← REP-03 ✅ ; la zone Scriptorium est livrée par **FAC-09a**, pas FAC-09c
- [x] Écran d'état : dominantes, seuil suivant, gestes retrouvés — **et jamais le prochain**
- [x] Porté par le **Scriptorium** (livré par FAC-09a) **et** par le Codex
- [x] `RepertoireState` est la source unique que FAC-09c lira aussi
- [x] Tests : état d'un monde vierge, **non-révélation vérifiée sur les données**, ordre de
      découverte, progression bornée, écho des services

> **Livré (2026-08-19).** **La dépendance s'est dénouée toute seule.** Le plan écrivait
> « porté par le Scriptorium *quand FAC-09c livrera la zone* ; d'ici là, accessible via le
> Codex ». Or FAC-09a a livré le Scriptorium en avance — c'est l'une des cinq portes —, parce
> que ce jalon a été redécoupé par mécanisme plutôt que par maison. La zone existe donc, et sa
> description dit déjà littéralement ce que cet écran affiche : *« sur la table centrale, l'état
> de ce que le monde a lu »*.
>
> **Les deux entrées restent, et le repli n'en est plus un.** Le Scriptorium demande une
> exaltation chez les Mages ; le savoir du serveur est un projet **collectif**, et un joueur
> sans les couleurs du Cercle doit pouvoir suivre ce que son monde retrouve — sinon la campagne
> que le canon appelle légitime ne serait le projet que d'une maison. L'écran est donc joignable
> depuis le Codex par tout le monde ; ce que le lieu ajoute, c'est de le voir **là où il
> s'écrit**.
>
> **La non-révélation est dans la forme, pas dans le gabarit.** `RepertoireState` n'a
> **aucune méthode qui rende le prochain geste**, alors que `RepertoireUnlocker` sait le
> calculer : le même geste qu'ARC-16a sur les accointances — *il n'y a pas de champ où
> l'écrire*, donc aucun écran ne peut le montrer par mégarde. Le test ne se contente pas de le
> constater : il calcule le prochain geste, aplatit tout l'instantané et vérifie que sa clé n'y
> figure nulle part.
>
> Ce qu'on rend est en revanche **exact** — total lu, seuil suivant, dominantes —, et un joueur
> peut en déduire la *famille* de ce qui vient. C'est précisément voulu : c'est ce que sa
> campagne a produit.
>
> Deux détails que la mesure a imposés : la progression est **bornée à 100** (au-delà du seuil
> le geste est *dû* et attend le calendrier — afficher 140 % ferait croire à un retard quand
> c'est une avance), et le nombre de gestes restants se dit **en nombre, jamais en liste** :
> *on sait qu'il en reste, pas lesquels*.

### REP-06 ✅ — Tests du plan (S | ★★ | HAUTE) — livré le 2026-08-19
> ‖ au fil des jalons.
- [x] Invariants transverses : latéral du côté de **l'Autel**, aucune table ne connaît de
      serveur, le déblocage jamais repris, l'Autel jamais fermable par une guilde, le
      plafond qui ne ferme jamais la lecture
- [x] Contrat transverse : **fondre ne verse rien au Répertoire**, et lire, si

> **Livré (2026-08-19).** `RepertoirePlanContractTest`. Cinq jalons ont leurs tests ; ce
> fichier n'en refait aucun et porte les invariants qu'aucun d'eux ne peut voir depuis sa
> position.
>
> **Le contrat central est l'asymétrie fondre / lire.** GAME_WORLD §12.2 : *« fondre paie
> l'individu aujourd'hui ; lire ouvre au serveur, pour toujours »*. Le coût collectif de la
> fonte est **réel**, et cela ne tient qu'à une chose — fondre ne verse rien au Répertoire.
> L'asymétrie était correcte dans le code livré (`melt()` ne dispatche rien, `read()` dispatche
> `MateriaReadEvent`), mais elle n'était vérifiée **nulle part** : c'est exactement le genre de
> propriété qui se perd sans bruit le jour où quelqu'un uniformise les deux chemins *par souci
> de symétrie*, et l'axe doctrinal du jeu tomberait avec elle. Le test la mesure dans les deux
> sens — sans la moitié « lire, si », il passerait aussi bien sur un Répertoire qui ne reçoit
> jamais rien de personne.
>
> **Les quatre autres.** *L'Autel ne bat pas monnaie* (il est le second lecteur du bassin, et
> `FoundGesturePoolTest` ne tenait la loi que sur le premier). *Aucune table du Répertoire ne
> porte de discriminant de serveur* — le catalogue tient la moitié « donnée », celle-ci ferme
> l'autre porte : une colonne suffirait à faire du bassin un contenu par serveur sans qu'aucun
> fichier de configuration ne le dise. *Aucun code ne retire un geste retrouvé*, ce qui se lit
> sur les sources parce que c'est la seule façon de mesurer ce qui **n'arrive pas**. Et *l'Autel
> s'ouvre sur le rang seul* — vérifié là où ça compte : sur une Métropole **sans guilde
> contrôlante**, il est ouvert ; si la permission dépendait d'un gouvernant, ce cas-là serait
> fermé, et le service de la ville serait devenu un pouvoir de guilde.
>
> Chaque invariant porte son garde-fou de non-vacuité — la fonte doit avoir rapporté quelque
> chose, trois fichiers au moins doivent nommer l'entité : *un contrat vide ressemble à un
> contrat tenu*.

---

## Risques

| Risque | Parade |
|---|---|
| Le bassin s'épuise (serveur ancien) | Conditions rares + le bassin s'enrichit par lots de contenu, jamais par serveur |
| Le forçage de dominante par spam | Plafonds `InfluenceAntiExploit` (REP-01) ; l'orientation reste une politique, pas un exploit |
| L'Autel arrive trop tard pour être désiré | Visible (fermé) dès la Cité, avec ce qui manque affiché |
| Deux sources d'agrégats (Programme vs Répertoire) qui divergent | Une seule source, testée (REP-05) |
