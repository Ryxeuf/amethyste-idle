<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'game_craft_recipes')]
class CraftRecipe
{
    use TimestampableEntity;

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

    #[ORM\Column(name: 'description', type: 'text')]
    private string $description;

    /**
     * Profession requise : blacksmith, tanner, alchemist, jeweler.
     */
    #[ORM\Column(name: 'profession', type: 'string', length: 50)]
    private string $profession;

    /**
     * Ingrédients au format JSON : [{"item_slug": "iron-ore", "quantity": 2}, ...].
     */
    #[ORM\Column(name: 'ingredients', type: 'json')]
    private array $ingredients = [];

    /**
     * Slug de l'item produit.
     */
    #[ORM\Column(name: 'result_item_slug', type: 'string', length: 255)]
    private string $resultItemSlug;

    #[ORM\Column(name: 'result_quantity', type: 'integer', options: ['default' => 1])]
    private int $resultQuantity = 1;

    /**
     * Slug du skill requis pour cette recette.
     */
    #[ORM\Column(name: 'required_skill_slug', type: 'string', length: 255, nullable: true)]
    private ?string $requiredSkillSlug = null;

    /**
     * Niveau minimum d'XP de domaine requis.
     */
    #[ORM\Column(name: 'required_level', type: 'integer', options: ['default' => 1])]
    private int $requiredLevel = 1;

    /**
     * Temps de fabrication en secondes.
     */
    #[ORM\Column(name: 'craft_time', type: 'integer', options: ['default' => 5])]
    private int $craftTime = 5;

    /**
     * XP gagnée à la fabrication.
     */
    #[ORM\Column(name: 'experience_gain', type: 'integer', options: ['default' => 1])]
    private int $experienceGain = 1;

    /**
     * Si true, la recette doit être découverte par expérimentation.
     */
    #[ORM\Column(name: 'is_discoverable', type: 'boolean', options: ['default' => false])]
    private bool $isDiscoverable = false;

    /**
     * Si true, la recette est visible par défaut (ou a été découverte).
     */
    #[ORM\Column(name: 'is_discovered', type: 'boolean', options: ['default' => false])]
    private bool $isDiscovered = false;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getProfession(): string
    {
        return $this->profession;
    }

    public function setProfession(string $profession): void
    {
        $this->profession = $profession;
    }

    public function getIngredients(): array
    {
        return $this->ingredients;
    }

    public function setIngredients(array $ingredients): void
    {
        $this->ingredients = $ingredients;
    }

    public function getResultItemSlug(): string
    {
        return $this->resultItemSlug;
    }

    public function setResultItemSlug(string $resultItemSlug): void
    {
        $this->resultItemSlug = $resultItemSlug;
    }

    public function getResultQuantity(): int
    {
        return $this->resultQuantity;
    }

    public function setResultQuantity(int $resultQuantity): void
    {
        $this->resultQuantity = $resultQuantity;
    }

    public function getRequiredSkillSlug(): ?string
    {
        return $this->requiredSkillSlug;
    }

    public function setRequiredSkillSlug(?string $requiredSkillSlug): void
    {
        $this->requiredSkillSlug = $requiredSkillSlug;
    }

    public function getRequiredLevel(): int
    {
        return $this->requiredLevel;
    }

    public function setRequiredLevel(int $requiredLevel): void
    {
        $this->requiredLevel = $requiredLevel;
    }

    public function getCraftTime(): int
    {
        return $this->craftTime;
    }

    public function setCraftTime(int $craftTime): void
    {
        $this->craftTime = $craftTime;
    }

    public function getExperienceGain(): int
    {
        return $this->experienceGain;
    }

    public function setExperienceGain(int $experienceGain): void
    {
        $this->experienceGain = $experienceGain;
    }

    public function isDiscoverable(): bool
    {
        return $this->isDiscoverable;
    }

    public function setIsDiscoverable(bool $isDiscoverable): void
    {
        $this->isDiscoverable = $isDiscoverable;
    }

    public function isDiscovered(): bool
    {
        return $this->isDiscovered;
    }

    public function setIsDiscovered(bool $isDiscovered): void
    {
        $this->isDiscovered = $isDiscovered;
    }
}
