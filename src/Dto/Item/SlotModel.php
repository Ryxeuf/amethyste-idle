<?php

namespace App\Dto\Item;

use App\Entity\App\Slot as SlotEntity;
use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;

class SlotModel
{
    #[ApiProperty(identifier: true)]
    #[Groups(['item_get'])]
    public int $id;

    #[Groups(['item_get', 'player_gear'])]
    public ?SlotItemModel $itemSet = null;

    public function __construct(SlotEntity $slot)
    {
        $this->id = $slot->getId();
        if ($slot->getItemSet()) {
            $this->itemSet = new SlotItemModel($slot->getItemSet());
        }
    }

}