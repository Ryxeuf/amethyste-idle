<?php

namespace App\Entity\App;

use App\Entity\Game\Item;
use App\Repository\PlayerResourceCatalogRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: PlayerResourceCatalogRepository::class)]
#[ORM\Table(name: 'player_resource_catalog')]
#[ORM\UniqueConstraint(name: 'uniq_player_resource_catalog', columns: ['player_id', 'item_id'])]
class PlayerResourceCatalog
{
    use TimestampableEntity;

    public const TIER_LOCATIONS = 5;
    public const TIER_RECIPES = 25;
    public const TIER_SPECIALIST = 50;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'resourceCatalogEntries')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Item $item;

    #[ORM\Column(name: 'collect_count', type: 'integer', options: ['default' => 0])]
    private int $collectCount = 0;

    #[ORM\Column(name: 'first_collected_at', type: 'datetime')]
    private \DateTimeInterface $firstCollectedAt;

    public function __construct(Player $player, Item $item, int $quantity = 1)
    {
        $this->player = $player;
        $this->item = $item;
        $this->collectCount = $quantity;
        $this->firstCollectedAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function getCollectCount(): int
    {
        return $this->collectCount;
    }

    public function incrementCollectCount(int $quantity = 1): void
    {
        $this->collectCount += $quantity;
    }

    public function getFirstCollectedAt(): \DateTimeInterface
    {
        return $this->firstCollectedAt;
    }

    public function hasLocationsRevealed(): bool
    {
        return $this->collectCount >= self::TIER_LOCATIONS;
    }

    public function hasRecipesRevealed(): bool
    {
        return $this->collectCount >= self::TIER_RECIPES;
    }

    public function hasSpecialistTitle(): bool
    {
        return $this->collectCount >= self::TIER_SPECIALIST;
    }

    public function getTier(): int
    {
        if ($this->collectCount >= self::TIER_SPECIALIST) {
            return 3;
        }
        if ($this->collectCount >= self::TIER_RECIPES) {
            return 2;
        }
        if ($this->collectCount >= self::TIER_LOCATIONS) {
            return 1;
        }

        return 0;
    }

    public function getNextTierThreshold(): ?int
    {
        if ($this->collectCount < self::TIER_LOCATIONS) {
            return self::TIER_LOCATIONS;
        }
        if ($this->collectCount < self::TIER_RECIPES) {
            return self::TIER_RECIPES;
        }
        if ($this->collectCount < self::TIER_SPECIALIST) {
            return self::TIER_SPECIALIST;
        }

        return null;
    }
}
