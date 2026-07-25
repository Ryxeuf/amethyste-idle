# Couverture de test — Plan narratif (NAR-14)

> Synthèse des tests couvrant le plan narratif ([PLAN_NARRATIVE.md](PLAN_NARRATIVE.md),
> NAR-01 → NAR-14). Les tests ont été livrés **au fil des jalons** ; ce document en dresse
> la carte et confirme l'objectif « **25+ tests unitaires** » de NAR-14.

## Objectif atteint

**80+ méthodes de test** dédiées à la narration (unitaires, intégration, fonctionnelles),
réparties sur 19 fichiers — largement au-delà de la cible de 25.

## Cartographie exigence → tests

### Marqueur d'arc (regroupement, tri, quêtes isolées) — NAR-01/02
- `tests/Unit/Entity/Game/QuestStoryArcTest.php` — champs `storyArc`/`arcOrder`, normalisation, `sortByArcOrder` (tri, positions nulles en fin, stabilité).
- `tests/Unit/GameEngine/Quest/QuestArcGrouperTest.php` — regroupement par arc, tri par `arcOrder`, **quêtes isolées** (« Divers »), progression `n/total`, ordre alphabétique des arcs.

### Codex (déblocage par découverte, idempotence, journal de monde public) — NAR-05/06/07
- `tests/Unit/GameEngine/Codex/CodexUnlockServiceTest.php` — déblocage, **idempotence** (pas de doublon), déblocage groupé par déclencheur.
- `tests/Unit/Entity/Game/CodexEntryTest.php` — catégories, localisation, normalisation, `isPublic`, `creditedGuildName`.
- `tests/Functional/Controller/Game/CodexControllerTest.php` — regroupement/complétion, faits de monde **exclus de la complétion** et rendus à part.
- `tests/Unit/GameEngine/Codex/WorldFactServiceTest.php` — fait de monde **public** créé, mise à jour **idempotente par slug**.

### Arc saisonnier (séquencement des beats, quêtes d'événement selon la fenêtre) — NAR-08/09/10
- `tests/Unit/Entity/GameEventBeatTest.php` — fenêtres `isActiveAt`, champs de beat, `isSeasonBeat`.
- `tests/Unit/GameEngine/Season/SeasonArcServiceTest.php` — beat actif à un instant donné, délégation.
- `tests/Integration/Season/SeasonArcFixturesTest.php` — 4 beats ordonnés, fenêtres contiguës dans les bornes de la saison.
- `tests/Integration/Quest/SeasonQuestFixturesTest.php` — quêtes de beat **actives selon la fenêtre** (montée active / climax gaté).
- `tests/Unit/GameEngine/Event/WorldBossManagerTest.php` — boss de climax spawné sur le beat, pas hors climax.
- `tests/Unit/Entity/App/MobSeasonBossTest.php` — `isSeasonBoss`.

### Crédits narratifs & canon (attribution guilde, génération de `world_fact`) — NAR-11/12
- `tests/Unit/GameEngine/Season/SeasonResolutionServiceTest.php` — crédit de la guilde contrôlante au journal de monde, cas sans guilde (fait neutre), **gating canon** (non-canon → aucun fait).
- `tests/Unit/Entity/App/InfluenceSeasonCanonTest.php` — marqueur `isCanon`, convention d'arc de saison.

### Onboarding & contenu de fond — NAR-03/04/13
- `tests/Integration/Quest/IntroArcFixturesTest.php` — arc `intro` ordonné, chaîné, étapes craft/guilde.
- `tests/Integration/Quest/OnboardingKitTest.php` + `tests/Unit/Entity/PlayerItemExchangeableTest.php` — kit T1 échangeable, boucle cœur accessible.
- `tests/Integration/Quest/BackgroundQuestFixturesTest.php` — chaîne de fond gatée (découverte → renommée), **jamais bloquante**.

### Contrat transverse — NAR-14
- `tests/Unit/Narrative/NarrativePlanContractTest.php` — garde-fou du vocabulaire déclaratif du plan (catégories/déblocages Codex, beats de saison, convention d'arc de saison, défaut non-canon, tri d'arc null-last).

## Exécution

```bash
docker compose exec php vendor/bin/phpunit --testsuite Unit,Functional,Integration
```

Tous ces tests tournent dans la CI (`.github/workflows/ci.yml`, job « Tests (PHPUnit) »).
