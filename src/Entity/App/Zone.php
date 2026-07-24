<?php

namespace App\Entity\App;

use App\Repository\ZoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Zone du graphe de monde (pivot PBBG, ZON-02).
 *
 * Une zone est un lieu nomme relie aux autres par des ZoneConnection ;
 * la zone courante du joueur conditionne les actions disponibles.
 */
#[ORM\Entity(repositoryClass: ZoneRepository::class)]
#[ORM\Table(name: 'zone')]
class Zone
{
    use TimestampableEntity;

    public const TYPE_CITY = 'city';
    public const TYPE_WILDERNESS = 'wilderness';
    public const TYPE_INTERIOR = 'interior';
    public const TYPE_DUNGEON = 'dungeon';

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

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'description_translations', type: 'json', nullable: true)]
    private ?array $descriptionTranslations = null;

    #[ORM\Column(name: 'illustration_path', type: 'string', length: 255, nullable: true)]
    private ?string $illustrationPath = null;

    #[ORM\Column(name: 'type', type: 'string', length: 32, options: ['default' => self::TYPE_WILDERNESS])]
    private string $type = self::TYPE_WILDERNESS;

    /**
     * Zone safe : aucune rencontre hostile (villes, interieurs de PNJ...).
     */
    #[ORM\Column(name: 'is_safe', type: 'boolean', options: ['default' => false])]
    private bool $isSafe = false;

    #[ORM\Column(name: 'enabled', type: 'boolean', options: ['default' => true])]
    private bool $enabled = true;

    /**
     * Configuration declarative de l'exploration (ZON-08, prelude ZON-11) :
     * `{"weights": {"mob": 50, "chest": 10, "harvest": 10, "pnj": 10, "nothing": 20},
     *   "chest_gils_min": 5, "chest_gils_max": 30}`.
     * Null = defauts d'ExploreService. Ajouter du contenu = ajouter de la donnee.
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'explore_config', type: 'json', nullable: true)]
    private ?array $exploreConfig = null;

    /**
     * Configuration declarative de la recolte (ZON-10) : filons partages de la
     * zone. Chaque entree decrit une ressource recoltable —
     * `{"resources": [{"slug": "filon-fer", "item": "ore-iron",
     *   "profession": "mining", "capacity": 20, "respawn_seconds": 1800,
     *   "yield_min": 1, "yield_max": 2}]}`.
     * Null ou liste vide = zone sans recolte. Ajouter du contenu = ajouter de
     * la donnee (le stock collectif runtime vit dans `ZoneVein`).
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'gather_config', type: 'json', nullable: true)]
    private ?array $gatherConfig = null;

    /**
     * Carte TMX d'origine (transition depuis la carte en tuiles) : permet de
     * rattacher spawns et positions existants a la zone (ZON-03 / ZON-04),
     * puis disparaitra avec la suppression du code carte (ZON-21).
     */
    #[ORM\ManyToOne(targetEntity: Map::class)]
    #[ORM\JoinColumn(name: 'source_map_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Map $sourceMap = null;

    /** @var Collection<int, ZoneConnection> */
    #[ORM\OneToMany(targetEntity: ZoneConnection::class, mappedBy: 'fromZone')]
    private Collection $connections;

    public function __construct()
    {
        $this->connections = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @return list<string>
     */
    public static function getTypes(): array
    {
        return [self::TYPE_CITY, self::TYPE_WILDERNESS, self::TYPE_INTERIOR, self::TYPE_DUNGEON];
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

    public function getIllustrationPath(): ?string
    {
        return $this->illustrationPath;
    }

    public function setIllustrationPath(?string $illustrationPath): self
    {
        $this->illustrationPath = $illustrationPath;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!\in_array($type, self::getTypes(), true)) {
            throw new \InvalidArgumentException(sprintf('Unknown zone type "%s". Valid types: %s', $type, implode(', ', self::getTypes())));
        }
        $this->type = $type;

        return $this;
    }

    public function isSafe(): bool
    {
        return $this->isSafe;
    }

    public function setIsSafe(bool $isSafe): self
    {
        $this->isSafe = $isSafe;

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
     * @return array<string, mixed>|null
     */
    public function getExploreConfig(): ?array
    {
        return $this->exploreConfig;
    }

    /**
     * @param array<string, mixed>|null $exploreConfig
     */
    public function setExploreConfig(?array $exploreConfig): self
    {
        $this->exploreConfig = $exploreConfig;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getGatherConfig(): ?array
    {
        return $this->gatherConfig;
    }

    /**
     * @param array<string, mixed>|null $gatherConfig
     */
    public function setGatherConfig(?array $gatherConfig): self
    {
        $this->gatherConfig = $gatherConfig;

        return $this;
    }

    /**
     * Ressources recoltables declarees pour la zone (liste normalisee, jamais
     * null).
     *
     * @return list<array<string, mixed>>
     */
    public function getGatherResources(): array
    {
        $resources = $this->gatherConfig['resources'] ?? [];
        if (!\is_array($resources)) {
            return [];
        }

        $normalized = [];
        foreach ($resources as $resource) {
            if (!\is_array($resource)) {
                continue;
            }
            /** @var array<string, mixed> $resource */
            $normalized[] = $resource;
        }

        return $normalized;
    }

    public function getSourceMap(): ?Map
    {
        return $this->sourceMap;
    }

    public function setSourceMap(?Map $sourceMap): self
    {
        $this->sourceMap = $sourceMap;

        return $this;
    }

    /** @return Collection<int, ZoneConnection> */
    public function getConnections(): Collection
    {
        return $this->connections;
    }
}
