<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'slot')]
#[ORM\Entity()]
class Slot
{
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'element', type: 'string', length: 25, nullable: true)]
    private $element;

    /**
     * Objet équipé dans le slot.
     */
    #[ORM\OneToOne(targetEntity: PlayerItem::class, inversedBy: 'slotSet')]
    #[ORM\JoinColumn(name: 'item_set_id', referencedColumnName: 'id')]
    private $item_set;

    /**
     * Equipement sur lequel est placé le slot.
     */
    #[ORM\ManyToOne(targetEntity: PlayerItem::class, inversedBy: 'slots')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id')]
    private $item;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set element.
     *
     * @param string $element
     */
    public function setElement($element): self
    {
        $this->element = $element;

        return $this;
    }

    /**
     * Get element.
     */
    public function getElement(): string
    {
        return $this->element;
    }

    /**
     * Set itemSet.
     */
    public function setItemSet(?PlayerItem $itemSet = null): self
    {
        $this->item_set = $itemSet;

        return $this;
    }

    /**
     * Get itemSet.
     *
     * @return PlayerItem
     */
    public function getItemSet()
    {
        return $this->item_set;
    }

    /**
     * Set item.
     */
    public function setItem(?PlayerItem $item = null): self
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get item.
     */
    public function getItem(): PlayerItem
    {
        return $this->item;
    }
}
