<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'game_achievements')]
class Achievement
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'slug', type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(name: 'description', type: 'text')]
    private string $description;

    #[ORM\Column(name: 'category', type: 'string', length: 50)]
    private string $category;

    #[ORM\Column(name: 'criteria', type: 'json')]
    private array $criteria = [];

    #[ORM\Column(name: 'reward', type: 'json', nullable: true)]
    private ?array $reward = [];

    #[ORM\Column(name: 'icon', type: 'string', length: 255, nullable: true)]
    private ?string $icon = null;

    public function getId(): int
    {
        return $this->id;
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getCriteria(): array
    {
        return $this->criteria;
    }

    public function setCriteria(array $criteria): self
    {
        $this->criteria = $criteria;

        return $this;
    }

    public function getReward(): ?array
    {
        return $this->reward;
    }

    public function setReward(?array $reward): self
    {
        $this->reward = $reward;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getCriteriaType(): string
    {
        return $this->criteria['type'] ?? '';
    }

    public function getCriteriaCount(): int
    {
        return $this->criteria['count'] ?? 0;
    }

    public function getCriteriaMonsterSlug(): ?string
    {
        return $this->criteria['monster_slug'] ?? null;
    }
}
