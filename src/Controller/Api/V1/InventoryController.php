<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Helper\PlayerHelper;
use App\Service\Inventory\InventoryPayloadBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Inventaire en JSON (migration API-first, phase 2.1). Lecture seule :
 * les actions (equiper, socketter, utiliser, banque) restent sur les
 * endpoints legacy jusqu'a la phase 2.2.
 */
#[Route('/api/v1')]
class InventoryController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly InventoryPayloadBuilder $payloadBuilder,
    ) {
    }

    #[Route('/inventory', name: 'api_v1_inventory', methods: ['GET'])]
    public function inventory(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->playerHelper->getPlayer() === null) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        return ApiResponse::success($this->payloadBuilder->build($request->getLocale()));
    }
}
