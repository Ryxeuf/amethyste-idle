<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'guild_challenge_progress')]
#[ORM\UniqueConstraint(name: 'uq_guild_challenge_progress', columns: ['guild_id', 'challenge_id'])]
class GuildChallengeProgress
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Guild::class)]
    #[ORM\JoinColumn(name: 'guild_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Guild $guild;

    #[ORM\ManyToOne(targetEntity: WeeklyChallenge::class)]
    #[ORM\JoinColumn(name: 'challenge_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private WeeklyChallenge $challenge;

    #[ORM\Column(name: 'progress', type: 'integer', options: ['default' => 0])]
    private int $progress = 0;

    #[ORM\Column(name: 'completed_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuild(): Guild
    {
        return $this->guild;
    }

    public function setGuild(Guild $guild): self
    {
        $this->guild = $guild;

        return $this;
    }

    public function getChallenge(): WeeklyChallenge
    {
        return $this->challenge;
    }

    public function setChallenge(WeeklyChallenge $challenge): self
    {
        $this->challenge = $challenge;

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

    public function incrementProgress(int $amount = 1): self
    {
        $this->progress += $amount;

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

    public function getPercentage(): float
    {
        $target = $this->challenge->getTarget();
        if ($target <= 0) {
            return 100.0;
        }

        return min(100.0, ($this->progress / $target) * 100);
    }
}
