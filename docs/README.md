# Symfony Docker

A [Docker](https://www.docker.com/)-based installer and runtime for the [Symfony](https://symfony.com) web framework,
with [FrankenPHP](https://frankenphp.dev) and [Caddy](https://caddyserver.com/) inside!

![CI](https://github.com/dunglas/symfony-docker/workflows/CI/badge.svg)

## Getting Started

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)
2. Run `docker compose build --no-cache` to build fresh images
3. Run `docker compose up --pull always -d --wait` to set up and start a fresh Symfony project
4. Open `https://localhost` in your favorite web browser and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
5. Run `docker compose down --remove-orphans` to stop the Docker containers.

## Features

* Production, development and CI ready
* Just 1 service by default
* Blazing-fast performance thanks to [the worker mode of FrankenPHP](https://github.com/dunglas/frankenphp/blob/main/docs/worker.md) (automatically enabled in prod mode)
* [Installation of extra Docker Compose services](extra-services.md) with Symfony Flex
* Automatic HTTPS (in dev and prod)
* HTTP/3 and [Early Hints](https://symfony.com/blog/new-in-symfony-6-3-early-hints) support
* Real-time messaging thanks to a built-in [Mercure hub](https://symfony.com/doc/current/mercure.html)
* [Vulcain](https://vulcain.rocks) support
* Native [XDebug](xdebug.md) integration
* Super-readable configuration

**Enjoy!**

## Docs

1. [Options available](options.md)
2. [Using Symfony Docker with an existing project](existing-project.md)
3. [Support for extra services](extra-services.md)
4. [Deploying in production](production.md)
5. [Debugging with Xdebug](xdebug.md)
6. [TLS Certificates](tls.md)
7. [Using MySQL instead of PostgreSQL](mysql.md)
8. [Using Alpine Linux instead of Debian](alpine.md)
9. [Using a Makefile](makefile.md)
10. [Updating the template](updating.md)
11. [Troubleshooting](troubleshooting.md)

## License

Symfony Docker is available under the MIT License.

## Credits

Created by [Kévin Dunglas](https://dunglas.dev), co-maintained by [Maxime Helias](https://twitter.com/maxhelias) and sponsored by [Les-Tilleuls.coop](https://les-tilleuls.coop).

# Sécurité et Authentification

## Configuration de l'héritage des rôles

Pour configurer l'héritage des rôles dans l'application, vous devez modifier le fichier `config/packages/security.yaml`. Voici un exemple de configuration que vous pouvez ajouter :

```yaml
security:
    # ...
    role_hierarchy:
        ROLE_ADMIN:       ROLE_USER
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
        ROLE_PLAYER:      ROLE_USER
```

Cette configuration établit la hiérarchie suivante :
- Un utilisateur avec le rôle `ROLE_ADMIN` hérite automatiquement du rôle `ROLE_USER`
- Un utilisateur avec le rôle `ROLE_SUPER_ADMIN` hérite des rôles `ROLE_ADMIN` et `ROLE_ALLOWED_TO_SWITCH` (ce dernier permet l'impersonation d'autres utilisateurs)
- Un utilisateur avec le rôle `ROLE_PLAYER` hérite du rôle `ROLE_USER`

Après avoir modifié ce fichier, n'oubliez pas de vider le cache :

```bash
php bin/console cache:clear
```

## Contrôle d'accès

Le contrôle d'accès est configuré dans la section `access_control` du fichier `security.yaml`. Actuellement, la configuration est la suivante :

```yaml
access_control:
    - { path: ^/game, roles: ROLE_USER }
    # - { path: ^/admin, roles: ROLE_ADMIN }
    # - { path: ^/profile, roles: ROLE_USER }
```

Cela signifie que toutes les URLs commençant par `/game` nécessitent au minimum le rôle `ROLE_USER`.

# Fixtures

Le projet inclut des fixtures pour charger des données de test dans la base de données.

## Comptes utilisateurs disponibles

Plusieurs comptes utilisateurs sont créés par les fixtures :

### Utilisateur principal
- Login : remy
- Email : remy@amethyste.game
- Mot de passe : test
- Rôles : ROLE_USER

### Utilisateur démo
- Login : demo
- Email : demo@amethyste.fr
- Mot de passe : test
- Rôles : ROLE_ADMIN, ROLE_SUPER_ADMIN, ROLE_PLAYER
- Joueur associé : player_demo

### Utilisateur démo 2
- Login : demo2
- Email : demo2@amethyste.fr
- Mot de passe : test
- Rôles : ROLE_ADMIN, ROLE_SUPER_ADMIN, ROLE_PLAYER
- Joueur associé : player_demo_2

## Chargement des fixtures

Pour charger les fixtures, exécutez la commande suivante :

```bash
php bin/console doctrine:fixtures:load
```

Cette commande va charger toutes les fixtures dans l'ordre suivant :
1. Utilisateurs (UserFixtures)
2. Mondes (WorldFixtures)
3. Cartes (MapFixtures)
4. Joueurs (PlayerFixtures)
5. Slots (SlotFixtures)
6. Inventaires (InventoryFixtures)
7. Domaines (DomainFixtures)
8. Expériences de domaine (DomainExperienceFixtures)
9. Compétences (SkillFixtures)
10. Monstres (MonsterFixtures)
11. Mobs (MobFixtures)
12. Objets (ItemFixtures)
13. Objets des joueurs (PlayerItemFixtures)
14. PNJ (PnjFixtures)
15. Quêtes (QuestFixtures)
16. Quêtes des joueurs (PlayerQuestFixtures)

## Structure des données

### Joueurs
Les joueurs créés par les fixtures sont :
- player_demo : associé à l'utilisateur demo
- player_demo_2 : associé à l'utilisateur demo2

### Quêtes
Les quêtes disponibles sont :
- Éliminer les zombies : éliminer 5 zombies
- Le Taiju solitaire : éliminer 1 Taiju
- Cueillette de champignons : récolter 5 champignons

### Objets
Plusieurs types d'objets sont disponibles :
- Materias (type de sort)
- Objets consommables
- Équipements
