<?php

namespace App\Repository;

use App\Entity\App\Mob;
use App\Entity\App\Zone;
use App\Entity\Game\Monster;
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
     * Mobs rencontrables d'une zone (vivants, hors combat), Monster hydrate.
     * Pivot PBBG (ZON-08) : vivier des rencontres de l'action Explorer.
     *
     * @return list<Mob>
     */
    public function findAvailableInZone(Zone $zone, int $limit = 25): array
    {
        return $this->createQueryBuilder('mob')
            ->join('mob.monster', 'monster')->addSelect('monster')
            ->andWhere('mob.zone = :zone')
            ->andWhere('mob.fight IS NULL')
            ->andWhere('mob.life > 0')
            ->setParameter('zone', $zone)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Un mob rencontrable d'un monstre precis dans une zone (vivant, hors
     * combat), Monster hydrate. Pivot PBBG (ZON-09) : proie ciblee par
     * l'action Chasser. Retourne null si la proie s'est dispersee.
     */
    public function findAvailableInZoneForMonster(Zone $zone, Monster $monster): ?Mob
    {
        return $this->createQueryBuilder('mob')
            ->join('mob.monster', 'monster')->addSelect('monster')
            ->andWhere('mob.zone = :zone')
            ->andWhere('mob.monster = :monster')
            ->andWhere('mob.fight IS NULL')
            ->andWhere('mob.life > 0')
            ->setParameter('zone', $zone)
            ->setParameter('monster', $monster)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
