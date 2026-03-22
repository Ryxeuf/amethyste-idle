<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerBestiary;
use App\Entity\Game\Monster;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerBestiary>
 */
class PlayerBestiaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerBestiary::class);
    }

    /** @return PlayerBestiary[] */
    public function findByPlayer(Player $player): array
    {
        return $this->createQueryBuilder('pb')
            ->join('pb.monster', 'm')->addSelect('m')
            ->leftJoin('m.monsterItems', 'mi')->addSelect('mi')
            ->leftJoin('mi.item', 'i')->addSelect('i')
            ->andWhere('pb.player = :player')
            ->setParameter('player', $player)
            ->orderBy('pb.killCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByPlayerAndMonster(Player $player, Monster $monster): ?PlayerBestiary
    {
        return $this->createQueryBuilder('pb')
            ->andWhere('pb.player = :player')
            ->andWhere('pb.monster = :monster')
            ->setParameter('player', $player)
            ->setParameter('monster', $monster)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countDiscoveredMonsters(Player $player): int
    {
        return (int) $this->createQueryBuilder('pb')
            ->select('COUNT(pb.id)')
            ->andWhere('pb.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalKills(Player $player): int
    {
        return (int) $this->createQueryBuilder('pb')
            ->select('SUM(pb.killCount)')
            ->andWhere('pb.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
