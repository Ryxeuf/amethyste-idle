---
description: Pipeline qualite complet (lint + PHPStan + tests). Equivalent de la CI locale. Usage — /quality-gate [--fix]
---

# /quality-gate — Pipeline de qualite

Execute le pipeline de qualite complet du projet, equivalent a la CI GitHub Actions en local.

## Arguments

$ARGUMENTS

- `--fix` : corrige automatiquement ce qui peut l'etre (PHP-CS-Fixer, etc.)

## Pipeline (4 etapes sequentielles)

### Etape 1 — Style de code (PHP-CS-Fixer)

```bash
# Verifier
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

# Si --fix : corriger automatiquement
docker compose exec php vendor/bin/php-cs-fixer fix
```

### Etape 2 — Analyse statique (PHPStan)

```bash
docker compose exec php vendor/bin/phpstan analyse
```

Signaler les erreurs avec fichier et ligne exacte.

### Etape 3 — Tests (PHPUnit)

```bash
docker compose exec php vendor/bin/phpunit
```

Capturer : nombre de tests, assertions, echecs, erreurs.

### Etape 4 — Detection debug statements

Rechercher les statements de debug oublies dans le code source :

```bash
grep -rn "dd(\|dump(\|var_dump(\|error_log(\|print_r(" src/ --include="*.php"
```

## Rapport

```markdown
## Quality Gate — [date]

| Check | Statut | Details |
|-------|--------|---------|
| PHP-CS-Fixer | ✅/❌ | X violations |
| PHPStan | ✅/❌ | X erreurs |
| PHPUnit | ✅/❌ | X tests, Y assertions, Z echecs |
| Debug statements | ✅/❌ | X trouves |

### Verdict : ✅ PASS / ❌ FAIL

### Problemes a corriger
- [liste des erreurs avec fichier:ligne]
```

## Si un check echoue

1. Lister les erreurs de maniere claire
2. Si `--fix` : corriger automatiquement ce qui peut l'etre et relancer
3. Sinon : proposer les corrections sans les appliquer

## Quand utiliser

- Avant de committer
- Avant de creer une PR
- Apres un refactoring important
- Quand la CI echoue et qu'on veut reproduire en local
