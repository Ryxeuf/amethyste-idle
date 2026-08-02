<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Entity\Game\Monster;
use App\Helper\PlayerHelper;
use App\Repository\PlayerBestiaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Bestiaire en JSON (migration API-first, ecrans meta). Lecture seule.
 */
#[Route('/api/v1')]
class BestiaryController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerBestiaryRepository $bestiaryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/bestiary', name: 'api_v1_bestiary', methods: ['GET'])]
    public function bestiary(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        $locale = $request->getLocale();

        $entries = [];
        foreach ($this->bestiaryRepository->findByPlayer($player) as $entry) {
            $monster = $entry->getMonster();
            $entries[] = [
                'monster' => [
                    'slug' => $monster->getSlug(),
                    'name' => $monster->getLocalizedName($locale),
                    'tier' => $monster->getTier(),
                    'rank' => $monster->getRank()->value,
                    'isBoss' => $monster->isBoss(),
                ],
                'killCount' => $entry->getKillCount(),
                'tier' => $entry->getTier(),
                'nextTierThreshold' => $entry->getNextTierThreshold(),
                'firstEncounteredAt' => $entry->getFirstEncounteredAt()->format(\DateTimeInterface::ATOM),
                'firstKilledAt' => $entry->getFirstKilledAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return ApiResponse::success([
            'summary' => [
                'discoveredCount' => \count($entries),
                'totalMonsters' => $this->entityManager->getRepository(Monster::class)->count([]),
                'totalKills' => $this->bestiaryRepository->getTotalKills($player),
            ],
            'entries' => $entries,
        ]);
    }
}
