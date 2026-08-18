<?php

namespace App\Controller\Game\Inventory;

use App\Entity\App\PlayerItem;
use App\GameEngine\Fight\BuildChangeLaw;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/game/inventory/equipment/modify/{id}', name: 'app_game_inventory_equipment_modify', requirements: ['id' => '\d+'], methods: ['GET'])]
class EquipmentModifyController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly GearHelper $gearHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // ARC-18i — refus n° 4 du § 13.5 : **le build se change hors combat**
        // (`BuildChangeLaw`). Sertir en plein combat rendrait le geste d'une
        // materia disponible au tour ou l'on en a besoin, ce qui supprimerait
        // le seul arbitrage que les emplacements imposent.
        $player = $this->playerHelper->getPlayer();
        if (!BuildChangeLaw::isAllowed($player)) {
            $this->addFlash('error', BuildChangeLaw::refusal());

            return $this->redirectToRoute('app_game_inventory_equipment_list');
        }

        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($id);
        if ($playerItem === null || !$playerItem->getGenericItem()->isGear()) {
            throw $this->createNotFoundException('Équipement introuvable.');
        }

        $ownsGear = false;
        foreach ($this->playerHelper->getBagInventory()->getItems() as $item) {
            if ($item->getId() === $playerItem->getId()) {
                $ownsGear = true;
                break;
            }
        }

        if (!$ownsGear) {
            throw $this->createAccessDeniedException('Cet équipement ne vous appartient pas.');
        }

        if (!$this->gearHelper->isEquipped($playerItem)) {
            throw $this->createNotFoundException('Cet objet n’est pas équipé.');
        }

        $slotKey = $playerItem->getGenericItem()->getGearLocation() ?? 'main_weapon';

        if ($request->headers->get('Turbo-Frame') === 'materia-pick') {
            return $this->render('game/inventory/equipment/_modify_materia_pick_placeholder.html.twig', [
                'playerItem' => $playerItem,
            ]);
        }

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render('game/inventory/equipment/_modify.html.twig', [
                'playerItem' => $playerItem,
                'slotKey' => $slotKey,
            ]);
        }

        // Full page visit (direct URL, back/forward) — redirect to inventory
        return $this->redirectToRoute('app_game_inventory_index');
    }
}
