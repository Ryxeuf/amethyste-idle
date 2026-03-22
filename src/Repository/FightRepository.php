<?php

namespace App\Repository;

use App\Entity\App\Fight;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fight>
 */
class FightRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fight::class);
    }

    public function findWithRelations(int $id): ?Fight
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.mobs', 'm')->addSelect('m')
            ->leftJoin('m.monster', 'mon')->addSelect('mon')
            ->leftJoin('mon.spells', 'sp')->addSelect('sp')
            ->leftJoin('mon.attack', 'att')->addSelect('att')
            ->leftJoin('mon.monsterItems', 'mi')->addSelect('mi')
            ->leftJoin('mi.item', 'it')->addSelect('it')
            ->leftJoin('f.players', 'p')->addSelect('p')
            ->where('f.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
