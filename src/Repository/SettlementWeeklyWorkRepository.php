<?php

namespace App\Repository;

use App\Entity\App\Settlement;
use App\Entity\App\SettlementWeeklyWork;
use App\Entity\App\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SettlementWeeklyWork>
 */
class SettlementWeeklyWorkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SettlementWeeklyWork::class);
    }

    public function findOneFor(Settlement $settlement, string $weekKey): ?SettlementWeeklyWork
    {
        return $this->findOneBy(['settlement' => $settlement, 'weekKey' => $weekKey]);
    }

    /**
     * Le chantier courant d'une zone, en une seule lecture.
     *
     * L'ecran de zone n'a pas a connaitre le foyer pour afficher son chantier :
     * il connait la zone, et c'est tout ce qu'il devrait avoir a connaitre.
     */
    public function findCurrentForZone(Zone $zone, string $weekKey): ?SettlementWeeklyWork
    {
        return $this->createQueryBuilder('w')
            ->join('w.settlement', 's')
            ->where('s.zone = :zone')
            ->andWhere('w.weekKey = :week')
            ->setParameter('zone', $zone)
            ->setParameter('week', $weekKey)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
