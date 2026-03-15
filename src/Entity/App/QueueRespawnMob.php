<?php

namespace App\Entity\App;

use App\Entity\App\Traits\CoordinatesTrait;
use App\Entity\Game\Monster;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'queue_respawn_mob')]
#[ORM\Entity]
class QueueRespawnMob
{
    use CoordinatesTrait;
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'delay', type: 'integer')]
    private $delay;

    #[ORM\ManyToOne(targetEntity: Monster::class)]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'id')]
    private $monster;

    #[ORM\ManyToOne(targetEntity: Map::class, inversedBy: 'queueRespawnMobs')]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id')]
    private ?Map $map;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function setDelay(int $delay): void
    {
        $this->delay = $delay;
    }

    public function getMonster(): Monster
    {
        return $this->monster;
    }

    public function setMonster(Monster $monster): void
    {
        $this->monster = $monster;
    }

    public function getMap(): ?Map
    {
        return $this->map;
    }

    public function setMap(?Map $map): void
    {
        $this->map = $map;
    }
}
