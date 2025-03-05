<?php

namespace App\Entity\App;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'map')]
#[ORM\Entity(repositoryClass: 'App\Repository\App\MapRepository')]
class Map
{
    use TimestampableEntity;

    function __toString()
    {
        return $this->getName();
    }

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    /**
     * Coordonnées de la carte au sein du monde
     */
    #[ORM\Column(name: 'coordinates', type: 'string', nullable: true)]
    protected ?string $coordinates = null;

    /**
     * Largeur de la zone
     */
    #[ORM\Column(name: 'areaWidth', type: 'integer')]
    protected int $areaWidth;

    /**
     * Hauteur de la zone
     */
    #[ORM\Column(name: 'areaHeight', type: 'integer')]
    protected int $areaHeight;

    #[ORM\ManyToOne(targetEntity: World::class, inversedBy: 'maps')]
    #[ORM\JoinColumn(name: 'world_id', referencedColumnName: 'id')]
    private World $world;

    /**
     * @var ArrayCollection|ObjectLayer[]|PersistentCollection
     */
    #[ORM\OneToMany(targetEntity: ObjectLayer::class, mappedBy: 'map')]
    private ArrayCollection|array|PersistentCollection $objectLayers;

    /**
     * @var ArrayCollection|Area[]|PersistentCollection
     */
    #[ORM\OneToMany(targetEntity: Area::class, mappedBy: 'map')]
    private ArrayCollection|array|PersistentCollection $areas;

    /**
     * @var ArrayCollection|Player[]|PersistentCollection
     */
    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: 'map')]
    private ArrayCollection|array|PersistentCollection $players;

    /**
     * @var ArrayCollection|Mob[]|PersistentCollection
     */
    #[ORM\OneToMany(targetEntity: Mob::class, mappedBy: 'map')]
    private ArrayCollection|array|PersistentCollection $mobs;

    /**
     * @var ArrayCollection|Pnj[]|PersistentCollection
     */
    #[ORM\OneToMany(targetEntity: Pnj::class, mappedBy: 'map')]
    private ArrayCollection|array|PersistentCollection $pnjs;

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

    /**
     * @return null|string
     */
    public function getCoordinates(): ?string
    {
        return $this->coordinates;
    }

    /**
     * @param null|string $coordinates
     */
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

    /**
     * @return int
     */
    public function getAreaWidth(): int
    {
        return $this->areaWidth;
    }

    /**
     * @param int $areaWidth
     */
    public function setAreaWidth(int $areaWidth): void
    {
        $this->areaWidth = $areaWidth;
    }

    /**
     * @return int
     */
    public function getAreaHeight(): int
    {
        return $this->areaHeight;
    }

    /**
     * @param int $areaHeight
     */
    public function setAreaHeight(int $areaHeight): void
    {
        $this->areaHeight = $areaHeight;
    }

    /**
     * @return ArrayCollection|ObjectLayer[]|PersistentCollection
     */
    public function getObjectLayers(): ArrayCollection|array|PersistentCollection
    {
        return $this->objectLayers;
    }

    /**
     * @return Area[]|ArrayCollection|PersistentCollection
     */
    public function getAreas(): ArrayCollection|array|PersistentCollection
    {
        return $this->areas;
    }

    /**
     * @return Player[]|ArrayCollection|PersistentCollection
     */
    public function getPlayers(): ArrayCollection|array|PersistentCollection
    {
        return $this->players;
    }

    /**
     * @return Mob[]|ArrayCollection|PersistentCollection
     */
    public function getMobs(): ArrayCollection|array|PersistentCollection
    {
        return $this->mobs;
    }

    /**
     * @return Pnj[]|ArrayCollection|PersistentCollection
     */
    public function getPnjs(): ArrayCollection|array|PersistentCollection
    {
        return $this->pnjs;
    }
}
