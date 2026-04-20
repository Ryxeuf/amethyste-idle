<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerVisitedRegion;
use App\Entity\App\Region;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerVisitedRegion>
 */
class PlayerVisitedRegionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerVisitedRegion::class);
    }

    public function hasVisited(Player $player, Region $region): bool
    {
        $count = (int) $this->createQueryBuilder('pvr')
            ->select('COUNT(pvr.id)')
            ->andWhere('pvr.player = :player')
            ->andWhere('pvr.region = :region')
            ->setParameter('player', $player)
            ->setParameter('region', $region)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @return int[] Region IDs visited by the player.
     */
    public function findVisitedRegionIds(Player $player): array
    {
        $rows = $this->createQueryBuilder('pvr')
            ->select('IDENTITY(pvr.region) AS regionId')
            ->andWhere('pvr.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row) => (int) $row['regionId'], $rows);
    }
}
