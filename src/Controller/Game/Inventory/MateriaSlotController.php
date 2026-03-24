<?php

namespace App\Controller\Game\Inventory;

use App\Entity\App\Slot;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/game/inventory/materia/slot/{slotId}', name: 'app_game_inventory_materia_slot', methods: ['GET'])]
class MateriaSlotController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(int $slotId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $slot = $this->entityManager->getRepository(Slot::class)->find($slotId);
        if (!$slot) {
            throw $this->createNotFoundException('Slot introuvable.');
        }

        // Verify the slot belongs to the current player's gear
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
            throw $this->createAccessDeniedException('Cet équipement ne vous appartient pas.');
        }

        // Get available materia from player's materia inventory
        $materiaInventory = $this->playerHelper->getMateriaInventory();
        $availableMateria = [];
        foreach ($materiaInventory->getItems() as $materiaItem) {
            if (!$materiaItem->isMateria()) {
                continue;
            }
            // Skip materia already socketed somewhere
            if ($materiaItem->getSlotSet() !== null) {
                continue;
            }
            $availableMateria[] = $materiaItem;
        }

        return $this->render('game/inventory/materia/_slot_select.html.twig', [
            'slot' => $slot,
            'gearItem' => $gearItem,
            'availableMateria' => $availableMateria,
        ]);
    }
}
