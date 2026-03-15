<?php

namespace App\Controller\Game\Inventory;

use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/items', name: 'app_game_inventory_items_list')]
class ItemsController extends AbstractController
{
    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Récupérer l'inventaire sac du joueur
        $bagInventory = $this->playerHelper->getBagInventory();

        // Récupérer les objets consommables de l'inventaire
        $playerItems = $bagInventory->getItems();

        // Transformer les objets PlayerItem en tableau pour la vue
        $items = [];
        foreach ($playerItems as $item) {
            if ($item->getGenericItem()->isObject()) {
                $genericItem = $item->getGenericItem();
                $items[] = [
                    'id' => $item->getId(),
                    'name' => $genericItem->getName(),
                    'type' => 'Consommable',
                    'quantity' => 1, // Si vous avez un champ pour la quantité dans PlayerItem, utilisez-le ici
                    'description' => $genericItem->getDescription(),
                ];
            }
        }

        return $this->render('game/inventory/items/_list.html.twig', [
            'items' => $items,
        ]);
    }
}
