<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'game_races')]
class Race
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'slug', type: 'string', length: 64, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'description', type: 'text')]
    private string $description;

    #[ORM\Column(name: 'sprite_sheet', type: 'string', length: 255, nullable: true)]
    private ?string $spriteSheet = null;

    #[ORM\Column(name: 'stat_modifiers', type: 'json')]
    private array $statModifiers = ['life' => 0, 'energy' => 0, 'speed' => 0, 'hit' => 0];

    #[ORM\Column(name: 'available_at_creation', type: 'boolean', options: ['default' => true])]
    private bool $availableAtCreation = true;

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

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSpriteSheet(): ?string
    {
        return $this->spriteSheet;
    }

    public function setSpriteSheet(?string $spriteSheet): self
    {
        $this->spriteSheet = $spriteSheet;

        return $this;
    }

    public function getStatModifiers(): array
    {
        return $this->statModifiers;
    }

    public function setStatModifiers(array $statModifiers): self
    {
        $this->statModifiers = $statModifiers;

        return $this;
    }

    public function getStatModifier(string $stat): int
    {
        return $this->statModifiers[$stat] ?? 0;
    }

    public function isAvailableAtCreation(): bool
    {
        return $this->availableAtCreation;
    }

    public function setAvailableAtCreation(bool $availableAtCreation): self
    {
        $this->availableAtCreation = $availableAtCreation;

        return $this;
    }
}
