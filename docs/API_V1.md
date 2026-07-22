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

## Authentification

Deux mecanismes coexistent sur le meme firewall :

- **Session cookie** (client web) : inchangee, avec la protection CSRF ci-dessous.
- **Bearer JWT** (clients natifs mobile/Steam) : header `Authorization: Bearer <accessToken>`.

| Endpoint | Corps | Reponse |
|----------|-------|---------|
| `POST /api/v1/auth/login` | `{email, password}` | `{user, accessToken, refreshToken, tokenType, expiresIn}` — 401 `invalid_credentials` sinon |
| `POST /api/v1/auth/refresh` | `{refreshToken}` | Nouvelle paire de tokens — 401 si invalide/expire |

Tokens HS256 signes avec `API_JWT_SECRET` (fallback `kernel.secret`), emis par
`ApiJwtManager` via lcobucci/jwt (dependance existante de Mercure, aucun bundle ajoute).
Access token : 1 h. Refresh token : 30 jours, **stateless** (pas de revocation
individuelle — a durcir si le besoin apparait).

## CORS

`ApiCorsSubscriber` (sans bundle) gere le preflight et les headers sur `/api/*`
pour les origins listes dans l'env `API_CORS_ALLOWED_ORIGINS` (virgules, ou `*`).
Vide (defaut) = inactif. `Access-Control-Allow-Credentials` n'est jamais emis :
le cross-origin s'authentifie par Bearer, jamais par cookie (CSRF intact).

## Temps reel (Mercure)

`GET /api/v1/realtime/config` (ROLE_USER) retourne tout ce qu'un client natif
doit savoir pour s'abonner au hub Mercure :

```json
{
  "hubUrl": "https://game.amethyste.best/.well-known/mercure",
  "topics": {
    "map": ["map/move", "map/respawn", "map/spot", "map/weather"],
    "chat": ["chat/global", "chat/private/{playerId}", "chat/map/{mapId}", "chat/guild/{guildId}"],
    "notifications": ["player/{playerId}/notifications"],
    "events": ["event/announce", "guild/city_control"],
    "fight": ["fight/{fightId}/turn"]
  },
  "subscriberToken": "<jwt>",
  "expiresIn": 3600
}
```

Le hub autorise aujourd'hui les abonnes anonymes ; `subscriberToken` (claim
`mercure.subscribe`, signe avec `MERCURE_JWT_SECRET`) est fourni pour que les
clients l'envoient des maintenant (query `authorization` ou header
`Authorization`) et survivent a un futur durcissement du hub. A rafraichir
quand l'etat change (combat, carte, guilde) ou a expiration.

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
| `/api/v1/inventory/equip/{id}` | POST | ROLE_USER | Equiper un objet du sac (gear ou outil) — 409 si prerequis/slot manquants |
| `/api/v1/inventory/unequip/{id}` | POST | ROLE_USER | Desequiper un objet |
| `/api/v1/inventory/use/{id}` | POST | ROLE_USER | Utiliser un consommable (sort ou parchemin de competence) — 409 en combat/mort/deja connu |
| `/api/v1/inventory/materia/set/{slotId}/{materiaId}` | POST | ROLE_USER | Socketter une materia (slot du gear du joueur) — 409 si deja socketee, competence manquante, equipement non porte |
| `/api/v1/inventory/materia/unset/{slotId}` | POST | ROLE_USER | Dessocketter la materia d'un slot — 409 si slot vide |
| `/api/v1/quests/{id}/accept` | POST | ROLE_USER | Accepter une quete — 400 si renommee/prerequis manquants, deja acceptee/completee |
| `/api/v1/quests/{id}/abandon` | POST | ROLE_USER | Abandonner une quete active |
| `/api/v1/quests/{id}/complete` | POST | ROLE_USER | Completer une quete (recompenses) — `{choice}` si la quete a des choix (renvoyes en `details.choices`) |
| `/api/v1/quests/deliver/{pnjId}` | POST | ROLE_USER | Livrer les objets requis a un PNJ — 409 si rien a livrer |
| `/api/v1/quests/puzzle-answer/{pnjId}` | POST | ROLE_USER | Repondre a une enigme (`{answer}`) — 409 si mauvaise reponse |
| `/api/v1/quests/daily/{id}/accept` `complete` `abandon` | POST | ROLE_USER | Quetes journalieres |
| `/api/v1/realtime/config` | GET | ROLE_USER | Config Mercure : hub, topics du joueur, token subscriber (voir section Temps reel) |
| `/api/v1/bestiary` | GET | ROLE_USER | Bestiaire : resume (decouverts/total/kills) + entrees (paliers, seuil suivant, dates) |
| `/api/v1/achievements` | GET | ROLE_USER | Succes par categorie avec progression — les succes caches non decouverts sont exclus |
| `/api/v1/rankings` | GET | ROLE_USER | Classements (`?tab=kills\|quests\|xp`, top 50) + rang du joueur + titres de saison |
| `/api/v1/factions` | GET | ROLE_USER | Factions avec reputation, palier, progression et recompenses par palier |
| `/api/v1/mounts` | GET | ROLE_USER | Catalogue de montures (`?type=` filtre d'obtention) avec possession et monture active |

**Protection CSRF des ecritures** : les endpoints POST authentifies par session exigent
`Content-Type: application/json` (400 sinon). Un formulaire HTML cross-site ne peut pas
l'envoyer, et un fetch cross-origin echoue au preflight CORS.

Les rejets metier (pas votre tour, cooldown, energie insuffisante, fuite impossible...)
repondent `409 action_rejected` avec le message du legacy ; les erreurs dures gardent
leur statut d'origine (400, 403, 404).

## Roadmap de la migration

Phases validees (voir plan API-first) :

- **0.1** ✅ Convention d'enveloppe + listener d'exceptions + `/api/v1/ping`
- **0.2** ✅ JWT sans bundle (lcobucci + authenticator access_token natif, login/refresh)
- **0.3** ✅ CORS sans bundle (ApiCorsSubscriber, env API_CORS_ALLOWED_ORIGINS)
- **0.4** ✅ Config temps reel + token subscriber Mercure (`GET /api/v1/realtime/config`)
- **1.1** ✅ `GET /api/v1/fight` (etat du combat) — **1.2** ✅ actions combat sous /api/v1
  (alias enveloppes des controleurs legacy) — **1.4** ✅ butin sous /api/v1 — **1.3** UI JS combat
- **2.1** ✅ `GET /api/v1/inventory` — **2.2** ✅ equiper/desequiper/utiliser — **2.3** ✅ materia socketing (pas d'actions banque dans le legacy)
- **3.1** ✅ `GET /api/v1/skills` — **3.2** ✅ acquisition/respec/presets — **3.3** ✅ `GET /api/v1/quests` — **3.4** ✅ actions de quetes
- **4.1** ✅ chat — **4.4** ✅ notifications — **6.x** ✅ bestiaire, succes, classements, factions, montures — **4.x/6.x** social et meta (suite) —
  **5.x** Economie — **6.x** Ecrans meta — **7.x** Shell SPA + PWA/Capacitor/Steam
