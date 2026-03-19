<?php

namespace App\Dto\Item;

use App\Dto\Skill\Requirement;
use App\Dto\Spell\SpellModel;
use App\Entity\App\PlayerItem as ItemEntity;
use App\Entity\Game\Item;

class ItemModel
{
    public int $id;

    public string $name;

    public string $description;

    public string $type;

    public int $genericItemId;

    public string $genericItemSlug;

    public ?int $level;

    public string $element;

    public ?SpellModel $spell = null;

    public array $slots = [];

    public int $nbSlots;

    public bool $isGear = false;

    public bool $isUsable = false;

    /**
     * @var Requirement[]
     */
    public array $requirements = [];

    public ?string $domain = null;

    public function __construct(ItemEntity $item)
    {
        $this->id = $item->getId();
        $this->name = $item->getGenericItem()->getName();
        $this->description = $item->getGenericItem()->getDescription();
        $this->element = $item->getGenericItem()->getElement()->value;
        $this->nbSlots = $item->getSlots()->count();
        $this->genericItemId = $item->getGenericItem()->getId();
        $this->genericItemSlug = $item->getGenericItem()->getSlug();
        $this->type = $item->getGenericItem()->getType();
        $this->level = $item->getGenericItem()->getLevel();
        $this->isGear = Item::TYPE_GEAR_PIECE === $item->getGenericItem()->getType();
        $this->isUsable = $item->getGenericItem()->getEffect() !== null;
        $this->domain = $item->getGenericItem()->getDomain()?->getTitle();

        if ($item->getGenericItem()->getSpell()) {
            $this->spell = new SpellModel($item->getGenericItem()->getSpell());
        }
    }
}
