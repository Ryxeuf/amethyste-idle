<?php

namespace App\Entity\App;

use App\Enum\SeasonStatus;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'influence_season')]
#[ORM\UniqueConstraint(name: 'uq_influence_season_slug', columns: ['slug'])]
#[ORM\UniqueConstraint(name: 'uq_influence_season_number', columns: ['season_number'])]
class InfluenceSeason
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(name: 'slug', type: 'string', length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'season_number', type: 'integer', unique: true)]
    private int $seasonNumber;

    #[ORM\Column(name: 'starts_at', type: 'datetime')]
    private \DateTimeInterface $startsAt;

    #[ORM\Column(name: 'ends_at', type: 'datetime')]
    private \DateTimeInterface $endsAt;

    #[ORM\Column(name: 'status', type: 'string', length: 20, enumType: SeasonStatus::class)]
    private SeasonStatus $status = SeasonStatus::Scheduled;

    #[ORM\Column(name: 'theme', type: 'string', length: 100, nullable: true)]
    private ?string $theme = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'parameters', type: 'json', nullable: true)]
    private ?array $parameters = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSeasonNumber(): int
    {
        return $this->seasonNumber;
    }

    public function setSeasonNumber(int $seasonNumber): self
    {
        $this->seasonNumber = $seasonNumber;

        return $this;
    }

    public function getStartsAt(): \DateTimeInterface
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeInterface $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): \DateTimeInterface
    {
        return $this->endsAt;
    }

    public function setEndsAt(\DateTimeInterface $endsAt): self
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getStatus(): SeasonStatus
    {
        return $this->status;
    }

    public function setStatus(SeasonStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    /**
     * @param array<string, mixed>|null $parameters
     */
    public function setParameters(?array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === SeasonStatus::Active;
    }

    public function isCompleted(): bool
    {
        return $this->status === SeasonStatus::Completed;
    }

    public function isScheduled(): bool
    {
        return $this->status === SeasonStatus::Scheduled;
    }

    /**
     * @return float Multiplicateur pour un type d'activite (default 1.0).
     */
    public function getMultiplier(string $activityType): float
    {
        $multipliers = $this->parameters['multipliers'] ?? [];

        return (float) ($multipliers[$activityType] ?? 1.0);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
