<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'game_recipes')]
class Recipe
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(length: 50)]
    private string $craft;

    #[ORM\Column]
    private int $requiredLevel = 1;

    #[ORM\Column(type: 'json')]
    private array $ingredients = [];

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Item $result;

    #[ORM\Column]
    private int $resultQuantity = 1;

    #[ORM\Column]
    private int $craftingTime = 5;

    #[ORM\Column]
    private int $xpReward = 10;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $quality = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getCraft(): string
    {
        return $this->craft;
    }

    public function setCraft(string $craft): self
    {
        $this->craft = $craft;

        return $this;
    }

    public function getRequiredLevel(): int
    {
        return $this->requiredLevel;
    }

    public function setRequiredLevel(int $requiredLevel): self
    {
        $this->requiredLevel = $requiredLevel;

        return $this;
    }

    public function getIngredients(): array
    {
        return $this->ingredients;
    }

    public function setIngredients(array $ingredients): self
    {
        $this->ingredients = $ingredients;

        return $this;
    }

    public function getResult(): Item
    {
        return $this->result;
    }

    public function setResult(Item $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getResultQuantity(): int
    {
        return $this->resultQuantity;
    }

    public function setResultQuantity(int $resultQuantity): self
    {
        $this->resultQuantity = $resultQuantity;

        return $this;
    }

    public function getCraftingTime(): int
    {
        return $this->craftingTime;
    }

    public function setCraftingTime(int $craftingTime): self
    {
        $this->craftingTime = $craftingTime;

        return $this;
    }

    public function getXpReward(): int
    {
        return $this->xpReward;
    }

    public function setXpReward(int $xpReward): self
    {
        $this->xpReward = $xpReward;

        return $this;
    }

    public function getQuality(): ?string
    {
        return $this->quality;
    }

    public function setQuality(?string $quality): self
    {
        $this->quality = $quality;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
