<?php

namespace App\Controller\Game\Inventory;

use App\Entity\App\Slot;
use App\Helper\PlayerHelper;
use App\Helper\PlayerItemHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/game/inventory/materia/slot/{slotId}', name: 'app_game_inventory_materia_slot', methods: ['GET'])]
class MateriaSlotController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerItemHelper $playerItemHelper,
    ) {
    }

    public function __invoke(Request $request, int $slotId): Response
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

        $slotElement = $slot->getElement();
        $materiaRows = [];
        foreach ($availableMateria as $materiaItem) {
            $blockMessage = $this->playerItemHelper->getMateriaSocketBlockMessage($materiaItem);
            $gi = $materiaItem->getGenericItem();
            $isElementMatch = $slotElement !== null
                && $slotElement->value !== 'none'
                && $gi->getElement()->value !== 'none'
                && $slotElement === $gi->getElement();
            $materiaRows[] = [
                'materia' => $materiaItem,
                'canSocket' => $blockMessage === null,
                'blockMessage' => $blockMessage,
                'isElementMatch' => $isElementMatch,
            ];
        }

        usort($materiaRows, static function (array $a, array $b): int {
            if ($a['canSocket'] !== $b['canSocket']) {
                return $a['canSocket'] ? -1 : 1;
            }
            if ($a['canSocket'] && $b['canSocket'] && $a['isElementMatch'] !== $b['isElementMatch']) {
                return $a['isElementMatch'] ? -1 : 1;
            }

            return $a['materia']->getGenericItem()->getName() <=> $b['materia']->getGenericItem()->getName();
        });

        $payload = [
            'slot' => $slot,
            'gearItem' => $gearItem,
            'materiaRows' => $materiaRows,
        ];

        if ($request->headers->get('Turbo-Frame') === 'materia-pick') {
            return $this->render('game/inventory/materia/_slot_select_embed.html.twig', $payload);
        }

        if ($request->headers->get('Turbo-Frame') === 'inventory-content') {
            return $this->render('game/inventory/materia/_slot_select.html.twig', $payload);
        }

        // Full page visit (direct URL, back/forward) — redirect to inventory
        return $this->redirectToRoute('app_game_inventory_index');
    }
}
