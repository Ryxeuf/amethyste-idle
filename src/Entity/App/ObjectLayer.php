<?php

namespace App\Entity\App;

use App\Entity\App\Traits\CoordinatesTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'object_layer')]
#[ORM\Entity()]
class ObjectLayer
{
    use TimestampableEntity;
    use CoordinatesTrait;

    public const TYPE_OTHER = 'other';
    public const TYPE_CHEST = 'chest';
    public const TYPE_SPOT = 'spot';
    public const TYPE_PORTAL = 'portal';
    public const TYPE_MOB_SPAWN = 'mob_spawn';
    public const TYPE_NPC_SPAWN = 'npc_spawn';
    public const TYPE_HARVEST_SPOT = 'harvest_spot';
    public const TYPE_FORGE = 'forge';
    public const TYPE_TANNERY = 'tannery';
    public const TYPE_ALCHEMY_LAB = 'alchemy_lab';
    public const TYPE_JEWELER_BENCH = 'jeweler_bench';

    public function __toString(): string
    {
        return $this->getName();
    }

    public function isDynamic(): bool
    {
        return $this->isUsable();
    }

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'slug', type: 'string', length: 255)]
    private string $slug;

    #[ORM\Column(name: 'type', type: 'string', length: 255)]
    private string $type = self::TYPE_OTHER;

    /**
     * Date d'utilisation.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $usedAt;

    /**
     * Liste des items que l'objet peut contenir.
     */
    #[ORM\Column(name: 'items', type: 'json', nullable: true)]
    private ?array $items;

    /**
     * Modificateur de mouvement
     * Si la valeur est -1, la case devient impénétrable.
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $movement = 0;

    #[ORM\Column(name: 'actions', type: 'json', nullable: true)]
    private ?array $actions;

    #[ORM\Column(name: 'usable', type: 'boolean', options: ['default' => 0])]
    private bool $usable = false;

    #[ORM\ManyToOne(targetEntity: Map::class, inversedBy: 'objectLayers', fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id')]
    private ?Map $map;

    #[ORM\Column(name: 'destination_map_id', type: 'integer', nullable: true)]
    private ?int $destinationMapId = null;

    #[ORM\Column(name: 'destination_coordinates', type: 'string', nullable: true)]
    private ?string $destinationCoordinates = null;

    /**
     * Délai de réapparition en secondes après récolte (null = pas de respawn).
     */
    #[ORM\Column(name: 'respawn_delay', type: 'integer', nullable: true)]
    private ?int $respawnDelay = null;

    /**
     * Type d'outil requis pour récolter ce spot (pickaxe, sickle, fishing_rod, skinning_knife).
     */
    #[ORM\Column(name: 'required_tool_type', type: 'string', length: 50, nullable: true)]
    private ?string $requiredToolType = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getUsedAt(): ?\DateTime
    {
        return $this->usedAt;
    }

    public function setUsedAt(?\DateTime $usedAt): void
    {
        $this->usedAt = $usedAt;
    }

    public function getItems(): ?array
    {
        return $this->items;
    }

    public function setItems(?array $items): void
    {
        $this->items = $items;
    }

    public function getMovement(): int
    {
        return $this->movement;
    }

    public function setMovement(int $movement): void
    {
        $this->movement = $movement;
    }

    public function getActions(): ?array
    {
        return $this->actions;
    }

    public function setActions(?array $actions): void
    {
        $this->actions = $actions;
    }

    public function isUsable(): bool
    {
        return $this->usable;
    }

    public function setUsable(bool $usable): void
    {
        $this->usable = $usable;
    }

    public function getMap(): ?Map
    {
        return $this->map;
    }

    public function setMap(?Map $map): void
    {
        $this->map = $map;
    }

    public function getDestinationMapId(): ?int
    {
        return $this->destinationMapId;
    }

    public function setDestinationMapId(?int $destinationMapId): void
    {
        $this->destinationMapId = $destinationMapId;
    }

    public function getDestinationCoordinates(): ?string
    {
        return $this->destinationCoordinates;
    }

    public function setDestinationCoordinates(?string $destinationCoordinates): void
    {
        $this->destinationCoordinates = $destinationCoordinates;
    }

    public function isPortal(): bool
    {
        return $this->type === self::TYPE_PORTAL;
    }

    public function isHarvestSpot(): bool
    {
        return $this->type === self::TYPE_HARVEST_SPOT;
    }

    public function isCraftStation(): bool
    {
        return in_array($this->type, [
            self::TYPE_FORGE,
            self::TYPE_TANNERY,
            self::TYPE_ALCHEMY_LAB,
            self::TYPE_JEWELER_BENCH,
        ], true);
    }

    /**
     * Retourne la profession associée au type de station d'artisanat.
     */
    public function getCraftProfession(): ?string
    {
        return match ($this->type) {
            self::TYPE_FORGE => 'blacksmith',
            self::TYPE_TANNERY => 'tanner',
            self::TYPE_ALCHEMY_LAB => 'alchemist',
            self::TYPE_JEWELER_BENCH => 'jeweler',
            default => null,
        };
    }

    /**
     * Vérifie si le spot est disponible (pas en cooldown de respawn).
     */
    public function isAvailable(): bool
    {
        if ($this->usedAt === null) {
            return true;
        }

        if ($this->respawnDelay === null) {
            return false;
        }

        $respawnAt = (clone $this->usedAt)->modify("+{$this->respawnDelay} seconds");

        return new \DateTime() >= $respawnAt;
    }

    /**
     * Retourne le nombre de secondes restantes avant le respawn, ou 0 si disponible.
     */
    public function getRemainingRespawnSeconds(): int
    {
        if ($this->isAvailable()) {
            return 0;
        }

        if ($this->usedAt === null || $this->respawnDelay === null) {
            return 0;
        }

        $respawnAt = (clone $this->usedAt)->modify("+{$this->respawnDelay} seconds");
        $diff = (new \DateTime())->getTimestamp() - $respawnAt->getTimestamp();

        return max(0, -$diff);
    }

    public function getRespawnDelay(): ?int
    {
        return $this->respawnDelay;
    }

    public function setRespawnDelay(?int $respawnDelay): void
    {
        $this->respawnDelay = $respawnDelay;
    }

    public function getRequiredToolType(): ?string
    {
        return $this->requiredToolType;
    }

    public function setRequiredToolType(?string $requiredToolType): void
    {
        $this->requiredToolType = $requiredToolType;
    }
}
