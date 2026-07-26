<?php

namespace App\Controller\Game;

use App\Enum\RankingTab;
use App\GameEngine\Guild\SeasonManager;
use App\GameEngine\Season\RankingBaselineService;
use App\Helper\PlayerHelper;
use App\Repository\PlayerSeasonRewardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Classement de la saison en cours (tache 132).
 *
 * Les valeurs affichees sont celles **de la saison** — cumul moins reference
 * figee a la cloture precedente. Jusqu'a la tache 132a, cet ecran montrait le
 * palmares depuis l'ouverture du serveur sous un titre saisonnier.
 */
class RankingController extends AbstractController
{
    private const int TOP_LIMIT = 50;

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly RankingBaselineService $baselineService,
        private readonly SeasonManager $seasonManager,
        private readonly PlayerSeasonRewardRepository $seasonRewardRepository,
    ) {
    }

    #[Route('/game/rankings', name: 'app_game_rankings', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $tab = RankingTab::tryFrom((string) $request->query->get('tab', RankingTab::Kills->value)) ?? RankingTab::Kills;

        return $this->render('game/ranking/index.html.twig', [
            'player' => $player,
            'tab' => $tab->value,
            'topLimit' => self::TOP_LIMIT,
            'playerTitles' => $this->seasonRewardRepository->findByPlayer($player),
            'topEntries' => $this->baselineService->topOfSeason($tab, self::TOP_LIMIT),
            'playerRank' => $this->baselineService->currentSeasonRankFor($player, $tab),
            'playerTotal' => $this->baselineService->currentSeasonTotalFor($player, $tab),
            // Nommer la saison en cours : sans elle, « classement de la saison »
            // reste une affirmation que le joueur ne peut pas verifier.
            'season' => $this->seasonManager->getCurrentSeason(),
        ]);
    }
}
