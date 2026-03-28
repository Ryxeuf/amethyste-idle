<?php

namespace App\Controller\Game\Inventory;

use App\Entity\App\Slot;
use App\GameEngine\Gear\MateriaGearSetter;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/game/inventory/materia/unset/{slotId}', name: 'app_game_inventory_materia_unset', methods: ['POST'])]
class UnsetMateriaController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly MateriaGearSetter $materiaGearSetter,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(int $slotId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $slot = $this->entityManager->getRepository(Slot::class)->find($slotId);
        if (!$slot) {
            $this->addFlash('error', 'Slot introuvable.');

            return $this->redirectToRoute('app_game_inventory_equipment_list');
        }

        // Verify the slot belongs to the current player's gear
        /** @var \App\Entity\App\PlayerItem $gearItem */
        $gearItem = $slot->getItem();
        $bagInventory = $this->playerHelper->getBagInventory();
        $ownsGear = false;
        foreach ($bagInventory->getItems() as $item) {
            if ($item->getId() === $gearItem->getId()) {
                $ownsGear = true;
                break;
            }
        }

        if (!$ownsGear) {
            $this->addFlash('error', 'Cet équipement ne vous appartient pas.');

            return $this->redirectToRoute('app_game_inventory_equipment_list');
        }

        $redirectToGear = fn (): Response => $this->redirectToRoute('app_game_inventory_equipment_modify', ['id' => $gearItem->getId()]);

        if ($slot->getItemSet() === null) {
            $this->addFlash('error', 'Ce slot est déjà vide.');

            return $redirectToGear();
        }

        $materiaName = $slot->getItemSet()->getGenericItem()->getName();
        $this->materiaGearSetter->unsetMateria($slot);
        $this->addFlash('success', 'Materia "' . $materiaName . '" retirée avec succès.');

        return $redirectToGear();
    }
}
