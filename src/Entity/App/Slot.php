<?php

namespace App\Entity\App;

use App\Enum\Element;
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

    #[ORM\Column(name: 'element', type: 'string', length: 25, nullable: true, enumType: Element::class)]
    private ?Element $element = null;

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
     * Slot lié pour la synergie materia (bonus si même élément).
     */
    #[ORM\OneToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'linked_slot_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?self $linkedSlot = null;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setElement(?Element $element): self
    {
        $this->element = $element;

        return $this;
    }

    public function getElement(): ?Element
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
     */
    public function getItemSet(): ?PlayerItem
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

    public function getLinkedSlot(): ?self
    {
        return $this->linkedSlot;
    }

    public function setLinkedSlot(?self $linkedSlot): self
    {
        $this->linkedSlot = $linkedSlot;

        return $this;
    }
}
