<?php

namespace App\Entity\App;

use App\Entity\Game\Item;
use App\Enum\Purity;
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

    // Tool slots (craft & gathering)
    public const GEAR_TOOL_PICKAXE = 0b1000000000000; // 4096
    public const GEAR_TOOL_SICKLE = 0b10000000000000; // 8192
    public const GEAR_TOOL_FISHING_ROD = 0b100000000000000; // 16384
    public const GEAR_TOOL_SKINNING_KNIFE = 0b1000000000000000; // 32768
    public const GEAR_TOOL_HAMMER = 0b10000000000000000; // 65536
    public const GEAR_TOOL_TANNING_KIT = 0b100000000000000000; // 131072
    public const GEAR_TOOL_MORTAR = 0b1000000000000000000; // 262144
    public const GEAR_TOOL_CHISEL = 0b10000000000000000000; // 524288
    // OBJ-05 : la hache avait un type d'outil (DOM-05) mais aucun bit — elle
    // ne pouvait pas etre equipee, donc le bucheronnage n'avait pas d'outil.
    public const GEAR_TOOL_AXE = 0b100000000000000000000; // 1048576
    // OBJ-06 : les outils du cuisinier, du charpentier et du tailleur.
    public const GEAR_TOOL_COOKPOT = 0b1000000000000000000000; // 2097152
    public const GEAR_TOOL_PLANE = 0b10000000000000000000000; // 4194304
    public const GEAR_TOOL_NEEDLE = 0b100000000000000000000000; // 8388608

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

    public const TOOL_GEARS = [
        self::GEAR_TOOL_PICKAXE,
        self::GEAR_TOOL_SICKLE,
        self::GEAR_TOOL_FISHING_ROD,
        self::GEAR_TOOL_SKINNING_KNIFE,
        self::GEAR_TOOL_HAMMER,
        self::GEAR_TOOL_TANNING_KIT,
        self::GEAR_TOOL_MORTAR,
        self::GEAR_TOOL_CHISEL,
        self::GEAR_TOOL_AXE,
        self::GEAR_TOOL_COOKPOT,
        self::GEAR_TOOL_PLANE,
        self::GEAR_TOOL_NEEDLE,
    ];

    public const TOOL_TYPE_TO_GEAR = [
        Item::TOOL_TYPE_PICKAXE => self::GEAR_TOOL_PICKAXE,
        Item::TOOL_TYPE_SICKLE => self::GEAR_TOOL_SICKLE,
        Item::TOOL_TYPE_FISHING_ROD => self::GEAR_TOOL_FISHING_ROD,
        Item::TOOL_TYPE_SKINNING_KNIFE => self::GEAR_TOOL_SKINNING_KNIFE,
        Item::TOOL_TYPE_HAMMER => self::GEAR_TOOL_HAMMER,
        Item::TOOL_TYPE_TANNING_KIT => self::GEAR_TOOL_TANNING_KIT,
        Item::TOOL_TYPE_MORTAR => self::GEAR_TOOL_MORTAR,
        Item::TOOL_TYPE_CHISEL => self::GEAR_TOOL_CHISEL,
        Item::TOOL_TYPE_AXE => self::GEAR_TOOL_AXE,
        Item::TOOL_TYPE_COOKPOT => self::GEAR_TOOL_COOKPOT,
        Item::TOOL_TYPE_PLANE => self::GEAR_TOOL_PLANE,
        Item::TOOL_TYPE_NEEDLE => self::GEAR_TOOL_NEEDLE,
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
    private ?Slot $slotSet = null;

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
     * Commande de craft detenant cet objet en escrow (ECO-05).
     *
     * Un materiau confie a une commande n'est dans **aucun** inventaire : il
     * n'appartient plus au commanditaire tant que la commande vit, et pas encore
     * a l'artisan. Meme mecanique que le depot a l'hotel des ventes, avec une
     * relation de collection puisqu'une commande porte plusieurs materiaux.
     */
    #[ORM\ManyToOne(targetEntity: CraftOrder::class, inversedBy: 'materials')]
    #[ORM\JoinColumn(name: 'craft_order_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?CraftOrder $craftOrder = null;

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

    /**
     * Qualite obtenue a la fabrication (ECO-20).
     *
     * `QualityCalculator` la calculait depuis toujours, `CraftingManager::craft()`
     * la placait dans son message de retour, et **rien ne la conservait** : le
     * joueur lisait « Qualite : Rare » une fois, puis l'objet devenait
     * indiscernable d'un autre. Nulle pour tout ce qui ne sort pas d'un etabli.
     */
    #[ORM\Column(name: 'craft_quality', type: 'string', length: 20, nullable: true)]
    private ?string $craftQuality = null;

    #[ORM\Column(name: 'custom_name', type: 'string', length: 100, nullable: true)]
    private ?string $customName = null;

    /**
     * Bande de purete du lot (ECO-21).
     *
     * **`null` est le cas normal**, et de tres loin : seule la ligne du cristal
     * — amethyste, minerais, gemmes — porte une bande. Herbes, poissons, cuirs
     * et bois restent fongibles, parce qu'un plancher T1 qui demanderait a un
     * debutant de comparer des lots avant sa premiere epee serait un mur, pas
     * un plancher (ECO-02).
     *
     * Le perimetre ne se lit pas ici : il se declare dans `config/game/purity.yaml`
     * et se decide dans `PurityScope`. Un lot hors perimetre porte `null`, et
     * c'est le seul endroit ou cette regle s'ecrit.
     */
    #[ORM\Column(name: 'purity', type: 'string', length: 20, nullable: true, enumType: Purity::class)]
    private ?Purity $purity = null;

    /**
     * Contrefacon des Ruelles (FAC-07).
     *
     * « Une contrefacon marche neuf fois et vous trahit a la dixieme »
     * (GAME_WORLD § 12.4). Le flag ne circule que par le marche gris et le
     * butin — jamais entre joueurs : le HV la refuse, l'echoppe la refuse,
     * le coffre de guilde la refuse. Un joueur ne trompe jamais un joueur.
     */
    #[ORM\Column(name: 'counterfeit', type: 'boolean', options: ['default' => false])]
    private bool $counterfeit = false;

    /**
     * Le compteur cache de la trahison : tire a la creation (8-12), decremente
     * a chaque lancement, et **jamais montre au joueur** — ni en Twig, ni en
     * JSON. A zero, le sort echoue, le contrecoup frappe, la materia se brise.
     */
    #[ORM\Column(name: 'counterfeit_charges', type: 'integer', nullable: true)]
    private ?int $counterfeitCharges = null;

    /**
     * L'etat identifie : le faussaire connait son œuvre, l'œil du faussaire
     * (Honore) voit celle des autres. Non identifiee, la contrefacon est
     * indiscernable — c'est sa definition.
     */
    #[ORM\Column(name: 'counterfeit_identified', type: 'boolean', options: ['default' => false])]
    private bool $counterfeitIdentified = false;

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

    public function getCraftOrder(): ?CraftOrder
    {
        return $this->craftOrder;
    }

    public function setCraftOrder(?CraftOrder $craftOrder): self
    {
        $this->craftOrder = $craftOrder;

        return $this;
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

    public function getCraftQuality(): ?string
    {
        return $this->craftQuality;
    }

    public function setCraftQuality(?string $craftQuality): void
    {
        $this->craftQuality = $craftQuality;
    }

    public function getPurity(): ?Purity
    {
        return $this->purity;
    }

    public function setPurity(?Purity $purity): void
    {
        $this->purity = $purity;
    }

    public function isCounterfeit(): bool
    {
        return $this->counterfeit;
    }

    public function setCounterfeit(bool $counterfeit): self
    {
        $this->counterfeit = $counterfeit;

        return $this;
    }

    public function getCounterfeitCharges(): ?int
    {
        return $this->counterfeitCharges;
    }

    public function setCounterfeitCharges(?int $charges): self
    {
        $this->counterfeitCharges = $charges;

        return $this;
    }

    public function isCounterfeitIdentified(): bool
    {
        return $this->counterfeitIdentified;
    }

    public function setCounterfeitIdentified(bool $identified): self
    {
        $this->counterfeitIdentified = $identified;

        return $this;
    }

    /**
     * Un objet est echangeable (vendable a l'HV / a un PNJ, cessible) tant qu'il
     * n'est ni lie au joueur ni actuellement equipe. Materialise la notion de
     * « plancher T1 echangeable » de l'onboarding (NAR-04) : les recompenses de
     * l'arc intro restent non liees, donc echangeables.
     */
    public function isExchangeable(): bool
    {
        return !$this->isBound() && $this->getGear() === 0;
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

    public function getCustomName(): ?string
    {
        return $this->customName;
    }

    public function setCustomName(?string $customName): void
    {
        $this->customName = $customName;
    }

    public function getDisplayName(): string
    {
        return $this->customName ?? $this->genericItem->getName();
    }
}
