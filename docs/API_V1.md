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

## Roadmap de la migration

Phases validees (voir plan API-first) :

- **0.1** ✅ Convention d'enveloppe + listener d'exceptions + `/api/v1/ping`
- **0.2** JWT (lexik) + firewall stateless session OU token
- **0.3** CORS (Capacitor/Tauri) + strategie CSRF
- **0.4** Auth Mercure par header pour clients natifs
- **1.1** ✅ `GET /api/v1/fight` (etat du combat) — **1.2+** actions combat JSON, UI JS, loot
- **2.x** Inventaire — **3.x** Progression — **4.x** Social —
  **5.x** Economie — **6.x** Ecrans meta — **7.x** Shell SPA + PWA/Capacitor/Steam
