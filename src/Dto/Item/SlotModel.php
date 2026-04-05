<?php

namespace App\Dto\Item;

use App\Entity\App\Slot as SlotEntity;

class SlotModel
{
    public int $id;

    public ?SlotItemModel $itemSet = null;

    public function __construct(SlotEntity $slot)
    {
        $this->id = $slot->getId();
        $itemSet = $slot->getItemSet();
        $this->itemSet = $itemSet !== null ? new SlotItemModel($itemSet) : null;
    }
}
