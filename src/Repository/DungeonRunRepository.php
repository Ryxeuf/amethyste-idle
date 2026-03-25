<?php

namespace App\Repository;

use App\Entity\App\DungeonRun;
use App\Entity\App\Player;
use App\Entity\Game\Dungeon;
use App\Enum\DungeonDifficulty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DungeonRun>
 */
class DungeonRunRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DungeonRun::class);
    }

    public function findActiveRun(Player $player): ?DungeonRun
    {
        return $this->createQueryBuilder('dr')
            ->join('dr.dungeon', 'd')->addSelect('d')
            ->where('dr.player = :player')
            ->andWhere('dr.completedAt IS NULL')
            ->setParameter('player', $player)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLastCompletedRun(Player $player, Dungeon $dungeon, DungeonDifficulty $difficulty): ?DungeonRun
    {
        return $this->createQueryBuilder('dr')
            ->where('dr.player = :player')
            ->andWhere('dr.dungeon = :dungeon')
            ->andWhere('dr.difficulty = :difficulty')
            ->andWhere('dr.completedAt IS NOT NULL')
            ->setParameter('player', $player)
            ->setParameter('dungeon', $dungeon)
            ->setParameter('difficulty', $difficulty)
            ->orderBy('dr.completedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return DungeonRun[]
     */
    public function findPlayerHistory(Player $player, int $limit = 20): array
    {
        return $this->createQueryBuilder('dr')
            ->join('dr.dungeon', 'd')->addSelect('d')
            ->where('dr.player = :player')
            ->andWhere('dr.completedAt IS NOT NULL')
            ->setParameter('player', $player)
            ->orderBy('dr.completedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
