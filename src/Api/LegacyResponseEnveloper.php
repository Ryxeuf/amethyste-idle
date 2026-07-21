<?php

namespace App\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-enveloppe la reponse JSON d'un controleur legacy /game/* dans la
 * convention ApiResponse des endpoints /api/v1 :
 * - 4xx legacy                      -> error {code du statut}
 * - 200 legacy avec success: false  -> error action_rejected (409)
 * - 200 legacy succes               -> success {payload}
 */
class LegacyResponseEnveloper
{
    public function envelope(Response $legacyResponse): JsonResponse
    {
        $payload = json_decode((string) $legacyResponse->getContent(), true);
        if (!is_array($payload)) {
            return ApiResponse::error('server_error', 'Unexpected legacy response format.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $status = $legacyResponse->getStatusCode();
        if ($status >= 400) {
            return ApiResponse::error(
                ApiResponse::errorCodeForStatus($status),
                (string) ($payload['error'] ?? (Response::$statusTexts[$status] ?? 'Error')),
                $status,
            );
        }

        // Rejet metier (pas votre tour, cooldown, energie insuffisante...) :
        // le legacy repond 200 + success: false, l'API v1 repond 409.
        if (($payload['success'] ?? true) === false) {
            return ApiResponse::error(
                'action_rejected',
                (string) ($payload['error'] ?? 'Action refusee.'),
                Response::HTTP_CONFLICT,
            );
        }

        unset($payload['success']);

        return ApiResponse::success($payload);
    }
}
