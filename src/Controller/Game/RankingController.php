<?php

namespace App\Controller\Game;

use App\Helper\PlayerHelper;
use App\Repository\PlayerBestiaryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RankingController extends AbstractController
{
    private const int TOP_LIMIT = 50;

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerBestiaryRepository $bestiaryRepository,
    ) {
    }

    #[Route('/game/rankings', name: 'app_game_rankings', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $topKillers = $this->bestiaryRepository->findTopKillers(self::TOP_LIMIT);
        $playerRank = $this->bestiaryRepository->getPlayerKillRank($player);
        $playerTotalKills = $this->bestiaryRepository->getTotalKills($player);

        return $this->render('game/ranking/index.html.twig', [
            'player' => $player,
            'topKillers' => $topKillers,
            'playerRank' => $playerRank,
            'playerTotalKills' => $playerTotalKills,
            'topLimit' => self::TOP_LIMIT,
        ]);
    }
}
