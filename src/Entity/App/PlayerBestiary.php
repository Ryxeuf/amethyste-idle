<?php

namespace App\Entity\App;

use App\Entity\Game\Monster;
use App\Repository\PlayerBestiaryRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: PlayerBestiaryRepository::class)]
#[ORM\Table(name: 'player_bestiary')]
#[ORM\UniqueConstraint(name: 'uniq_player_bestiary', columns: ['player_id', 'monster_id'])]
class PlayerBestiary
{
    use TimestampableEntity;

    public const TIER_WEAKNESSES = 10;
    public const TIER_LOOT_TABLE = 50;
    public const TIER_HUNTER_TITLE = 100;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'bestiaryEntries')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Monster::class)]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Monster $monster;

    #[ORM\Column(name: 'kill_count', type: 'integer', options: ['default' => 0])]
    private int $killCount = 0;

    #[ORM\Column(name: 'first_encountered_at', type: 'datetime')]
    private \DateTimeInterface $firstEncounteredAt;

    #[ORM\Column(name: 'first_killed_at', type: 'datetime')]
    private \DateTimeInterface $firstKilledAt;

    public function __construct(Player $player, Monster $monster)
    {
        $this->player = $player;
        $this->monster = $monster;
        $this->killCount = 1;
        $this->firstEncounteredAt = new \DateTime();
        $this->firstKilledAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getMonster(): Monster
    {
        return $this->monster;
    }

    public function getKillCount(): int
    {
        return $this->killCount;
    }

    public function incrementKillCount(): void
    {
        ++$this->killCount;
    }

    public function getFirstEncounteredAt(): \DateTimeInterface
    {
        return $this->firstEncounteredAt;
    }

    public function getFirstKilledAt(): \DateTimeInterface
    {
        return $this->firstKilledAt;
    }

    public function hasWeaknessesRevealed(): bool
    {
        return $this->killCount >= self::TIER_WEAKNESSES;
    }

    public function hasLootTableRevealed(): bool
    {
        return $this->killCount >= self::TIER_LOOT_TABLE;
    }

    public function hasHunterTitle(): bool
    {
        return $this->killCount >= self::TIER_HUNTER_TITLE;
    }

    public function getTier(): int
    {
        if ($this->killCount >= self::TIER_HUNTER_TITLE) {
            return 3;
        }
        if ($this->killCount >= self::TIER_LOOT_TABLE) {
            return 2;
        }
        if ($this->killCount >= self::TIER_WEAKNESSES) {
            return 1;
        }

        return 0;
    }

    public function getNextTierThreshold(): ?int
    {
        if ($this->killCount < self::TIER_WEAKNESSES) {
            return self::TIER_WEAKNESSES;
        }
        if ($this->killCount < self::TIER_LOOT_TABLE) {
            return self::TIER_LOOT_TABLE;
        }
        if ($this->killCount < self::TIER_HUNTER_TITLE) {
            return self::TIER_HUNTER_TITLE;
        }

        return null;
    }
}
