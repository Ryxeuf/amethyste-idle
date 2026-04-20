<?php

namespace App\Repository;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\PlayerSeasonRankingSnapshot;
use App\Enum\RankingTab;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerSeasonRankingSnapshot>
 */
class PlayerSeasonRankingSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerSeasonRankingSnapshot::class);
    }

    /** @return PlayerSeasonRankingSnapshot[] */
    public function findBySeasonAndTab(InfluenceSeason $season, RankingTab $tab): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.season = :season')
            ->andWhere('s.tab = :tab')
            ->setParameter('season', $season)
            ->setParameter('tab', $tab->value)
            ->orderBy('s.rank', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countForSeason(InfluenceSeason $season): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.season = :season')
            ->setParameter('season', $season)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
