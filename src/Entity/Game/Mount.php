<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'game_mounts')]
class Mount
{
    use TimestampableEntity;

    public const OBTENTION_QUEST = 'quest';
    public const OBTENTION_DROP = 'drop';
    public const OBTENTION_PURCHASE = 'purchase';
    public const OBTENTION_ACHIEVEMENT = 'achievement';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'slug', type: 'string', length: 64, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'name_translations', type: 'json', nullable: true)]
    private ?array $nameTranslations = null;

    #[ORM\Column(name: 'description', type: 'text')]
    private string $description;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'description_translations', type: 'json', nullable: true)]
    private ?array $descriptionTranslations = null;

    #[ORM\Column(name: 'sprite_sheet', type: 'string', length: 255, nullable: true)]
    private ?string $spriteSheet = null;

    #[ORM\Column(name: 'icon_path', type: 'string', length: 255, nullable: true)]
    private ?string $iconPath = null;

    #[ORM\Column(name: 'speed_bonus', type: 'integer', options: ['default' => 50])]
    private int $speedBonus = 50;

    #[ORM\Column(name: 'obtention_type', type: 'string', length: 32, options: ['default' => self::OBTENTION_PURCHASE])]
    private string $obtentionType = self::OBTENTION_PURCHASE;

    #[ORM\Column(name: 'gil_cost', type: 'integer', nullable: true)]
    private ?int $gilCost = null;

    #[ORM\Column(name: 'required_level', type: 'integer', options: ['default' => 1])]
    private int $requiredLevel = 1;

    #[ORM\Column(name: 'enabled', type: 'boolean', options: ['default' => true])]
    private bool $enabled = true;

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the name translated for the requested locale, or fall back to the base `name` column.
     */
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

    public function getSpriteSheet(): ?string
    {
        return $this->spriteSheet;
    }

    public function setSpriteSheet(?string $spriteSheet): self
    {
        $this->spriteSheet = $spriteSheet;

        return $this;
    }

    public function getIconPath(): ?string
    {
        return $this->iconPath;
    }

    public function setIconPath(?string $iconPath): self
    {
        $this->iconPath = $iconPath;

        return $this;
    }

    public function getSpeedBonus(): int
    {
        return $this->speedBonus;
    }

    public function setSpeedBonus(int $speedBonus): self
    {
        $this->speedBonus = $speedBonus;

        return $this;
    }

    public function getObtentionType(): string
    {
        return $this->obtentionType;
    }

    public function setObtentionType(string $obtentionType): self
    {
        if (!in_array($obtentionType, self::getObtentionTypes(), true)) {
            throw new \InvalidArgumentException(sprintf('Type d\'obtention "%s" invalide.', $obtentionType));
        }

        $this->obtentionType = $obtentionType;

        return $this;
    }

    public function getGilCost(): ?int
    {
        return $this->gilCost;
    }

    public function setGilCost(?int $gilCost): self
    {
        $this->gilCost = $gilCost;

        return $this;
    }

    public function getRequiredLevel(): int
    {
        return $this->requiredLevel;
    }

    public function setRequiredLevel(int $requiredLevel): self
    {
        $this->requiredLevel = $requiredLevel;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return list<string>
     */
    public static function getObtentionTypes(): array
    {
        return [
            self::OBTENTION_QUEST,
            self::OBTENTION_DROP,
            self::OBTENTION_PURCHASE,
            self::OBTENTION_ACHIEVEMENT,
        ];
    }
}
