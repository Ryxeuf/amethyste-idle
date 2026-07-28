# Couverture de test — Plan de rétention (RET-07)

> Synthèse des tests couvrant le plan de rétention hebdomadaire
> ([PLAN_RETENTION.md](PLAN_RETENTION.md), RET-01 → RET-07). Les tests ont été livrés **au fil
> des jalons** ; ce document en dresse la carte et ajoute ce qu'aucun d'eux ne pouvait
> vérifier seul — le **contrat transverse**.

## Ce que couvre le plan

**119 méthodes de test** réparties sur 13 fichiers, six briques et un contrat.

| Brique | Fichiers | Méthodes |
|---|---|---:|
| RET-01 — défi de guilde | `WeeklyChallengeRotatorTest`, `WeeklyChallengeTemplateLoaderTest`, `ChallengeTrackerTest`, `WeeklyChallengeLocalizationTest` | 51 |
| RET-02 — commission personnelle | `WeeklyCommissionGeneratorTest`, `WeeklyCommissionProgressTest`, `WeeklyCommissionDeliveryTest`, `WeeklyCommissionDomainTest` | 30 |
| RET-04 — assiduité | `WeeklyAttendanceServiceTest`, `WeeklyAttendanceDefinitionLoaderTest` | 16 |
| RET-05 — chantier de foyer | `SettlementWeeklyWorkTest` | 10 |
| RET-06 — affleurement | `WeeklyOutcropTest` | 7 |
| **RET-07 — contrat transverse** | `RetentionPlanContractTest` | **5** |

RET-03 (la commande de guilde) n'a pas de fichier propre : elle est un **canal** sur les
commandes de craft, et se teste avec elles (`CraftOrderManagerTest`).

## Cartographie exigence → tests

### RET-01 — rotation du défi de guilde & restitution
- `tests/Unit/GameEngine/Guild/WeeklyChallengeRotatorTest.php` — clôture de la semaine écoulée,
  ouverture de la suivante, **idempotence par semaine ISO** (`Parameter`), absence de saison
  active, versement de l'influence, publication Mercure.
- `tests/Unit/GameEngine/Guild/WeeklyChallengeTemplateLoaderTest.php` — pool déclaratif validé
  **à la lecture**.
- `tests/Unit/EventListener/ChallengeTrackerTest.php` — avancement branché sur les événements.
- `tests/Unit/Entity/App/WeeklyChallengeLocalizationTest.php` — parité FR/EN du contenu.

### RET-02 — la Commission de la semaine
- `tests/Unit/GameEngine/Retention/WeeklyCommissionGeneratorTest.php` — tirage **déterministe**
  par semaine et par joueur, une seule commission par semaine (**pas de reroll**), fermeture des
  semaines précédentes, choix de la zone de livraison parmi les foyers.
- `tests/Unit/GameEngine/Retention/WeeklyCommissionProgressTest.php` — avancement sur les six
  événements de domaine.
- `tests/Unit/GameEngine/Retention/WeeklyCommissionDeliveryTest.php` — dépôt au foyer **hors
  plafond journalier**, les trois récompenses d'ampleur comparable, le Tribut qui triple le dépôt.
- `tests/Unit/DataFixtures/WeeklyCommissionDomainTest.php` — la colonne `domain` du pool
  correspond aux domaines réellement livrés (`Domain::getSlug()` étant dérivé du titre affiché,
  un renommage la casserait en silence).

### RET-04 — l'assiduité en paliers
- `tests/Unit/GameEngine/Retention/WeeklyAttendanceServiceTest.php` — comptage par **jour
  distinct**, idempotence à la journée, paliers payés une fois, énergie du dernier palier bornée
  par le plafond, **une semaine sautée ne coûte rien**, bascule de semaine sans cron, la lecture
  n'inscrit rien.
- `tests/Unit/GameEngine/Retention/WeeklyAttendanceDefinitionLoaderTest.php` — seuils strictement
  croissants, **palier à 7 jours refusé** (une série déguisée), table vide, fichier absent.

### RET-05 — le chantier de la semaine
- `tests/Unit/GameEngine/Settlement/SettlementWeeklyWorkTest.php` — besoins générés depuis le
  **type** du foyer et son rang, avancement, clôture.

### RET-06 — l'Affleurement de la semaine
- `tests/Unit/GameEngine/Economy/WeeklyOutcropTest.php` — tirage déterministe, jamais deux
  semaines de suite la même zone, plafond relevé d'un cran sans ressusciter un filon éreinté, et
  **`testNothingPublicNamesTheOutcrop`** : la discrétion est un critère d'acceptance exécutable.

### RET-07 — le contrat transverse
`tests/Unit/GameEngine/Retention/RetentionPlanContractTest.php` vérifie ce qu'aucune brique ne
peut vérifier seule, en reprenant deux lignes de la table des risques du plan :

**« Cinq mécaniques hebdomadaires = cinq horloges qui dérivent »**
- `testTheIsoWeekFormatLivesInExactlyOnePlace` — le format `o-\WW` et l'ancrage `monday this
  week` n'existent que dans `WeekKey`. Ils vivaient à **deux** endroits avant ce jalon.
- `testEveryWeeklyRotationFiresOnTheSameMonday` — les quatre rotations tombent le lundi à 00h,
  à des minutes **distinctes** (leur ordre porte du sens ; une collision le rendrait dépendant
  de l'ordonnanceur).
- `testTheWeekKeyIsAnchoredOnMonday` — dimanche soir et lundi matin sont bien deux semaines.

**« La série d'assiduité réintroduite parce que c'est standard »**
- `testNoWeeklyBrickSpeaksTheLanguageOfStreaks` — aucun moteur ne connaît le vocabulaire de la
  série continue (scan **hors commentaires** : ce plan doit pouvoir nommer par écrit ce qu'il
  refuse d'implémenter).
- `testTheAttendanceTableOnlyKnowsTheCurrentWeek` — les colonnes de `player_weekly_attendance`
  ne parlent que de la semaine courante ; une colonne de report rouvrirait la porte.

## Exécution

```bash
docker compose exec php vendor/bin/phpunit --filter 'Weekly|Retention|Attendance|Outcrop|Challenge'
```

Tous ces tests tournent dans la CI (`.github/workflows/ci.yml`, job « Tests (PHPUnit) »).
