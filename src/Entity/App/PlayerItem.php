<?php

namespace App\Entity\App;

use App\Entity\Game\Item;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'player_item')]
#[ORM\Entity(repositoryClass: 'App\Repository\App\PlayerItemRepository')]
class PlayerItem
{
    use TimestampableEntity;

    public const GEAR_HEAD        = 0b1; // 1
    public const GEAR_NECK        = 0b10; // 2
    public const GEAR_CHEST       = 0b100; // 4
    public const GEAR_HAND        = 0b1000; // 8
    public const GEAR_MAIN_WEAPON = 0b10000; // 16
    public const GEAR_SIDE_WEAPON = 0b100000; // 32
    public const GEAR_BELT        = 0b1000000; // 64
    public const GEAR_LEG         = 0b10000000; // 128
    public const GEAR_FOOT        = 0b100000000; // 256
    public const GEAR_RING_1      = 0b1000000000; // 512
    public const GEAR_RING_2      = 0b10000000000; // 1024
    public const GEAR_SHOULDER    = 0b100000000000; // 2048

    public const GEARS = [
        self::GEAR_HEAD,
        self::GEAR_NECK,
        self::GEAR_CHEST,
        self::GEAR_HAND,
        self::GEAR_MAIN_WEAPON,
        self::GEAR_SIDE_WEAPON,
        self::GEAR_BELT,
        self::GEAR_LEG,
        self::GEAR_FOOT,
        self::GEAR_RING_1,
        self::GEAR_RING_2,
        self::GEAR_SHOULDER
    ];

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    /**
     * Objet dont l'item est la représentation
     */
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id')]
    private Item $genericItem;

    /**
     * Slots que l'objet possede
     *
     * @var Slot[]|ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: Slot::class, mappedBy: 'item')]
    private $slots;

    /**
     * Slot dans lequel est serti l'item (materia)
     */
    #[ORM\OneToOne(targetEntity: Slot::class, mappedBy: 'item_set')]
    private ?Slot $slotSet;

    /**
     * Inventaire du joueur dans lequel se trouve cet item
     */
    #[ORM\ManyToOne(targetEntity: Inventory::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'inventory_id', referencedColumnName: 'id')]
    private ?Inventory $inventory;

    /**
     * Si l'item est généré à la mort d'un mob, il s'agit du mob sur lequel on loot cet objet
     */
    #[ORM\ManyToOne(targetEntity: Mob::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'mob_id', referencedColumnName: 'id')]
    private ?Mob $mob;

    #[ORM\Column(name: 'gear', type: 'integer')]
    private int $gear = 0;

    /**
     * Le nombre de fois restantes que l'objet est utilisable
     * -1 Signifie qu'il est utilisable à l'infini
     */
    #[ORM\Column(name: 'nb_usages', type: 'integer', options: ['default' => -1])]
    private int $nbUsages = -1;

    public function __construct()
    {
        $this->slots = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return Item
     */
    public function getGenericItem(): Item
    {
        return $this->genericItem;
    }

    /**
     * @param Item $genericItem
     */
    public function setGenericItem(Item $genericItem): void
    {
        $this->genericItem = $genericItem;
    }

    /**
     * @return Slot[]|ArrayCollection
     */
    public function getSlots()
    {
        return $this->slots;
    }

    /**
     * @param Slot[]|ArrayCollection $slots
     */
    public function setSlots($slots): void
    {
        $this->slots = $slots;
    }

    /**
     * @return Slot|null
     */
    public function getSlotSet(): ?Slot
    {
        return $this->slotSet;
    }

    /**
     * @param Slot|null $slotSet
     */
    public function setSlotSet(?Slot $slotSet): void
    {
        $this->slotSet = $slotSet;
    }

    /**
     * @return Inventory|null
     */
    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }

    /**
     * @param Inventory|null $inventory
     */
    public function setInventory(?Inventory $inventory): void
    {
        $this->inventory = $inventory;
    }

    /**
     * @return Mob|null
     */
    public function getMob(): ?Mob
    {
        return $this->mob;
    }

    /**
     * @param Mob|null $mob
     */
    public function setMob(?Mob $mob): void
    {
        $this->mob = $mob;
    }

    /**
     * @return int
     */
    public function getGear(): int
    {
        return $this->gear;
    }

    /**
     * @param int $gear
     */
    public function setGear(int $gear): void
    {
        $this->gear = $gear;
    }

    /**
     * @return int
     */
    public function getNbUsages(): int
    {
        return $this->nbUsages;
    }

    /**
     * @param int $nbUsages
     */
    public function setNbUsages(int $nbUsages): void
    {
        $this->nbUsages = $nbUsages;
    }

    public function isMateria(): bool
    {
        return $this->getGenericItem()->isMateria();
    }
    public function isGear(): bool
    {
        return $this->getGenericItem()->isGear();
    }
    public function removeGear(): void
    {
        $this->gear = 0;
    }
}
