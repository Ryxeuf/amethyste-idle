<?php

namespace App\Repository;

use App\Entity\App\GameEvent;
use App\Entity\App\ZoneBoss;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ZoneBoss>
 */
class ZoneBossRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZoneBoss::class);
    }

    public function findOneByGameEvent(GameEvent $gameEvent): ?ZoneBoss
    {
        return $this->findOneBy(['gameEvent' => $gameEvent]);
    }
}
