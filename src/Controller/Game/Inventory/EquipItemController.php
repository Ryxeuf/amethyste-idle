<?php

namespace App\Controller\Game\Inventory;

use App\Entity\App\PlayerItem;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/equipment/equip/{id}', name: 'app_game_inventory_equipment_equip', methods: ['POST'])]
class EquipItemController extends AbstractController
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

        $itemToEquip = null;
        foreach ($bagInventory->getItems() as $item) {
            if ($item->getId() === $id) {
                $itemToEquip = $item;
                break;
            }
        }

        if (!$itemToEquip) {
            throw $this->createNotFoundException('Item non trouvé');
        }

        if (!$itemToEquip->getGenericItem()->isGear()) {
            throw new \LogicException('Cet item n\'est pas un équipement');
        }

        $slotType = $itemToEquip->getGenericItem()->getGearLocation();
        $gearValue = $this->gearHelper->getPlayerItemGearByLocation($slotType);

        if ($gearValue === null) {
            throw new \LogicException('Emplacement d\'équipement invalide');
        }

        $currentEquipped = $this->gearHelper->getEquippedGearByLocation($slotType);
        if ($currentEquipped) {
            $currentEquipped->setGear(0);
        }

        $itemToEquip->setGear($gearValue);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_game_inventory_equipment_list');
    }
}
