<?php

namespace App\Entity\App;

use App\Entity\App\Traits\CoordinatesTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'area')]
#[ORM\Index(name: 'area_coordinates_idx', columns: ['coordinates'])]
class Area
{
    use CoordinatesTrait;
    use TimestampableEntity;

    public function __toString()
    {
        return $this->getName();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'slug', type: 'string', length: 255)]
    private string $slug;

    #[ORM\Column(name: 'fullData', type: 'json')]
    private string $fullData;

    #[ORM\Column(name: 'biome', type: 'string', length: 50, nullable: true)]
    private ?string $biome = null;

    #[ORM\Column(name: 'weather', type: 'string', length: 50, nullable: true)]
    private ?string $weather = null;

    #[ORM\Column(name: 'music', type: 'string', length: 255, nullable: true)]
    private ?string $music = null;

    #[ORM\Column(name: 'light_level', type: 'float', nullable: true)]
    private ?float $lightLevel = null;

    #[ORM\Column(name: 'zone_x', type: 'integer', nullable: true)]
    private ?int $zoneX = null;

    #[ORM\Column(name: 'zone_y', type: 'integer', nullable: true)]
    private ?int $zoneY = null;

    #[ORM\Column(name: 'zone_width', type: 'integer', nullable: true)]
    private ?int $zoneWidth = null;

    #[ORM\Column(name: 'zone_height', type: 'integer', nullable: true)]
    private ?int $zoneHeight = null;

    #[ORM\ManyToOne(targetEntity: Map::class, inversedBy: 'areas')]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id')]
    private Map $map;

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

    public function getFullData(): string
    {
        return $this->fullData;
    }

    public function getFullDataArray(): array
    {
        $data = json_decode($this->fullData, true);

        // Vérifier si les données sont correctement décodées
        if (!is_array($data)) {
            return ['cells' => []];
        }

        // S'assurer que la clé 'cells' existe
        if (!isset($data['cells'])) {
            $data['cells'] = [];
        }

        return $data;
    }

    public function setFullData(string $fullData): void
    {
        $this->fullData = $fullData;
    }

    public function getMap(): Map
    {
        return $this->map;
    }

    public function setMap(Map $map): void
    {
        $this->map = $map;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getBiome(): ?string
    {
        return $this->biome;
    }

    public function setBiome(?string $biome): void
    {
        $this->biome = $biome;
    }

    public function getWeather(): ?string
    {
        return $this->weather;
    }

    public function setWeather(?string $weather): void
    {
        $this->weather = $weather;
    }

    public function getMusic(): ?string
    {
        return $this->music;
    }

    public function setMusic(?string $music): void
    {
        $this->music = $music;
    }

    public function getLightLevel(): ?float
    {
        return $this->lightLevel;
    }

    public function setLightLevel(?float $lightLevel): void
    {
        $this->lightLevel = $lightLevel;
    }

    public function getZoneX(): ?int
    {
        return $this->zoneX;
    }

    public function setZoneX(?int $zoneX): void
    {
        $this->zoneX = $zoneX;
    }

    public function getZoneY(): ?int
    {
        return $this->zoneY;
    }

    public function setZoneY(?int $zoneY): void
    {
        $this->zoneY = $zoneY;
    }

    public function getZoneWidth(): ?int
    {
        return $this->zoneWidth;
    }

    public function setZoneWidth(?int $zoneWidth): void
    {
        $this->zoneWidth = $zoneWidth;
    }

    public function getZoneHeight(): ?int
    {
        return $this->zoneHeight;
    }

    public function setZoneHeight(?int $zoneHeight): void
    {
        $this->zoneHeight = $zoneHeight;
    }

    public function hasZoneBounds(): bool
    {
        return $this->zoneX !== null && $this->zoneY !== null
            && $this->zoneWidth !== null && $this->zoneHeight !== null;
    }

    public function getZoneData(): ?array
    {
        if (!$this->hasZoneBounds()) {
            return null;
        }

        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'biome' => $this->biome,
            'weather' => $this->weather,
            'music' => $this->music,
            'lightLevel' => $this->lightLevel,
            'x' => $this->zoneX,
            'y' => $this->zoneY,
            'width' => $this->zoneWidth,
            'height' => $this->zoneHeight,
        ];
    }
}
