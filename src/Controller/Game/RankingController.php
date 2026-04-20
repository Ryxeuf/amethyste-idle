<?php

namespace App\Controller\Game;

use App\Helper\PlayerHelper;
use App\Repository\DomainExperienceRepository;
use App\Repository\PlayerBestiaryRepository;
use App\Repository\PlayerQuestCompletedRepository;
use App\Repository\PlayerSeasonRewardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RankingController extends AbstractController
{
    private const int TOP_LIMIT = 50;
    private const string TAB_KILLS = 'kills';
    private const string TAB_QUESTS = 'quests';
    private const string TAB_XP = 'xp';
    private const array TABS = [self::TAB_KILLS, self::TAB_QUESTS, self::TAB_XP];

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerBestiaryRepository $bestiaryRepository,
        private readonly PlayerQuestCompletedRepository $questCompletedRepository,
        private readonly DomainExperienceRepository $domainExperienceRepository,
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

        $tab = (string) $request->query->get('tab', self::TAB_KILLS);
        if (!\in_array($tab, self::TABS, true)) {
            $tab = self::TAB_KILLS;
        }

        $data = [
            'player' => $player,
            'tab' => $tab,
            'topLimit' => self::TOP_LIMIT,
            'playerTitles' => $this->seasonRewardRepository->findByPlayer($player),
        ];

        if (self::TAB_QUESTS === $tab) {
            $data['topEntries'] = $this->questCompletedRepository->findTopQuestCompleters(self::TOP_LIMIT);
            $data['playerRank'] = $this->questCompletedRepository->getPlayerQuestRank($player);
            $data['playerTotal'] = $this->questCompletedRepository->countQuestsCompleted($player);
        } elseif (self::TAB_XP === $tab) {
            $data['topEntries'] = $this->domainExperienceRepository->findTopXpEarners(self::TOP_LIMIT);
            $data['playerRank'] = $this->domainExperienceRepository->getPlayerXpRank($player);
            $data['playerTotal'] = $this->domainExperienceRepository->getTotalXpEarned($player);
        } else {
            $data['topEntries'] = $this->bestiaryRepository->findTopKillers(self::TOP_LIMIT);
            $data['playerRank'] = $this->bestiaryRepository->getPlayerKillRank($player);
            $data['playerTotal'] = $this->bestiaryRepository->getTotalKills($player);
        }

        return $this->render('game/ranking/index.html.twig', $data);
    }
}
