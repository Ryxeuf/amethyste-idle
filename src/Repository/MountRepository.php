<?php

namespace App\Repository;

use App\Entity\Game\Monster;
use App\Entity\Game\Mount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mount>
 */
class MountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mount::class);
    }

    /**
     * Returns enabled mounts whose `dropMonster` matches the given monster
     * and whose `dropProbability` is strictly positive.
     *
     * @return list<Mount>
     */
    public function findEnabledByDropMonster(Monster $monster): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.dropMonster = :monster')
            ->andWhere('m.enabled = true')
            ->andWhere('m.dropProbability > 0')
            ->setParameter('monster', $monster)
            ->getQuery()
            ->getResult();
    }
}
