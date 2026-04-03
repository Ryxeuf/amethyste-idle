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
        $this->itemSet = new SlotItemModel($slot->getItemSet());
    }
}
