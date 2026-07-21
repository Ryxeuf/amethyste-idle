<?php

namespace App\Service\Inventory;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\GameEngine\Fight\EquipmentSetResolver;
use App\GameEngine\Player\PlayerActionHelper;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;
use App\Helper\GearHelper;
use App\Helper\ItemHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerItemHelper;

/**
 * Construit le payload JSON de l'inventaire pour /api/v1/inventory
 * (migration API-first, phase 2.1). Lecture seule : reprend les donnees
 * des ecrans Twig items/materiaux/equipement/materia/banque.
 */
class InventoryPayloadBuilder
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly GearHelper $gearHelper,
        private readonly ItemHelper $itemHelper,
        private readonly PlayerItemHelper $playerItemHelper,
        private readonly EquipmentSetResolver $equipmentSetResolver,
        private readonly PlayerEffectiveStatsCalculator $playerEffectiveStatsCalculator,
        private readonly PlayerActionHelper $playerActionHelper,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(?string $locale = null): array
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            throw new \LogicException('Aucun joueur courant pour construire le payload inventaire.');
        }

        $bagInventory = $this->playerHelper->getBagInventory();
        $bankInventory = $this->playerHelper->getBankInventory();

        return [
            'summary' => [
                'gold' => $bagInventory->getGold(),
                'gils' => $player->getGils(),
                'bagSize' => $bagInventory->getSize(),
                'bagUsed' => $bagInventory->getOccupiedSpace(),
                'bankSize' => $bankInventory->getSize(),
            ],
            'consumables' => $this->buildConsumables($locale),
            'materials' => $this->buildMaterials($locale),
            'equipment' => $this->buildEquipment($player, $locale),
            'materia' => $this->buildMateria($locale),
            'bank' => $this->buildBank($locale),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildConsumables(?string $locale): array
    {
        $grouped = [];
        foreach ($this->playerHelper->getBagInventory()->getItems() as $item) {
            $genericItem = $item->getGenericItem();
            if (!$genericItem->isObject()) {
                continue;
            }

            $slug = $genericItem->getSlug();
            if (!isset($grouped[$slug])) {
                $grouped[$slug] = [
                    'id' => $item->getId(),
                    'slug' => $slug,
                    'name' => $genericItem->getLocalizedName($locale),
                    'description' => $genericItem->getLocalizedDescription($locale),
                    'quantity' => 0,
                    'usable' => $this->itemHelper->isUsable($genericItem),
                    'bound' => $item->isBound(),
                ];
            }
            ++$grouped[$slug]['quantity'];
        }

        return array_values($grouped);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildMaterials(?string $locale): array
    {
        $grouped = [];
        foreach ($this->playerHelper->getBagInventory()->getItems() as $item) {
            if (!$item->isResource()) {
                continue;
            }

            $genericItem = $item->getGenericItem();
            $slug = $genericItem->getSlug();
            if (!isset($grouped[$slug])) {
                $grouped[$slug] = [
                    'id' => $item->getId(),
                    'slug' => $slug,
                    'name' => $genericItem->getLocalizedName($locale),
                    'description' => $genericItem->getLocalizedDescription($locale),
                    'rarity' => $genericItem->getRarity(),
                    'quantity' => 0,
                ];
            }
            ++$grouped[$slug]['quantity'];
        }

        return array_values($grouped);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEquipment(Player $player, ?string $locale): array
    {
        $bagInventory = $this->playerHelper->getBagInventory();

        $equipped = [];
        $totalProtection = 0;
        foreach (Item::GEAR_LOCATIONS as $location) {
            $playerItem = $this->gearHelper->getEquippedGearByLocation($location);
            $equipped[$location] = $playerItem !== null ? $this->serializePlayerItem($playerItem, $locale) : null;
            if ($playerItem !== null) {
                $totalProtection += $playerItem->getGenericItem()->getProtection();
            }
        }

        $availableGear = [];
        $availableTools = [];
        foreach ($bagInventory->getItems() as $item) {
            if ($item->getGenericItem()->isGear() && !$this->gearHelper->isEquipped($item)) {
                $entry = $this->serializePlayerItem($item, $locale);
                $entry['canEquip'] = $this->playerItemHelper->canBeEquipped($item);
                $availableGear[] = $entry;
            }
            if ($item->getGenericItem()->isTool() && !$this->gearHelper->isToolEquipped($item)) {
                $availableTools[] = $this->serializePlayerItem($item, $locale);
            }
        }

        $setBonuses = $this->equipmentSetResolver->getSetBonuses($player);
        $totalProtection += $setBonuses['protection'];

        $sets = [];
        foreach ($this->equipmentSetResolver->getActiveSets($player) as $entry) {
            $sets[] = [
                'slug' => $entry['set']->getSlug(),
                'name' => $entry['set']->getLocalizedName($locale),
                'equippedCount' => $entry['equippedCount'],
                'totalPieces' => $entry['totalPieces'],
                'activeBonuses' => array_map(
                    fn ($bonus) => ['requiredPieces' => $bonus->getRequiredPieces(), 'type' => $bonus->getBonusType(), 'value' => $bonus->getBonusValue()],
                    $entry['activeBonuses'],
                ),
                'inactiveBonuses' => array_map(
                    fn ($bonus) => ['requiredPieces' => $bonus->getRequiredPieces(), 'type' => $bonus->getBonusType(), 'value' => $bonus->getBonusValue()],
                    $entry['inactiveBonuses'],
                ),
            ];
        }

        $allToolSlots = array_unique(array_merge(
            $player->getUnlockedToolSlots(),
            $this->playerActionHelper->getUnlockedToolSlots(),
        ));

        $toolSlots = [];
        foreach ($allToolSlots as $toolType) {
            $equippedTool = $this->gearHelper->getEquippedToolByType($toolType);
            $toolSlots[$toolType] = [
                'label' => Item::TOOL_TYPE_LABELS[$toolType] ?? $toolType,
                'equipped' => $equippedTool !== null ? $this->serializePlayerItem($equippedTool, $locale) : null,
            ];
        }

        return [
            'equipped' => $equipped,
            'toolSlots' => $toolSlots,
            'availableGear' => $availableGear,
            'availableTools' => $availableTools,
            'sets' => $sets,
            'setBonuses' => $setBonuses,
            'stats' => $this->playerEffectiveStatsCalculator->getInventorySheetStats($player, $totalProtection),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildMateria(?string $locale): array
    {
        $grouped = [];
        foreach ($this->playerHelper->getMateriaInventory()->getItems() as $item) {
            if (!$item->isMateria()) {
                continue;
            }

            $genericItem = $item->getGenericItem();
            $key = $genericItem->getSlug() . '_' . $genericItem->getElement()->value . '_' . ($genericItem->getLevel() ?? 1);
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'id' => $item->getId(),
                    'slug' => $genericItem->getSlug(),
                    'name' => $genericItem->getLocalizedName($locale),
                    'level' => $genericItem->getLevel() ?? 1,
                    'element' => $genericItem->getElement()->value,
                    'rarity' => $genericItem->getRarity(),
                    'description' => $genericItem->getLocalizedDescription($locale),
                    'quantity' => 0,
                ];
            }
            ++$grouped[$key]['quantity'];
        }

        return array_values($grouped);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBank(?string $locale): array
    {
        $bankInventory = $this->playerHelper->getBankInventory();

        $grouped = [];
        foreach ($bankInventory->getItems() as $item) {
            $slug = $item->getGenericItem()->getSlug();
            if (!isset($grouped[$slug])) {
                $grouped[$slug] = $this->serializePlayerItem($item, $locale);
                $grouped[$slug]['quantity'] = 0;
            }
            ++$grouped[$slug]['quantity'];
        }

        return [
            'size' => $bankInventory->getSize(),
            'items' => array_values($grouped),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlayerItem(PlayerItem $item, ?string $locale): array
    {
        $genericItem = $item->getGenericItem();

        return [
            'id' => $item->getId(),
            'slug' => $genericItem->getSlug(),
            'name' => $genericItem->getLocalizedName($locale),
            'displayName' => $item->getDisplayName(),
            'description' => $genericItem->getLocalizedDescription($locale),
            'rarity' => $genericItem->getRarity(),
            'protection' => $genericItem->getProtection(),
            'value' => $genericItem->getValue(),
            'durability' => $item->getCurrentDurability(),
            'bound' => $item->isBound(),
        ];
    }
}
