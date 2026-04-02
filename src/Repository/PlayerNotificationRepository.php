<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerNotification>
 */
class PlayerNotificationRepository extends ServiceEntityRepository
{
    private const MAX_NOTIFICATIONS = 50;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerNotification::class);
    }

    /**
     * @return PlayerNotification[]
     */
    public function findRecentByPlayer(Player $player, int $limit = 30): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.player = :player')
            ->setParameter('player', $player)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnreadByPlayer(Player $player): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.player = :player')
            ->andWhere('n.readAt IS NULL')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllAsReadForPlayer(Player $player): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.readAt', ':now')
            ->where('n.player = :player')
            ->andWhere('n.readAt IS NULL')
            ->setParameter('now', new \DateTime())
            ->setParameter('player', $player)
            ->getQuery()
            ->execute();
    }

    public function pruneOldNotifications(Player $player): void
    {
        $ids = $this->createQueryBuilder('n')
            ->select('n.id')
            ->where('n.player = :player')
            ->setParameter('player', $player)
            ->orderBy('n.createdAt', 'DESC')
            ->setFirstResult(self::MAX_NOTIFICATIONS)
            ->getQuery()
            ->getSingleColumnResult();

        if (\count($ids) === 0) {
            return;
        }

        $this->createQueryBuilder('n')
            ->delete()
            ->where('n.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }
}
