<?php

namespace App\Entity\Game;

use App\Enum\ReputationTier;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'game_faction_rewards')]
class FactionReward
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Faction::class)]
    #[ORM\JoinColumn(name: 'faction_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Faction $faction;

    #[ORM\Column(name: 'required_tier', type: 'string', length: 32)]
    private string $requiredTier;

    #[ORM\Column(name: 'reward_type', type: 'string', length: 64)]
    private string $rewardType;

    #[ORM\Column(name: 'reward_data', type: 'json')]
    private array $rewardData = [];

    #[ORM\Column(name: 'label', type: 'string', length: 255)]
    private string $label;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'label_translations', type: 'json', nullable: true)]
    private ?array $labelTranslations = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'description_translations', type: 'json', nullable: true)]
    private ?array $descriptionTranslations = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFaction(): Faction
    {
        return $this->faction;
    }

    public function setFaction(Faction $faction): self
    {
        $this->faction = $faction;

        return $this;
    }

    public function getRequiredTier(): ReputationTier
    {
        return ReputationTier::from($this->requiredTier);
    }

    public function setRequiredTier(ReputationTier $tier): self
    {
        $this->requiredTier = $tier->value;

        return $this;
    }

    public function getRewardType(): string
    {
        return $this->rewardType;
    }

    public function setRewardType(string $rewardType): self
    {
        $this->rewardType = $rewardType;

        return $this;
    }

    public function getRewardData(): array
    {
        return $this->rewardData;
    }

    public function setRewardData(array $rewardData): self
    {
        $this->rewardData = $rewardData;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the label translated for the requested locale, or fall back to the base `label` column.
     */
    public function getLocalizedLabel(?string $locale): string
    {
        if ($locale === null || $locale === '' || $this->labelTranslations === null) {
            return $this->label;
        }
        $translation = $this->labelTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->label;
    }

    /**
     * @return array<string, string>
     */
    public function getLabelTranslations(): array
    {
        return $this->labelTranslations ?? [];
    }

    /**
     * @param array<string, mixed>|null $translations
     */
    public function setLabelTranslations(?array $translations): self
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && \is_string($value) && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->labelTranslations = $normalized === [] ? null : $normalized;

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

    /**
     * Get the description translated for the requested locale, or fall back to the base `description` column.
     */
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
}
