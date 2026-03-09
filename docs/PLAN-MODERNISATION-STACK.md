# Recommandations de modernisation de la stack Amethyste-Idle

> Plan etabli le 9 mars 2026

## Diagnostic de l'existant

La stack actuelle est **solide dans ses fondations** (Symfony 7.2, FrankenPHP, PostgreSQL) mais souffre de plusieurs problemes architecturaux et de complexite inutile.

---

## 1. Mercure : GARDER -- il est deja integre dans FrankenPHP

**Constat** : Mercure n'est pas un serveur a installer separement. Il est **deja integre dans le Caddyfile** de FrankenPHP.

Mercure tourne **dans le meme processus** que FrankenPHP/Caddy. Zero service supplementaire, zero configuration reseau. C'est l'un des gros avantages de la stack FrankenPHP.

**Pourquoi Mercure est le bon choix pour ce projet :**

- **Integre nativement** dans FrankenPHP/Caddy (pas de service Docker supplementaire)
- **Natif Symfony** avec `symfony/mercure-bundle` (deja installe)
- **SSE (Server-Sent Events)** est parfait pour le broadcast server-to-client (mouvements, events, respawn)
- **Compatible Turbo Streams** (fonctionnalite deja presente mais desactivee)
- Pour les actions client-to-server (clic sur la carte, combat), les requetes HTTP classiques suffisent largement pour un idle RPG

**Mercure vs WebSockets :**

- Les WebSockets (Ratchet, Swoole) ajoutent un serveur separe, de la complexite, et ne sont necessaires que pour du vrai temps reel bidirectionnel haute frequence (FPS, RTS). Pour un idle RPG tour par tour avec carte, Mercure SSE est largement suffisant et beaucoup plus simple.

**Corrections appliquees :**

- L'URL Mercure etait **codee en dur** dans le client JS (`https://amethyste-idle.local:8243`) -- injectee dynamiquement
- Le controller `mercure-turbo-stream` etait **desactive** dans `assets/controllers.json` -- active

---

## 2. Eliminer completement Node.js de la stack

**Constat** : Node.js etait installe dans l'image Docker (2 etapes d'installation dans le Dockerfile) **uniquement** pour compiler Tailwind CSS via `npx tailwindcss`. Le bundle `symfonycasts/tailwind-bundle` gere cela avec un **binaire standalone** sans Node.js.

**Actions :**

- **Supprimer** `package.json`, `package-lock.json`, `postcss.config.js` et `node_modules/`
- **Supprimer** les etapes Node.js du Dockerfile
- **Configurer** le tailwind-bundle pour utiliser son binaire standalone
- En dev : `php bin/console tailwind:build --watch`
- En prod : `php bin/console tailwind:build --minify`

**Impact** : image Docker **significativement plus legere**, build plus rapide, une dependance systeme en moins.

---

## 3. Migration Tailwind v3 → v4.2

Tailwind v4 (derniere version stable : **4.2.1**) est une reecriture complete avec :

- **Configuration CSS-native** : plus de `tailwind.config.js`, tout se fait en CSS via `@theme` et `@import "tailwindcss"`
- **Builds 5x plus rapides** en full, 100x plus rapides en incremental
- **Detection automatique du contenu** : plus besoin de `content: [...]`
- Le bundle `symfonycasts/tailwind-bundle` v0.10+ supporte Tailwind v4

Migration :

- Supprimer `tailwind.config.js` et `postcss.config.js`
- Adapter `assets/styles/app.css` : remplacer `@tailwind base/components/utilities` par `@import "tailwindcss"`
- Migrer la palette custom `primary` vers `@theme { --color-primary: #6D28D9; ... }`
- Outil automatique : `npx @tailwindcss/upgrade` (execute avant suppression de Node.js)
- Mettre a jour la config bundle : passer a v4.2

---

## 4. Repenser l'architecture temps reel

### Problemes identifies

1. Le composant Live `Map` utilise un **polling toutes les 200ms** pendant les deplacements
2. Le handler Mercure simule un clic DOM pour declencher un re-render complet du Live Component
3. `usleep(250_000)` dans le message handler bloque le worker pendant 250ms par case

### Architecture cible

```
Player Action (clic) → Controller Stimulus → HTTP POST → Symfony Controller
→ Game Engine calcule le chemin → Publie le chemin complet via Mercure
→ Tous les clients recoivent le SSE → Animation case par case cote client
→ Le serveur met a jour la position finale en DB
```

- Turbo Streams via Mercure active pour les mises a jour necessitant un re-render Twig
- Controller Stimulus dedie pour la carte avec ecoute SSE directe
- Deplacements animes cote client (zero requete serveur pendant l'animation)

---

## 5. Remplacer Typesense par PostgreSQL / cache APCu

**Constat** : Typesense est utilise **uniquement** pour la recherche de cellules visibles autour du joueur. C'est de la sur-ingenierie pour un `WHERE x BETWEEN ... AND y BETWEEN ...`.

**Actions :**

- Creer un `CellRepository` Doctrine avec une requete PostgreSQL indexee
- Cache APCu pour les cartes (3600 cellules par carte 60x60)
- Supprimer `typesense/typesense-php`, le service Docker, les commandes CLI associees

---

## 6. Migration Doctrine ORM 2.x → 3.6 / DBAL 3.x → 4.x

**Versions cibles** : Doctrine ORM **3.6.2** + DBAL **4.x**

ORM 2.x entre en mode maintenance a partir de mars 2026.

**Strategie de migration :**

1. Resoudre toutes les deprecations dans le code existant
2. Adapter aux changements breaking (flush sans argument, PSR-6 cache, typage strict)
3. Mettre a jour composer.json
4. Verifier compatibilite de doctrine-bundle et stof/doctrine-extensions-bundle

---

## 7. Simplification des dependances

| Package | Action | Raison |
|---|---|---|
| `cron/cron-bundle` | **Remplacer** par `symfony/scheduler` | Natif Symfony 7.2, integre a Messenger |
| `symfony/css-selector` + `symfony/dom-crawler` | **Retirer** | Inutilises en runtime |
| `typesense/typesense-php` | **Retirer** | Remplace par PostgreSQL/cache |
| `stof/doctrine-extensions-bundle` | **Evaluer** | Garder si compatible ORM 3 |

---

## 8. Infrastructure Docker simplifiee (cible)

Passage de **4 services** a **2 services** (+ Traefik externe) :

- Supprimer `typesense`
- Supprimer `watcher_async_move_consumer`
- FrankenPHP en mode worker gere tout : HTTP, Mercure, et taches async

---

## 9. Stack finale cible

**Backend :**

- PHP 8.3 + Symfony 7.2
- FrankenPHP (Caddy) avec Mercure integre
- PostgreSQL 16
- Doctrine ORM **3.6** / DBAL **4.x**
- Symfony Scheduler

**Frontend :**

- Twig + Turbo (Frames + Streams via Mercure)
- Stimulus (controllers JS)
- Tailwind CSS **v4.2** (binaire standalone, zero Node.js, config CSS-native)
- AssetMapper (importmap, zero bundler)

**Infrastructure :**

- Docker : **2 services** (FrankenPHP + PostgreSQL) + Traefik externe
- Zero Node.js dans l'image
- Zero service de recherche externe
- Zero worker Messenger dedie

**Temps reel :**

- Mercure SSE integre dans Caddy (server-to-client broadcast)
- Turbo Streams pour les mises a jour DOM automatiques
- HTTP standard pour les actions joueur (client-to-server)
