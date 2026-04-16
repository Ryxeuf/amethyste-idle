Objectif : implementer UNE tache concrete de la roadmap « a faire », avec le meilleur rapport effort / gain, en une PR raisonnable (~300 lignes de diff max, un seul commit fonctionnel).

---

## 0. Regles d'execution

> **Ces regles priment sur tout le reste du prompt.**

### Limites de taille
- **Fichier** : aucun fichier cree ou modifie ne doit depasser **400 lignes**. Si un fichier existant depasse deja 400 lignes, ne pas aggraver la situation (les modifications restent sous 50 lignes ajoutees).
- **Diff total** : ~300 lignes max (ajouts + suppressions). Si la tache depasse, la decouper en sous-phases.
- **Fixtures** : max 200 lignes par fichier de fixtures. Decouper en plusieurs fichiers si necessaire.

### Execution progressive (anti-timeout)
Chaque etape de travail doit etre **courte et autonome** :
1. **Micro-etapes** : ne jamais implementer plus de 2-3 fichiers avant de valider (PHPStan ou test).
2. **Checkpoints** : apres chaque micro-etape, lancer une verification rapide :
   ```bash
   docker compose exec php vendor/bin/phpstan analyse --memory-limit=512M
   ```
3. **Pas de gros batch** : ne pas lancer plusieurs commandes lourdes en parallele (ex: PHPStan + tests + lint en meme temps). Les executer sequentiellement.
4. **Lectures ciblees** : ne lire que les fichiers necessaires a l'etape en cours. Ne pas charger tout le code en amont.
5. **Si une commande prend trop de temps** : l'interrompre, reduire le scope (ex: `--filter` pour les tests), et reessayer.

### Ordre des operations
Toujours suivre cet ordre strict :
1. Verifier les PR existantes (avant de coder quoi que ce soit)
2. Analyser la tache (pas de code)
3. Implementer par micro-etapes avec validation
4. Qualite finale
5. Finalisation (roadmap, commit, push, PR)

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

### Etape 1 — Verifier les PR existantes

> **OBLIGATOIRE avant tout code.** Cette etape evite le travail en double.

1. Lister les PR ouvertes sur le repository via les outils GitHub MCP.
2. Pour chaque PR ouverte, verifier si elle traite une tache de la roadmap.
3. **Si une PR existe pour la tache selectionnee** :
   - **Ne pas la re-implementer.**
   - Se brancher sur la branche de la PR (`git checkout <branche>`).
   - Analyser ce qui bloque : echecs CI, conflits de merge, review comments.
   - Corriger les problemes identifies (erreurs CI, conflits, remarques review).
   - Verifier que la CI passe et qu'il n'y a plus de conflits.
   - Pousser les corrections et s'assurer que la PR est prete a merger.
   - **S'arreter la.** Ne pas chercher une autre tache.
4. **Si aucune PR ne concerne la tache** : continuer avec l'etape 2.

### Etape 2 — Selectionner la tache

1. **Lire** `ROADMAP_TODO_INDEX.md` pour comprendre l'etat global et le graphe de dependances.
2. **Parcourir les sprints dans l'ordre** : Sprint 1, puis 2, 3… jusqu'au 12. Chercher les `- [ ]` ouvertes.
3. **Lire** `ROADMAP_DONE.md` pour confirmer quels prerequis sont satisfaits.
4. **Choisir UNE seule tache** selon ces criteres, par ordre de priorite :
   a. **Sprint le plus prioritaire d'abord** — Sprint 1 (Critique) avant Sprint 2 (Haute), etc.
   b. **Dependances satisfaites** — tous les `← XX` sont ✅ dans ROADMAP_DONE ou coches dans le sprint.
   c. **Deblocage maximal** — la tache debloque le plus d'autres taches dans le graphe.
   d. **Gain gameplay fort** (★★★ > ★★ > ★).
   e. **Taille raisonnable** (S ou M prefere ; si L, decouper en sous-phase commitable).
   f. **Testabilite** — privilegier les taches verifiables par test automatise ou commande.
   g. **Isolation** — diff minimal, peu de fichiers touches.
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
2. Lire **uniquement** le code directement lie a la tache (entites, services, controllers, templates, tests). Ne pas explorer tout le codebase.
3. Proposer un **plan d'implementation** en etapes numerotees (fichiers a creer/modifier, logique metier, tests).
4. Verifier que le plan respecte les limites de taille (aucun fichier > 400 lignes, diff total < 300 lignes).

### Phase B — Implementation progressive

> **Regle cle : implementer par micro-etapes de 1-3 fichiers maximum.**

Pour chaque micro-etape :
1. Implementer les modifications (1-3 fichiers max).
2. Valider immediatement :
   ```bash
   docker compose exec php vendor/bin/phpstan analyse --memory-limit=512M
   ```
3. Si erreur : corriger avant de passer a l'etape suivante.
4. Passer a la micro-etape suivante.

**Regles de decoupe** :
- Etape 1 : Entite / migration (si applicable)
- Etape 2 : Service / logique metier
- Etape 3 : Controller / route
- Etape 4 : Template / frontend (si applicable)
- Etape 5 : Tests
- Etape 6 : Fixtures (si applicable)

Ne jamais fusionner plusieurs etapes en une seule. Valider entre chaque.

### Phase C — Qualite finale

Lancer les verifications **sequentiellement** (pas en parallele) :

```bash
# 1. Lint (correction auto)
docker compose exec php vendor/bin/php-cs-fixer fix

# 2. Analyse statique
docker compose exec php vendor/bin/phpstan analyse --memory-limit=512M

# 3. Tests (cibles d'abord, puis complets si OK)
docker compose exec php vendor/bin/phpunit --filter <TestConcerne>
docker compose exec php vendor/bin/phpunit
```

Corriger tout ce qui echoue. Ne pas finaliser avec des erreurs.

**Si les tests complets sont trop longs** : lancer uniquement les tests lies a la tache (`--filter`) et les tests de non-regression des modules touches.

---

## 5. Finalisation

### 5a. Mise a jour roadmap

1. Cocher la tache dans le fichier de sprint concerne (`- [x]`).
2. Si **toutes les sous-taches** d'une tache principale sont cochees, marquer le titre comme termine (`### ~~141 — ...~~ ✅`).
3. Ajouter la tache dans `docs/ROADMAP_DONE.md` avec le detail des sous-taches realisees.
4. Mettre a jour les compteurs dans `ROADMAP_TODO_INDEX.md` si necessaire.

### 5b. Commit et push

1. **Commit** : message conventionnel (`feat:`, `fix:`, `test:`, etc.) decrivant le changement.
2. **Push** : `git push -u origin <branch>`. Si echec reseau, reessayer (max 4 fois, backoff 2s/4s/8s/16s).

### 5c. Pull Request

Creer une PR via les outils GitHub MCP :
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
