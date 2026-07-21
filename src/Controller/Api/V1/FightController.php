<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Helper\PlayerHelper;
use App\Repository\FightRepository;
use App\Service\Fight\FightStatePayloadBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Etat du combat en JSON (migration API-first, phase 1.1).
 * Lecture seule : les actions (attaque, sort, objet, fuite) et les
 * transitions victoire/defaite restent sur leurs endpoints dedies.
 */
#[Route('/api/v1')]
class FightController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly FightRepository $fightRepository,
        private readonly FightStatePayloadBuilder $payloadBuilder,
    ) {
    }

    #[Route('/fight', name: 'api_v1_fight_state', methods: ['GET'])]
    public function state(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        $currentFight = $player->getFight();
        $fight = $currentFight !== null ? $this->fightRepository->findWithRelations($currentFight->getId()) : null;

        if ($fight === null) {
            return ApiResponse::success([
                'inFight' => false,
                'fight' => null,
            ]);
        }

        return ApiResponse::success([
            'inFight' => true,
            'fight' => $this->payloadBuilder->build($fight, $player, $request->getLocale()),
        ]);
    }
}
