<?php

namespace App\Repository;

use App\Entity\App\GardenPlot;
use App\Entity\App\PlayerHouse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GardenPlot>
 */
class GardenPlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GardenPlot::class);
    }

    /**
     * @return GardenPlot[]
     */
    public function findForHouse(PlayerHouse $house): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.crop', 'c')->addSelect('c')
            ->where('p.house = :house')
            ->setParameter('house', $house)
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
