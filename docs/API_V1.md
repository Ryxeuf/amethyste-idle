# API v1 — Conventions

Convention des endpoints `/api/v1/*` introduits par la migration API-first du frontend de jeu
(strangler pattern : les ecrans Twig migrent un par un vers un client JS consommant cette API).

## Enveloppe de reponse

Toutes les reponses passent par `App\Api\ApiResponse` :

```json
// Succes
{ "success": true, "data": { "...": "..." } }

// Erreur
{ "success": false, "error": { "code": "not_found", "message": "...", "details": { } } }
```

`details` est optionnel (ex: erreurs de validation par champ).

## Codes d'erreur

| HTTP | `error.code` |
|------|--------------|
| 400 | `bad_request` |
| 401 | `unauthenticated` |
| 403 | `forbidden` |
| 404 | `not_found` |
| 405 | `method_not_allowed` |
| 409 | `conflict` |
| 422 | `validation_failed` |
| 429 | `too_many_requests` |
| 409 (rejet metier combat) | `action_rejected` |
| 503 | `service_unavailable` |
| autres | `server_error` |

## Gestion des exceptions

`App\EventListener\ApiExceptionListener` convertit toute exception levee sous `/api/*`
en reponse JSON enveloppee (au lieu d'une page HTML ou d'une redirection login) :

- `AccessDeniedException` → 401 (anonyme) ou 403 (connecte)
- `HttpExceptionInterface` → statut de l'exception
- Toute autre exception → 500 (message masque hors debug)

## Endpoints

| Endpoint | Methode | Auth | Description |
|----------|---------|------|-------------|
| `/api/v1/ping` | GET | Public | Sante de l'API : `pong`, `version`, `serverTime` |
| `/api/v1/fight` | GET | ROLE_USER | Etat du combat courant (lecture seule) : `inFight`, `fight` (participants, effets de statut, sorts materia + cooldowns, timeline, logs, statut `active`/`victory`/`defeat`) |
| `/api/v1/fight/attack` | POST | ROLE_USER | Attaque de base (`targetId`, `targetType`) — delegue au legacy, enveloppe v1 |
| `/api/v1/fight/spell` | POST | ROLE_USER | Sort materia (`spellSlug`, `targetId`, `targetType`) — delegue au legacy, enveloppe v1 |
| `/api/v1/fight/item` | POST | ROLE_USER | Objet en combat — delegue au legacy, enveloppe v1 |
| `/api/v1/fight/flee` | POST | ROLE_USER | Fuite — delegue au legacy, enveloppe v1 |
| `/api/v1/fight/loot` | GET | ROLE_USER | Butin de fin de combat : `fightId`, `victory`, `items`, `contributions` (world boss) |
| `/api/v1/fight/loot/proceed` | POST | ROLE_USER | Ramasser le butin (`fightId`, `items[]`) et clore le combat — delegue au legacy, enveloppe v1 |
| `/api/v1/inventory` | GET | ROLE_USER | Inventaire complet (lecture seule) : `summary` (or, gils, sac, banque), `consumables`, `materials`, `equipment` (equipe par slot, outils, gear disponible, sets + bonus, stats), `materia`, `bank` |
| `/api/v1/skills` | GET | ROLE_USER | Arbres de talent (lecture seule) : `domains` (XP, competences avec `acquired`/`canBeAcquired`/`requirementIds`/`actions`), `buildStats`, `respec`, `points`, `presets` |
| `/api/v1/skills/acquire` | POST | ROLE_USER | Acquerir une competence (`skillId`) — 409 si deja acquise ou prerequis manquants |
| `/api/v1/skills/respec` | POST | ROLE_USER | Redistribuer les points (coute des gils) — 409 si aucune competence, en combat, ou fonds insuffisants |
| `/api/v1/skills/presets` | POST | ROLE_USER | Sauvegarder un preset de build (`name`) — 201, 409 (limite), 422 (nom invalide) |
| `/api/v1/skills/presets/{id}/load` | POST | ROLE_USER | Charger un preset (respec + reacquisition) — 409 avec message sinon |
| `/api/v1/skills/presets/{id}` | DELETE | ROLE_USER | Supprimer un preset |
| `/api/v1/quests` | GET | ROLE_USER | Journal de quetes (lecture seule) : `active` (progression, tracking, donneur, chaine), `available`, `completed`, `daily` (actives/terminees/disponibles du jour) |
| `/api/v1/chat/send` | POST | ROLE_USER | Envoyer un message (`content`, `channel` global/map/private/guild, `recipientId`) — commandes / incluses, delegue au legacy |
| `/api/v1/chat/history/{channel}` | GET | ROLE_USER | Historique d'un canal (50 derniers) — `?with={playerId}` pour le prive |
| `/api/v1/chat/conversations` | GET | ROLE_USER | Conversations privees existantes |
| `/api/v1/chat/players/search` | GET | ROLE_USER | Recherche de joueurs (`?q=`, min 2 caracteres) |
| `/api/v1/notifications` | GET | ROLE_USER | Notifications recentes (`?limit=`, 30 par defaut, max 100) + `unreadCount` |
| `/api/v1/notifications/mark-all-read` | POST | ROLE_USER | Tout marquer comme lu |
| `/api/v1/notifications/{id}/read` | POST | ROLE_USER | Marquer une notification comme lue (403 si autrui) |

**Protection CSRF des ecritures** : les endpoints POST authentifies par session exigent
`Content-Type: application/json` (400 sinon). Un formulaire HTML cross-site ne peut pas
l'envoyer, et un fetch cross-origin echoue au preflight CORS.

Les rejets metier (pas votre tour, cooldown, energie insuffisante, fuite impossible...)
repondent `409 action_rejected` avec le message du legacy ; les erreurs dures gardent
leur statut d'origine (400, 403, 404).

## Roadmap de la migration

Phases validees (voir plan API-first) :

- **0.1** ✅ Convention d'enveloppe + listener d'exceptions + `/api/v1/ping`
- **0.2** JWT (lexik) + firewall stateless session OU token
- **0.3** CORS (Capacitor/Tauri) + strategie CSRF
- **0.4** Auth Mercure par header pour clients natifs
- **1.1** ✅ `GET /api/v1/fight` (etat du combat) — **1.2** ✅ actions combat sous /api/v1
  (alias enveloppes des controleurs legacy) — **1.4** ✅ butin sous /api/v1 — **1.3** UI JS combat
- **2.1** ✅ `GET /api/v1/inventory` — **2.2+** actions inventaire (equiper, socketter, banque)
- **3.1** ✅ `GET /api/v1/skills` — **3.2** ✅ acquisition/respec/presets — **3.3** ✅ `GET /api/v1/quests`
- **4.1** ✅ chat — **4.4** ✅ notifications — **4.x** social (suite) —
  **5.x** Economie — **6.x** Ecrans meta — **7.x** Shell SPA + PWA/Capacitor/Steam
