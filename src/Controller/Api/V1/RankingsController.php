<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Helper\PlayerHelper;
use App\Repository\DomainExperienceRepository;
use App\Repository\PlayerBestiaryRepository;
use App\Repository\PlayerQuestCompletedRepository;
use App\Repository\PlayerSeasonRewardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Classements en JSON (migration API-first, ecrans meta). Lecture seule,
 * memes onglets et limites que l'ecran Twig game/rankings.
 */
#[Route('/api/v1')]
class RankingsController extends AbstractController
{
    private const int TOP_LIMIT = 50;
    private const array TABS = ['kills', 'quests', 'xp'];

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerBestiaryRepository $bestiaryRepository,
        private readonly PlayerQuestCompletedRepository $questCompletedRepository,
        private readonly DomainExperienceRepository $domainExperienceRepository,
        private readonly PlayerSeasonRewardRepository $seasonRewardRepository,
    ) {
    }

    #[Route('/rankings', name: 'api_v1_rankings', methods: ['GET'])]
    public function rankings(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        $tab = (string) $request->query->get('tab', 'kills');
        if (!\in_array($tab, self::TABS, true)) {
            $tab = 'kills';
        }

        if ($tab === 'quests') {
            $rows = $this->questCompletedRepository->findTopQuestCompleters(self::TOP_LIMIT);
            $totalKey = 'totalQuests';
            $playerRank = $this->questCompletedRepository->getPlayerQuestRank($player);
            $playerTotal = $this->questCompletedRepository->countQuestsCompleted($player);
        } elseif ($tab === 'xp') {
            $rows = $this->domainExperienceRepository->findTopXpEarners(self::TOP_LIMIT);
            $totalKey = 'totalXp';
            $playerRank = $this->domainExperienceRepository->getPlayerXpRank($player);
            $playerTotal = $this->domainExperienceRepository->getTotalXpEarned($player);
        } else {
            $rows = $this->bestiaryRepository->findTopKillers(self::TOP_LIMIT);
            $totalKey = 'totalKills';
            $playerRank = $this->bestiaryRepository->getPlayerKillRank($player);
            $playerTotal = $this->bestiaryRepository->getTotalKills($player);
        }

        $top = [];
        foreach ($rows as $index => $row) {
            $top[] = [
                'rank' => $index + 1,
                'player' => [
                    'id' => $row['player']->getId(),
                    'name' => $row['player']->getName(),
                ],
                'total' => (int) $row[$totalKey],
            ];
        }

        $titles = [];
        foreach ($this->seasonRewardRepository->findByPlayer($player) as $reward) {
            $titles[] = [
                'tab' => $reward->getTab()->value,
                'rank' => $reward->getRank(),
                'title' => $reward->getTitleLabel(),
                'icon' => $reward->getCosmeticIcon(),
                'awardedAt' => $reward->getAwardedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return ApiResponse::success([
            'tab' => $tab,
            'tabs' => self::TABS,
            'topLimit' => self::TOP_LIMIT,
            'top' => $top,
            'me' => [
                'rank' => $playerRank,
                'total' => $playerTotal,
            ],
            'titles' => $titles,
        ]);
    }
}
