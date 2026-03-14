<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'game_spells')]
class Spell
{
    use TimestampableEntity;

    public const ELEMENT_NONE = 'none';
    public const ELEMENT_FIRE = 'fire';
    public const ELEMENT_WATER = 'water';
    public const ELEMENT_EARTH = 'earth';
    public const ELEMENT_AIR = 'air';
    public const ELEMENT_LIGHT = 'light';
    public const ELEMENT_DARK = 'dark';

    public const ELEMENTS = [
        self::ELEMENT_NONE,
        self::ELEMENT_FIRE,
        self::ELEMENT_WATER,
        self::ELEMENT_EARTH,
        self::ELEMENT_AIR,
        self::ELEMENT_LIGHT,
        self::ELEMENT_DARK,
    ];

    public function __toString(): string
    {
        return $this->getName();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'slug', type: 'string', length: 255, unique: true)]
    private $slug;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    #[ORM\Column(name: 'description', type: 'text')]
    private $description;

    #[ORM\Column(name: 'damage', type: 'integer', nullable: true)]
    private $damage;

    #[ORM\Column(name: 'heal', type: 'integer', nullable: true)]
    private $heal;

    #[ORM\Column(name: 'hit', type: 'integer', options: ['default' => 75])]
    private $hit = 75;

    #[ORM\Column(name: 'critical', type: 'integer', options: ['default' => 5])]
    private $critical = 5;

    #[ORM\Column(name: 'spell_range', type: 'integer', nullable: true)]
    private $spellRange;

    #[ORM\Column(name: 'element', type: 'string', length: 25)]
    private $element = self::ELEMENT_NONE;

    #[ORM\Column(name: 'cooldown', type: 'integer', nullable: true)]
    private ?int $cooldown = null;

    #[ORM\Column(name: 'energy_cost', type: 'integer', options: ['default' => 0])]
    private int $energyCost = 0;

    #[ORM\Column(name: 'status_effect_slug', type: 'string', length: 255, nullable: true)]
    private ?string $statusEffectSlug = null;

    #[ORM\Column(name: 'aoe_targets', type: 'integer', options: ['default' => 1])]
    private int $aoeTargets = 1;

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Set name.
     */
    public function setName(string $name): Spell
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
     * Set description.
     */
    public function setDescription(string $description): Spell
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set damage.
     */
    public function setDamage(?int $damage): Spell
    {
        $this->damage = $damage;

        return $this;
    }

    /**
     * Get damage.
     */
    public function getDamage(): ?int
    {
        return $this->damage;
    }

    /**
     * Set heal.
     */
    public function setHeal(?int $heal): Spell
    {
        $this->heal = $heal;

        return $this;
    }

    public function getHeal(): ?int
    {
        return $this->heal;
    }

    /**
     * Set spellRange.
     */
    public function setSpellRange(?int $spellRange): Spell
    {
        $this->spellRange = $spellRange;

        return $this;
    }

    public function getSpellRange(): ?int
    {
        return $this->spellRange;
    }

    /**
     * Set element.
     */
    public function setElement(string $element): Spell
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

    public function getHit(): int
    {
        return $this->hit;
    }

    public function setHit(int $hit): void
    {
        $this->hit = $hit;
    }

    public function getCritical(): int
    {
        return $this->critical;
    }

    public function setCritical(int $critical): void
    {
        $this->critical = $critical;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getCooldown(): ?int
    {
        return $this->cooldown;
    }

    public function setCooldown(?int $cooldown): void
    {
        $this->cooldown = $cooldown;
    }

    public function getEnergyCost(): int
    {
        return $this->energyCost;
    }

    public function setEnergyCost(int $energyCost): void
    {
        $this->energyCost = $energyCost;
    }

    public function getStatusEffectSlug(): ?string
    {
        return $this->statusEffectSlug;
    }

    public function setStatusEffectSlug(?string $statusEffectSlug): void
    {
        $this->statusEffectSlug = $statusEffectSlug;
    }

    public function getAoeTargets(): int
    {
        return $this->aoeTargets;
    }

    public function setAoeTargets(int $aoeTargets): void
    {
        $this->aoeTargets = $aoeTargets;
    }

    public function isAoe(): bool
    {
        return $this->aoeTargets === 0 || $this->aoeTargets > 1;
    }
}
