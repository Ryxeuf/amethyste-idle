<?php

namespace App\Controller\Game\Inventory;

use App\Helper\ItemHelper;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/items', name: 'app_game_inventory_items_list')]
class ItemsController extends AbstractController
{
    public function __construct(private readonly PlayerHelper $playerHelper, private readonly ItemHelper $itemHelper)
    {
    }

    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $bagInventory = $this->playerHelper->getBagInventory();
        $playerItems = $bagInventory->getItems();

        $grouped = [];
        foreach ($playerItems as $item) {
            if ($item->getGenericItem()->isObject()) {
                $genericItem = $item->getGenericItem();
                $slug = $genericItem->getSlug();
                if (!isset($grouped[$slug])) {
                    $grouped[$slug] = [
                        'id' => $item->getId(),
                        'name' => $genericItem->getName(),
                        'type' => 'Consommable',
                        'quantity' => 0,
                        'description' => $genericItem->getDescription(),
                        'usable' => $this->itemHelper->isUsable($genericItem),
                        'bound' => $item->isBound(),
                    ];
                }
                ++$grouped[$slug]['quantity'];
            }
        }
        $items = array_values($grouped);

        return $this->render('game/inventory/items/_list.html.twig', [
            'items' => $items,
        ]);
    }
}
