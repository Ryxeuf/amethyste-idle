<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Helper\PlayerHelper;
use App\Service\Quest\QuestJournalPayloadBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Journal de quetes en JSON (migration API-first, phase 3.3). Lecture seule :
 * accepter, abandonner, livrer et completer restent sur les endpoints legacy.
 */
#[Route('/api/v1')]
class QuestsController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly QuestJournalPayloadBuilder $payloadBuilder,
    ) {
    }

    #[Route('/quests', name: 'api_v1_quests', methods: ['GET'])]
    public function quests(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->playerHelper->getPlayer() === null) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        return ApiResponse::success($this->payloadBuilder->build($request->getLocale()));
    }
}
