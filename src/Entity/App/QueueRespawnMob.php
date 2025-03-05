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
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\Column(name: 'delay', type: 'integer')]
    private $delay;

    #[ORM\ManyToOne(targetEntity: Monster::class)]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'id')]
    private $monster;

    #[ORM\ManyToOne(targetEntity: Map::class, inversedBy: 'queueRespawnMobs')]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id')]
    private ?Map $map;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * @param int $delay
     */
    public function setDelay(int $delay): void
    {
        $this->delay = $delay;
    }

    /**
     * @return Monster
     */
    public function getMonster(): Monster
    {
        return $this->monster;
    }

    /**
     * @param Monster $monster
     */
    public function setMonster(Monster $monster): void
    {
        $this->monster = $monster;
    }

    /**
     * @return null|Map
     */
    public function getMap(): ?Map
    {
        return $this->map;
    }

    /**
     * @param null|Map $map
     */
    public function setMap(?Map $map): void
    {
        $this->map = $map;
    }
}