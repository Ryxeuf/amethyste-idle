<?php

namespace App\Repository;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\PlayerSeasonReward;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerSeasonReward>
 */
class PlayerSeasonRewardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerSeasonReward::class);
    }

    public function countForSeason(InfluenceSeason $season): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.season = :season')
            ->setParameter('season', $season)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return PlayerSeasonReward[] */
    public function findByPlayer(Player $player): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.player = :player')
            ->setParameter('player', $player)
            ->orderBy('r.awardedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
