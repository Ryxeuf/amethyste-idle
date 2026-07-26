<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Enum\RankingTab;
use App\GameEngine\Guild\SeasonManager;
use App\GameEngine\Season\RankingBaselineService;
use App\Helper\PlayerHelper;
use App\Repository\PlayerSeasonRewardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Classements en JSON (migration API-first, ecrans meta). Lecture seule,
 * memes onglets et limites que l'ecran Twig game/rankings.
 *
 * Les valeurs sont celles **de la saison en cours** depuis la tache 132.
 */
#[Route('/api/v1')]
class RankingsController extends AbstractController
{
    private const int TOP_LIMIT = 50;
    private const array TABS = ['kills', 'quests', 'xp'];

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly RankingBaselineService $baselineService,
        private readonly SeasonManager $seasonManager,
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

        $tab = RankingTab::tryFrom((string) $request->query->get('tab', RankingTab::Kills->value)) ?? RankingTab::Kills;

        $top = [];
        foreach ($this->baselineService->topOfSeason($tab, self::TOP_LIMIT) as $index => $row) {
            $top[] = [
                'rank' => $index + 1,
                'player' => [
                    'id' => $row['player']->getId(),
                    'name' => $row['player']->getName(),
                ],
                'total' => $row['total'],
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

        $season = $this->seasonManager->getCurrentSeason();

        return ApiResponse::success([
            'tab' => $tab->value,
            'tabs' => self::TABS,
            'topLimit' => self::TOP_LIMIT,
            'season' => null === $season ? null : [
                'number' => $season->getSeasonNumber(),
                'name' => $season->getName(),
            ],
            'top' => $top,
            'me' => [
                'rank' => $this->baselineService->currentSeasonRankFor($player, $tab),
                'total' => $this->baselineService->currentSeasonTotalFor($player, $tab),
            ],
            'titles' => $titles,
        ]);
    }
}
