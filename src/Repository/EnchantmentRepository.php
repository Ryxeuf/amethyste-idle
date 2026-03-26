<?php

namespace App\Repository;

use App\Entity\App\Enchantment;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enchantment>
 */
class EnchantmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enchantment::class);
    }

    public function findActiveByPlayerItem(PlayerItem $playerItem): ?Enchantment
    {
        return $this->createQueryBuilder('e')
            ->where('e.playerItem = :playerItem')
            ->andWhere('e.expiresAt > :now')
            ->setParameter('playerItem', $playerItem)
            ->setParameter('now', new \DateTime())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Enchantment[]
     */
    public function findActiveByPlayer(Player $player): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.playerItem', 'pi')
            ->join('pi.inventory', 'inv')
            ->where('inv.player = :player')
            ->andWhere('e.expiresAt > :now')
            ->setParameter('player', $player)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function removeExpired(): int
    {
        return $this->createQueryBuilder('e')
            ->delete()
            ->where('e.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
