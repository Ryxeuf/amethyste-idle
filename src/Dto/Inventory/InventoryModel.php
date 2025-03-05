<?php

namespace App\Dto\Inventory;

use App\Dto\Item\ItemModel;
use App\Entity\App\Inventory as InventoryEntity;

class InventoryModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $gold;

    /**
     * @var int
     */
    public $size;

    /**
     * @var ItemModel[]
     */
    public $items;

    public function __construct(InventoryEntity $inventory)
    {
        $this->id = $inventory->getId();
        $this->gold = $inventory->getGold();
        $this->size = $inventory->getSize();
    }
}
