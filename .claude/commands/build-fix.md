---
description: Diagnostiquer et corriger les erreurs de build, PHPStan, ou tests. Approche incrementale — une erreur a la fois. Usage — /build-fix
---

# /build-fix — Correction d'erreurs de build

Diagnostique et corrige les erreurs de build, d'analyse statique ou de tests de maniere incrementale.

## Processus

### Etape 1 — Detection

Identifier le type d'erreur en lancant les outils :

```bash
# Cache Symfony
docker compose exec php php bin/console cache:clear

# Analyse statique
docker compose exec php vendor/bin/phpstan analyse

# Tests
docker compose exec php vendor/bin/phpunit

# Style
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

# Build assets
docker compose exec php php bin/console tailwind:build
```

### Etape 2 — Triage

Organiser les erreurs par priorite :
1. **Erreurs fatales** (syntax, classe introuvable, service manquant)
2. **Erreurs de type** (PHPStan — types incompatibles, methodes inexistantes)
3. **Echecs de tests** (PHPUnit — assertions fausses, exceptions inattendues)
4. **Violations de style** (PHP-CS-Fixer — formatage)

Trier par dependance : corriger les imports et types AVANT la logique.

### Etape 3 — Correction incrementale

Pour chaque erreur :

1. **Lire** le fichier concerne (~10 lignes autour de l'erreur)
2. **Diagnostiquer** la cause racine
3. **Appliquer** la correction minimale (pas de refactoring)
4. **Relancer** l'outil pour verifier que l'erreur est corrigee
5. **Passer** a l'erreur suivante

### Etape 4 — Verification finale

```bash
# Tout relancer
docker compose exec php vendor/bin/phpstan analyse && \
docker compose exec php vendor/bin/phpunit && \
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Regles de securite

- **DEMANDER** a l'utilisateur si un fix cree de NOUVELLES erreurs
- **DEMANDER** si une erreur persiste apres 3 tentatives
- **DEMANDER** si la correction necessite un changement structurel (nouvelle migration, nouveau service)
- **NE PAS** installer de dependances sans validation utilisateur
- **NE PAS** modifier les fixtures sans validation

## Erreurs courantes et solutions

| Erreur | Solution |
|--------|----------|
| `Class not found` | Verifier namespace, `composer dump-autoload` |
| `Service not found` | Verifier `services.yaml`, autowiring |
| `Table not found` | Lancer les migrations |
| `Column not found` | Verifier l'entite et generer une migration |
| `Type error PHPStan` | Ajouter le type hint correct |
| `CSRF token invalid` | Verifier `csrf_protection` dans le FormType |
| `Tailwind class not applied` | Relancer `tailwind:build` |
| `AssetMapper file not found` | Verifier `importmap.php`, relancer `asset-map:compile` |

## Principe directeur

**Une erreur a la fois, correction minimale, verification systematique.**
Ne jamais tenter de tout corriger d'un coup. Preferer des corrections ciblees a un refactoring global.
