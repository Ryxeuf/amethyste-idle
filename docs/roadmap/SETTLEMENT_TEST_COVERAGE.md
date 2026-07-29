# Couverture de test — Pilier territorial (FOY-16)

> Synthèse des tests couvrant le plan des foyers
> ([PLAN_SETTLEMENTS.md](PLAN_SETTLEMENTS.md), FOY-01 → FOY-17). Les tests ont été livrés **au
> fil des jalons** ; ce document en dresse la carte et ajoute ce qu'aucun d'eux ne pouvait
> vérifier seul — le **contrat transverse**.

## Ce que couvre le plan

**235 méthodes de test** réparties sur 21 fichiers, six pistes et un contrat — l'objectif du
jalon était « 40+ ».

| Piste | Fichiers | Méthodes |
|---|---|---:|
| A — socle du foyer | `SettlementSeederTest`, `SettlementDepositServiceTest`, `SettlementSedimentWiringTest`, `SettlementRankCalculatorTest`, `SettlementTickServiceTest`, `SettlementPanelBuilderTest` | 73 |
| B — ce que le rang ouvre | `SettlementGateTest`, `SettlementServiceDirectoryTest`, `SettlementWorkshopBonusTest` | 28 |
| C — la Crue | `CrueQuotaServiceTest`, `VassalageServiceTest` | 16 |
| D — la Pâleur | `VeinPalenessServiceTest`, `VeinRestorationServiceTest` | 24 |
| E — doctrine & guilde | `SettlementDoctrineServiceTest`, `SettlementChronicleServiceTest` | 26 |
| F — marées conséquence | `ConsequenceTideSelectorTest`, `ConsequenceTideDefinitionLoaderTest`, `ConsequenceTideComposerTest` | 23 |
| Calibrage déclaratif | `SettlementDefinitionLoaderTest` | 25 |
| **Contrat transverse** | `SettlementPlanContractTest` | **10** |

RET-05 (le chantier de la semaine) est compté avec le plan de rétention : c'est une brique
hebdomadaire qui se **pose** sur un foyer, pas une brique du pilier
(`SettlementWeeklyWorkTest`, 10 méthodes, voir [RETENTION_TEST_COVERAGE.md](RETENTION_TEST_COVERAGE.md)).

## Cartographie exigence → tests

### Piste A — socle du foyer (FOY-01 → 04)
- `SettlementSeederTest` — le monde livré naît **non nul** : les Vallons en Ruine, tout est à
  bâtir, et re-seeder n'écrase pas un foyer déjà monté.
- `SettlementDepositServiceTest` — la table de sédiment, le plafond journalier qui mord sur
  **ce qui dépasse** et non sur l'action entière, le report fractionnaire (une traversée vaut
  0,2 grain : sans report, la zone de transit ne vivrait jamais), la réascension à dépôts
  doublés qui **ne mange pas** le quota du joueur, et la zone sans foyer qui n'accumule rien
  **en silence et sans erreur**.
- `SettlementSedimentWiringTest` — le dépôt est branché sur les événements de domaine déjà
  émis ; aucun événement nouveau.
- `SettlementRankCalculatorTest` — le rang se lit sur la somme, le type sur le dominant.
- `SettlementTickServiceTest` — décroissance, hystérésis du type tenue **une marée entière**,
  bornes de régression, événement de changement de rang.
- `SettlementPanelBuilderTest` — ce que l'écran de zone a le droit de dire du foyer.

### Piste B — ce que le rang ouvre (FOY-05 → 07)
- `SettlementGateTest` — le gate déclaratif, et surtout : **rien n'est rétro-gaté**.
- `SettlementServiceDirectoryTest` — les services fermés restent **affichés** avec leur rang
  manquant ; les masquer rendrait le palier suivant abstrait au moment où il compte le plus.
- `SettlementWorkshopBonusTest` — rang × type × ligne de production, et le plafond de cumul :
  un lieu ne doit pas valoir plus qu'une carrière.

### Piste C — la Crue (FOY-08 → 10, FOY-17)
- `CrueQuotaServiceTest` — quotas indexés sur la population effective, et la descente cran par
  cran plutôt que le refus en bloc : un foyer qui mérite la Cité mais ne peut pas l'avoir doit
  quand même devenir Bourg si la place existe.
- `VassalageServiceTest` — une grande voisine plafonne, mais **ne reprend jamais** un rang tenu ;
  un voisin de même rang ne domine pas, sinon deux bourgs voisins se bloqueraient mutuellement.

### Piste D — la Pâleur (FOY-11 → 12)
- `VeinPalenessServiceTest` — la Pâleur monte sous pression, **redescend pendant qu'on joue**
  (aucune jachère à protéger), reste bornée (un filon pâli n'est jamais stérile), et mesure un
  **rythme** et non un cumul : le compteur repart à zéro chaque jour.
- `VeinRestorationServiceTest` — coût indexé et **linéaire**, chantier complet qui ne rend jamais
  l'intact, paiement sans effet sur une surexploitation en cours, trésor insuffisant qui ne
  débite rien, second chantier refusé.

### Piste E — doctrine & guilde (FOY-13 → 14)
- `SettlementDoctrineServiceTest` — effets **opposés** à pression égale, exclusivité, verrou
  d'une marée, patrimoine conservé à la régression, les deux ateliers toujours affichés.
- `SettlementChronicleServiceTest` — la chronique de fin de marée, le plancher de notabilité,
  la chute racontée **sans accuser personne**.

### Piste F — marées conséquence (FOY-15)
- `ConsequenceTideSelectorTest` — la Pâleur sur un **état**, l'Appel de la Crue sur une
  **variation**, la première clôture qui pose le repère sans rien déclencher, la préséance de la
  conséquence négative.
- `ConsequenceTideDefinitionLoaderTest` — arcs contigus et couvrant la marée entière, ordre
  canonique des beats, marée déclarée par l'enum et absente du fichier.
- `ConsequenceTideComposerTest` — fenêtres dérivées des bornes réelles de la saison, idempotence.

### Le calibrage déclaratif
- `SettlementDefinitionLoaderTest` — 25 méthodes sur le seul chargeur. C'est délibéré : chaque
  invariant qu'il refuse est un **défaut muet** que rien d'autre n'aurait vu (un seuil de
  rendements décroissants au-dessus du plafond, un multiplicateur de réascension à 1, un seuil
  d'effet au-dessus du plafond de Pâleur, deux ateliers de doctrine qui abîment autant).

## Le contrat transverse — `SettlementPlanContractTest`

Ce qu'aucune brique ne peut vérifier seule, parce que ce n'est vrai que **de l'ensemble** :

| Propriété | Ce qu'elle empêche |
|---|---|
| Tout paramètre déclaré est **lu** | un bloc de calibrage que le chargeur ignore, qui se lit comme une garantie |
| Aucun seuil de rang dans le moteur | une constante de classe cohabitant avec le fichier, sans qu'on sache laquelle gagne |
| Le rang ne se pose qu'à **deux** endroits | un troisième écrivain qui contourne la Crue sans même la connaître |
| Le tick passe par `highestAllowed` | une montée qui ignore le quota |
| `never_gated` non vide, sans intersection | la décision A désarmée en la vidant plutôt qu'en la contredisant |
| `paleness.max` < 1, seuils sous le plafond | une Pâleur qui deviendrait une Étale |
| Le plancher d'une unité à la récolte | un filon pâli qui deviendrait stérile en pratique |
| La Pâleur vit sur `ZoneVein`, nulle part ailleurs | un agrégat de zone qui punirait le passage diffus |
| Le vocabulaire du fichier est figé | un renommage de bloc que le chargeur lirait comme `null` |
| Le fichier livré passe tous ses invariants d'un coup | un calibrage valide « en pièces » et cassé en entier |

## Ce que la couverture ne prétend pas

- **Le contenu** des marées écrites de l'an 1 (dialogues, quêtes d'événement) : c'est la vague 2
  du plan narration (NAR-15+), pas le pilier territorial.
- **Le comportement en charge** : les scénarios k6 (`scripts/load-test/`) couvrent la tenue du
  serveur ; ce plan couvre la justesse des règles.
- **L'équilibrage lui-même** : les tests vérifient que le calibrage est *cohérent*, pas qu'il est
  *bon*. Le chiffrage et sa justification vivent dans [BALANCE.md §23](../BALANCE.md), et se
  retendent en observant un vrai serveur.
