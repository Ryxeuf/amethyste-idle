<?php

namespace App\Entity\App;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'world')]
class World
{
    use TimestampableEntity;

    public function __construct()
    {
        $this->maps = new ArrayCollection();
    }

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

    #[ORM\OneToMany(targetEntity: Map::class, mappedBy: 'world')]
    private Collection $maps;

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

    public function getMaps(): Collection
    {
        return $this->maps;
    }

    public function addMap(Map $map): void
    {
        $this->maps->add($map);
    }

    public function removeMap(Map $map): void
    {
        $this->maps->removeElement($map);
    }
}
