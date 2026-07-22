<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Helper\PlayerHelper;
use App\Service\Skill\SkillTreePayloadBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Arbres de talent en JSON (migration API-first, phase 3.1). Lecture seule :
 * l'acquisition de competence, le respec et les presets restent sur les
 * endpoints legacy jusqu'a la phase 3.2.
 */
#[Route('/api/v1')]
class SkillsController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly SkillTreePayloadBuilder $payloadBuilder,
    ) {
    }

    #[Route('/skills', name: 'api_v1_skills', methods: ['GET'])]
    public function skills(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->playerHelper->getPlayer() === null) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        return ApiResponse::success($this->payloadBuilder->build($request->getLocale()));
    }
}
