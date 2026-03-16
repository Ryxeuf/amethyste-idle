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
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    #[Route('', name: '_index')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            throw $this->createNotFoundException('Joueur non trouvé');
        }

        $inventory = $this->playerHelper->getBagInventory();

        return $this->render('game/inventory/index.html.twig', [
            'gold' => $inventory->getGold(),
            'gils' => $player->getGils(),
            'bagSize' => $inventory->getSize(),
            'bagUsed' => $inventory->getOccupiedSpace(),
        ]);
    }
}
