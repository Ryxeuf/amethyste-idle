<?php

namespace App\Repository;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mob>
 */
class MobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mob::class);
    }

    /**
     * @return Mob[]
     */
    public function findByMapWithMonster(Map $map): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.monster', 'mon')->addSelect('mon')
            ->leftJoin('mon.spells', 'sp')->addSelect('sp')
            ->leftJoin('mon.attack', 'att')->addSelect('att')
            ->leftJoin('mon.monsterItems', 'mi')->addSelect('mi')
            ->leftJoin('mi.item', 'it')->addSelect('it')
            ->where('m.map = :map')
            ->setParameter('map', $map)
            ->getQuery()
            ->getResult();
    }
}
