<?php

namespace App\Entity\App;

use App\Entity\Game\StatusEffect;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'fight_status_effect')]
class FightStatusEffect
{
    use TimestampableEntity;

    public const TARGET_TYPE_PLAYER = 'player';
    public const TARGET_TYPE_MOB = 'mob';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Fight::class)]
    #[ORM\JoinColumn(name: 'fight_id', referencedColumnName: 'id', nullable: false)]
    private Fight $fight;

    #[ORM\Column(name: 'target_type', type: 'string', length: 20)]
    private string $targetType;

    #[ORM\Column(name: 'target_id', type: 'integer')]
    private int $targetId;

    #[ORM\ManyToOne(targetEntity: StatusEffect::class)]
    #[ORM\JoinColumn(name: 'status_effect_id', referencedColumnName: 'id', nullable: false)]
    private StatusEffect $statusEffect;

    #[ORM\Column(name: 'remaining_turns', type: 'integer')]
    private int $remainingTurns;

    #[ORM\Column(name: 'applied_at', type: 'datetime')]
    private \DateTime $appliedAt;

    #[ORM\Column(name: 'last_tick_turn', type: 'integer', nullable: true)]
    private ?int $lastTickTurn = null;

    public function __construct()
    {
        $this->appliedAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFight(): Fight
    {
        return $this->fight;
    }

    public function setFight(Fight $fight): void
    {
        $this->fight = $fight;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function setTargetType(string $targetType): void
    {
        $this->targetType = $targetType;
    }

    public function getTargetId(): int
    {
        return $this->targetId;
    }

    public function setTargetId(int $targetId): void
    {
        $this->targetId = $targetId;
    }

    public function getStatusEffect(): StatusEffect
    {
        return $this->statusEffect;
    }

    public function setStatusEffect(StatusEffect $statusEffect): void
    {
        $this->statusEffect = $statusEffect;
    }

    public function getRemainingTurns(): int
    {
        return $this->remainingTurns;
    }

    public function setRemainingTurns(int $remainingTurns): void
    {
        $this->remainingTurns = $remainingTurns;
    }

    public function getAppliedAt(): \DateTime
    {
        return $this->appliedAt;
    }

    public function setAppliedAt(\DateTime $appliedAt): void
    {
        $this->appliedAt = $appliedAt;
    }

    public function getLastTickTurn(): ?int
    {
        return $this->lastTickTurn;
    }

    public function setLastTickTurn(?int $lastTickTurn): void
    {
        $this->lastTickTurn = $lastTickTurn;
    }

    public function isExpired(): bool
    {
        return $this->remainingTurns <= 0;
    }

    public function decrementTurn(): void
    {
        --$this->remainingTurns;
    }
}
