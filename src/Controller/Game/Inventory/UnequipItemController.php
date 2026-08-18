<?php

namespace App\Controller\Game\Inventory;

use App\GameEngine\Fight\BuildChangeLaw;
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

        $player = $this->playerHelper->getPlayer();

        // ARC-18i — refus n° 4 du § 13.5 : **le build se change hors combat**.
        // Il contredit DOM-02, et surtout il effondre les passifs conditionnels
        // d'ARC-12 : porter la dague pour le geste qui aime la dague puis la
        // hache au tour suivant rendrait *chaque condition vraie tout le
        // temps*, donc jamais payee.
        if (!BuildChangeLaw::isAllowed($player)) {
            $this->addFlash('error', BuildChangeLaw::refusal());

            return $this->redirectToRoute('app_game_inventory_equipment_list');
        }

        $itemToUnequip = null;
        foreach ($bagInventory->getItems() as $item) {
            if ($item->getId() === $id && $this->gearHelper->isEquipped($item)) {
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
