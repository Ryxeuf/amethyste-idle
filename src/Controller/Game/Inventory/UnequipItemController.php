<?php

namespace App\Controller\Game\Inventory;

use App\Entity\App\PlayerItem;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/equipment/unequip/{id}', name: 'app_game_inventory_equipment_unequip', methods: ['POST'])]
class UnequipItemController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly GearHelper $gearHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $bagInventory = $this->playerHelper->getBagInventory();

        $itemToUnequip = null;
        foreach ($bagInventory->getItems() as $item) {
            if ($item->getId() === $id && $item->getGenericItem()->isGear() && $this->gearHelper->isEquipped($item)) {
                $itemToUnequip = $item;
                break;
            }
        }

        if (!$itemToUnequip) {
            throw $this->createNotFoundException('Équipement non trouvé');
        }

        $itemToUnequip->setGear(0);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_game_inventory_equipment_list');
    }
}
