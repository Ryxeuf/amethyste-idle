<?php

namespace App\Entity\App;

use App\Enum\ReputationTier;
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

    /**
     * Position sur la carte du monde illustree (pivot PBBG, ZON-16), en
     * pourcentage 0-100 du cadre. null = zone non placee (absente de la carte).
     */
    #[ORM\Column(name: 'map_x', type: 'integer', nullable: true)]
    private ?int $mapX = null;

    #[ORM\Column(name: 'map_y', type: 'integer', nullable: true)]
    private ?int $mapY = null;

    /**
     * Contour de la zone sur la carte illustree : une liste de points « x,y »
     * separes par des espaces, dans le **meme espace 0-100** que `mapX`/`mapY`
     * (format `points` d'un `<polygon>` SVG). null = zone sans contour trace :
     * seule sa pastille reste cliquable, et le brouillard ne s'y perce pas.
     */
    #[ORM\Column(name: 'map_shape', type: 'text', nullable: true)]
    private ?string $mapShape = null;

    /**
     * Palier de la zone (BES-01, GAME_ZONES §2) : T0 (sur) a T4. C'est le
     * meme vocabulaire que les profils de filon, les bandes de purete et les
     * rangs de foyer — et la source du palier des monstres qui y vivent.
     */
    #[ORM\Column(name: 'tier', type: 'integer', options: ['default' => 0])]
    private int $tier = 0;

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
     * La porte : le slug de la faction dont cette zone exige les faveurs (FAC-09).
     *
     * Un **slug** et non une cle etrangere, pour la meme raison que le reste du
     * bloc declaratif : les zones s'importent depuis `zones.yaml` par une
     * commande, les factions viennent des fixtures, et faire dependre l'import
     * du graphe de l'ordre de chargement des fixtures ferait echouer un
     * deploiement sur une question de sequence.
     *
     * `null` = aucune garde, ce qui reste le cas de toutes les zones du monde
     * sauf cinq : *le gate est opt-in, rien de ce qui etait accessible ne se
     * ferme*.
     */
    #[ORM\Column(name: 'required_faction', type: 'string', length: 64, nullable: true)]
    private ?string $requiredFaction = null;

    /**
     * Le palier exige a cette faction (FAC-09), valeur de `ReputationTier`.
     *
     * Toujours renseigne quand `requiredFaction` l'est, et jamais seul : une
     * garde a moitie ecrite laisserait la porte ouverte ou la fermerait a tout
     * le monde, et `ZoneDefinitionLoader` refuse les deux.
     */
    #[ORM\Column(name: 'required_tier', type: 'string', length: 32, nullable: true)]
    private ?string $requiredTier = null;

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

    /**
     * Cette zone est-elle la zone **principale** de sa carte d'origine ?
     *
     * Le lien zone -> carte est un « plusieurs vers un » assume : le Fanal et
     * son Quartier des Jardins partagent la meme carte, et `Dungeon::$zone` le
     * documente depuis DON-01. Le lien **inverse** — a quelle zone appartient
     * une entite qui ne connait que sa carte ? — n'avait, lui, aucune reponse
     * definie : `ZoneRepository::findEnabledBySourceMap()` prenait la premiere
     * ligne rendue par PostgreSQL, c'est-a-dire l'ordre physique, c'est-a-dire
     * un ordre qui change des qu'une zone est mise a jour.
     *
     * Consequence vecue : les habitants du Fanal poses par les fixtures — dont
     * la maitresse d'armes, premiere porte de la chaine de l'acte I — sont
     * partis dans le Quartier des Jardins. Rien n'a casse, aucune erreur n'a ete
     * levee : l'ecran de zone liste **strictement** par zone, donc ils ont
     * simplement cesse d'exister la ou le tutoriel envoie les chercher.
     */
    #[ORM\Column(name: 'source_map_primary', type: 'boolean', options: ['default' => false])]
    private bool $sourceMapPrimary = false;

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

    public function getMapX(): ?int
    {
        return $this->mapX;
    }

    public function setMapX(?int $mapX): self
    {
        $this->mapX = $mapX;

        return $this;
    }

    public function getMapY(): ?int
    {
        return $this->mapY;
    }

    public function setMapY(?int $mapY): self
    {
        $this->mapY = $mapY;

        return $this;
    }

    public function hasMapPosition(): bool
    {
        return null !== $this->mapX && null !== $this->mapY;
    }

    public function getMapShape(): ?string
    {
        return $this->mapShape;
    }

    public function setMapShape(?string $mapShape): self
    {
        $this->mapShape = $mapShape;

        return $this;
    }

    public function hasMapShape(): bool
    {
        return null !== $this->mapShape && '' !== $this->mapShape;
    }

    public function setIllustrationPath(?string $illustrationPath): self
    {
        $this->illustrationPath = $illustrationPath;

        return $this;
    }

    public function getTier(): int
    {
        return $this->tier;
    }

    public function setTier(int $tier): self
    {
        $this->tier = max(0, min(4, $tier));

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
     * @return list<array<array-key, mixed>>
     */
    public function getGatherResources(): array
    {
        $resources = $this->gatherConfig['resources'] ?? [];
        if (!\is_array($resources)) {
            return [];
        }

        $normalized = [];
        foreach ($resources as $resource) {
            if (\is_array($resource)) {
                $normalized[] = $resource;
            }
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

    public function isSourceMapPrimary(): bool
    {
        return $this->sourceMapPrimary;
    }

    public function setSourceMapPrimary(bool $sourceMapPrimary): self
    {
        $this->sourceMapPrimary = $sourceMapPrimary;

        return $this;
    }

    /** @return Collection<int, ZoneConnection> */
    public function getConnections(): Collection
    {
        return $this->connections;
    }

    public function getRequiredFaction(): ?string
    {
        return $this->requiredFaction;
    }

    public function setRequiredFaction(?string $slug): self
    {
        $this->requiredFaction = $slug;

        return $this;
    }

    public function getRequiredTier(): ?ReputationTier
    {
        return $this->requiredTier === null ? null : ReputationTier::from($this->requiredTier);
    }

    public function setRequiredTier(?ReputationTier $tier): self
    {
        $this->requiredTier = $tier?->value;

        return $this;
    }

    /**
     * Cette zone est-elle gardee par un palier de reputation (FAC-09) ?
     */
    public function isGuarded(): bool
    {
        return $this->requiredFaction !== null && $this->requiredTier !== null;
    }
}
