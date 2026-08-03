<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Api\LegacyResponseEnveloper;
use App\Controller\Game\ChatController as LegacyChatController;
use App\Security\Attribute\RequiresVerifiedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Chat sous /api/v1 (migration API-first, phase 4.1).
 *
 * Delegue au controleur legacy /game/chat/* (deja JSON) et re-enveloppe.
 * L'envoi accepte un corps JSON {content, channel, recipientId} : il est
 * transmute en parametres de formulaire dans une sous-requete pour le
 * legacy, qui garde toute la logique (commandes /, moderation, canaux).
 * Content-Type application/json exige sur le POST (protection CSRF,
 * cf. docs/API_V1.md).
 */
#[Route('/api/v1/chat')]
class ChatController extends AbstractController
{
    public function __construct(
        private readonly LegacyChatController $legacyChatController,
        private readonly LegacyResponseEnveloper $enveloper,
    ) {
    }

    #[RequiresVerifiedEmail(channel: 'chat')]
    #[Route('/send', name: 'api_v1_chat_send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        $contentType = (string) $request->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'json')) {
            return ApiResponse::error('bad_request', 'Content-Type application/json requis.', 400);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = [];
        }

        $legacyRequest = new Request(request: [
            'content' => (string) ($data['content'] ?? ''),
            'channel' => (string) ($data['channel'] ?? 'global'),
            'recipient_id' => (int) ($data['recipientId'] ?? 0),
        ]);

        return $this->enveloper->envelope($this->legacyChatController->send($legacyRequest));
    }

    #[Route('/history/{channel}', name: 'api_v1_chat_history', methods: ['GET'])]
    public function history(string $channel, Request $request): JsonResponse
    {
        return $this->enveloper->envelope($this->legacyChatController->history($channel, $request));
    }

    #[Route('/conversations', name: 'api_v1_chat_conversations', methods: ['GET'])]
    public function conversations(): JsonResponse
    {
        return $this->enveloper->envelope($this->legacyChatController->conversations());
    }

    #[Route('/players/search', name: 'api_v1_chat_players_search', methods: ['GET'])]
    public function searchPlayers(Request $request): JsonResponse
    {
        return $this->enveloper->envelope($this->legacyChatController->searchPlayers($request));
    }
}
