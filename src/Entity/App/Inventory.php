<?php

namespace App\Entity\App;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'inventory')]
#[ORM\Index(columns: ['player_id'], name: 'idx_inventory_player')]
#[ORM\Entity(repositoryClass: \App\Repository\InventoryRepository::class)]
class Inventory
{
    use TimestampableEntity;

    public const TYPE_BAG = 1;
    public const TYPE_MATERIA = 2;
    public const TYPE_BANK = 3;

    public function __toString(): string
    {
        return 'Inventaire [' . $this->getType() . '] de ' . $this->getPlayer();
    }

    public function isMateria(): bool
    {
        return $this->getType() === self::TYPE_MATERIA;
    }

    public function isBag(): bool
    {
        return $this->getType() === self::TYPE_BAG;
    }

    public function isBank(): bool
    {
        return $this->getType() === self::TYPE_BANK;
    }

    public function getOccupiedSpace(): int
    {
        return count($this->getItems());
    }

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'size', type: 'integer')]
    private $size;

    #[ORM\Column(name: 'type', type: 'integer', options: ['default' => 1])]
    private $type = self::TYPE_BAG;

    #[ORM\Column(name: 'gold', type: 'integer', options: ['default' => 0])]
    private $gold = 0;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'inventories')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id')]
    private $player;

    #[ORM\OneToMany(targetEntity: PlayerItem::class, mappedBy: 'inventory')]
    private $items;

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
     * Set size.
     *
     * @param int $size
     *
     * @return Inventory
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set player.
     *
     * @return Inventory
     */
    public function setPlayer(?Player $player = null)
    {
        $this->player = $player;

        return $this;
    }

    /**
     * Get player.
     *
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * Set gold.
     *
     * @param int $gold
     *
     * @return Inventory
     */
    public function setGold($gold)
    {
        $this->gold = $gold;

        return $this;
    }

    public function addGold(int $gold)
    {
        $this->gold += $gold;
    }

    /**
     * Get gold.
     *
     * @return int
     */
    public function getGold()
    {
        return $this->gold;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return Inventory
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * Add item.
     *
     * @return Inventory
     */
    public function addItem(PlayerItem $item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Remove item.
     */
    public function removeItem(PlayerItem $item)
    {
        $this->items->removeElement($item);
    }

    /**
     * Get items.
     *
     * @return \Doctrine\Common\Collections\Collection|PlayerItem[]
     */
    public function getItems()
    {
        return $this->items;
    }
}
