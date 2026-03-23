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
        return
               $item->getGear() & PlayerItem::GEAR_HEAD
            || $item->getGear() & PlayerItem::GEAR_NECK
            || $item->getGear() & PlayerItem::GEAR_CHEST
            || $item->getGear() & PlayerItem::GEAR_HAND
            || $item->getGear() & PlayerItem::GEAR_MAIN_WEAPON
            || $item->getGear() & PlayerItem::GEAR_SIDE_WEAPON
            || $item->getGear() & PlayerItem::GEAR_BELT
            || $item->getGear() & PlayerItem::GEAR_LEG
            || $item->getGear() & PlayerItem::GEAR_FOOT
            || $item->getGear() & PlayerItem::GEAR_RING_1
            || $item->getGear() & PlayerItem::GEAR_RING_2
            || $item->getGear() & PlayerItem::GEAR_SHOULDER;
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
            default => null,
        };
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
