<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerAchievement;
use App\Entity\Game\Achievement;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/achievements')]
class AchievementController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_game_achievements', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $achievements = $this->entityManager->getRepository(Achievement::class)->findAll();

        // Index player achievements by achievement id
        $playerAchievements = $this->entityManager->getRepository(PlayerAchievement::class)->findBy(['player' => $player]);
        $playerAchievementMap = [];
        foreach ($playerAchievements as $pa) {
            $playerAchievementMap[$pa->getAchievement()->getId()] = $pa;
        }

        // Group achievements by category
        $categories = [];
        foreach ($achievements as $achievement) {
            $cat = $achievement->getCategory();
            if (!isset($categories[$cat])) {
                $categories[$cat] = [];
            }
            $categories[$cat][] = $achievement;
        }

        $completedCount = 0;
        foreach ($playerAchievements as $pa) {
            if ($pa->isCompleted()) {
                ++$completedCount;
            }
        }

        // Count visible achievements (non-hidden + hidden that have been discovered)
        $totalVisible = 0;
        foreach ($achievements as $achievement) {
            if (!$achievement->isHidden() || isset($playerAchievementMap[$achievement->getId()])) {
                ++$totalVisible;
            }
        }

        return $this->render('game/achievements/index.html.twig', [
            'categories' => $categories,
            'playerAchievementMap' => $playerAchievementMap,
            'totalAchievements' => $totalVisible,
            'completedCount' => $completedCount,
            'player' => $player,
        ]);
    }
}
