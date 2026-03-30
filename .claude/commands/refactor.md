---
description: Detecter et supprimer le code mort, les imports inutiles, les doublons. Suppression securisee avec tests entre chaque etape. Usage — /refactor [path]
---

# /refactor — Nettoyage de code mort

Detecte et supprime le code mort de maniere securisee, avec verification par tests a chaque etape.

## Cible

$ARGUMENTS

Si aucun argument : analyser tout `src/`.

## Processus

### Etape 1 — Baseline tests

```bash
docker compose exec php vendor/bin/phpunit
```

Les tests DOIVENT etre verts avant de commencer. Si echec → utiliser `/build-fix` d'abord.

### Etape 2 — Detection

```bash
# PHPStan niveau 6 (detecte plus de code mort)
docker compose exec php vendor/bin/phpstan analyse --level 6

# Methodes/classes non referencees
grep -rn "function methodName" src/ --include="*.php"
# puis verifier les usages
grep -rn "methodName" src/ templates/ --include="*.php" --include="*.twig"
```

### Etape 3 — Categorisation

| Categorie | Risque | Exemples | Action |
|-----------|--------|----------|--------|
| **SAFE** | Bas | Variables locales, `use` inutiles | Supprimer directement |
| **PRUDENT** | Moyen | Methodes privees, templates | Verifier avec grep |
| **RISQUE** | Haut | Methodes publiques, services, listeners | Verifier config YAML + appels dynamiques |

### Etape 4 — Suppression par batch

Pour chaque batch :
1. Supprimer les elements identifies
2. `docker compose exec php vendor/bin/phpunit`
3. Si FAIL → `git checkout -- fichier` et passer au suivant
4. Si OK → continuer

### Etape 5 — Consolidation

Chercher :
- Fonctions dupliquees (logique similaire > 80%)
- Wrappers inutiles
- Types redondants
- `/** @deprecated */` anciens

## Rapport

```markdown
## Refactoring — [date]

### Supprime
- [fichier:ligne] : [description de ce qui a ete supprime]

### Conserve (doute)
- [fichier:ligne] : [pourquoi on ne supprime pas]

### Impact
- Lignes supprimees : X
- Fichiers touches : X
- Tests : ✅ toujours verts
```

## Quand NE PAS utiliser

- Pendant le dev actif d'une feature
- Juste avant un deploiement
- Sans tests suffisants
- Sur du code mal compris
