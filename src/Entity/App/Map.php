<?php

namespace App\Entity\App;

use App\Enum\WeatherType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'map')]
#[ORM\Entity()]
class Map
{
    use TimestampableEntity;

    public function __construct()
    {
        $this->objectLayers = new ArrayCollection();
        $this->areas = new ArrayCollection();
        $this->players = new ArrayCollection();
        $this->mobs = new ArrayCollection();
        $this->pnjs = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    /**
     * Coordonnées de la carte au sein du monde.
     */
    #[ORM\Column(name: 'coordinates', type: 'string', nullable: true)]
    protected ?string $coordinates = null;

    /**
     * Largeur de la zone.
     */
    #[ORM\Column(name: 'areaWidth', type: 'integer')]
    protected int $areaWidth;

    /**
     * Hauteur de la zone.
     */
    #[ORM\Column(name: 'areaHeight', type: 'integer')]
    protected int $areaHeight;

    #[ORM\ManyToOne(targetEntity: World::class, inversedBy: 'maps')]
    #[ORM\JoinColumn(name: 'world_id', referencedColumnName: 'id')]
    private World $world;

    /** @var Collection<int, ObjectLayer> */
    #[ORM\OneToMany(targetEntity: ObjectLayer::class, mappedBy: 'map')]
    private Collection $objectLayers;

    /** @var Collection<int, Area> */
    #[ORM\OneToMany(targetEntity: Area::class, mappedBy: 'map')]
    private Collection $areas;

    /** @var Collection<int, Player> */
    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: 'map')]
    private Collection $players;

    /** @var Collection<int, Mob> */
    #[ORM\OneToMany(targetEntity: Mob::class, mappedBy: 'map')]
    private Collection $mobs;

    /** @var Collection<int, Pnj> */
    #[ORM\OneToMany(targetEntity: Pnj::class, mappedBy: 'map')]
    private Collection $pnjs;

    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: WeatherType::class)]
    private ?WeatherType $currentWeather = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $weatherChangedAt = null;

    public function getAreaByCoordinates(int $x, int $y): ?Area
    {
        foreach ($this->areas as $area) {
            if ($area->getCoordinates() === intval($x / $this->areaWidth) . '.' . intval($y / $this->areaHeight)) {
                return $area;
            }
        }

        return null;
    }

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

    public function getCoordinates(): ?string
    {
        return $this->coordinates;
    }

    public function setCoordinates(?string $coordinates): void
    {
        $this->coordinates = $coordinates;
    }

    public function getWorld(): World
    {
        return $this->world;
    }

    public function setWorld(World $world): void
    {
        $this->world = $world;
    }

    public function getAreaWidth(): int
    {
        return $this->areaWidth;
    }

    public function setAreaWidth(int $areaWidth): void
    {
        $this->areaWidth = $areaWidth;
    }

    public function getAreaHeight(): int
    {
        return $this->areaHeight;
    }

    public function setAreaHeight(int $areaHeight): void
    {
        $this->areaHeight = $areaHeight;
    }

    /** @return Collection<int, ObjectLayer> */
    public function getObjectLayers(): Collection
    {
        return $this->objectLayers;
    }

    /** @return Collection<int, Area> */
    public function getAreas(): Collection
    {
        return $this->areas;
    }

    /** @return Collection<int, Player> */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    /** @return Collection<int, Mob> */
    public function getMobs(): Collection
    {
        return $this->mobs;
    }

    /** @return Collection<int, Pnj> */
    public function getPnjs(): Collection
    {
        return $this->pnjs;
    }

    public function getCurrentWeather(): ?WeatherType
    {
        return $this->currentWeather;
    }

    public function setCurrentWeather(?WeatherType $currentWeather): void
    {
        $this->currentWeather = $currentWeather;
    }

    public function getWeatherChangedAt(): ?\DateTimeImmutable
    {
        return $this->weatherChangedAt;
    }

    public function setWeatherChangedAt(?\DateTimeImmutable $weatherChangedAt): void
    {
        $this->weatherChangedAt = $weatherChangedAt;
    }
}
