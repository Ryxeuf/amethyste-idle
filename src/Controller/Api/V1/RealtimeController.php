<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Helper\PlayerHelper;
use App\Service\Realtime\RealtimeConfigBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Configuration temps reel Mercure pour les clients natifs
 * (migration API-first, phase 0.4) : URL du hub, topics du joueur,
 * JWT subscriber. A rafraichir quand l'etat change (entree en combat,
 * changement de carte, adhesion a une guilde) ou a expiration du token.
 */
#[Route('/api/v1')]
class RealtimeController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly RealtimeConfigBuilder $configBuilder,
    ) {
    }

    #[Route('/realtime/config', name: 'api_v1_realtime_config', methods: ['GET'])]
    public function config(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        return ApiResponse::success($this->configBuilder->build($player));
    }
}
