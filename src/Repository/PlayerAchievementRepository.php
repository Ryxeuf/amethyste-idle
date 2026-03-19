<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerAchievement;
use App\Entity\Game\Achievement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerAchievement>
 */
class PlayerAchievementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerAchievement::class);
    }

    public function findOneByPlayerAndAchievement(Player $player, Achievement $achievement): ?PlayerAchievement
    {
        return $this->findOneBy([
            'player' => $player,
            'achievement' => $achievement,
        ]);
    }

    /**
     * @return PlayerAchievement[]
     */
    public function findByPlayer(Player $player): array
    {
        return $this->findBy(['player' => $player]);
    }

    public function countCompletedByPlayer(Player $player): int
    {
        return (int) $this->createQueryBuilder('pa')
            ->select('COUNT(pa.id)')
            ->where('pa.player = :player')
            ->andWhere('pa.completedAt IS NOT NULL')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count distinct monster types killed by a player (from completed mob_kill achievements).
     */
    public function countDistinctMonstersKilled(Player $player): int
    {
        return (int) $this->createQueryBuilder('pa')
            ->select('COUNT(pa.id)')
            ->join('pa.achievement', 'a')
            ->where('pa.player = :player')
            ->andWhere('pa.completedAt IS NOT NULL')
            ->andWhere("JSON_EXTRACT(a.criteria, '$.type') = :type")
            ->andWhere("JSON_EXTRACT(a.criteria, '$.count') = :count")
            ->setParameter('player', $player)
            ->setParameter('type', 'mob_kill')
            ->setParameter('count', 10)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
