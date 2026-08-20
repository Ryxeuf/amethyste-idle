# CI/CD — Amethyste-Idle

## Vue d'ensemble

Le projet utilise **GitHub Actions** pour l'intégration continue (CI) et le déploiement continu (CD).

```
PR vers main
     │
     ▼
┌──────────────────────────────────────────────────────────┐
│                     CI (ci.yml)                          │
│                                                          │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌─────────────┐  │
│  │ pr-title │ │   Lint   │ │ PHPStan  │ │    Tests    │  │
│  └────┬─────┘ └────┬─────┘ └────┬─────┘ └──────┬──────┘  │
│       │            └────────────┼──────────────┤         │
│       │                         ▼              ▼         │
│       │                 ┌──────────────┐  ┌─────────┐    │
│       │                 │ Docker Build │  │   E2E   │    │
│       │                 │ (validation) │  │ Panther │    │
│       │                 └──────┬───────┘  └────┬────┘    │
│       └────────────────────────┴───────────────┘         │
│                                ▼                         │
│                         ┌─────────────┐                  │
│                         │ ci-success  │  ← check requis  │
│                         └─────────────┘                  │
└──────────────────────────────────────────────────────────┘
     │ CI verte
     ▼
┌──────────────────────────────────────────────────────────┐
│              Auto-Merge (auto-merge.yml)                 │
│  squash merge, puis DECLENCHEMENT EXPLICITE de la release │
└──────────────────────────────────────────────────────────┘
     │ workflow_dispatch
     ▼
┌──────────────────────────────────────────────────────────┐
│                  Release (release.yml)                   │
│  1. CI complete (workflow_call)                          │
│  2. Semantic Release → tag vX.Y.Z + GitHub Release        │
│  3. Docker build → push GHCR (latest, vX.Y.Z, sha)        │
│  4. appelle deploy.yml  ← si, et seulement si, une        │
│                            nouvelle version est publiee   │
└──────────────────────────────────────────────────────────┘
     │ workflow_call
     ▼
┌──────────────────────────────────────────────────────────┐
│                   Deploy (deploy.yml)                    │
│  1. Verification des secrets de deploiement              │
│  2. SSH vers le serveur de production                    │
│  3. docker login ghcr.io                                 │
│  4. git fetch && git reset --hard origin/main            │
│  5. deploy.sh --prod (pull image, migrations, assets)    │
│  6. Health check du conteneur PHP (60 s max)             │
└──────────────────────────────────────────────────────────┘
```

### Les deux maillons qui manquaient (corriges le 2026-08-02)

GitHub n'emet **aucun evenement** pour une action effectuee par le `GITHUB_TOKEN`
(« events triggered by the GITHUB_TOKEN [...] will not create a new workflow
run »). La chaine etait donc coupee a deux endroits, et les deux pannes etaient
silencieuses :

1. Le squash-merge fait par `auto-merge.yml` ne produisait **pas d'evenement
   `push`** sur `main` : `release.yml` ne se declenchait jamais. Entre le
   2026-07-29 et le 2026-08-01, sept PR ont ete fusionnees sans qu'une seule
   version soit publiee.
2. La GitHub Release creee par semantic-release (donc par
   `github-actions[bot]`) ne produisait **pas d'evenement `release: published`** :
   `deploy.yml` ne se declenchait jamais. Sur les 30 derniers deploiements du
   depot, **aucun** n'etait automatique — tous etaient des `workflow_dispatch`
   manuels.

Les deux correctifs suivent le meme principe : **ne jamais dependre d'un
evenement qu'un jeton d'action n'emettra pas**.

1. `auto-merge.yml` appelle explicitement `release.yml` par
   `createWorkflowDispatch` (`workflow_dispatch` est l'exception documentee a la
   regle ci-dessus). Si le secret `PAT_AUTO_MERGE` existe, cet appel est saute :
   le merge fait au PAT emet un vrai `push`, et deux releases pour un commit
   n'auraient aucun sens.
2. `release.yml` appelle `deploy.yml` en `workflow_call`, dans le meme run, une
   fois l'image poussee. L'evenement `release: published` reste declare dans
   `deploy.yml` comme filet de securite pour une release publiee a la main.

### Ce qui declenche un deploiement, et ce qui n'en declenche pas

Le deploiement suit la **version**, pas le merge : `deploy.yml` n'est appele que
si semantic-release a publie une nouvelle version. Or c'est le **titre de la
PR** qui devient le message du commit (merge en squash), et lui seul est
analyse.

| Type du titre de PR | Version publiee | Deploiement |
|---|---|---|
| `feat:` | mineure (1.46 → 1.47) | oui |
| `fix:`, `perf:`, `refactor:` | corrective (1.46.0 → 1.46.1) | oui |
| `docs:`, `ci:`, `test:`, `chore:`, `style:`, `build:` | aucune | non (attendu) |
| Hors convention (`RET-10 — ...`) | aucune | **non — panne silencieuse** |

La derniere ligne est la raison d'etre du job `pr-title` : il refuse le merge
plutot que de laisser une livraison n'arriver jamais en production.

---

## Pipeline CI (`.github/workflows/ci.yml`)

Déclenchée sur :
- **Pull Request** vers `main`
- **workflow_call** depuis `release.yml` (push ou merge sur `main`)

Il n'y a **pas** de déclencheur `push` : un push sur `main` passe par
`release.yml`, qui appelle cette CI. Un déclencheur `push` ferait tourner la
suite deux fois pour le même commit. (Le déclencheur historique visait
`develop`, une branche qui n'existe pas.)

### Jobs

| Job | Description | Durée estimée |
|-----|-------------|---------------|
| `pr-title` | Vérifie que le titre de la PR est un commit conventionnel | ~5 s |
| `lint` | Vérifie le style PSR-12/Symfony avec PHP-CS-Fixer + parité des traductions | ~1 min |
| `phpstan` | Analyse statique niveau 5 (types, Symfony, Doctrine) | ~2 min |
| `tests` | Tests unitaires/fonctionnels/intégration avec PostgreSQL 17 + couverture | ~4 min |
| `e2e` | Parcours de bout en bout (Panther + Chrome headless) | ~4 min |
| `docker-build` | Build de l'image Docker de production (validation, sans push) | ~3 min |
| `ci-success` | Verdict unique agrégeant les 6 jobs | ~5 s |

`docker-build` ne s'exécute que si `lint`, `phpstan` et `tests` passent ; `e2e`
attend `tests`. Le push vers GHCR n'a **pas** lieu ici : il appartient à
`release.yml`, qui seul connaît le numéro de version à poser sur l'image.

`ci-success` est la cible à déclarer comme *required status check* sur `main` :
un seul check à configurer, qui reste valide quand la liste des jobs change. Il
accepte `skipped` (le job `pr-title` n'existe pas hors pull request) et refuse
tout le reste.

### Facteurs communs

L'installation de PHP et des dépendances vit dans une action composite,
`.github/actions/php-setup` : les cinq jobs qui en ont besoin l'appellent au
lieu de recopier le même bloc. Les variables d'environnement applicatives
(`DATABASE_URL`, Mercure, Messenger) sont définies **une fois** au niveau du
workflow.

### Outils de qualité

#### PHP-CS-Fixer
- **Config** : `.php-cs-fixer.dist.php`
- **Règles** : PSR-12 + Symfony (imports triés, pas d'imports inutilisés, etc.)
- **Commande locale** :
  ```bash
  # Vérifier (dry-run)
  docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

  # Corriger automatiquement
  docker compose exec php vendor/bin/php-cs-fixer fix
  ```

#### PHPStan
- **Config** : `phpstan.neon`
- **Niveau** : 5 (types stricts, détection d'erreurs courantes)
- **Extensions** : Symfony + Doctrine (résolution de container, repositories)
- **Commande locale** :
  ```bash
  docker compose exec php vendor/bin/phpstan analyse
  ```

#### PHPUnit
- **Config** : `phpunit.xml.dist`
- **Suites** : `Unit` (tests/Unit) et `Integration` (tests/Integration)
- **Commande locale** :
  ```bash
  # Tous les tests
  docker compose exec php vendor/bin/phpunit

  # Uniquement les tests unitaires
  docker compose exec php vendor/bin/phpunit --testsuite Unit

  # Un test spécifique
  docker compose exec php vendor/bin/phpunit --filter DijkstraTest
  ```

#### Le banc d'essai tourne à part, et sans profileur

`PerformanceBenchmarkTest` (groupe `benchmark`) chronomètre les routes critiques.
Il est **exclu de la course de couverture** et rejoué dans une étape dédiée avec
`XDEBUG_MODE=off`.

Ce n'est pas un confort. Xdebug instrumente chaque opcode dès que son mode contient
`coverage` — que PHPUnit collecte ou non, retirer `--coverage-*` ne retire pas le
coût. Un banc chronométré là-dedans mesure le profileur, pas la page, et la
compensation qui vivait dans le test (`×2` en présence de Xdebug) était un dosage :
elle a été re-dosée une fois, puis la mesure est repassée au-dessus (1035 ms pour un
plafond à 1000, le 2026-08-19, en bloquant une release), alors que le rejeu sans
couverture de la même CI passait.

Deux moitiés tiennent la règle, et il faut les deux :

- le test **refuse de mesurer** quand un pilote de couverture est actif (il se met en
  `skipped` avec sa raison) — sans quoi un `phpunit --coverage` lancé à la main
  rendrait un chiffre faux sans rien dire ;
- `CiBenchmarkWiringTest` vérifie que l'étape dédiée existe encore et coupe bien
  l'instrumentation — sans quoi le banc ne tournerait **nulle part**, et la CI serait
  verte pour cette raison.

Le seuil (`PERF_MAX_RESPONSE_MS`, 1000 ms) garde une page devenue **inutilisable** —
une explosion de requêtes N+1, un gabarit qui se recompile à chaque appel — et non un
contrat à la milliseconde : sur un runner partagé, la milliseconde mesure l'humeur du
voisin. Il est déclaré à deux endroits (le workflow et `DEFAULT_THRESHOLD_MS`), et un
test refuse qu'ils divergent.

---

## Auto-Merge (`.github/workflows/auto-merge.yml`)

Déclenché quand le workflow CI se termine, via `workflow_run`.

### Ce qu'il fait

1. Retrouve la PR ouverte vers `main` correspondant à la branche testée.
2. **Vérifie que le commit testé est bien le commit courant de la PR** — si des
   commits sont arrivés depuis, c'est leur propre CI qui décidera.
3. Refuse de merger une PR en **brouillon**, ou portant l'un des libellés
   `wip`, `do-not-merge`, `ne-pas-merger`, `hold`.
4. Refuse de merger une PR **en conflit** avec `main`, avec un avertissement
   lisible plutôt qu'une erreur d'API.
5. Squash-merge.
6. **Déclenche explicitement `release.yml`** (voir « les deux maillons qui
   manquaient » plus haut).

Le groupe de concurrence est unique (`auto-merge`, sans `github.ref`) : deux PR
ne sont jamais fusionnées en parallèle sur `main`.

### Secret optionnel

`PAT_AUTO_MERGE` (PAT avec droits `repo`) permet de contourner des règles de
protection de branche. S'il existe, il sert au merge — et le déclenchement
explicite de la release est alors sauté, puisque le merge fait au PAT émet un
vrai événement `push`.

---

## Pipeline Release (`.github/workflows/release.yml`)

Déclenché par un push sur `main`, par l'appel explicite d'`auto-merge.yml`, ou
à la main depuis l'onglet Actions.

1. **CI complète** — `workflow_call` vers `ci.yml`.
2. **Semantic Release** — analyse les commits depuis le dernier tag, crée le tag
   `vX.Y.Z` et la GitHub Release.
3. **Docker push** — image poussée vers GHCR en `latest`, `vX.Y.Z` et `sha`,
   avec `APP_VERSION` injecté au build (cf. règle 14 de `CLAUDE.md`).
4. **Deploy** — appelle `deploy.yml`.

Les étapes 3 et 4 ne s'exécutent **que** si une nouvelle version a été publiée.
Sinon, le résumé du run indique pourquoi (et affiche le dernier commit analysé).

---

## Pipeline CD (`.github/workflows/deploy.yml`)

Appelé par `release.yml` (`workflow_call`), manuellement
(`workflow_dispatch`, pour un re-déploiement ou un rollback), ou par une release
publiée à la main (`release: published`).

### Flux

1. **Vérification des secrets** — un secret manquant est signalé tout de suite,
   avec son nom, avant même que la clé SSH ne soit écrite sur le disque.
2. **Connexion SSH** — la clé d'hôte est relevée par `ssh-keyscan` ; un
   `known_hosts` vide arrête le déploiement au lieu de produire un
   « Host key verification failed » sans cause apparente.
3. **Login GHCR** — authentification Docker au registry GitHub sur le serveur.
4. **Pull du code** — `git fetch && git reset --hard origin/main`
5. **Déploiement** — Exécution de `./scripts/deploy.sh --prod` qui :
   - Pull l'image Docker depuis GHCR (pas de rebuild sur le serveur)
   - Active la page de maintenance
   - Applique les migrations Doctrine
   - Compile les assets (Tailwind + AssetMapper)
   - Vide et préchauffe le cache Symfony
   - Désactive la maintenance
6. **Health check** — Vérifie que le conteneur PHP répond (max 60 secondes)

### Sécurité

- Le déploiement utilise un **environment GitHub** `production` (approbation manuelle optionnelle)
- `cancel-in-progress: false` — Un déploiement en cours n'est jamais annulé
- La clé SSH est nettoyée systématiquement (même en cas d'erreur)
- Les variables passent par l'**environnement de la commande distante**, jamais
  par interpolation dans le corps du script : il n'y a plus de `\$` à compter à
  la main pour savoir ce qui s'évalue localement et ce qui s'évalue sur le
  serveur.

---

## Configuration requise (GitHub Secrets)

Pour activer le déploiement automatique, configurer ces secrets dans **Settings > Secrets and variables > Actions** :

| Secret | Description | Exemple |
|--------|-------------|---------|
| `DEPLOY_HOST` | IP ou hostname du serveur | `amethyste.best` |
| `DEPLOY_USER` | Utilisateur SSH | `deploy` |
| `DEPLOY_SSH_KEY` | Clé privée SSH (ed25519) | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `DEPLOY_PATH` | Chemin du projet sur le serveur | `/opt/amethyste-idle` |
| `DEPLOY_SSH_PORT` | Port SSH (optionnel) | `22` |
| `PAT_AUTO_MERGE` | PAT `repo` pour l'auto-merge (optionnel — voir plus haut) | `github_pat_...` |

### Générer la clé SSH

```bash
# Sur votre machine locale
ssh-keygen -t ed25519 -C "github-actions-deploy" -f deploy_key

# Copier la clé publique sur le serveur
ssh-copy-id -i deploy_key.pub deploy@amethyste.best

# Le contenu de deploy_key (clé privée) va dans le secret DEPLOY_SSH_KEY
```

### Créer l'environment GitHub (optionnel mais recommandé)

1. Aller dans **Settings > Environments**
2. Créer un environment `production`
3. Activer **Required reviewers** si vous voulez une approbation manuelle avant chaque déploiement
4. Restreindre aux branches : `main` uniquement

---

## Rollback

En cas de problème après un déploiement :

```bash
# Se connecter au serveur
ssh deploy@amethyste.best

# Aller dans le projet
cd /opt/amethyste-idle

# Revenir au commit précédent
git log --oneline -5  # trouver le commit à restaurer
git reset --hard <commit-hash>

# Redéployer
./scripts/deploy.sh --prod
```

---

## Ajout de tests

### Test unitaire (sans base de données)

```php
// tests/Unit/MonDomaine/MaClasseTest.php
namespace App\Tests\Unit\MonDomaine;

use PHPUnit\Framework\TestCase;

class MaClasseTest extends TestCase
{
    public function testMonComportement(): void
    {
        // Arrange → Act → Assert
    }
}
```

### Test d'intégration (avec Symfony kernel + BDD)

```php
// tests/Integration/MonDomaine/MonServiceTest.php
namespace App\Tests\Integration\MonDomaine;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MonServiceTest extends KernelTestCase
{
    public function testMonService(): void
    {
        self::bootKernel();
        $service = self::getContainer()->get(MonService::class);
        // ...
    }
}
```

---

## Résumé des commandes

```bash
# Lancer tous les checks localement (comme la CI)
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff
docker compose exec php vendor/bin/phpstan analyse
docker compose exec php vendor/bin/phpunit

# Corriger le style automatiquement
docker compose exec php vendor/bin/php-cs-fixer fix

# Tests avec couverture
docker compose exec php vendor/bin/phpunit --coverage-text
```
