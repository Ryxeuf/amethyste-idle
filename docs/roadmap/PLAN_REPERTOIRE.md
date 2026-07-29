# Plan — L'Autel d'éveil & le Répertoire

> **Numérotation :** jalons préfixés **REP-** (Répertoire). Pas de conflit avec les
> autres préfixes.

> Décline [../GAME_WORLD.md](../GAME_WORLD.md) **§12.3** (l'Autel d'éveil, le Répertoire
> des gestes retrouvés, l'équilibre — dossier tranché, y compris la nuance « le
> Répertoire est orienté par les lectures ») et §2.1 (les trois verbes de la matéria).
> C'était **le dernier système acté sans plan d'exécution**. Prérequis largement
> livrés : fondre/lire (FAC-04b — chaque lecture est déjà versée à un crochet no-op
> prévu pour ce plan), la pureté et le Parfait (ECO-21/22 ✅), le gate Métropole
> (FOY-05/06 ✅), le journal de monde (NAR ✅).

## Vue d'ensemble

**6 jalons** (**REP-01** à **REP-06**) en 2 pistes.

| Code | Livrable | Taille | Dépendances |
|------|----------|--------|-------------|
| REP-01 | Les lectures contextées (le savoir du serveur) | S | ← FAC-04b (le crochet existe) |
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

### REP-01 — Les lectures contextées (S | ★★★ | HAUTE)
> Chaque lecture est déjà versée au Répertoire (crochet FAC-04b). Ce jalon lui donne
> une mémoire : le **contexte** de la lecture est ce qui orientera tout.
> Prérequis : ← FAC-04b
- [ ] Entité de registre des lectures : élément de la matéria, provenance (zone
      d'obtention si connue, sinon zone de lecture), lieu de lecture (zone + type de
      foyer), semaine — **agrégats par serveur**, pas de PII de gameplay inutile
- [ ] Compteurs d'agrégat par élément / provenance / lieu (les « dominantes » se lisent
      dessus)
- [ ] Plafond anti-forçage : les lectures comptent au Répertoire via
      `InfluenceAntiExploit` (le spam de lectures Troubles ne force pas un seuil)
- [ ] Tests : versement contexté, agrégats, plafond

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
