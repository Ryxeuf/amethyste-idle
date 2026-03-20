<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'game_event')]
#[ORM\Entity]
class GameEvent
{
    use TimestampableEntity;

    public const TYPE_BOSS_SPAWN = 'boss_spawn';
    public const TYPE_XP_BONUS = 'xp_bonus';
    public const TYPE_DROP_BONUS = 'drop_bonus';
    public const TYPE_INVASION = 'invasion';
    public const TYPE_CUSTOM = 'custom';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'type', type: 'string', length: 50)]
    private string $type = self::TYPE_CUSTOM;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'status', type: 'string', length: 20)]
    private string $status = self::STATUS_SCHEDULED;

    #[ORM\Column(name: 'starts_at', type: 'datetime')]
    private \DateTime $startsAt;

    #[ORM\Column(name: 'ends_at', type: 'datetime')]
    private \DateTime $endsAt;

    #[ORM\Column(name: 'parameters', type: 'json', nullable: true)]
    private ?array $parameters = null;

    #[ORM\Column(name: 'recurring', type: 'boolean', options: ['default' => false])]
    private bool $recurring = false;

    #[ORM\Column(name: 'recurrence_interval', type: 'integer', nullable: true)]
    private ?int $recurrenceInterval = null;

    #[ORM\ManyToOne(targetEntity: Map::class)]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id', nullable: true)]
    private ?Map $map = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStartsAt(): \DateTime
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTime $startsAt): void
    {
        $this->startsAt = $startsAt;
    }

    public function getEndsAt(): \DateTime
    {
        return $this->endsAt;
    }

    public function setEndsAt(\DateTime $endsAt): void
    {
        $this->endsAt = $endsAt;
    }

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    public function setParameters(?array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function isRecurring(): bool
    {
        return $this->recurring;
    }

    public function setRecurring(bool $recurring): void
    {
        $this->recurring = $recurring;
    }

    public function getRecurrenceInterval(): ?int
    {
        return $this->recurrenceInterval;
    }

    public function setRecurrenceInterval(?int $recurrenceInterval): void
    {
        $this->recurrenceInterval = $recurrenceInterval;
    }

    public function getMap(): ?Map
    {
        return $this->map;
    }

    public function setMap(?Map $map): void
    {
        $this->map = $map;
    }

    public function isActive(): bool
    {
        $now = new \DateTime();

        return $this->status === self::STATUS_ACTIVE
            || ($this->status === self::STATUS_SCHEDULED && $now >= $this->startsAt && $now <= $this->endsAt);
    }

    public function isPast(): bool
    {
        return new \DateTime() > $this->endsAt;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_BOSS_SPAWN => 'Spawn de boss',
            self::TYPE_XP_BONUS => 'Bonus XP',
            self::TYPE_DROP_BONUS => 'Bonus drop',
            self::TYPE_INVASION => 'Invasion',
            self::TYPE_CUSTOM => 'Personnalise',
            default => $this->type,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SCHEDULED => 'Programme',
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_COMPLETED => 'Termine',
            self::STATUS_CANCELLED => 'Annule',
            default => $this->status,
        };
    }
}
