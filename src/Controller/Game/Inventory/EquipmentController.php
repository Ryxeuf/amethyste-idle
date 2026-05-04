<?php

namespace App\Controller\Game\Inventory;

use App\Entity\Game\Item;
use App\GameEngine\Fight\EquipmentSetResolver;
use App\GameEngine\Player\PlayerActionHelper;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerItemHelper;
use App\Service\Avatar\PlayerAvatarPayloadBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/equipment', name: 'app_game_inventory_equipment_list')]
class EquipmentController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly GearHelper $gearHelper,
        private readonly EquipmentSetResolver $equipmentSetResolver,
        private readonly PlayerEffectiveStatsCalculator $playerEffectiveStatsCalculator,
        private readonly PlayerActionHelper $playerActionHelper,
        private readonly PlayerItemHelper $playerItemHelper,
        private readonly PlayerAvatarPayloadBuilder $avatarPayloadBuilder,
    ) {
    }

    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $bagInventory = $this->playerHelper->getBagInventory();
        $player = $this->playerHelper->getPlayer();

        $equipped = [];
        foreach (Item::GEAR_LOCATIONS as $location) {
            $equipped[$location] = $this->gearHelper->getEquippedGearByLocation($location);
        }

        $availableGear = [];
        $availableTools = [];
        $canEquipMap = [];
        foreach ($bagInventory->getItems() as $item) {
            if ($item->getGenericItem()->isGear() && !$this->gearHelper->isEquipped($item)) {
                $availableGear[] = $item;
                $canEquipMap[$item->getId()] = $this->playerItemHelper->canBeEquipped($item);
            }
            if ($item->getGenericItem()->isTool() && !$this->gearHelper->isToolEquipped($item)) {
                $availableTools[] = $item;
            }
        }

        $totalProtection = 0;
        foreach ($equipped as $playerItem) {
            if ($playerItem !== null) {
                $totalProtection += $playerItem->getGenericItem()->getProtection();
            }
        }

        $activeSets = $this->equipmentSetResolver->getActiveSets($player);
        $setBonuses = $this->equipmentSetResolver->getSetBonuses($player);

        $totalProtection += $setBonuses['protection'];

        $stats = $this->playerEffectiveStatsCalculator->getInventorySheetStats($player, $totalProtection);

        // Tool slots data
        $unlockedToolSlots = $player->getUnlockedToolSlots();
        // Also check from skills in case sync hasn't happened yet
        $skillToolSlots = $this->playerActionHelper->getUnlockedToolSlots();
        $allToolSlots = array_unique(array_merge($unlockedToolSlots, $skillToolSlots));

        $toolSlots = [];
        foreach ($allToolSlots as $toolType) {
            $toolSlots[$toolType] = [
                'equipped' => $this->gearHelper->getEquippedToolByType($toolType),
                'label' => Item::TOOL_TYPE_LABELS[$toolType],
            ];
        }

        return $this->render('game/inventory/equipment/_list.html.twig', [
            'equipped' => $equipped,
            'availableGear' => $availableGear,
            'availableTools' => $availableTools,
            'canEquipMap' => $canEquipMap,
            'stats' => $stats,
            'player' => $player,
            'activeSets' => $activeSets,
            'setBonuses' => $setBonuses,
            'toolSlots' => $toolSlots,
            'avatarPayload' => $this->avatarPayloadBuilder->build($player),
        ]);
    }
}
