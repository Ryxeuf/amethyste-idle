<?php

namespace App\Controller\Game\Inventory;

use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/materials', name: 'app_game_inventory_materials_list')]
class MaterialsController extends AbstractController
{
    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $bagInventory = $this->playerHelper->getBagInventory();

        $materials = [];
        foreach ($bagInventory->getItems() as $item) {
            if ($item->isResource()) {
                $materials[] = $item;
            }
        }

        return $this->render('game/inventory/materials/_list.html.twig', [
            'materials' => $materials,
        ]);
    }
}
