<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'fight_log')]
#[ORM\Index(columns: ['fight_id', 'turn'], name: 'idx_fight_log_fight_turn')]
class FightLog
{
    public const ACTOR_PLAYER = 'player';
    public const ACTOR_MOB = 'mob';
    public const ACTOR_SYSTEM = 'system';

    public const TYPE_ATTACK = 'attack';
    public const TYPE_SPELL = 'spell';
    public const TYPE_ITEM = 'item';
    public const TYPE_DAMAGE = 'damage';
    public const TYPE_HEAL = 'heal';
    public const TYPE_DEATH = 'death';
    public const TYPE_FLEE = 'flee';
    public const TYPE_FLEE_FAIL = 'flee_fail';
    public const TYPE_STATUS_APPLY = 'status_apply';
    public const TYPE_STATUS_TICK = 'status_tick';
    public const TYPE_MISS = 'miss';
    public const TYPE_CRITICAL = 'critical';
    public const TYPE_SYNERGY = 'synergy';
    public const TYPE_RESIST = 'resist';
    public const TYPE_SHIELD = 'shield';
    public const TYPE_IMMOBILIZED = 'immobilized';
    public const TYPE_FIGHT_START = 'fight_start';
    public const TYPE_VICTORY = 'victory';
    public const TYPE_DEFEAT = 'defeat';
    public const TYPE_SUMMON = 'summon';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Fight::class)]
    #[ORM\JoinColumn(name: 'fight_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Fight $fight;

    #[ORM\Column(type: 'integer')]
    private int $turn;

    #[ORM\Column(type: 'string', length: 20)]
    private string $actorType;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $actorId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $actorName;

    #[ORM\Column(type: 'string', length: 30)]
    private string $type;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFight(): Fight
    {
        return $this->fight;
    }

    public function setFight(Fight $fight): self
    {
        $this->fight = $fight;

        return $this;
    }

    public function getTurn(): int
    {
        return $this->turn;
    }

    public function setTurn(int $turn): self
    {
        $this->turn = $turn;

        return $this;
    }

    public function getActorType(): string
    {
        return $this->actorType;
    }

    public function setActorType(string $actorType): self
    {
        $this->actorType = $actorType;

        return $this;
    }

    public function getActorId(): ?int
    {
        return $this->actorId;
    }

    public function setActorId(?int $actorId): self
    {
        $this->actorId = $actorId;

        return $this;
    }

    public function getActorName(): string
    {
        return $this->actorName;
    }

    public function setActorName(string $actorName): self
    {
        $this->actorName = $actorName;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
