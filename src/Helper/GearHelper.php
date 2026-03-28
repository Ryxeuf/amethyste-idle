<?php

namespace App\Helper;

use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Enum\Element;

class GearHelper
{
    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    /**
     * Calcule le bonus de dégâts élémentaires (%) accordé par l'équipement porté.
     * Chaque pièce d'équipement dont l'élément correspond ajoute son bonus (typiquement +10%).
     */
    public function getEquippedElementalDamageBonus(Element $element): float
    {
        if ($element === Element::None) {
            return 0.0;
        }

        $bonus = 0.0;
        $inventory = $this->playerHelper->getInventory();

        foreach ($inventory->getItems() as $playerItem) {
            if (!$this->isEquipped($playerItem)) {
                continue;
            }

            $genericItem = $playerItem->getGenericItem();
            if ($genericItem->getElement() !== $element) {
                continue;
            }

            $effect = $genericItem->getEffect();
            if ($effect === null) {
                continue;
            }

            $decoded = json_decode($effect, true);
            if (($decoded['action'] ?? null) === 'elemental_damage_boost' && isset($decoded['amount'])) {
                $bonus += (float) $decoded['amount'] / 100.0;
            }
        }

        return $bonus;
    }

    public function isEquipped(PlayerItem $item): bool
    {
        if ($item->getGear() === 0) {
            return false;
        }

        foreach (PlayerItem::GEARS as $gear) {
            if ($item->getGear() & $gear) {
                return true;
            }
        }

        foreach (PlayerItem::TOOL_GEARS as $gear) {
            if ($item->getGear() & $gear) {
                return true;
            }
        }

        return false;
    }

    public function isToolEquipped(PlayerItem $item): bool
    {
        if ($item->getGear() === 0) {
            return false;
        }

        foreach (PlayerItem::TOOL_GEARS as $gear) {
            if ($item->getGear() & $gear) {
                return true;
            }
        }

        return false;
    }

    public function getEquippedGearByLocation(string $location): ?PlayerItem
    {
        return $this->getEquippedItem($this->getPlayerItemGearByLocation($location));
    }

    public function getWeaponGear(): ?PlayerItem
    {
        return $this->getEquippedItem(PlayerItem::GEAR_MAIN_WEAPON);
    }

    public function getHeadGear(): ?PlayerItem
    {
        return $this->getEquippedItem(PlayerItem::GEAR_HEAD);
    }

    public function getChestGear(): ?PlayerItem
    {
        return $this->getEquippedItem(PlayerItem::GEAR_CHEST);
    }

    public function getFootGear(): ?PlayerItem
    {
        return $this->getEquippedItem(PlayerItem::GEAR_FOOT);
    }

    public function getPlayerItemGearByLocation(string $location): ?int
    {
        return match ($location) {
            Item::GEAR_LOCATION_HEAD => PlayerItem::GEAR_HEAD,
            Item::GEAR_LOCATION_NECK => PlayerItem::GEAR_NECK,
            Item::GEAR_LOCATION_CHEST => PlayerItem::GEAR_CHEST,
            Item::GEAR_LOCATION_HAND => PlayerItem::GEAR_HAND,
            Item::GEAR_LOCATION_MAIN_WEAPON, Item::GEAR_LOCATION_MAIN_HAND => PlayerItem::GEAR_MAIN_WEAPON,
            Item::GEAR_LOCATION_SIDE_WEAPON, Item::GEAR_LOCATION_OFF_HAND => PlayerItem::GEAR_SIDE_WEAPON,
            Item::GEAR_LOCATION_BELT => PlayerItem::GEAR_BELT,
            Item::GEAR_LOCATION_LEG, Item::GEAR_LOCATION_LEGS => PlayerItem::GEAR_LEG,
            Item::GEAR_LOCATION_FOOT, Item::GEAR_LOCATION_FEET => PlayerItem::GEAR_FOOT,
            Item::GEAR_LOCATION_RING_1, Item::GEAR_LOCATION_FINGER => PlayerItem::GEAR_RING_1,
            Item::GEAR_LOCATION_RING_2 => PlayerItem::GEAR_RING_2,
            Item::GEAR_LOCATION_SHOULDER => PlayerItem::GEAR_SHOULDER,
            'tool_pickaxe' => PlayerItem::GEAR_TOOL_PICKAXE,
            'tool_sickle' => PlayerItem::GEAR_TOOL_SICKLE,
            'tool_fishing_rod' => PlayerItem::GEAR_TOOL_FISHING_ROD,
            'tool_skinning_knife' => PlayerItem::GEAR_TOOL_SKINNING_KNIFE,
            'tool_hammer' => PlayerItem::GEAR_TOOL_HAMMER,
            'tool_tanning_kit' => PlayerItem::GEAR_TOOL_TANNING_KIT,
            'tool_mortar' => PlayerItem::GEAR_TOOL_MORTAR,
            'tool_chisel' => PlayerItem::GEAR_TOOL_CHISEL,
            default => null,
        };
    }

    /**
     * Retourne l'outil équipé pour un type d'outil donné (pickaxe, hammer, etc.).
     */
    public function getEquippedToolByType(string $toolType): ?PlayerItem
    {
        $gearBit = PlayerItem::TOOL_TYPE_TO_GEAR[$toolType] ?? null;
        if ($gearBit === null) {
            return null;
        }

        return $this->getEquippedItem($gearBit);
    }

    protected function getEquippedItem(int $gear): ?PlayerItem
    {
        foreach ($this->playerHelper->getInventory()->getItems() as $item) {
            if ($item->getGear() & $gear) {
                return $item;
            }
        }

        return null;
    }
}
