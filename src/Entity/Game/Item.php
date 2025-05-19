<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: "game_items")]
class Item
{
    use TimestampableEntity;

    public const TYPE_STUFF      = 'stuff';
    public const TYPE_GEAR_PIECE = 'gear';
    public const TYPE_MATERIA    = 'materia';

    public const GEAR_LOCATION_HEAD        = 'head';
    public const GEAR_LOCATION_NECK        = 'neck';
    public const GEAR_LOCATION_CHEST       = 'chest';
    public const GEAR_LOCATION_HAND        = 'hand';
    public const GEAR_LOCATION_FINGER      = 'finger';
    public const GEAR_LOCATION_LEGS        = 'legs';
    public const GEAR_LOCATION_FEET        = 'feet';
    public const GEAR_LOCATION_MAIN_HAND   = 'main_hand';
    public const GEAR_LOCATION_OFF_HAND    = 'off_hand';
    public const GEAR_LOCATION_TWO_HAND    = 'two_hand';
    public const GEAR_LOCATION_RANGED      = 'ranged';
    public const GEAR_LOCATION_AMMO        = 'ammo';
    
    public const GEAR_LOCATION_MAIN_WEAPON = 'main_weapon';
    public const GEAR_LOCATION_SIDE_WEAPON = 'side_weapon';
    public const GEAR_LOCATION_BELT        = 'belt';
    public const GEAR_LOCATION_LEG         = 'leg';
    public const GEAR_LOCATION_FOOT        = 'foot';
    public const GEAR_LOCATION_RING_1      = 'ring_1';
    public const GEAR_LOCATION_RING_2      = 'ring_2';
    public const GEAR_LOCATION_SHOULDER    = 'shoulder';

    public const ELEMENT_NONE = 'none';
    public const ELEMENT_FIRE = 'fire';
    public const ELEMENT_WATER = 'water';
    public const ELEMENT_EARTH = 'earth';
    public const ELEMENT_AIR = 'air';
    public const ELEMENT_LIGHT = 'light';
    public const ELEMENT_DARK = 'dark';

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
        self::GEAR_LOCATION_SHOULDER
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

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->requirements = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 255)]
    private $name;

    #[ORM\Column(name: "price", type: "integer", nullable: true)]
    private $price;

    #[ORM\Column(name: "description", type: "text")]
    private $description;

    #[ORM\Column(name: "protection", type: "integer", nullable: true)]
    private $protection;

    #[ORM\Column(name: "energy_cost", type: "integer", nullable: true)]
    private $energyCost;

    #[ORM\Column(name: "type", type: "string", length: 50, options: ["default" => "stuff"])]
    private $type = self::TYPE_STUFF;

    #[ORM\Column(name: "space", type: "integer")]
    private $space = 1;

    #[ORM\Column(name: "element", type: "string", length: 25)]
    private $element = self::ELEMENT_NONE;

    #[ORM\Column(name: "gear_location", type: "string", nullable: true)]
    private $gearLocation;

    #[ORM\Column(name: "slug", type: "string", length: 255)]
    private $slug;

    #[ORM\Column(name: "effect", type: "text", nullable: true)]
    private $effect;

    #[ORM\ManyToOne(targetEntity: Spell::class)]
    #[ORM\JoinColumn(name: "spell_id", referencedColumnName: "id")]
    private $spell;

    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: "items")]
    #[ORM\JoinTable(name: "item_skill_requirement")]
    /**  */
    private $requirements;

    #[ORM\Column(name: "level", type: "integer", nullable: true)]
    private $level;

    #[ORM\Column(name: "nb_usages", type: "integer", options: ["default" => -1])]
    private $nbUsages = -1;

    #[ORM\ManyToOne(targetEntity: Domain::class, inversedBy: "items")]
    #[ORM\JoinColumn(name: "domain_id", referencedColumnName: "id")]
    private $domain;

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Item
     */
    public function setName($name): Item
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return Item
     */
    public function setType($type): Item
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set space
     *
     * @param integer $space
     *
     * @return Item
     */
    public function setSpace($space): Item
    {
        $this->space = $space;

        return $this;
    }

    /**
     * Get space
     *
     * @return integer
     */
    public function getSpace(): int
    {
        return $this->space;
    }

    /**
     * Set protection
     *
     * @param integer $protection
     *
     * @return Item
     */
    public function setProtection($protection): Item
    {
        $this->protection = $protection;

        return $this;
    }

    /**
     * Get protection
     *
     * @return integer
     */
    public function getProtection(): int
    {
        return $this->protection ?? 0;
    }

    /**
     * Set energyCost
     *
     * @param integer $energyCost
     *
     * @return Item
     */
    public function setEnergyCost($energyCost): Item
    {
        $this->energyCost = $energyCost;

        return $this;
    }

    /**
     * Get energyCost
     *
     * @return integer
     */
    public function getEnergyCost(): int
    {
        return $this->energyCost;
    }

    /**
     * Set element
     *
     * @param string $element
     *
     * @return Item
     */
    public function setElement(string $element): Item
    {
        $this->element = $element;

        return $this;
    }

    /**
     * Get element
     */
    public function getElement(): string
    {
        return $this->element;
    }

    /**
     * Set gearLocation
     *
     * @param string|null $gearLocation
     *
     * @return Item
     */
    public function setGearLocation($gearLocation): Item
    {
        $this->gearLocation = $gearLocation;

        return $this;
    }

    /**
     * Get gearLocation
     */
    public function getGearLocation(): ?string
    {
        return $this->gearLocation;
    }

    /**
     * Set spell
     *
     * @param Spell $spell
     *
     * @return Item
     */
    public function setSpell(Spell $spell = null): Item
    {
        $this->spell = $spell;

        return $this;
    }

    /**
     * Get spell
     *
     * @return Spell
     */
    public function getSpell(): ?Spell
    {
        return $this->spell;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return Item
     */
    public function setPrice($price): Item
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * Add requirement
     *
     * @param Skill $requirement
     *
     * @return Item
     */
    public function addRequirement(Skill $requirement): Item
    {
        $this->requirements[] = $requirement;

        return $this;
    }

    /**
     * Remove requirement
     *
     * @param Skill $requirement
     */
    public function removeRequirement(Skill $requirement): void
    {
        $this->requirements->removeElement($requirement);
    }

    /**
     * Get requirements
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

    /**
     * @return string
     */
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
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    /**
     * @return Domain
     */
    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    /**
     * @param Domain $domain
     */
    public function setDomain(?Domain $domain = null): void
    {
        $this->domain = $domain;
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

    public function getEffect(): ?string
    {
        return $this->effect;
    }

    public function setEffect(string $effect): void
    {
        $this->effect = $effect;
    }
}
