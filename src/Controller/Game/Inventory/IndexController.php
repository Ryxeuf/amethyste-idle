<?php

namespace App\Controller\Game\Inventory;

use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory', name: 'app_game_inventory')]
#[AsController]
class IndexController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper
    ) {
    }

    #[Route('', name: '_index')]
    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            throw $this->createNotFoundException('Joueur non trouvé');
        }

        $inventory = $this->playerHelper->getBagInventory();

        // Données de base pour l'inventaire
        $inventoryData = [
            'items' => $inventory->getItems(),
            'size' => $inventory->getSize(),
            'type' => $inventory->getType(),
        ];

        return $this->render('game/inventory/index.html.twig', [
            'inventory' => $inventoryData,
        ]);
    }
}
