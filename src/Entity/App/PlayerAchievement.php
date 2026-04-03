<?php

namespace App\Entity\App;

use App\Entity\Game\Achievement;
use App\Repository\PlayerAchievementRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: PlayerAchievementRepository::class)]
#[ORM\Table(name: 'player_achievements')]
#[ORM\UniqueConstraint(name: 'player_achievement_unique', columns: ['player_id', 'achievement_id'])]
class PlayerAchievement
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'achievements')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Achievement::class)]
    #[ORM\JoinColumn(name: 'achievement_id', referencedColumnName: 'id')]
    private Achievement $achievement;

    #[ORM\Column(name: 'progress', type: 'integer', options: ['default' => 0])]
    private int $progress = 0;

    #[ORM\Column(name: 'completed_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column(name: 'featured', type: 'boolean', options: ['default' => false])]
    private bool $featured = false;

    public function getId(): int
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

    public function getAchievement(): Achievement
    {
        return $this->achievement;
    }

    public function setAchievement(Achievement $achievement): self
    {
        $this->achievement = $achievement;

        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;

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
        return null !== $this->completedAt;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): self
    {
        $this->featured = $featured;

        return $this;
    }
}
