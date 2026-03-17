# CI/CD — Amethyste-Idle

## Vue d'ensemble

Le projet utilise **GitHub Actions** pour l'intégration continue (CI) et le déploiement continu (CD).

```
Push/PR sur n'importe quelle branche
        │
        ▼
┌─────────────────────────────────────────────┐
│              CI Pipeline                    │
│                                             │
│  ┌──────────┐  ┌──────────┐  ┌───────────┐ │
│  │   Lint   │  │ PHPStan  │  │   Tests   │ │
│  │ CS-Fixer │  │ Level 5  │  │  PHPUnit  │ │
│  └────┬─────┘  └────┬─────┘  └─────┬─────┘ │
│       └──────────────┼──────────────┘       │
│                      ▼                      │
│              ┌──────────────┐               │
│              │ Docker Build │               │
│              │   (prod)     │               │
│              └──────────────┘               │
└─────────────────────────────────────────────┘
          │                          │
          │ (PR vers main)           │ (push sur develop)
          ▼                          ▼
┌────────────────────┐   ┌─────────────────────┐
│  Auto-merge PR     │   │ Auto-merge develop  │
│  (squash merge)    │   │    → main           │
└────────┬───────────┘   └──────────┬──────────┘
         └───────────┬──────────────┘
                     │ (push sur main)
                     ▼
┌─────────────────────────────────────────────┐
│            CD Pipeline (Deploy)             │
│                                             │
│  1. SSH vers le serveur de production       │
│  2. git pull origin main                    │
│  3. ./scripts/deploy.sh --prod              │
│  4. Health check du conteneur PHP           │
└─────────────────────────────────────────────┘
```

---

## Pipeline CI (`.github/workflows/ci.yml`)

Déclenchée sur :
- **Push** sur `main`, `develop`, `feature/**`, `fix/**`
- **Pull Request** vers `main` ou `develop`

### Jobs

| Job | Description | Durée estimée |
|-----|-------------|---------------|
| `lint` | Vérifie le style PSR-12/Symfony avec PHP-CS-Fixer | ~1 min |
| `phpstan` | Analyse statique niveau 5 (types, Symfony, Doctrine) | ~2 min |
| `tests` | Tests unitaires PHPUnit avec PostgreSQL 17 | ~2 min |
| `docker-build` | Build de l'image Docker de production (validation) | ~3 min |

Le job `docker-build` ne s'exécute que si les 3 premiers passent.

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

---

## Auto-Merge (`.github/workflows/auto-merge.yml`)

Déclenché automatiquement quand le workflow CI se termine avec succès.

### Deux modes

| Mode | Déclencheur | Action |
|------|-------------|--------|
| **Auto-merge PR** | CI passe sur une PR vers `main` | Squash-merge automatique de la PR |
| **Auto-merge develop** | CI passe sur un push vers `develop` | Merge `develop` dans `main` |

### Comportement

- **PRs** : quand la CI réussit sur une PR ciblant `main`, le workflow la merge automatiquement (squash merge). Si le merge échoue (conflits, protections), il échoue silencieusement.
- **develop** : quand un push sur `develop` passe la CI, le workflow merge `develop` dans `main`. En cas de conflit, une issue GitHub est créée automatiquement pour alerter.
- Le push résultant sur `main` déclenche ensuite le pipeline CD (deploy).

---

## Pipeline CD (`.github/workflows/deploy.yml`)

Déclenchée **uniquement** sur push vers `main`.

### Flux

1. **CI complète** — Tous les jobs CI doivent passer
2. **Connexion SSH** — Connexion sécurisée au serveur de production
3. **Pull du code** — `git fetch && git reset --hard origin/main`
4. **Déploiement** — Exécution de `./scripts/deploy.sh --prod` qui :
   - Reconstruit les conteneurs Docker
   - Active la page de maintenance
   - Compile les assets (Tailwind + AssetMapper)
   - Vide et préchauffe le cache Symfony
   - Désactive la maintenance
5. **Health check** — Vérifie que le conteneur PHP répond (max 60 secondes)

### Sécurité

- Le déploiement utilise un **environment GitHub** `production` (approbation manuelle optionnelle)
- `cancel-in-progress: false` — Un déploiement en cours n'est jamais annulé
- La clé SSH est nettoyée systématiquement (même en cas d'erreur)

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
