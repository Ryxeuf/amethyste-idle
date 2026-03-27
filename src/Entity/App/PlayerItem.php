<?php

namespace App\Entity\App;

use App\Entity\Game\Item;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'player_item')]
#[ORM\Index(columns: ['inventory_id', 'item_id'], name: 'idx_player_item_inventory_item')]
#[ORM\Entity()]
class PlayerItem
{
    use TimestampableEntity;

    public const GEAR_HEAD = 0b1; // 1
    public const GEAR_NECK = 0b10; // 2
    public const GEAR_CHEST = 0b100; // 4
    public const GEAR_HAND = 0b1000; // 8
    public const GEAR_MAIN_WEAPON = 0b10000; // 16
    public const GEAR_SIDE_WEAPON = 0b100000; // 32
    public const GEAR_BELT = 0b1000000; // 64
    public const GEAR_LEG = 0b10000000; // 128
    public const GEAR_FOOT = 0b100000000; // 256
    public const GEAR_RING_1 = 0b1000000000; // 512
    public const GEAR_RING_2 = 0b10000000000; // 1024
    public const GEAR_SHOULDER = 0b100000000000; // 2048

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
        self::GEAR_SHOULDER,
    ];

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    /**
     * Objet dont l'item est la représentation.
     */
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id')]
    private Item $genericItem;

    /**
     * Slots que l'objet possede.
     *
     * @var Slot[]|ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: Slot::class, mappedBy: 'item')]
    private $slots;

    /**
     * Slot dans lequel est serti l'item (materia).
     */
    #[ORM\OneToOne(targetEntity: Slot::class, mappedBy: 'item_set')]
    private ?Slot $slotSet;

    /**
     * Inventaire du joueur dans lequel se trouve cet item.
     */
    #[ORM\ManyToOne(targetEntity: Inventory::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'inventory_id', referencedColumnName: 'id')]
    private ?Inventory $inventory;

    #[ORM\ManyToOne(targetEntity: GuildVault::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'guild_vault_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?GuildVault $guildVault = null;

    /**
     * Si l'item est généré à la mort d'un mob, il s'agit du mob sur lequel on loot cet objet.
     */
    #[ORM\ManyToOne(targetEntity: Mob::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'mob_id', referencedColumnName: 'id')]
    private ?Mob $mob;

    #[ORM\Column(name: 'gear', type: 'integer')]
    private int $gear = 0;

    /**
     * Le nombre de fois restantes que l'objet est utilisable
     * -1 Signifie qu'il est utilisable à l'infini.
     */
    #[ORM\Column(name: 'nb_usages', type: 'integer', options: ['default' => -1])]
    private int $nbUsages = -1;

    /**
     * Durabilité restante de l'outil (null si ce n'est pas un outil).
     */
    #[ORM\Column(name: 'current_durability', type: 'integer', nullable: true)]
    private ?int $currentDurability = null;

    #[ORM\Column(name: 'experience', type: 'integer', options: ['default' => 0])]
    private int $experience = 0;

    #[ORM\Column(name: 'bound_to_player_id', type: 'integer', nullable: true)]
    private ?int $boundToPlayerId = null;

    public function __construct()
    {
        $this->slots = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getGenericItem(): Item
    {
        return $this->genericItem;
    }

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

    public function getSlotSet(): ?Slot
    {
        return $this->slotSet;
    }

    public function setSlotSet(?Slot $slotSet): void
    {
        $this->slotSet = $slotSet;
    }

    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }

    public function setInventory(?Inventory $inventory): void
    {
        $this->inventory = $inventory;
    }

    public function getGuildVault(): ?GuildVault
    {
        return $this->guildVault;
    }

    public function setGuildVault(?GuildVault $guildVault): void
    {
        $this->guildVault = $guildVault;
    }

    public function getMob(): ?Mob
    {
        return $this->mob;
    }

    public function setMob(?Mob $mob): void
    {
        $this->mob = $mob;
    }

    public function getGear(): int
    {
        return $this->gear;
    }

    public function setGear(int $gear): void
    {
        $this->gear = $gear;
    }

    public function getNbUsages(): int
    {
        return $this->nbUsages;
    }

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

    public function getCurrentDurability(): ?int
    {
        return $this->currentDurability;
    }

    public function setCurrentDurability(?int $currentDurability): void
    {
        $this->currentDurability = $currentDurability;
    }

    public function isTool(): bool
    {
        return $this->getGenericItem()->isTool();
    }

    public function isResource(): bool
    {
        return $this->getGenericItem()->isResource();
    }

    /**
     * Réduit la durabilité de l'outil de $amount points.
     * Retourne true si l'outil est cassé (durabilité <= 0).
     */
    public function reduceDurability(int $amount = 1): bool
    {
        if ($this->currentDurability === null) {
            return false;
        }

        $this->currentDurability = max(0, $this->currentDurability - $amount);

        return $this->currentDurability <= 0;
    }

    public function getExperience(): int
    {
        return $this->experience;
    }

    public function setExperience(int $experience): void
    {
        $this->experience = $experience;
    }

    public function addExperience(int $amount): void
    {
        $this->experience += $amount;
    }

    public function getBoundToPlayerId(): ?int
    {
        return $this->boundToPlayerId;
    }

    public function setBoundToPlayerId(?int $boundToPlayerId): void
    {
        $this->boundToPlayerId = $boundToPlayerId;
    }

    public function isBound(): bool
    {
        return $this->boundToPlayerId !== null;
    }

    public function getMateriaLevel(): int
    {
        if ($this->experience < 100) {
            return 1;
        }
        if ($this->experience < 300) {
            return 2;
        }
        if ($this->experience < 600) {
            return 3;
        }
        if ($this->experience < 1000) {
            return 4;
        }

        return 5;
    }
}
