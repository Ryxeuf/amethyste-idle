---
description: Developper en TDD (Test-Driven Development). Ecrit les tests d'abord (RED), puis le code minimal (GREEN), puis refactorise (REFACTOR). Usage — /tdd FightTurnResolver doit gerer le cas 0 HP
---

# /tdd — Developpement dirige par les tests

Applique la methodologie TDD stricte : **RED → GREEN → REFACTOR**.

## Feature ou bug a implementer

$ARGUMENTS

## Cycle TDD

### 1. RED — Ecrire le test d'abord

- Definir le comportement attendu sous forme de test PHPUnit
- Le test DOIT echouer (sinon il ne teste rien de nouveau)

```bash
docker compose exec php vendor/bin/phpunit --filter NomDuNouveauTest
# -> FAIL (expected)
```

### 2. GREEN — Implementer le minimum

- Ecrire uniquement le code necessaire pour faire passer le test
- Pas de refactoring, pas d'optimisation, pas de generalisation
- Juste faire passer le test

```bash
docker compose exec php vendor/bin/phpunit --filter NomDuNouveauTest
# -> OK (1 test, X assertions)
```

### 3. REFACTOR — Ameliorer

- Eliminer la duplication
- Ameliorer la lisibilite
- Les tests doivent rester verts

```bash
docker compose exec php vendor/bin/phpunit
# -> OK (tous les tests passent)
```

### 4. REPEAT — Prochain cas

Reprendre au step 1 avec le cas suivant.

## Types de tests

| Type | Base class | Quand |
|------|-----------|-------|
| **Unitaire** | `TestCase` | Toujours — tester la logique isolee (mocks) |
| **Integration** | `KernelTestCase` | Quand Doctrine est implique |
| **Fonctionnel** | `WebTestCase` | Endpoints HTTP |

## Cas a TOUJOURS tester

1. **Cas nominal** (happy path)
2. **Cas limites** : 0 HP, inventaire plein, max stats, combat fini
3. **Cas d'erreur** : entite inexistante, acces refuse, donnees invalides
4. **Cas de bord** : valeurs negatives, string vide, null
5. **Regles metier** : competences passives seulement, materia requise pour sorts actifs

## Conventions de nommage

```php
public function test_applySpell_withFireOnWaterMob_dealsDoubleDamage(): void
//     ^methode  ^scenario                ^resultat_attendu
```

## Couverture cible

- **80%** minimum pour le code nouveau
- **100%** pour les calculs de combat (degats, critique, precision)
- **100%** pour les regles metier critiques (materia, skills passifs)

## Commandes

```bash
# Lancer un test specifique
docker compose exec php vendor/bin/phpunit --filter NomDuTest

# Lancer une suite
docker compose exec php vendor/bin/phpunit --testsuite Unit

# Tous les tests
docker compose exec php vendor/bin/phpunit

# Avec couverture
docker compose exec php vendor/bin/phpunit --coverage-text
```

## Enchainer avec

- `/code-review` pour une revue du code produit
- `/quality-gate` pour verifier la qualite globale
