---
description: Verification pre-commit ou pre-PR complete. Build, qualite, tests, debug statements, git status. Usage — /verify [quick|full|pre-commit|pre-pr]
---

# /verify — Verification complete

Execute une verification systematique du projet avant un commit ou une PR.

## Mode

$ARGUMENTS

- `quick` : Build + PHPStan uniquement (rapide)
- `full` : Tous les checks (defaut)
- `pre-commit` : Checks pertinents pour un commit
- `pre-pr` : Tous les checks + audit securite

## Etapes

### 1. Build (tous les modes)

```bash
docker compose exec php php bin/console cache:clear
docker compose exec php php bin/console tailwind:build
```

Si echec → STOP, reporter l'erreur.

### 2. Analyse statique (tous les modes)

```bash
docker compose exec php vendor/bin/phpstan analyse
```

Reporter les erreurs avec fichier:ligne.

### 3. Style de code (pre-commit, full, pre-pr)

```bash
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff
```

### 4. Tests (full, pre-pr)

```bash
docker compose exec php vendor/bin/phpunit
```

Capturer : pass/fail, nombre de tests, couverture.

### 5. Debug statements (pre-commit, full, pre-pr)

```bash
grep -rn "dd(\|dump(\|var_dump(\|error_log(\|print_r(" src/ --include="*.php"
```

### 6. Audit securite (pre-pr uniquement)

```bash
docker compose exec php composer audit
```

### 7. Git status (tous les modes)

```bash
git status
git diff --stat
```

## Rapport final

```
╔══════════════════════════════════╗
║     VERIFICATION — [mode]        ║
╠══════════════════════════════════╣
║ Build        : ✅ PASS / ❌ FAIL ║
║ PHPStan      : ✅ PASS / ❌ FAIL ║
║ PHP-CS-Fixer : ✅ PASS / ❌ FAIL ║
║ PHPUnit      : ✅ PASS / ❌ FAIL ║
║ Debug stmts  : ✅ PASS / ❌ FAIL ║
║ Securite     : ✅ PASS / ❌ FAIL ║
╠══════════════════════════════════╣
║ PR READY : ✅ OUI / ❌ NON       ║
╚══════════════════════════════════╝
```

### Si des problemes critiques sont detectes

Lister les problemes et proposer les corrections.
