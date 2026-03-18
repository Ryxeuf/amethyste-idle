<?php

namespace App\Entity\App;

use App\Entity\Game\StatusEffect;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'player_status_effects')]
#[ORM\Index(columns: ['player_id'], name: 'IDX_player_status_effect_player')]
#[ORM\Index(columns: ['expires_at'], name: 'IDX_player_status_effect_expires')]
class PlayerStatusEffect
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'statusEffects')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false)]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: StatusEffect::class)]
    #[ORM\JoinColumn(name: 'status_effect_id', referencedColumnName: 'id', nullable: false)]
    private StatusEffect $statusEffect;

    #[ORM\Column(name: 'applied_at', type: 'datetime')]
    private \DateTime $appliedAt;

    #[ORM\Column(name: 'expires_at', type: 'datetime')]
    private \DateTime $expiresAt;

    public function __construct()
    {
        $this->appliedAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }

    public function getStatusEffect(): StatusEffect
    {
        return $this->statusEffect;
    }

    public function setStatusEffect(StatusEffect $statusEffect): void
    {
        $this->statusEffect = $statusEffect;
    }

    public function getAppliedAt(): \DateTime
    {
        return $this->appliedAt;
    }

    public function setAppliedAt(\DateTime $appliedAt): void
    {
        $this->appliedAt = $appliedAt;
    }

    public function getExpiresAt(): \DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTime();
    }

    public function getRemainingSeconds(): int
    {
        $now = new \DateTime();
        if ($this->expiresAt <= $now) {
            return 0;
        }

        return $this->expiresAt->getTimestamp() - $now->getTimestamp();
    }
}
