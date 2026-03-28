<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\App\Festival;
use App\GameEngine\World\GameTimeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/game')]
class GameTimeController extends AbstractController
{
    public function __construct(
        private readonly GameTimeService $gameTimeService,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/time', name: 'api_game_time', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $snapshot = $this->gameTimeService->getSnapshot();

        $festivals = $this->em->getRepository(Festival::class)->findBy(['active' => true]);
        $activeFestivals = [];
        foreach ($festivals as $festival) {
            if ($festival->isCurrentlyRunning($snapshot['season'], $snapshot['day'])) {
                $activeFestivals[] = [
                    'slug' => $festival->getSlug(),
                    'name' => $festival->getName(),
                    'description' => $festival->getDescription(),
                    'season' => $festival->getSeason(),
                    'rewards' => $festival->getRewards(),
                ];
            }
        }

        $snapshot['festivals'] = $activeFestivals;

        return $this->json($snapshot);
    }
}
