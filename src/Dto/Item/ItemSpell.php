<?php

namespace App\Dto\Item;

use App\Entity\Game\Spell as SpellEntity;

class ItemSpell
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $damage;

    /**
     * @var int
     */
    public $heal;

    /**
     * @var string
     */
    public $element;

    /**
     * @var int
     */
    public $spellRange;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    public function __construct(SpellEntity $spell)
    {
        $this->id = $spell->getId();
        $this->damage = $spell->getDamage();
        $this->heal = $spell->getHeal();
        $this->element = $spell->getElement();
        $this->spellRange = $spell->getSpellRange();
        $this->name = $spell->getName();
        $this->description = $spell->getDescription();
    }
}
