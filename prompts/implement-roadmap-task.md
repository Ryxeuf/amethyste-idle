Objectif : implementer UNE tache concrete de la roadmap « a faire », avec le meilleur rapport effort / gain, en une PR raisonnable (~300 lignes max, un seul commit fonctionnel).

---

## 1. Conventions projet

Lire `CLAUDE.md` et `AGENTS.md` a la racine du depot. Ces fichiers font autorite pour la stack, les regles, les commandes Docker, et les conventions. Ne pas repeter leur contenu ici — les appliquer.

---

## 2. Structure de la roadmap

La roadmap est organisee en **12 sprints** focuses, par ordre de priorite.

| Source | Contenu |
|--------|---------|
| `docs/ROADMAP_DONE.md` | Taches realisees (reference historique) |
| `docs/roadmap/ROADMAP_TODO_INDEX.md` | Legende, graphe de dependances inter-sprints, etat d'avancement global |
| `docs/roadmap/SPRINT_01.md` … `SPRINT_12.md` | Taches detaillees par sprint (priorite Critique → Basse) |
| `docs/roadmap/PLAN_AVATAR_SYSTEM.md` | Reference detaillee du systeme avatar (Sprints 7-10) |
| `docs/roadmap/PLAN_TESTING.md` | Jalons TST restants (integres en Sprint 1) |

### Sprints (ordre de priorite)

| Sprint | Theme | Priorite |
|--------|-------|----------|
| 1 | Stabilite & Onboarding | Critique |
| 2 | Bestiaire & PNJ | Haute |
| 3 | Arsenal & Magie | Haute |
| 4 | Progression & Narration | Haute |
| 5 | Hotel des ventes | Moyenne |
| 6 | Social & Economie | Moyenne |
| 7-10 | Avatar modulaire (4 sprints) | Moyenne / Basse |
| 11 | Monde vivant | Basse |
| 12 | Technique & i18n | Basse |

### Plans termines (ne pas toucher sauf bug)

- `PLAN_GUILD_CITY_CONTROL.md` (GCC) — ✅ termine
- `PLAN_MAP_EDITOR.md` (MED) — ✅ termine

---

## 3. Choix de la tache

### Procedure

1. **Lire** `ROADMAP_TODO_INDEX.md` pour comprendre l'etat global et le graphe de dependances entre sprints.
2. **Parcourir les sprints dans l'ordre** : Sprint 1, puis 2, 3… jusqu'au 12. Chercher les `- [ ]` ouvertes.
3. **Lire** `ROADMAP_DONE.md` pour confirmer quels prerequis sont satisfaits.
4. **Choisir UNE seule tache** selon ces criteres, par ordre de priorite :
   a. **Sprint le plus prioritaire d'abord** — Sprint 1 (Critique) avant Sprint 2 (Haute), etc.
   b. **Dependances satisfaites** — tous les `← XX` sont ✅ dans ROADMAP_DONE ou coches dans le sprint
   c. **Deblocage maximal** — la tache debloque le plus d'autres taches dans le graphe
   d. **Gain gameplay fort** (★★★ > ★★ > ★)
   e. **Taille raisonnable** (S ou M prefere ; si L, decouper en sous-phase commitable)
   f. **Testabilite** — privilegier les taches verifiables par test automatise ou commande
   g. **Isolation** — diff minimal, peu de fichiers touches
5. **Parallelisation** : les sprints 7-10 (avatar) peuvent avancer en parallele des sprints 1-6. Si le sprint courant est bloque (dependances non satisfaites), chercher dans un sprint parallelisable.

### Garde-fous

- **Ne pas implementer** une tache dont un prerequis est ouvert, meme si elle semble simple.
- **Ne pas toucher** aux plans GCC/MED (termines) sauf correction de bug explicite.
- **Ne pas sauter de sprint** : terminer le sprint en cours avant de passer au suivant (sauf parallelisation documentee dans l'index).
- **Taille max** : ~300 lignes de diff, ~200 lignes de fixtures. Si la tache depasse, la decouper et n'implementer que la premiere sous-phase.

---

## 4. Execution

### Phase A — Analyse (pas de code)

1. Annoncer la tache choisie avec sa justification (numero, titre, sprint, pourquoi celle-la).
2. Lire le code existant lie a la tache (entites, services, controllers, templates, tests).
3. Proposer un **plan d'implementation** en etapes numerotees (fichiers a creer/modifier, logique metier, tests).

### Phase B — Implementation

1. Implementer etape par etape en suivant le plan.
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

1. **Roadmap** :
   - Cocher la tache dans le fichier de sprint concerne (`- [x]`).
   - Si **toutes les sous-taches** d'une tache principale sont cochees, barrer le titre (`~~### 141 — ...~~` → `### ~~141 — ...~~ ✅`).
   - Ajouter la tache dans `docs/ROADMAP_DONE.md` avec le detail des sous-taches realisees.
   - Mettre a jour les compteurs dans `ROADMAP_TODO_INDEX.md` si necessaire.
2. **Commit** : message conventionnel (`feat:`, `fix:`, `test:`, etc.) decrivant le changement.
3. **Push** : `git push -u origin <branch>`.
4. **Pull Request** via les outils MCP GitHub :
   - Titre court et descriptif (< 70 caracteres)
   - Body structure :
     ```
     ## Tache roadmap
     [Numero] — [Titre] (Sprint X)

     ## Changements
     - ...

     ## Plan de test
     - [ ] Tests unitaires / integration ajoutes
     - [ ] PHPStan + lint passent
     - [ ] Verification manuelle : [decrire]
     ```
