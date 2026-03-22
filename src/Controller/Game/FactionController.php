<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/factions')]
class FactionController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_game_factions', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $factions = $this->entityManager->getRepository(Faction::class)->findAll();

        $playerFactions = $this->entityManager->getRepository(PlayerFaction::class)->findBy(['player' => $player]);
        $playerFactionMap = [];
        foreach ($playerFactions as $pf) {
            $playerFactionMap[$pf->getFaction()->getId()] = $pf;
        }

        return $this->render('game/factions/index.html.twig', [
            'factions' => $factions,
            'playerFactionMap' => $playerFactionMap,
            'player' => $player,
        ]);
    }
}
