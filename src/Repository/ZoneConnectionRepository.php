<?php

namespace App\Repository;

use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ZoneConnection>
 */
class ZoneConnectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZoneConnection::class);
    }

    /**
     * Liaisons sortantes utilisables depuis une zone (liaison ET zone cible activees).
     *
     * @return list<ZoneConnection>
     */
    public function findEnabledFrom(Zone $zone): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.toZone', 'target')
            ->andWhere('c.fromZone = :zone')
            ->andWhere('c.enabled = true')
            ->andWhere('target.enabled = true')
            ->setParameter('zone', $zone)
            ->orderBy('c.travelSeconds', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Toutes les liaisons sortantes, desactivees comprises.
     *
     * Reservee a la vue MJ : c'est justement le contenu en preparation, celui
     * qu'aucun joueur ne voit, qu'un maitre du jeu doit pouvoir aller regarder.
     *
     * @return list<ZoneConnection>
     */
    public function findAllFrom(Zone $zone): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.toZone', 'target')
            ->andWhere('c.fromZone = :zone')
            ->setParameter('zone', $zone)
            ->orderBy('c.travelSeconds', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
