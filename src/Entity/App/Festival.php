<?php

declare(strict_types=1);

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'festival')]
#[ORM\UniqueConstraint(name: 'uniq_festival_slug', columns: ['slug'])]
#[ORM\Entity]
class Festival
{
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    /** @var array<string, string>|null */
    #[ORM\Column(name: 'name_translations', type: 'json', nullable: true)]
    private ?array $nameTranslations = null;

    #[ORM\Column(name: 'slug', type: 'string', length: 100)]
    private string $slug;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    /** @var array<string, string>|null */
    #[ORM\Column(name: 'description_translations', type: 'json', nullable: true)]
    private ?array $descriptionTranslations = null;

    /** @var string spring|summer|autumn|winter */
    #[ORM\Column(name: 'season', type: 'string', length: 20)]
    private string $season;

    /** Jour de début dans la saison (1-28, basé sur le cycle in-game) */
    #[ORM\Column(name: 'start_day', type: 'integer')]
    private int $startDay;

    /** Jour de fin dans la saison (1-28) */
    #[ORM\Column(name: 'end_day', type: 'integer')]
    private int $endDay;

    /** @var array<string, mixed>|null Récompenses / bonus pendant le festival */
    #[ORM\Column(name: 'rewards', type: 'json', nullable: true)]
    private ?array $rewards = null;

    #[ORM\Column(name: 'active', type: 'boolean', options: ['default' => true])]
    private bool $active = true;

    public function getId(): int
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

    public function getLocalizedName(?string $locale): string
    {
        if ($locale === null || $locale === '' || $this->nameTranslations === null) {
            return $this->name;
        }
        $translation = $this->nameTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function getNameTranslations(): array
    {
        return $this->nameTranslations ?? [];
    }

    /**
     * @param array<string, mixed>|null $translations
     */
    public function setNameTranslations(?array $translations): self
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && \is_string($value) && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->nameTranslations = $normalized === [] ? null : $normalized;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLocalizedDescription(?string $locale): ?string
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

    public function getSeason(): string
    {
        return $this->season;
    }

    public function setSeason(string $season): self
    {
        $this->season = $season;

        return $this;
    }

    public function getStartDay(): int
    {
        return $this->startDay;
    }

    public function setStartDay(int $startDay): self
    {
        $this->startDay = $startDay;

        return $this;
    }

    public function getEndDay(): int
    {
        return $this->endDay;
    }

    public function setEndDay(int $endDay): self
    {
        $this->endDay = $endDay;

        return $this;
    }

    public function getRewards(): ?array
    {
        return $this->rewards;
    }

    public function setRewards(?array $rewards): self
    {
        $this->rewards = $rewards;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Vérifie si le festival est en cours (saison + jour in-game correspondants).
     */
    public function isCurrentlyRunning(string $currentSeason, int $currentDay): bool
    {
        if (!$this->active || $currentSeason !== $this->season) {
            return false;
        }

        if ($this->startDay <= $this->endDay) {
            return $currentDay >= $this->startDay && $currentDay <= $this->endDay;
        }

        // wrap-around (ex: startDay=25, endDay=5 → jours 25-28 + 1-5)
        return $currentDay >= $this->startDay || $currentDay <= $this->endDay;
    }
}
