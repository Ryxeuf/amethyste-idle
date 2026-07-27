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

        // Emplacements d'outils ouverts par les talents (ou deja acquis).
        $unlockedToolSlots = $player->getUnlockedToolSlots();
        $skillToolSlots = $this->playerActionHelper->getUnlockedToolSlots();
        $allToolSlots = array_values(array_unique(array_merge($unlockedToolSlots, $skillToolSlots)));

        // Outils que les talents autorisent, palier par palier (`equip.tool`).
        $equippableToolSlugs = $this->playerActionHelper->getEquippableToolSlugs();

        $availableGear = [];
        $availableTools = [];
        $canEquipMap = [];
        $toolEquipStates = [];
        foreach ($bagInventory->getItems() as $item) {
            if ($item->getGenericItem()->isGear() && !$this->gearHelper->isEquipped($item)) {
                $availableGear[] = $item;
                $canEquipMap[$item->getId()] = $this->playerItemHelper->canBeEquipped($item);
            }
            if ($item->getGenericItem()->isTool() && !$this->gearHelper->isToolEquipped($item)) {
                $availableTools[] = $item;
                $toolEquipStates[$item->getId()] = $this->resolveToolEquipState(
                    $item->getGenericItem(),
                    $allToolSlots,
                    $equippableToolSlugs,
                );
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
            'toolEquipStates' => $toolEquipStates,
            'avatarPayload' => $this->avatarPayloadBuilder->build($player),
        ]);
    }

    /**
     * Pourquoi un outil est equipable, ou ne l'est pas.
     *
     * La liste affichait un bouton « equiper » des lors que l'emplacement etait
     * ouvert, alors que `EquipItemController` exige en plus le talent du palier
     * concerne (`equip.tool`) : le joueur cliquait sur des pioches T2/T3/T4 pour
     * ne recevoir qu'un refus. L'ecran distingue maintenant les deux verrous.
     *
     * @param array<int, string> $unlockedToolSlots
     * @param array<int, string> $equippableToolSlugs
     *
     * @return 'ok'|'locked_slot'|'locked_skill'
     */
    private function resolveToolEquipState(Item $tool, array $unlockedToolSlots, array $equippableToolSlugs): string
    {
        $toolType = $tool->getToolType();
        if (null === $toolType || !\in_array($toolType, $unlockedToolSlots, true)) {
            return 'locked_slot';
        }

        return \in_array($tool->getSlug(), $equippableToolSlugs, true) ? 'ok' : 'locked_skill';
    }
}
