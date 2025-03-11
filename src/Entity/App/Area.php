<?php

namespace App\Entity\App;

use App\Entity\App\Traits\CoordinatesTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;


#[ORM\Entity()]
#[ORM\Table(name: "area")]
#[ORM\Index(name: "area_coordinates_idx", columns: ["coordinates"])]
class Area
{
    use CoordinatesTrait;
    use TimestampableEntity;

    function __toString()
    {
        return $this->getName();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "name", type: "string", length: 255)]
    private string $name;

    #[ORM\Column(name: "slug", type: "string", length: 255)]
    private string $slug;

    #[ORM\Column(name: "fullData", type: "json")]
    private string $fullData;

    #[ORM\ManyToOne(targetEntity: Map::class, inversedBy: "areas")]
    #[ORM\JoinColumn(name: "map_id", referencedColumnName: "id")]
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
        return json_decode($this->fullData, true);
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
}
