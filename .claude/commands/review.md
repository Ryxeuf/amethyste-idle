---
description: Revue de code complete (securite + qualite) des changements non commites. Bloque si problemes critiques. Usage — /review
---

# /review — Revue de code

Lance une revue complete des changements non commites. Combine les agents **code-reviewer** et **security-reviewer**.

## Processus

### Etape 1 — Identifier les changements

```bash
git diff --name-only HEAD
git diff HEAD
```

### Etape 2 — Revue en 4 niveaux

**Securite (CRITIQUE)** :
- Credentials en dur, cles API, tokens
- Injection SQL (DQL non parametre)
- XSS (`|raw` sans sanitisation dans Twig)
- CSRF manquant, auth bypassee
- Donnees sensibles dans les logs ou reponses JSON

**Qualite de code (HAUT)** :
- Fonctions > 50 lignes, classes > 500 lignes
- Nesting > 4 niveaux, erreurs non gerees
- `dd()`, `dump()`, `var_dump()` oublies
- Code mort, imports inutilises

**Patterns Symfony/Doctrine (HAUT)** :
- N+1 queries, logique metier dans controllers
- Migrations non idempotentes
- Services avec > 5 dependances

**Bonnes pratiques (MOYEN)** :
- TODO sans reference, nommage inconsistant
- Tests manquants, nombres magiques

### Etape 3 — Rapport

Produit un rapport structure :
```
🔴 CRITIQUE — bloquer
🟠 HAUT — corriger
🟡 MOYEN — recommander
✅ Verdict : Approuve / A corriger / Bloque
```

### Etape 4 — Decision

- **CRITIQUE ou HAUT** : bloquer le merge, lister les corrections obligatoires
- **MOYEN uniquement** : approuver avec recommandations
- Ne signaler que les problemes avec confiance > 80%
