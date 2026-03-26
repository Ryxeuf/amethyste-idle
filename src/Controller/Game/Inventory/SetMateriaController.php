<?php

namespace App\Controller\Game\Inventory;

use App\Entity\App\Slot;
use App\Exception\ItemNotEquippedException;
use App\Exception\ItemNotMateriaException;
use App\Exception\ItemRequirementsException;
use App\GameEngine\Gear\MateriaGearSetter;
use App\Helper\PlayerHelper;
use App\Helper\PlayerItemHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/game/inventory/materia/set/{slotId}/{materiaId}', name: 'app_game_inventory_materia_set', methods: ['POST'])]
class SetMateriaController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly MateriaGearSetter $materiaGearSetter,
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerItemHelper $playerItemHelper,
    ) {
    }

    public function __invoke(int $slotId, int $materiaId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $slot = $this->entityManager->getRepository(Slot::class)->find($slotId);
        if (!$slot) {
            $this->addFlash('error', 'Slot introuvable.');

            return $this->redirectToRoute('app_game_inventory_equipment_list');
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
            $this->addFlash('error', 'Cet équipement ne vous appartient pas.');

            return $this->redirectToRoute('app_game_inventory_equipment_list');
        }

        // Find the materia in the player's materia inventory
        $materiaInventory = $this->playerHelper->getMateriaInventory();
        $materia = null;
        foreach ($materiaInventory->getItems() as $item) {
            if ($item->getId() === $materiaId && $item->isMateria()) {
                $materia = $item;
                break;
            }
        }

        if (!$materia) {
            $this->addFlash('error', 'Materia introuvable dans votre inventaire.');

            return $this->redirectToRoute('app_game_inventory_equipment_list');
        }

        // Check the materia isn't already socketed somewhere
        if ($materia->getSlotSet() !== null) {
            $this->addFlash('error', 'Cette materia est déjà équipée dans un autre slot.');

            return $this->redirectToRoute('app_game_inventory_equipment_list');
        }

        $block = $this->playerItemHelper->getMateriaSocketBlockMessage($materia);
        if ($block !== null) {
            $this->addFlash('error', $block);

            return $this->redirectToRoute('app_game_inventory_equipment_list');
        }

        try {
            $this->materiaGearSetter->setMateria($materia, $slot);
            $this->addFlash('success', 'Materia "' . $materia->getGenericItem()->getName() . '" équipée avec succès.');
        } catch (ItemRequirementsException $e) {
            $this->addFlash('error', $this->playerItemHelper->getMateriaSocketBlockMessage($materia) ?? $e->getMessage());
        } catch (ItemNotEquippedException) {
            $this->addFlash('error', 'L\'équipement n\'est plus porté.');
        } catch (ItemNotMateriaException) {
            $this->addFlash('error', 'Cet objet n\'est pas une materia.');
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible d\'équiper cette materia. Réessayez.');
        }

        return $this->redirectToRoute('app_game_inventory_equipment_list');
    }
}
