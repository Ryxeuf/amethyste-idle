<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Api\LegacyResponseEnveloper;
use App\Controller\Game\QuestController as LegacyQuestController;
use App\GameEngine\Quest\PlayerQuestUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Actions de quetes sous /api/v1 (migration API-first, phase 3.4).
 * Delegue au controleur legacy /game/quests/* (deja JSON) avec l'enveloppe
 * v1 ; les rejets metier (rien a livrer, mauvaise reponse d'enigme...)
 * ressortent en 409 action_rejected.
 * Content-Type application/json exige (convention CSRF v1).
 */
#[Route('/api/v1/quests')]
class QuestActionsController extends AbstractController
{
    public function __construct(
        private readonly LegacyQuestController $legacyQuestController,
        private readonly PlayerQuestUpdater $playerQuestUpdater,
        private readonly LegacyResponseEnveloper $enveloper,
    ) {
    }

    #[Route('/{id}/accept', name: 'api_v1_quest_accept', methods: ['POST'])]
    public function accept(Request $request, int $id): JsonResponse
    {
        return $this->guard($request) ?? $this->enveloper->envelope($this->legacyQuestController->accept($id));
    }

    #[Route('/{id}/abandon', name: 'api_v1_quest_abandon', methods: ['POST'])]
    public function abandon(Request $request, int $id): JsonResponse
    {
        return $this->guard($request) ?? $this->enveloper->envelope($this->legacyQuestController->abandon($id));
    }

    #[Route('/{id}/complete', name: 'api_v1_quest_complete', methods: ['POST'])]
    public function complete(Request $request, int $id): JsonResponse
    {
        return $this->guard($request) ?? $this->enveloper->envelope($this->legacyQuestController->complete($id, $request));
    }

    #[Route('/deliver/{pnjId}', name: 'api_v1_quest_deliver', methods: ['POST'])]
    public function deliver(Request $request, int $pnjId): JsonResponse
    {
        return $this->guard($request) ?? $this->enveloper->envelope($this->legacyQuestController->deliver($pnjId, $this->playerQuestUpdater));
    }

    #[Route('/puzzle-answer/{pnjId}', name: 'api_v1_quest_puzzle_answer', methods: ['POST'])]
    public function puzzleAnswer(Request $request, int $pnjId): JsonResponse
    {
        return $this->guard($request) ?? $this->enveloper->envelope($this->legacyQuestController->puzzleAnswer($pnjId, $request, $this->playerQuestUpdater));
    }

    #[Route('/daily/{id}/accept', name: 'api_v1_quest_daily_accept', methods: ['POST'])]
    public function dailyAccept(Request $request, int $id): JsonResponse
    {
        return $this->guard($request) ?? $this->enveloper->envelope($this->legacyQuestController->dailyAccept($id));
    }

    #[Route('/daily/{id}/complete', name: 'api_v1_quest_daily_complete', methods: ['POST'])]
    public function dailyComplete(Request $request, int $id): JsonResponse
    {
        return $this->guard($request) ?? $this->enveloper->envelope($this->legacyQuestController->dailyComplete($id));
    }

    #[Route('/daily/{id}/abandon', name: 'api_v1_quest_daily_abandon', methods: ['POST'])]
    public function dailyAbandon(Request $request, int $id): JsonResponse
    {
        return $this->guard($request) ?? $this->enveloper->envelope($this->legacyQuestController->dailyAbandon($id));
    }

    private function guard(Request $request): ?JsonResponse
    {
        $contentType = (string) $request->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'json')) {
            return ApiResponse::error('bad_request', 'Content-Type application/json requis.', 400);
        }

        return null;
    }
}
