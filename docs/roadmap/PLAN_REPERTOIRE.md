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

**6 jalons** (**REP-01** à **REP-06**) en 2 pistes.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| REP-01 ✅ | Les lectures contextées (le savoir du serveur) | S | ← FAC-04b (le crochet existe) |
| REP-02 | Le bassin des gestes retrouvés (contenu tagué) | M | ∅ (données pures) |
| REP-03 | Seuils, dominantes & déblocage orienté | M | ← REP-01, REP-02 |
| REP-04 | L'Autel d'éveil (le seul craft de matéria) | M | ← ECO-22 ✅, FOY-06 ✅ |
| REP-05 | La restitution : le Scriptorium & le journal | S | ← REP-03 ; converge FAC-09c |
| REP-06 | Tests du plan | S | ‖ |

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

### REP-02 — Le bassin des gestes retrouvés (M | ★★ | HAUTE)
> **Un seul bassin, écrit une fois** (§12.3) : le contenu est global et tagué, les
> serveurs le traversent dans des ordres différents. Jamais de contenu par serveur.
> Prérequis : ∅ — données pures (`config/game/repertoire.yaml`)
- [ ] Format d'entrée : geste (slug de recette d'éveil), tags (éléments, provenances,
      lieux), condition rare éventuelle (l'exclusivité naturelle), texte de révélation
- [ ] Premier lot : ~10-15 gestes couvrant les huit éléments, dont 2-3 à conditions
      rares (que la plupart des serveurs ne rempliront jamais)
- [ ] **La règle latérale vérifiable** : un geste retrouvé produit une matéria du
      **catalogue standard** (jamais un sort inédit, jamais plus de puissance — des
      options)
- [ ] Tests : validation du format à la lecture, latéralité (le résultat existe au
      catalogue)

### REP-03 — Seuils, dominantes & déblocage orienté (M | ★★★ | HAUTE)
> La nuance actée : ce qu'un serveur lit est ce dont il se souvient. Le déblocage est
> tiré du bassin **selon la dominante des lectures** — deux serveurs d'un an n'ont pas
> le même Répertoire parce qu'ils n'ont pas vécu pareil.
> Prérequis : ← REP-01, REP-02
- [ ] Seuils de déblocage **indexés sur la population effective** (BALANCE §22.5 —
      même mécanique que la Crue : un petit serveur retrouve aussi, à son rythme)
- [ ] Au franchissement : tirage dans le bassin **filtré par la dominante** de
      l'agrégat (élément, puis provenance, puis lieu en départage) ; les gestes à
      condition rare exigent leur condition en plus
- [ ] Annonce au journal de monde (« le serveur a retrouvé le geste de… ») — canon
- [ ] Le déblocage est **cumulatif et sans retrait** : un geste retrouvé ne se
      re-perd jamais (le savoir n'est jamais borné)
- [ ] Tests : seuil indexé, tirage par dominante, condition rare, idempotence

## Piste B — Le geste d'éveil

### REP-04 — L'Autel d'éveil (M | ★★★ | MOYENNE)
> Le **seul craft de matéria** du jeu (§12.3) : un service de la ville, jamais un
> pouvoir de joueur. Exceptionnel, tardif, jamais nécessaire (§2.1).
> Prérequis : ← ECO-22 ✅ (le Parfait), FOY-06 ✅ (gate Métropole), accords d'arbre
- [ ] Service de cité gaté **Métropole** (`SettlementGate`, déjà déclaratif) — ouvert à
      tous, **jamais fermé par la guilde contrôlante** (doctrine D14), taxé par elle ;
      un foyer de type **Sanctuaire** réduit coût et délai
- [ ] Entrées : lots d'améthyste **Parfaite** + gils + **temps réel** (time-gating) ;
      sortie : une matéria choisie dans le **catalogue standard**, filtrée par
      l'élément/provenance des lots, et dont le joueur possède l'**accord**
- [ ] Les **gestes retrouvés** (REP-03) élargissent la liste des recettes d'éveil
      disponibles à l'Autel — c'est leur débouché
- [ ] Visible avant d'être atteignable : l'Autel apparaît (fermé) dès la Cité, avec ce
      qui manque — l'objet de désir de l'horizon de l'an se voit
- [ ] Tests : gate, taxe, réduction Sanctuaire, entrées/sortie, accord requis, jamais
      de sort inédit

> **Vigilance (audit 2026-07-29)** : `awakening_altar` est déjà gaté dans
> `config/game/settlements.yaml` (`minimum_rank: metropolis`) mais **absent du routeur**
> `SettlementServiceDirectory` — un service « ouvert » sans écran. Couvrir par un test
> de cohérence gate ↔ routeur au moment de REP-04.

### REP-05 — La restitution : le Scriptorium & le journal (S | ★★ | MOYENNE)
> Le Répertoire doit se **voir** pour être un projet collectif.
> Prérequis : ← REP-03 ; converge avec FAC-09c (la zone Scriptorium des Mages)
- [ ] Un écran d'état du Répertoire : dominantes en cours, prochain seuil (sans révéler
      quel geste — on sait qu'on approche, pas de quoi), gestes déjà retrouvés
- [ ] Restitution portée par le **Scriptorium** quand FAC-09c livrera la zone ; d'ici
      là, accessible via le Codex
- [ ] Le Programme du Cercle (FAC-09c) lit les mêmes agrégats — une seule source
- [ ] Tests : état, non-révélation du prochain geste, source unique

### REP-06 — Tests du plan (S | ★★ | HAUTE)
> ‖ au fil des jalons.
- [ ] Invariants : latéral jamais vertical (aucun geste ne produit hors catalogue), un
      seul bassin (aucune entrée par-serveur), le déblocage jamais repris, l'Autel
      jamais fermable par une guilde, le plafond anti-forçage
- [ ] Contrat transverse : fondre ne verse **rien** au Répertoire (le coût collectif de
      la fonte est réel, §12.2)

---

## Risques

| Risque | Parade |
|---|---|
| Le bassin s'épuise (serveur ancien) | Conditions rares + le bassin s'enrichit par lots de contenu, jamais par serveur |
| Le forçage de dominante par spam | Plafonds `InfluenceAntiExploit` (REP-01) ; l'orientation reste une politique, pas un exploit |
| L'Autel arrive trop tard pour être désiré | Visible (fermé) dès la Cité, avec ce qui manque affiché |
| Deux sources d'agrégats (Programme vs Répertoire) qui divergent | Une seule source, testée (REP-05) |
