<?php

namespace App\Entity\Game;

use App\Entity\App\Map;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'game_dungeons')]
class Dungeon
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'slug', type: 'string', length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'description', type: 'text')]
    private string $description;

    #[ORM\ManyToOne(targetEntity: Map::class)]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id', nullable: false)]
    private Map $map;

    #[ORM\Column(name: 'min_level', type: 'integer')]
    private int $minLevel;

    #[ORM\Column(name: 'max_players', type: 'integer', options: ['default' => 1])]
    private int $maxPlayers = 1;

    #[ORM\Column(name: 'icon', type: 'string', length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(name: 'loot_preview', type: 'json', nullable: true)]
    private ?array $lootPreview = null;

    #[ORM\Column(name: 'entry_requirements', type: 'json', nullable: true)]
    private ?array $entryRequirements = null;

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

    public function getMap(): Map
    {
        return $this->map;
    }

    public function setMap(Map $map): self
    {
        $this->map = $map;

        return $this;
    }

    public function getMinLevel(): int
    {
        return $this->minLevel;
    }

    public function setMinLevel(int $minLevel): self
    {
        $this->minLevel = $minLevel;

        return $this;
    }

    public function getMaxPlayers(): int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): self
    {
        $this->maxPlayers = $maxPlayers;

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

    public function getLootPreview(): ?array
    {
        return $this->lootPreview;
    }

    public function setLootPreview(?array $lootPreview): self
    {
        $this->lootPreview = $lootPreview;

        return $this;
    }

    public function getEntryRequirements(): ?array
    {
        return $this->entryRequirements;
    }

    public function setEntryRequirements(?array $entryRequirements): self
    {
        $this->entryRequirements = $entryRequirements;

        return $this;
    }
}
