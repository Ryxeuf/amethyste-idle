<?php

namespace App\Entity\Game;

use App\Entity\App\Map;
use App\Entity\App\Zone;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'game_dungeons')]
class Dungeon
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'slug', type: 'string', length: 100, unique: true)]
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

    #[ORM\ManyToOne(targetEntity: Map::class)]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id', nullable: false)]
    private Map $map;

    #[ORM\Column(name: 'min_level', type: 'integer')]
    private int $minLevel;

    #[ORM\Column(name: 'max_players', type: 'integer', options: ['default' => 1])]
    private int $maxPlayers = 1;

    /**
     * Zone depuis laquelle le donjon se lance (pivot PBBG : la position d'un
     * joueur est sa zone, cf. regle #7). null = donjon hors graphe, accessible
     * uniquement par la liste globale `/game/dungeon` (donjons solo legacy).
     *
     * C'est ce lien, et non `map`, qui rattache un donjon au monde : plusieurs
     * zones peuvent partager une meme `sourceMap` (le Fanal en est
     * un cas dans `config/game/zones/world_1.yaml`), la carte est donc un
     * rattachement ambigu.
     */
    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'zone_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Zone $zone = null;

    #[ORM\Column(name: 'icon', type: 'string', length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(name: 'loot_preview', type: 'json', nullable: true)]
    private ?array $lootPreview = null;

    #[ORM\Column(name: 'entry_requirements', type: 'json', nullable: true)]
    private ?array $entryRequirements = null;

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

    public function getMap(): Map
    {
        return $this->map;
    }

    public function setMap(Map $map): self
    {
        $this->map = $map;

        return $this;
    }

    public function getMinLevel(): int
    {
        return $this->minLevel;
    }

    /**
     * Seuil d'experience requis pour entrer, en XP d'un domaine de combat.
     *
     * DON-01 : le calcul `minLevel x 100` vivait en trois endroits
     * (`DungeonManager` x2, `ZoneController`) — il n'a plus qu'une maison.
     * `minLevel` n'est pas un niveau de joueur (regle 6 : pas de niveau
     * global), c'est le parametre de ce seuil.
     */
    public function getRequiredExperience(): int
    {
        return $this->minLevel * 100;
    }

    public function setMinLevel(int $minLevel): self
    {
        $this->minLevel = $minLevel;

        return $this;
    }

    public function getMaxPlayers(): int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): self
    {
        $this->maxPlayers = $maxPlayers;

        return $this;
    }

    /**
     * Un donjon est un donjon de **groupe** des lors qu'il accepte plus d'un
     * joueur : pas de drapeau dedie a maintenir en parallele de `maxPlayers`.
     */
    public function isGroupDungeon(): bool
    {
        return $this->maxPlayers > 1;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): self
    {
        $this->zone = $zone;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getLootPreview(): ?array
    {
        return $this->lootPreview;
    }

    public function setLootPreview(?array $lootPreview): self
    {
        $this->lootPreview = $lootPreview;

        return $this;
    }

    public function getEntryRequirements(): ?array
    {
        return $this->entryRequirements;
    }

    public function setEntryRequirements(?array $entryRequirements): self
    {
        $this->entryRequirements = $entryRequirements;

        return $this;
    }
}
