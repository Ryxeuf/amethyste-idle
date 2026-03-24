<?php

namespace App\Entity\App;

use App\Entity\Game\Quest;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'player_daily_quest')]
#[ORM\UniqueConstraint(name: 'player_daily_quest_unique', columns: ['player_id', 'quest_id', 'date'])]
class PlayerDailyQuest
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false)]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Quest::class)]
    #[ORM\JoinColumn(name: 'quest_id', referencedColumnName: 'id', nullable: false)]
    private Quest $quest;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $date;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $tracking = [];

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getQuest(): Quest
    {
        return $this->quest;
    }

    public function setQuest(Quest $quest): self
    {
        $this->quest = $quest;

        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getTracking(): array
    {
        return $this->tracking;
    }

    public function setTracking(array $tracking): self
    {
        $this->tracking = $tracking;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }
}
