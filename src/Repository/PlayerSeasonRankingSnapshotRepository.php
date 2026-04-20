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

    /**
     * @return InfluenceSeason[] Saisons distinctes ayant au moins un snapshot, triees par seasonNumber DESC.
     */
    public function findArchivedSeasons(int $limit = 10): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('season')
            ->from(InfluenceSeason::class, 'season')
            ->where('EXISTS (SELECT 1 FROM '.PlayerSeasonRankingSnapshot::class.' snap WHERE snap.season = season)')
            ->orderBy('season.seasonNumber', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PlayerSeasonRankingSnapshot[] Top-N (default 3) pour (saison, onglet), trie par rang ASC.
     */
    public function findPodiumBySeasonAndTab(InfluenceSeason $season, RankingTab $tab, int $limit = 3): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.season = :season')
            ->andWhere('s.tab = :tab')
            ->setParameter('season', $season)
            ->setParameter('tab', $tab->value)
            ->orderBy('s.rank', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
