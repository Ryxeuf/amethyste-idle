<?php

namespace App\Controller\Game;

use App\Entity\App\Inventory;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/game', name: 'app_game')]
    public function __invoke(): Response
    {
        $player = $this->playerHelper->getPlayer();

        if (!$player) {
            return $this->redirectToRoute('app_home');
        }

        // Equipped items (gear > 0)
        $equippedItems = [];
        $bagInventory = $this->playerHelper->getBagInventory();
        foreach ($bagInventory->getItems() as $playerItem) {
            if ($playerItem->getGear() > 0) {
                $equippedItems[] = $playerItem;
            }
        }

        // Inventory stats
        $bagSize = $bagInventory->getSize();
        $bagUsed = $bagInventory->getOccupiedSpace();

        // Domain experiences
        $domainExperiences = $player->getDomainExperiences();

        // Active quests
        $activeQuests = $player->getQuests();

        // Skills count
        $skillsCount = $player->getSkills()->count();

        // Players on same map
        $playersOnMap = 0;
        if ($player->getMap()) {
            $playersOnMap = $player->getMap()->getPlayers()->count() - 1; // exclude self
        }

        return $this->render('game/index.html.twig', [
            'player' => $player,
            'equippedItems' => $equippedItems,
            'bagSize' => $bagSize,
            'bagUsed' => $bagUsed,
            'domainExperiences' => $domainExperiences,
            'activeQuests' => $activeQuests,
            'skillsCount' => $skillsCount,
            'playersOnMap' => max(0, $playersOnMap),
        ]);
    }
}
