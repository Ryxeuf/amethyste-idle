<?php

namespace App\Entity\Game;

use App\Enum\Element;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'game_status_effects')]
class StatusEffect
{
    use TimestampableEntity;

    public const TYPE_POISON = 'poison';
    public const TYPE_PARALYSIS = 'paralysis';
    public const TYPE_BURN = 'burn';
    public const TYPE_FREEZE = 'freeze';
    public const TYPE_SILENCE = 'silence';
    public const TYPE_REGENERATION = 'regeneration';
    public const TYPE_SHIELD = 'shield';
    public const TYPE_BERSERK = 'berserk';

    public const TYPES = [
        self::TYPE_POISON,
        self::TYPE_PARALYSIS,
        self::TYPE_BURN,
        self::TYPE_FREEZE,
        self::TYPE_SILENCE,
        self::TYPE_REGENERATION,
        self::TYPE_SHIELD,
        self::TYPE_BERSERK,
    ];

    public const CATEGORY_BUFF = 'buff';
    public const CATEGORY_DEBUFF = 'debuff';
    public const CATEGORY_HOT = 'hot';
    public const CATEGORY_DOT = 'dot';

    public const CATEGORIES = [
        self::CATEGORY_BUFF,
        self::CATEGORY_DEBUFF,
        self::CATEGORY_HOT,
        self::CATEGORY_DOT,
    ];

    public function __toString(): string
    {
        return $this->name;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'slug', type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'type', type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(name: 'duration', type: 'integer')]
    private int $duration;

    #[ORM\Column(name: 'damage_per_turn', type: 'integer', nullable: true)]
    private ?int $damagePerTurn = null;

    #[ORM\Column(name: 'heal_per_turn', type: 'integer', nullable: true)]
    private ?int $healPerTurn = null;

    #[ORM\Column(name: 'stat_modifier', type: 'json', nullable: true)]
    private ?array $statModifier = null;

    #[ORM\Column(name: 'chance', type: 'integer', options: ['default' => 100])]
    private int $chance = 100;

    #[ORM\Column(name: 'element', type: 'string', length: 25, enumType: Element::class, options: ['default' => 'none'])]
    private Element $element = Element::None;

    #[ORM\Column(name: 'icon', type: 'string', length: 100, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(name: 'category', type: 'string', length: 20, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(name: 'frequency', type: 'integer', nullable: true)]
    private ?int $frequency = null;

    #[ORM\Column(name: 'real_time_duration', type: 'integer', nullable: true)]
    private ?int $realTimeDuration = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    public function getDamagePerTurn(): ?int
    {
        return $this->damagePerTurn;
    }

    public function setDamagePerTurn(?int $damagePerTurn): void
    {
        $this->damagePerTurn = $damagePerTurn;
    }

    public function getHealPerTurn(): ?int
    {
        return $this->healPerTurn;
    }

    public function setHealPerTurn(?int $healPerTurn): void
    {
        $this->healPerTurn = $healPerTurn;
    }

    public function getStatModifier(): ?array
    {
        return $this->statModifier;
    }

    public function setStatModifier(?array $statModifier): void
    {
        $this->statModifier = $statModifier;
    }

    public function getChance(): int
    {
        return $this->chance;
    }

    public function setChance(int $chance): void
    {
        $this->chance = $chance;
    }

    public function getElement(): Element
    {
        return $this->element;
    }

    public function setElement(Element $element): void
    {
        $this->element = $element;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getFrequency(): ?int
    {
        return $this->frequency;
    }

    public function setFrequency(?int $frequency): void
    {
        $this->frequency = $frequency;
    }

    public function getRealTimeDuration(): ?int
    {
        return $this->realTimeDuration;
    }

    public function setRealTimeDuration(?int $realTimeDuration): void
    {
        $this->realTimeDuration = $realTimeDuration;
    }

    public function isBuff(): bool
    {
        return $this->category === self::CATEGORY_BUFF;
    }

    public function isDebuff(): bool
    {
        return $this->category === self::CATEGORY_DEBUFF;
    }

    public function isHot(): bool
    {
        return $this->category === self::CATEGORY_HOT;
    }

    public function isDot(): bool
    {
        return $this->category === self::CATEGORY_DOT;
    }

    public function hasPersistentDuration(): bool
    {
        return $this->realTimeDuration !== null && $this->realTimeDuration > 0;
    }

    public function isDamaging(): bool
    {
        return $this->damagePerTurn !== null && $this->damagePerTurn > 0;
    }

    public function isHealing(): bool
    {
        return $this->healPerTurn !== null && $this->healPerTurn > 0;
    }

    public function hasStatModifier(): bool
    {
        return $this->statModifier !== null && count($this->statModifier) > 0;
    }
}
