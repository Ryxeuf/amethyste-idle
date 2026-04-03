Objectif : implementer UNE tache concrete de la roadmap « a faire », avec le meilleur rapport effort / gain, en une PR raisonnable (~300 lignes max, un seul commit fonctionnel).

---

## 1. Conventions projet

Lire `CLAUDE.md` et `AGENTS.md` a la racine du depot. Ces fichiers font autorite pour la stack, les regles, les commandes Docker, et les conventions. Ne pas repeter leur contenu ici — les appliquer.

---

## 2. Ou lire la roadmap

| Source | Contenu |
|--------|---------|
| `docs/ROADMAP_DONE.md` | Taches realisees (reference) |
| `docs/roadmap/ROADMAP_TODO_INDEX.md` | Legende, graphe de dependances global, etat d'avancement par vague |
| `docs/roadmap/ROADMAP_TODO_VAGUE_07.md` … `_10.md` | Taches ouvertes par priorite (vagues 1-6 terminees) |
| `docs/roadmap/PLAN_TESTING.md` | Jalons **TST-01** a **TST-15** (testing & qualite) |
| `docs/roadmap/PLAN_AVATAR_SYSTEM.md` | Jalons **AVT-01** a **AVT-30** (avatar modulaire, 6 phases) |
| `docs/roadmap/PLAN_GUILD_CITY_CONTROL.md` | Jalons **GCC-01** a **GCC-20** (controle de cite — ✅ termine) |
| `docs/roadmap/PLAN_MAP_EDITOR.md` | Jalons **MED-01** a **MED-16** (editeur de cartes — ✅ termine) |

---

## 3. Choix de la tache

### Procedure

1. **Lire** `ROADMAP_TODO_INDEX.md` pour comprendre l'etat global et le graphe de dependances.
2. **Parcourir les vagues ouvertes** (7, puis 8, 9, 10) et les plans annexes (TST, AVT) pour lister les `- [ ]`.
3. **Lire** `ROADMAP_DONE.md` pour confirmer quels prerequis sont satisfaits.
4. **Choisir UNE seule tache** selon ces criteres, par ordre de priorite :
   a. **Dependances satisfaites** — tous les `← XX` / `← TST-XX` sont ✅
   b. **Deblocage maximal** — la tache debloque le plus d'autres taches dans le graphe
   c. **Gain gameplay fort** (★★★ > ★★ > ★)
   d. **Taille raisonnable** (S ou M prefere ; si L, decouper en sous-phase commitable)
   e. **Testabilite** — privilegier les taches verifiables par test automatise ou commande
   f. **Isolation** — diff minimal, peu de fichiers touches

### Garde-fous

- **Ne pas implementer** une tache dont un prerequis est ouvert, meme si elle semble simple.
- **Ne pas toucher** aux plans GCC/MED (termines) sauf correction de bug explicite.
- **Taille max** : ~300 lignes de diff, ~200 lignes de fixtures. Si la tache depasse, la decouper et n'implementer que la premiere sous-phase.

---

## 4. Execution

### Phase A — Analyse (pas de code)

1. Annoncer la tache choisie avec sa justification (numero, titre, pourquoi celle-la).
2. Lire le code existant lie a la tache (entites, services, controllers, templates, tests).
3. Proposer un **plan d'implementation** en etapes numerotees (fichiers a creer/modifier, logique metier, tests).
4. **Attendre la validation** de l'utilisateur avant de coder.

### Phase B — Implementation

1. Implementer etape par etape en suivant le plan valide.
2. Apres chaque etape significative, verifier que le code fonctionne :
   ```bash
   docker compose exec php vendor/bin/phpstan analyse
   docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff
   docker compose exec php vendor/bin/phpunit --filter <TestConcerne>
   ```
3. Si une etape revele un probleme, corriger avant de continuer.

### Phase C — Qualite

Avant de finaliser, lancer le pipeline qualite complet :

```bash
# Lint
docker compose exec php vendor/bin/php-cs-fixer fix

# Analyse statique
docker compose exec php vendor/bin/phpstan analyse

# Tests
docker compose exec php vendor/bin/phpunit
```

Corriger tout ce qui echoue. Ne pas finaliser avec des erreurs.

---

## 5. Finalisation

1. **Roadmap** : cocher la tache dans le fichier de vague concerne (`- [x]`) et l'ajouter dans `docs/ROADMAP_DONE.md` avec le detail des sous-taches realisees.
2. **Commit** : message conventionnel (`feat:`, `fix:`, `test:`, etc.) decrivant le changement.
3. **Push** : `git push -u origin <branch>`.
4. **Pull Request** via les outils MCP GitHub :
   - Titre court et descriptif (< 70 caracteres)
   - Body structure :
     ```
     ## Tache roadmap
     [Numero] — [Titre] (Vague X / Plan Y)

     ## Changements
     - ...

     ## Plan de test
     - [ ] Tests unitaires / integration ajoutes
     - [ ] PHPStan + lint passent
     - [ ] Verification manuelle : [decrire]
     ```
