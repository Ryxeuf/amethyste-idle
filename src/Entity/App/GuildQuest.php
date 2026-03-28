<?php

namespace App\Entity\App;

use App\Enum\GuildQuestType;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'guild_quest')]
#[ORM\Index(name: 'idx_guild_quest_guild', columns: ['guild_id'])]
#[ORM\Index(name: 'idx_guild_quest_expires', columns: ['expires_at'])]
class GuildQuest
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Guild::class)]
    #[ORM\JoinColumn(name: 'guild_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Guild $guild;

    #[ORM\Column(name: 'type', type: 'string', length: 20, enumType: GuildQuestType::class)]
    private GuildQuestType $type;

    #[ORM\Column(name: 'target', type: 'string', length: 100)]
    private string $target;

    #[ORM\Column(name: 'target_label', type: 'string', length: 150)]
    private string $targetLabel;

    #[ORM\Column(name: 'progress', type: 'integer', options: ['default' => 0])]
    private int $progress = 0;

    #[ORM\Column(name: 'goal', type: 'integer')]
    private int $goal;

    #[ORM\Column(name: 'gils_reward', type: 'integer')]
    private int $gilsReward;

    #[ORM\Column(name: 'points_reward', type: 'integer')]
    private int $pointsReward;

    #[ORM\Column(name: 'expires_at', type: 'datetime')]
    private \DateTimeInterface $expiresAt;

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

    public function getType(): GuildQuestType
    {
        return $this->type;
    }

    public function setType(GuildQuestType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getTargetLabel(): string
    {
        return $this->targetLabel;
    }

    public function setTargetLabel(string $targetLabel): self
    {
        $this->targetLabel = $targetLabel;

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

    public function addProgress(int $amount): self
    {
        $this->progress += $amount;

        return $this;
    }

    public function getGoal(): int
    {
        return $this->goal;
    }

    public function setGoal(int $goal): self
    {
        $this->goal = $goal;

        return $this;
    }

    public function getGilsReward(): int
    {
        return $this->gilsReward;
    }

    public function setGilsReward(int $gilsReward): self
    {
        $this->gilsReward = $gilsReward;

        return $this;
    }

    public function getPointsReward(): int
    {
        return $this->pointsReward;
    }

    public function setPointsReward(int $pointsReward): self
    {
        $this->pointsReward = $pointsReward;

        return $this;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

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

    public function isExpired(): bool
    {
        return !$this->isCompleted() && $this->expiresAt < new \DateTime();
    }

    public function isActive(): bool
    {
        return !$this->isCompleted() && !$this->isExpired();
    }

    public function getProgressPercent(): float
    {
        if ($this->goal <= 0) {
            return 100.0;
        }

        return min(100.0, round(($this->progress / $this->goal) * 100, 1));
    }
}
