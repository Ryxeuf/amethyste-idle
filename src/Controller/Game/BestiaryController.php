<?php

namespace App\Controller\Game;

use App\Entity\Game\Monster;
use App\Helper\PlayerHelper;
use App\Repository\PlayerBestiaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BestiaryController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerBestiaryRepository $bestiaryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/game/bestiary', name: 'app_game_bestiary', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $entries = $this->bestiaryRepository->findByPlayer($player);
        $totalMonsters = $this->entityManager->getRepository(Monster::class)->count([]);
        $totalKills = $this->bestiaryRepository->getTotalKills($player);

        return $this->render('game/bestiary/index.html.twig', [
            'player' => $player,
            'entries' => $entries,
            'totalMonsters' => $totalMonsters,
            'discoveredCount' => \count($entries),
            'totalKills' => $totalKills,
        ]);
    }
}
