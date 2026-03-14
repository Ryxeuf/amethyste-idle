<?php

namespace App\Helper;

use App\Entity\App\PlayerItem;

class PlayerItemHelper
{
    public function __construct(private readonly PlayerHelper $playerHelper, private readonly InventoryHelper $inventoryHelper)
    {
    }

    /**
     * Return if a player can equip an item he has.
     */
    public function canBeEquipped(PlayerItem $item): bool
    {
        if ($item->getGenericItem()->getRequirements()->count() === 0) {
            $playerMeetRequirements = true;
        } else {
            $intersect = array_intersect($item->getGenericItem()->getRequirements()->toArray(), $this->playerHelper->getPlayer()->getSkills()->toArray());
            $playerMeetRequirements = count($intersect) === $item->getGenericItem()->getRequirements()->count();
        }

        return $this->inventoryHelper->hasItem($item) && $playerMeetRequirements;
    }
}
