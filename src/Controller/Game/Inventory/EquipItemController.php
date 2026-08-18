<?php

namespace App\Controller\Game\Inventory;

use App\Entity\App\PlayerItem;
use App\Enum\QuestGesture;
use App\Event\Game\PlayerGestureEvent;
use App\GameEngine\Fight\BuildChangeLaw;
use App\GameEngine\Player\PlayerActionHelper;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerItemHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/game/inventory/equipment/equip/{id}', name: 'app_game_inventory_equipment_equip', methods: ['POST'])]
class EquipItemController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly GearHelper $gearHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerActionHelper $playerActionHelper,
        private readonly PlayerItemHelper $playerItemHelper,
        private readonly EquipmentPortCatalog $equipmentPortCatalog,
        private readonly EventDispatcherInterface $eventDispatcher,
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

        $genericItem = $itemToEquip->getGenericItem();

        // Handle tool equipping
        if ($genericItem->isTool()) {
            $toolType = $genericItem->getToolType();
            $hasSlot = $toolType !== null && (
                $player->hasToolSlot($toolType)
                || \in_array($toolType, $this->playerActionHelper->getUnlockedToolSlots(), true)
            );

            if ($hasSlot && !$player->hasToolSlot($toolType)) {
                $player->unlockToolSlot($toolType);
            }

            if (!$hasSlot) {
                $this->addFlash('warning', 'Vous n\'avez pas débloqué cet emplacement d\'outil.');

                return $this->redirectToRoute('app_game_inventory_equipment_list');
            }

            // Vérifier que le joueur a la compétence pour équiper cet outil spécifique
            $toolSlug = $genericItem->getSlug();
            if (!$this->playerActionHelper->canEquipTool($toolSlug)) {
                $this->addFlash('warning', 'Vous n\'avez pas la compétence requise pour équiper cet outil.');

                return $this->redirectToRoute('app_game_inventory_equipment_list');
            }

            $gearValue = PlayerItem::TOOL_TYPE_TO_GEAR[$toolType] ?? null;
            if ($gearValue === null) {
                throw new \LogicException('Type d\'outil invalide');
            }

            // Déséquiper l'outil actuel du même type
            $currentTool = $this->gearHelper->getEquippedToolByType($toolType);
            if ($currentTool) {
                $currentTool->setGear(0);
            }

            $itemToEquip->setGear($gearValue);
            $this->entityManager->flush();
            $this->announceEquipped($itemToEquip);

            return $this->redirectToRoute('app_game_inventory_equipment_list');
        }

        if (!$genericItem->isGear()) {
            throw new \LogicException('Cet item n\'est pas un équipement');
        }

        if (!$this->playerItemHelper->canBeEquipped($itemToEquip)) {
            $this->addFlash('warning', 'Vous n\'avez pas les compétences requises pour équiper cet objet.');

            return $this->redirectToRoute('app_game_inventory_equipment_list');
        }

        $slotType = $genericItem->getGearLocation();
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
        $this->announceEquipped($itemToEquip);

        return $this->redirectToRoute('app_game_inventory_equipment_list');
    }

    /**
     * Annonce le port (ONB-12a).
     *
     * Le geste est annonce **apres** les deux refus qui le precedent — pas
     * d'emplacement d'outil, pas de nœud de port (ONB-20b) — donc jamais pour
     * une piece qui n'a pas ete portee. C'est ce qui en fait une preuve
     * suffisante du tour de boucle : parchemin, arbre, nœud, arme.
     */
    private function announceEquipped(PlayerItem $item): void
    {
        $generic = $item->getGenericItem();

        // Les lectures possibles de la cible : le slug de l'objet et la famille
        // d'arme. Une quete d'introduction demande « une arme », jamais une epee
        // courte precise — sinon le choix de l'etape 1 n'en serait pas un.
        $targets = [$generic->getSlug()];
        foreach ($generic->getRequirements() as $requirement) {
            $family = $this->equipmentPortCatalog->familyOfPortSkill($requirement->getSlug());
            if (null !== $family) {
                $targets[] = $family;
            }
        }

        $this->eventDispatcher->dispatch(
            new PlayerGestureEvent(QuestGesture::EquipItem, $targets),
            PlayerGestureEvent::NAME,
        );
    }
}
