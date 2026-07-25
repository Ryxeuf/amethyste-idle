<?php

namespace App\Repository;

use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupDungeonRun>
 */
class GroupDungeonRunRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupDungeonRun::class);
    }

    /**
     * Run de groupe actif (en formation ou en cours) auquel le joueur participe
     * (comme leader ou membre), ou null.
     */
    public function findActiveForPlayer(Player $player): ?GroupDungeonRun
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.members', 'm')
            ->andWhere('r.status IN (:active)')
            ->andWhere('r.leader = :player OR m.player = :player')
            ->setParameter('active', [GroupDungeonRun::STATUS_FORMING, GroupDungeonRun::STATUS_IN_PROGRESS])
            ->setParameter('player', $player)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
