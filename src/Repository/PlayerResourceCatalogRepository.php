<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerResourceCatalog;
use App\Entity\Game\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerResourceCatalog>
 */
class PlayerResourceCatalogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerResourceCatalog::class);
    }

    /** @return PlayerResourceCatalog[] */
    public function findByPlayer(Player $player): array
    {
        return $this->createQueryBuilder('prc')
            ->join('prc.item', 'i')->addSelect('i')
            ->andWhere('prc.player = :player')
            ->setParameter('player', $player)
            ->orderBy('prc.collectCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByPlayerAndItem(Player $player, Item $item): ?PlayerResourceCatalog
    {
        return $this->createQueryBuilder('prc')
            ->andWhere('prc.player = :player')
            ->andWhere('prc.item = :item')
            ->setParameter('player', $player)
            ->setParameter('item', $item)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getTotalCollected(Player $player): int
    {
        return (int) $this->createQueryBuilder('prc')
            ->select('SUM(prc.collectCount)')
            ->andWhere('prc.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
