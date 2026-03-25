<?php

namespace App\Entity\App;

use App\Entity\Game\Dungeon;
use App\Enum\DungeonDifficulty;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\DungeonRunRepository::class)]
#[ORM\Table(name: 'dungeon_run')]
#[ORM\Index(columns: ['player_id', 'dungeon_id'], name: 'idx_dungeon_run_player_dungeon')]
class DungeonRun
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Dungeon::class)]
    #[ORM\JoinColumn(name: 'dungeon_id', referencedColumnName: 'id', nullable: false)]
    private Dungeon $dungeon;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false)]
    private Player $player;

    #[ORM\Column(name: 'difficulty', type: 'string', length: 20, enumType: DungeonDifficulty::class)]
    private DungeonDifficulty $difficulty;

    #[ORM\Column(name: 'started_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(name: 'completed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    public function setDungeon(Dungeon $dungeon): self
    {
        $this->dungeon = $dungeon;

        return $this;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): self
    {
        $this->player = $player;

        return $this;
    }

    public function getDifficulty(): DungeonDifficulty
    {
        return $this->difficulty;
    }

    public function setDifficulty(DungeonDifficulty $difficulty): self
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    public function isInProgress(): bool
    {
        return $this->completedAt === null;
    }
}
