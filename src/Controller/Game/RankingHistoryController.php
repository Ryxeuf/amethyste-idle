<?php

namespace App\Controller\Game;

use App\Enum\RankingTab;
use App\Helper\PlayerHelper;
use App\Repository\PlayerSeasonRankingSnapshotRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RankingHistoryController extends AbstractController
{
    private const int MAX_SEASONS = 10;
    private const int PODIUM_LIMIT = 3;

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerSeasonRankingSnapshotRepository $snapshotRepository,
    ) {
    }

    #[Route('/game/rankings/history', name: 'app_game_rankings_history', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $seasons = $this->snapshotRepository->findArchivedSeasons(self::MAX_SEASONS);

        $podiums = [];
        foreach ($seasons as $season) {
            $podiums[] = [
                'season' => $season,
                'tabs' => [
                    RankingTab::Kills->value => $this->snapshotRepository->findPodiumBySeasonAndTab($season, RankingTab::Kills, self::PODIUM_LIMIT),
                    RankingTab::Quests->value => $this->snapshotRepository->findPodiumBySeasonAndTab($season, RankingTab::Quests, self::PODIUM_LIMIT),
                    RankingTab::Xp->value => $this->snapshotRepository->findPodiumBySeasonAndTab($season, RankingTab::Xp, self::PODIUM_LIMIT),
                ],
            ];
        }

        return $this->render('game/ranking/history.html.twig', [
            'player' => $player,
            'podiums' => $podiums,
        ]);
    }
}
