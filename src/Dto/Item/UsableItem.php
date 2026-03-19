<?php

namespace App\Dto\Item;

use App\Entity\App\PlayerItem as ItemEntity;

class UsableItem
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $type;

    /**
     * @var int
     */
    public $genericItemId;

    /**
     * @var string
     */
    public $genericItemSlug;

    /**
     * @var string
     */
    public $element;

    /**
     * @var ItemSpell
     */
    public $spell;

    public function __construct(ItemEntity $item)
    {
        $this->id = $item->getId();
        $this->name = $item->getGenericItem()->getName();
        $this->description = $item->getGenericItem()->getDescription();
        $this->element = $item->getGenericItem()->getElement()->value;
        $this->genericItemId = $item->getGenericItem()->getId();
        $this->genericItemSlug = $item->getGenericItem()->getSlug();
        $this->type = $item->getGenericItem()->getType();

        if ($item->getGenericItem()->getSpell()) {
            $this->spell = new ItemSpell($item->getGenericItem()->getSpell());
        }
    }
}
