<?php

namespace App\Controller\Game;

use App\Entity\Game\Monster;
use App\GameEngine\Bestiary\BestiaryRevealPolicy;
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
        private readonly BestiaryRevealPolicy $revealPolicy,
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
            // ONB-07b : le gabarit consulte une liste plutot que de rejouer la
            // regle a chacun des deux endroits qui l'affichent.
            'readableWeaknesses' => $this->revealPolicy->readableMonsterIds($entries),
            'readsByScent' => $this->revealPolicy->readsByScent($player),
        ]);
    }
}
