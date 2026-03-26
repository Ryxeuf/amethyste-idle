<?php

namespace App\Entity\Game;

use App\Enum\Element;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'game_enchantment_definitions')]
class EnchantmentDefinition
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'slug', type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'element', type: 'string', length: 25, enumType: Element::class, options: ['default' => 'none'])]
    private Element $element = Element::None;

    /** @var array<string, float|int> Bonus de stats appliques (ex: {"damage": 5, "defense": 3}) */
    #[ORM\Column(name: 'stat_bonuses', type: 'json')]
    private array $statBonuses = [];

    /** Duree en secondes de l'enchantement une fois applique */
    #[ORM\Column(name: 'duration', type: 'integer')]
    private int $duration;

    /** @var array<array{slug: string, quantity: int}> Ingredients necessaires */
    #[ORM\Column(name: 'ingredients', type: 'json')]
    private array $ingredients = [];

    /** Niveau alchimiste requis */
    #[ORM\Column(name: 'required_level', type: 'integer', options: ['default' => 1])]
    private int $requiredLevel = 1;

    /** Cout en gils */
    #[ORM\Column(name: 'cost', type: 'integer', options: ['default' => 0])]
    private int $cost = 0;

    #[ORM\Column(name: 'icon', type: 'string', length: 100, nullable: true)]
    private ?string $icon = null;

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getElement(): Element
    {
        return $this->element;
    }

    public function setElement(Element $element): void
    {
        $this->element = $element;
    }

    public function getStatBonuses(): array
    {
        return $this->statBonuses;
    }

    public function setStatBonuses(array $statBonuses): void
    {
        $this->statBonuses = $statBonuses;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    public function getIngredients(): array
    {
        return $this->ingredients;
    }

    public function setIngredients(array $ingredients): void
    {
        $this->ingredients = $ingredients;
    }

    public function getRequiredLevel(): int
    {
        return $this->requiredLevel;
    }

    public function setRequiredLevel(int $requiredLevel): void
    {
        $this->requiredLevel = $requiredLevel;
    }

    public function getCost(): int
    {
        return $this->cost;
    }

    public function setCost(int $cost): void
    {
        $this->cost = $cost;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }
}
