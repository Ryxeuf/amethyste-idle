<?php

namespace App\Repository;

use App\Entity\App\GroupDungeonClear;
use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Player;
use App\Entity\Game\Dungeon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupDungeonClear>
 */
class GroupDungeonClearRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupDungeonClear::class);
    }

    /**
     * Nombre de reussites d'un donjon par un joueur depuis un instant donne
     * (fenetre glissante des recompenses decroissantes).
     */
    public function countRecentClears(Player $player, Dungeon $dungeon, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.player = :player')
            ->andWhere('c.dungeon = :dungeon')
            ->andWhere('c.clearedAt >= :since')
            ->setParameter('player', $player)
            ->setParameter('dungeon', $dungeon)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findForRunAndPlayer(GroupDungeonRun $run, Player $player): ?GroupDungeonClear
    {
        return $this->findOneBy(['run' => $run, 'player' => $player]);
    }
}
