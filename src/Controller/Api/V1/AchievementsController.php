<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Entity\App\PlayerAchievement;
use App\Entity\Game\Achievement;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Succes en JSON (migration API-first, ecrans meta). Lecture seule.
 * Les succes caches non decouverts sont exclus du payload (anti-spoiler),
 * comme sur l'ecran Twig.
 */
#[Route('/api/v1')]
class AchievementsController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/achievements', name: 'api_v1_achievements', methods: ['GET'])]
    public function achievements(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        $locale = $request->getLocale();

        $playerAchievementMap = [];
        $completedCount = 0;
        foreach ($this->entityManager->getRepository(PlayerAchievement::class)->findBy(['player' => $player]) as $playerAchievement) {
            $playerAchievementMap[$playerAchievement->getAchievement()->getId()] = $playerAchievement;
            if ($playerAchievement->isCompleted()) {
                ++$completedCount;
            }
        }

        $categories = [];
        $totalVisible = 0;
        foreach ($this->entityManager->getRepository(Achievement::class)->findAll() as $achievement) {
            $playerAchievement = $playerAchievementMap[$achievement->getId()] ?? null;

            // Anti-spoiler : un succes cache n'apparait qu'une fois decouvert
            if ($achievement->isHidden() && $playerAchievement === null) {
                continue;
            }

            ++$totalVisible;

            $categories[$achievement->getCategory()][] = [
                'id' => $achievement->getId(),
                'slug' => $achievement->getSlug(),
                'title' => $achievement->getLocalizedTitle($locale),
                'description' => $achievement->getLocalizedDescription($locale),
                'icon' => $achievement->getIcon(),
                'hidden' => $achievement->isHidden(),
                'criteriaCount' => $achievement->getCriteriaCount(),
                'reward' => $achievement->getReward(),
                'progress' => $playerAchievement?->getProgress() ?? 0,
                'completed' => $playerAchievement?->isCompleted() ?? false,
                'completedAt' => $playerAchievement?->getCompletedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }

        return ApiResponse::success([
            'summary' => [
                'completed' => $completedCount,
                'totalVisible' => $totalVisible,
            ],
            'categories' => $categories,
        ]);
    }
}
