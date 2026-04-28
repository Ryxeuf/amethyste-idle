<?php

namespace App\Entity\App;

use App\Enum\InfluenceActivityType;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'weekly_challenge')]
#[ORM\Index(name: 'idx_weekly_challenge_season_week', columns: ['season_id', 'week_number'])]
class WeeklyChallenge
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: InfluenceSeason::class)]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id', nullable: false)]
    private InfluenceSeason $season;

    #[ORM\Column(name: 'title', type: 'string', length: 100)]
    private string $title;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'title_translations', type: 'json', nullable: true)]
    private ?array $titleTranslations = null;

    #[ORM\Column(name: 'description', type: 'text')]
    private string $description;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'description_translations', type: 'json', nullable: true)]
    private ?array $descriptionTranslations = null;

    #[ORM\Column(name: 'activity_type', type: 'string', length: 20, enumType: InfluenceActivityType::class)]
    private InfluenceActivityType $activityType;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'criteria', type: 'json')]
    private array $criteria = [];

    #[ORM\Column(name: 'bonus_points', type: 'integer')]
    private int $bonusPoints;

    #[ORM\Column(name: 'week_number', type: 'integer')]
    private int $weekNumber;

    #[ORM\Column(name: 'starts_at', type: 'datetime')]
    private \DateTimeInterface $startsAt;

    #[ORM\Column(name: 'ends_at', type: 'datetime')]
    private \DateTimeInterface $endsAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeason(): InfluenceSeason
    {
        return $this->season;
    }

    public function setSeason(InfluenceSeason $season): self
    {
        $this->season = $season;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the title translated for the requested locale, or fall back to the base `title` column.
     */
    public function getLocalizedTitle(?string $locale): string
    {
        if ($locale === null || $locale === '' || $this->titleTranslations === null) {
            return $this->title;
        }
        $translation = $this->titleTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->title;
    }

    /**
     * @return array<string, string>
     */
    public function getTitleTranslations(): array
    {
        return $this->titleTranslations ?? [];
    }

    /**
     * @param array<string, mixed>|null $translations
     */
    public function setTitleTranslations(?array $translations): self
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && \is_string($value) && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->titleTranslations = $normalized === [] ? null : $normalized;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the description translated for the requested locale, or fall back to the base `description` column.
     */
    public function getLocalizedDescription(?string $locale): string
    {
        if ($locale === null || $locale === '' || $this->descriptionTranslations === null) {
            return $this->description;
        }
        $translation = $this->descriptionTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->description;
    }

    /**
     * @return array<string, string>
     */
    public function getDescriptionTranslations(): array
    {
        return $this->descriptionTranslations ?? [];
    }

    /**
     * @param array<string, mixed>|null $translations
     */
    public function setDescriptionTranslations(?array $translations): self
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && \is_string($value) && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->descriptionTranslations = $normalized === [] ? null : $normalized;

        return $this;
    }

    public function getActivityType(): InfluenceActivityType
    {
        return $this->activityType;
    }

    public function setActivityType(InfluenceActivityType $activityType): self
    {
        $this->activityType = $activityType;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function setCriteria(array $criteria): self
    {
        $this->criteria = $criteria;

        return $this;
    }

    public function getBonusPoints(): int
    {
        return $this->bonusPoints;
    }

    public function setBonusPoints(int $bonusPoints): self
    {
        $this->bonusPoints = $bonusPoints;

        return $this;
    }

    public function getWeekNumber(): int
    {
        return $this->weekNumber;
    }

    public function setWeekNumber(int $weekNumber): self
    {
        $this->weekNumber = $weekNumber;

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

    public function isActive(): bool
    {
        $now = new \DateTime();

        return $now >= $this->startsAt && $now <= $this->endsAt;
    }

    /**
     * Retourne le nombre requis pour completer le defi (criteria.target).
     */
    public function getTarget(): int
    {
        return (int) ($this->criteria['target'] ?? 1);
    }
}
