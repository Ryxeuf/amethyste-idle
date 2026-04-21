<?php

namespace App\Entity\Game;

use App\Enum\Element;
use App\Enum\ItemRarity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'game_items')]
class Item
{
    use TimestampableEntity;

    public const TYPE_STUFF = 'stuff';
    public const TYPE_GEAR_PIECE = 'gear';
    public const TYPE_MATERIA = 'materia';
    public const TYPE_RESOURCE = 'resource';
    public const TYPE_TOOL = 'tool';

    public const TOOL_TYPE_PICKAXE = 'pickaxe';
    public const TOOL_TYPE_SICKLE = 'sickle';
    public const TOOL_TYPE_FISHING_ROD = 'fishing_rod';
    public const TOOL_TYPE_SKINNING_KNIFE = 'skinning_knife';
    public const TOOL_TYPE_HAMMER = 'hammer';
    public const TOOL_TYPE_TANNING_KIT = 'tanning_kit';
    public const TOOL_TYPE_MORTAR = 'mortar';
    public const TOOL_TYPE_CHISEL = 'chisel';

    public const TOOL_GEAR_LOCATIONS = [
        self::TOOL_TYPE_PICKAXE => 'tool_pickaxe',
        self::TOOL_TYPE_SICKLE => 'tool_sickle',
        self::TOOL_TYPE_FISHING_ROD => 'tool_fishing_rod',
        self::TOOL_TYPE_SKINNING_KNIFE => 'tool_skinning_knife',
        self::TOOL_TYPE_HAMMER => 'tool_hammer',
        self::TOOL_TYPE_TANNING_KIT => 'tool_tanning_kit',
        self::TOOL_TYPE_MORTAR => 'tool_mortar',
        self::TOOL_TYPE_CHISEL => 'tool_chisel',
    ];

    public const CRAFT_TOOL_TYPES = [
        'forgeron' => self::TOOL_TYPE_HAMMER,
        'tanneur' => self::TOOL_TYPE_TANNING_KIT,
        'alchimiste' => self::TOOL_TYPE_MORTAR,
        'joaillier' => self::TOOL_TYPE_CHISEL,
    ];

    public const TOOL_TYPE_LABELS = [
        self::TOOL_TYPE_PICKAXE => 'une pioche',
        self::TOOL_TYPE_SICKLE => 'une faucille',
        self::TOOL_TYPE_FISHING_ROD => 'une canne à pêche',
        self::TOOL_TYPE_SKINNING_KNIFE => 'un couteau de dépeçage',
        self::TOOL_TYPE_HAMMER => 'un marteau de forge',
        self::TOOL_TYPE_TANNING_KIT => 'un kit de tannage',
        self::TOOL_TYPE_MORTAR => 'un mortier d\'alchimie',
        self::TOOL_TYPE_CHISEL => 'un burin de joaillier',
    ];

    public const TOOL_TIER_BRONZE = 1;
    public const TOOL_TIER_IRON = 2;
    public const TOOL_TIER_STEEL = 3;
    public const TOOL_TIER_MITHRIL = 4;

    public const GEAR_LOCATION_HEAD = 'head';
    public const GEAR_LOCATION_NECK = 'neck';
    public const GEAR_LOCATION_CHEST = 'chest';
    public const GEAR_LOCATION_HAND = 'hand';
    public const GEAR_LOCATION_FINGER = 'finger';
    public const GEAR_LOCATION_LEGS = 'legs';
    public const GEAR_LOCATION_FEET = 'feet';
    public const GEAR_LOCATION_MAIN_HAND = 'main_hand';
    public const GEAR_LOCATION_OFF_HAND = 'off_hand';
    public const GEAR_LOCATION_TWO_HAND = 'two_hand';
    public const GEAR_LOCATION_RANGED = 'ranged';
    public const GEAR_LOCATION_AMMO = 'ammo';

    public const GEAR_LOCATION_MAIN_WEAPON = 'main_weapon';
    public const GEAR_LOCATION_SIDE_WEAPON = 'side_weapon';
    public const GEAR_LOCATION_BELT = 'belt';
    public const GEAR_LOCATION_LEG = 'leg';
    public const GEAR_LOCATION_FOOT = 'foot';
    public const GEAR_LOCATION_RING_1 = 'ring_1';
    public const GEAR_LOCATION_RING_2 = 'ring_2';
    public const GEAR_LOCATION_SHOULDER = 'shoulder';

    public const GEAR_LOCATIONS = [
        self::GEAR_LOCATION_HEAD,
        self::GEAR_LOCATION_NECK,
        self::GEAR_LOCATION_CHEST,
        self::GEAR_LOCATION_HAND,
        self::GEAR_LOCATION_MAIN_WEAPON,
        self::GEAR_LOCATION_SIDE_WEAPON,
        self::GEAR_LOCATION_BELT,
        self::GEAR_LOCATION_LEG,
        self::GEAR_LOCATION_FOOT,
        self::GEAR_LOCATION_RING_1,
        self::GEAR_LOCATION_RING_2,
        self::GEAR_LOCATION_SHOULDER,
    ];

    public function __toString(): string
    {
        return $this->getName();
    }

    public function isObject(): bool
    {
        return $this->getType() === self::TYPE_STUFF;
    }

    public function isMateria(): bool
    {
        return $this->getType() === self::TYPE_MATERIA;
    }

    public function isGear(): bool
    {
        return $this->getType() === self::TYPE_GEAR_PIECE;
    }

    public function isResource(): bool
    {
        return $this->getType() === self::TYPE_RESOURCE;
    }

    public function isTool(): bool
    {
        return $this->getType() === self::TYPE_TOOL;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->requirements = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'name_translations', type: 'json', nullable: true)]
    private ?array $nameTranslations = null;

    #[ORM\Column(name: 'price', type: 'integer', nullable: true)]
    private $price;

    #[ORM\Column(name: 'description', type: 'text')]
    private $description;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'description_translations', type: 'json', nullable: true)]
    private ?array $descriptionTranslations = null;

    #[ORM\Column(name: 'protection', type: 'integer', nullable: true)]
    private $protection;

    #[ORM\Column(name: 'energy_cost', type: 'integer', nullable: true)]
    private $energyCost;

    #[ORM\Column(name: 'type', type: 'string', length: 50, options: ['default' => 'stuff'])]
    private $type = self::TYPE_STUFF;

    #[ORM\Column(name: 'space', type: 'integer')]
    private $space = 1;

    #[ORM\Column(name: 'element', type: 'string', length: 25, enumType: Element::class)]
    private Element $element = Element::None;

    #[ORM\Column(name: 'gear_location', type: 'string', nullable: true)]
    private $gearLocation;

    #[ORM\Column(name: 'slug', type: 'string', length: 255)]
    private $slug;

    #[ORM\Column(name: 'effect', type: 'text', nullable: true)]
    private $effect;

    #[ORM\ManyToOne(targetEntity: Spell::class)]
    #[ORM\JoinColumn(name: 'spell_id', referencedColumnName: 'id')]
    private $spell;

    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'items')]
    #[ORM\JoinTable(name: 'item_skill_requirement')]
    private $requirements;

    #[ORM\Column(name: 'level', type: 'integer', nullable: true)]
    private $level;

    #[ORM\Column(name: 'nb_usages', type: 'integer', options: ['default' => -1])]
    private $nbUsages = -1;

    #[ORM\ManyToOne(targetEntity: Domain::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'domain_id', referencedColumnName: 'id')]
    private $domain;

    #[ORM\Column(name: 'tool_type', type: 'string', length: 50, nullable: true)]
    private ?string $toolType = null;

    #[ORM\Column(name: 'tool_tier', type: 'integer', nullable: true)]
    private ?int $toolTier = null;

    #[ORM\Column(name: 'durability', type: 'integer', nullable: true)]
    private ?int $durability = null;

    #[ORM\Column(name: 'value', type: 'integer', nullable: true)]
    private ?int $value = null;

    #[ORM\Column(name: 'rarity', type: 'string', length: 50, nullable: true, enumType: ItemRarity::class)]
    private ?ItemRarity $rarity = null;

    #[ORM\Column(name: 'bound_to_player', type: 'boolean', options: ['default' => false])]
    private bool $boundToPlayer = false;

    #[ORM\Column(name: 'materia_slots', type: 'integer', options: ['default' => 0])]
    private int $materiaSlots = 0;

    #[ORM\Column(name: 'materia_slot_config', type: 'json', nullable: true)]
    private ?array $materiaSlotConfig = null;

    #[ORM\Column(name: 'is_cosmetic', type: 'boolean', options: ['default' => false])]
    private bool $isCosmetic = false;

    #[ORM\Column(name: 'avatar_sheet', type: 'string', length: 255, nullable: true)]
    private ?string $avatarSheet = null;

    #[ORM\ManyToOne(targetEntity: EquipmentSet::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'equipment_set_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?EquipmentSet $equipmentSet = null;

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name): Item
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the name translated for the requested locale, or fall back to the base `name` column.
     */
    public function getLocalizedName(?string $locale): string
    {
        if ($locale === null || $locale === '' || $this->nameTranslations === null) {
            return $this->name;
        }
        $translation = $this->nameTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function getNameTranslations(): array
    {
        return $this->nameTranslations ?? [];
    }

    /**
     * @param array<string, string>|null $translations
     */
    public function setNameTranslations(?array $translations): Item
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->nameTranslations = $normalized === [] ? null : $normalized;

        return $this;
    }

    /**
     * Set type.
     *
     * @param int $type
     */
    public function setType($type): Item
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set space.
     *
     * @param int $space
     */
    public function setSpace($space): Item
    {
        $this->space = $space;

        return $this;
    }

    /**
     * Get space.
     */
    public function getSpace(): int
    {
        return $this->space;
    }

    /**
     * Set protection.
     *
     * @param int $protection
     */
    public function setProtection($protection): Item
    {
        $this->protection = $protection;

        return $this;
    }

    /**
     * Get protection.
     */
    public function getProtection(): int
    {
        return $this->protection ?? 0;
    }

    /**
     * Set energyCost.
     *
     * @param int $energyCost
     */
    public function setEnergyCost($energyCost): Item
    {
        $this->energyCost = $energyCost;

        return $this;
    }

    /**
     * Get energyCost.
     */
    public function getEnergyCost(): ?int
    {
        return $this->energyCost;
    }

    public function setElement(Element $element): Item
    {
        $this->element = $element;

        return $this;
    }

    public function getElement(): Element
    {
        return $this->element;
    }

    /**
     * Set gearLocation.
     *
     * @param string|null $gearLocation
     */
    public function setGearLocation($gearLocation): Item
    {
        $this->gearLocation = $gearLocation;

        return $this;
    }

    /**
     * Get gearLocation.
     */
    public function getGearLocation(): ?string
    {
        return $this->gearLocation;
    }

    /**
     * Set spell.
     */
    public function setSpell(?Spell $spell = null): Item
    {
        $this->spell = $spell;

        return $this;
    }

    /**
     * Get spell.
     */
    public function getSpell(): ?Spell
    {
        return $this->spell;
    }

    /**
     * Set price.
     *
     * @param int $price
     */
    public function setPrice($price): Item
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price.
     */
    public function getPrice(): ?int
    {
        return $this->price;
    }

    /**
     * Add requirement.
     */
    public function addRequirement(Skill $requirement): Item
    {
        $this->requirements[] = $requirement;

        return $this;
    }

    /**
     * Remove requirement.
     */
    public function removeRequirement(Skill $requirement): void
    {
        $this->requirements->removeElement($requirement);
    }

    /**
     * Get requirements.
     *
     * @return Collection<Skill>
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * @param array<Skill> $requirements
     */
    public function setRequirements(array $requirements): void
    {
        $this->requirements = new ArrayCollection($requirements);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * Get the description translated for the requested locale, or fall back to the base `description` column.
     */
    public function getLocalizedDescription(?string $locale): string
    {
        if ($locale === null || $locale === '' || $this->descriptionTranslations === null) {
            return $this->description;
        }
        $translation = $this->descriptionTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->description;
    }

    /**
     * @return array<string, string>
     */
    public function getDescriptionTranslations(): array
    {
        return $this->descriptionTranslations ?? [];
    }

    /**
     * @param array<string, string>|null $translations
     */
    public function setDescriptionTranslations(?array $translations): Item
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->descriptionTranslations = $normalized === [] ? null : $normalized;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain = null): void
    {
        $this->domain = $domain;
    }

    public function getNbUsages(): int
    {
        return $this->nbUsages;
    }

    public function setNbUsages(int $nbUsages): void
    {
        $this->nbUsages = $nbUsages;
    }

    public function getEffect(): ?string
    {
        return $this->effect;
    }

    public function setEffect(string $effect): void
    {
        $this->effect = $effect;
    }

    public function getToolType(): ?string
    {
        return $this->toolType;
    }

    public function setToolType(?string $toolType): void
    {
        $this->toolType = $toolType;
    }

    public function getToolTier(): ?int
    {
        return $this->toolTier;
    }

    public function setToolTier(?int $toolTier): void
    {
        $this->toolTier = $toolTier;
    }

    public function getDurability(): ?int
    {
        return $this->durability;
    }

    public function setDurability(?int $durability): void
    {
        $this->durability = $durability;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(?int $value): void
    {
        $this->value = $value;
    }

    public function getRarity(): ?string
    {
        return $this->rarity?->value;
    }

    public function getRarityEnum(): ?ItemRarity
    {
        return $this->rarity;
    }

    public function setRarity(ItemRarity|string|null $rarity): void
    {
        if (\is_string($rarity)) {
            $rarity = ItemRarity::tryFrom($rarity);
        }
        $this->rarity = $rarity;
    }

    public function isBoundToPlayer(): bool
    {
        return $this->boundToPlayer;
    }

    public function setBoundToPlayer(bool $boundToPlayer): void
    {
        $this->boundToPlayer = $boundToPlayer;
    }

    public function getMateriaSlots(): int
    {
        return $this->materiaSlots;
    }

    public function setMateriaSlots(int $materiaSlots): void
    {
        $this->materiaSlots = $materiaSlots;
    }

    public function getMateriaSlotConfig(): array
    {
        return $this->materiaSlotConfig ?? [];
    }

    public function setMateriaSlotConfig(?array $config): void
    {
        $this->materiaSlotConfig = $config;

        if ($config !== null) {
            $this->materiaSlots = \count($config);
        }
    }

    public function isCosmetic(): bool
    {
        return $this->isCosmetic;
    }

    public function setIsCosmetic(bool $isCosmetic): void
    {
        $this->isCosmetic = $isCosmetic;
    }

    public function getEquipmentSet(): ?EquipmentSet
    {
        return $this->equipmentSet;
    }

    public function setEquipmentSet(?EquipmentSet $equipmentSet): void
    {
        $this->equipmentSet = $equipmentSet;
    }

    public function hasEquipmentSet(): bool
    {
        return $this->equipmentSet !== null;
    }

    public function getAvatarSheet(): ?string
    {
        return $this->avatarSheet;
    }

    public function setAvatarSheet(?string $avatarSheet): void
    {
        $this->avatarSheet = $avatarSheet;
    }
}
