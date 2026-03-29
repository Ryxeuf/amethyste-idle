# Plan Testing & Qualite — Prevention des bugs

> Plan annexe pour ameliorer la detection et prevention des bugs en conditions reelles.
> 15 taches numerotees **TST-01** a **TST-15**, reparties en 5 jalons.
> Derniere mise a jour : 2026-03-29

---

## Contexte — Pourquoi la CI passe mais le jeu casse

| Metrique | Etat actuel | Probleme |
|----------|-------------|----------|
| Tests unitaires | 97 (mocks) | Logique isolee OK, assemblage non teste |
| Tests "fonctionnels" | 11 (mocks aussi) | Ne touchent jamais la vraie DB |
| Tests integration | 3 (events seuls) | Combat, quetes, progression = 0 |
| Tests E2E | 5 (desactives) | Flux utilisateur non valides |
| Tests JS/frontend | 0 | PixiJS, Stimulus, Turbo non testes |
| Couverture code | Non tracee | Impossible de voir le code non teste |
| PHPStan baseline | 313 erreurs masquees | Bugs de types caches |

**Diagnostic** : la CI valide que chaque brique fonctionne seule, mais jamais que le jeu fonctionne quand les briques s'assemblent.

---

## Graphe de dependances

```
JALON 1 — Fondations CI (TST-01 a TST-03)
  TST-01 Fixtures + schema dans CI       ∅
  TST-02 Smoke tests routes critiques    ← TST-01
  TST-03 Couverture de code dans CI      ∅

JALON 2 — Tests integration (TST-04 a TST-08)
  TST-04 AbstractIntegrationTestCase     ← TST-01
  TST-05 Integration combat              ← TST-04
  TST-06 Integration status effects      ← TST-04
  TST-07 Integration quetes/progression  ← TST-04
  TST-08 Testsuite Integration dans CI   ← TST-05

JALON 3 — E2E (TST-09 a TST-11)
  TST-09 Stabiliser tests E2E existants  ← TST-01
  TST-10 Nouveaux tests E2E critiques    ← TST-09
  TST-11 Reactiver E2E dans CI           ← TST-09

JALON 4 — Analyse statique (TST-12 a TST-13)
  TST-12 PHPStan niveau 6 + baseline     ∅
  TST-13 Mutation testing (Infection)    ← TST-05

JALON 5 — Prevention proactive (TST-14 a TST-15)
  TST-14 Assertions metier GameEngine    ∅
  TST-15 GameStateValidator              ← TST-14
```

---

## Jalon 1 — Fondations CI

> Prerequis pour tout le reste : que la CI ait une vraie DB avec des donnees.

### TST-01 — Schema + fixtures dans le job CI tests `S`

- **Prerequis** : ∅
- **Fichier** : `.github/workflows/ci.yml` (job `tests`)
- **Action** : ajouter apres "Create test database" :
  ```yaml
  - name: Setup test schema and fixtures
    run: |
      php bin/console doctrine:schema:create --env=test
      php bin/console doctrine:fixtures:load --env=test --no-interaction
  ```
- **Verification** : la CI passe avec le schema + fixtures charges

---

### TST-02 — Smoke tests routes critiques `S`

- **Prerequis** : ← TST-01
- **Fichier** : `tests/Functional/SmokeTest.php` (nouveau)
- **Action** : test `WebTestCase` (vraie DB, pas de mocks) qui verifie que les routes principales ne renvoient pas d'erreur 500 :
  - `/game/map`, `/game/inventory`, `/game/skills`, `/game/bestiary`
  - `/game/achievements`, `/game/quests`
  - `/api/map/config`
- **Verification** : `docker compose exec php vendor/bin/phpunit --filter SmokeTest`

---

### TST-03 — Couverture de code dans la CI `S`

- **Prerequis** : ∅
- **Fichier** : `.github/workflows/ci.yml` (job `tests`)
- **Action** : modifier la commande PHPUnit pour generer `--coverage-clover coverage.xml --coverage-text`
- **Verification** : le job CI affiche le pourcentage de couverture

---

## Jalon 2 — Tests d'integration (vraie DB, vrais services)

> Le coeur du probleme : tester l'assemblage des services avec une vraie DB.

### TST-04 — Classe de base AbstractIntegrationTestCase `M`

- **Prerequis** : ← TST-01
- **Fichier** : `tests/Integration/AbstractIntegrationTestCase.php` (nouveau)
- **Action** : etend `KernelTestCase`, charge schema + fixtures une seule fois par classe, fournit helpers `getPlayer()`, `getFight()`, `getMob()`, gere le rollback entre chaque test (transaction wrapping)
- **Verification** : un test vide qui boot le kernel et recupere un Player

---

### TST-05 — Tests integration combat `L` (decouper en 3 sous-taches)

- **Prerequis** : ← TST-04
- **Fichiers** : `tests/Integration/Fight/` (nouveau dossier)
- **Sous-tache A** — `FightFlowIntegrationTest.php` :
  - `testEngageMobCreatesFight` : engager un mob → Fight creee en DB
  - `testPlayerAttackReducesMobHp` : attaque basique → HP du mob diminuent
  - `testMobDeathEndsFight` : mob a 0 HP → combat termine, events dispatches
  - `testLootAfterVictory` : victoire → loot genere, XP accorde
- **Sous-tache B** — `StatusEffectIntegrationTest.php` :
  - `testPoisonTicksDamagePerTurn` : poison → degats chaque tour → expiration
  - `testSilencePreventsSpellCasting` : silence → sort refuse → silence expire → sort OK
  - `testEffectRefreshResetsDuration` : reappliquer meme effet → duree reset
- **Sous-tache C** — `FightEdgeCasesTest.php` :
  - `testPlayerWithNoWeaponCanStillAttack` : attaque de base sans arme
  - `testFleeFromCombat` : fuite → joueur libere, combat nettoye
  - `testPlayerDeathInCombat` : joueur meurt → respawn, etat coherent
- **Verification** : `docker compose exec php vendor/bin/phpunit --testsuite Integration --filter Fight`

---

### TST-06 — Tests integration status effects complet `M`

- **Prerequis** : ← TST-04
- **Fichier** : `tests/Integration/Fight/StatusEffectFullIntegrationTest.php`
- **Action** : tester `StatusEffectManager` + `SpellApplicator` + `FightTurnResolver` ensemble :
  - Application effet via sort → verification en DB
  - Tick degats/soin a chaque tour
  - Modification stats (buff/debuff) visible dans calculs de degats
  - Expiration et nettoyage corrects
  - Cas berserk : bonus degats + reduction defense
- **Verification** : `docker compose exec php vendor/bin/phpunit --filter StatusEffectFull`

---

### TST-07 — Tests integration quetes et progression `M`

- **Prerequis** : ← TST-04
- **Fichiers** : `tests/Integration/Quest/`, `tests/Integration/Progression/`
- **Quetes** — `QuestProgressionIntegrationTest.php` :
  - Accepter quete → tuer mob cible → objectif mis a jour → completion → recompense
  - Quete de collecte → obtenir items → rendre au PNJ → completion
- **Progression** — `SkillProgressionIntegrationTest.php` :
  - Gagner XP domaine → niveau domaine augmente → competence deblocable
  - Apprendre competence materia → equiper materia → sort disponible en combat
- **Verification** : `docker compose exec php vendor/bin/phpunit --testsuite Integration --filter Quest`

---

### TST-08 — Ajouter testsuite Integration dans la CI `S`

- **Prerequis** : ← TST-05
- **Fichier** : `.github/workflows/ci.yml`
- **Action** : modifier la commande PHPUnit :
  ```yaml
  run: vendor/bin/phpunit --testdox --exclude-group e2e --testsuite Unit,Functional,Integration
  ```
- **Verification** : la CI execute les tests d'integration et passe

---

## Jalon 3 — Tests E2E

> Simuler un vrai joueur dans un vrai navigateur.

### TST-09 — Stabiliser les tests E2E existants `M`

- **Prerequis** : ← TST-01
- **Fichiers** : `tests/E2E/CombatFlowTest.php`, `tests/E2E/QuestFlowTest.php`, `tests/E2E/AbstractE2ETestCase.php`
- **Problemes actuels** :
  - Tests skippes car fixtures incompletes (pas de mob a combattre, pas de quete active)
  - Pas de `waitFor()` pour PixiJS et Turbo (chargements asynchrones)
- **Actions** :
  - Ajouter fixtures specifiques E2E (joueur avec mob adjacent, quete en cours)
  - Ajouter helpers `waitForPixi()`, `waitForTurbo()` dans `AbstractE2ETestCase`
  - Corriger les selecteurs CSS casses
- **Verification** : `docker compose exec php vendor/bin/phpunit --testsuite E2E`

---

### TST-10 — Nouveaux tests E2E critiques `M`

- **Prerequis** : ← TST-09
- **Fichiers** : `tests/E2E/` (nouveaux fichiers)

| Test | Flux valide |
|------|-------------|
| `InventoryFlowTest` | Equiper item → stat change → desequiper → stat revient |
| `MapNavigationTest` | Clic deplacement → joueur bouge → changement de carte |
| `ShopFlowTest` | Acheter item → or diminue → item dans inventaire |

---

### TST-11 — Reactiver E2E dans la CI `S`

- **Prerequis** : ← TST-09
- **Fichier** : `.github/workflows/ci.yml`
- **Action** :
  - Decomenter le job `e2e` (lignes 178-279)
  - Ajouter `continue-on-error: true` initialement
  - Retirer `continue-on-error` une fois stables (apres 5 CI vertes consecutives)

---

## Jalon 4 — Analyse statique renforcee

### TST-12 — PHPStan niveau 6 + reduction baseline `M`

- **Prerequis** : ∅
- **Fichiers** : `phpstan.neon`, `phpstan-baseline.neon`
- **Actions** :
  1. Corriger erreurs `property.onlyWritten` du baseline (code mort = bugs potentiels)
  2. Corriger erreurs `nullCoalesce.offset` (verifications inutiles)
  3. Passer le niveau de 5 a 6 (verification types de retour)
  4. Regenerer le baseline : `docker compose exec php vendor/bin/phpstan analyse --generate-baseline`
  5. Objectif : reduire baseline de 313 a < 100 erreurs
- **Verification** : `docker compose exec php vendor/bin/phpstan analyse` passe au niveau 6

---

### TST-13 — Mutation testing avec Infection PHP `M`

- **Prerequis** : ← TST-05
- **Fichiers** : `composer.json`, `infection.json5` (nouveau)
- **Actions** :
  1. Installer : `docker compose exec php composer require --dev infection/infection`
  2. Configurer sur `src/GameEngine/Fight/Calculator/` d'abord (zone critique, petite)
  3. Lancer : `docker compose exec php vendor/bin/infection --threads=4`
  4. Objectif initial : MSI >= 60%, Covered MSI >= 80% sur les calculateurs
  5. Etendre progressivement a tout `src/GameEngine/Fight/`
- **Verification** : `docker compose exec php vendor/bin/infection --min-msi=60`

---

## Jalon 5 — Prevention proactive

### TST-14 — Assertions metier dans le GameEngine `M`

- **Prerequis** : ∅
- **Fichiers** : services dans `src/GameEngine/Fight/`, `src/GameEngine/Movement/`
- **Action** : ajouter des `LogicException` pour les invariants metier :

| Service | Assertion |
|---------|-----------|
| `PlayerMoveProcessor` | Joueur en combat → exception (pas de deplacement) |
| `MobActionHandler` | Mob avec 0 HP → exception (ne peut pas agir) |
| `SpellApplicator` | Degats calcules < 0 → forcer a 0 (jamais negatif) |
| `StatusEffectManager` | Duree restante < 0 → expirer immediatement |
| `FightTurnResolver` | Fight sans participants → exception |

---

### TST-15 — GameStateValidator (commande de diagnostic) `M`

- **Prerequis** : ← TST-14
- **Fichiers** : `src/Command/GameStateValidateCommand.php` (nouveau), `src/GameEngine/Debug/GameStateValidator.php` (nouveau)
- **Action** : commande Symfony qui verifie la coherence de la DB :
  ```bash
  docker compose exec php php bin/console app:game:validate
  ```
- **Verifications** :
  - [ ] Joueur en `fight_id` non null mais Fight inexistante ou terminee
  - [ ] Fight active sans mobs vivants
  - [ ] Inventaire avec PlayerItem orphelins (item_id null)
  - [ ] Quete en `status = completed` mais recompense non distribuee
  - [ ] Joueur avec coordonnees hors limites de la carte

---

## Ordre d'implementation recommande

| Etape | Tache | Taille | Impact |
|-------|-------|--------|--------|
| 1 | **TST-01** Schema + fixtures CI | S | Debloque tout le reste |
| 2 | **TST-02** Smoke tests routes | S | Attrape les erreurs 500 |
| 3 | **TST-03** Couverture de code CI | S | Visibilite sur le non-teste |
| 4 | **TST-04** AbstractIntegrationTestCase | M | Base pour tous les tests integration |
| 5 | **TST-05** Integration combat | L | **Impact maximal** — zone la plus bugguee |
| 6 | **TST-08** Integration dans CI | S | Active TST-05 dans le pipeline |
| 7 | **TST-12** PHPStan niveau 6 | M | Bugs de types detectes statiquement |
| 8 | **TST-06** Integration status effects | M | Complemente TST-05 |
| 9 | **TST-07** Integration quetes/progression | M | Autre source majeure de bugs |
| 10 | **TST-09** Stabiliser E2E | M | Flux utilisateur reel |
| 11 | **TST-11** E2E dans CI | S | Active TST-09 dans le pipeline |
| 12 | **TST-14** Assertions metier | M | Prevention au runtime |
| 13 | **TST-13** Mutation testing | M | Qualite des tests eux-memes |
| 14 | **TST-10** Nouveaux tests E2E | M | Couverture E2E elargie |
| 15 | **TST-15** GameStateValidator | M | Diagnostic proactif |

**Convention** : chaque TST-XX termine = commit + push + verification CI.

---

## Verification globale

A la fin de tous les jalons :

```bash
# Tout doit passer
docker compose exec php vendor/bin/phpunit --testsuite Unit,Functional,Integration
docker compose exec php vendor/bin/phpunit --testsuite E2E
docker compose exec php vendor/bin/phpstan analyse
docker compose exec php vendor/bin/infection --threads=4 --min-msi=60
docker compose exec php php bin/console app:game:validate
```
