<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\GameEngine\World\GameTimeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/game')]
class GameTimeController extends AbstractController
{
    public function __construct(
        private readonly GameTimeService $gameTimeService,
    ) {
    }

    #[Route('/time', name: 'api_game_time', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->json($this->gameTimeService->getSnapshot());
    }
}
