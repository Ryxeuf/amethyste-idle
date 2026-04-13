<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PrivateMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrivateMessage>
 */
class PrivateMessageRepository extends ServiceEntityRepository
{
    private const MAX_MESSAGES_PER_PLAYER = 100;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrivateMessage::class);
    }

    /** @return PrivateMessage[] */
    public function findInboxForPlayer(Player $player, int $limit = 50): array
    {
        return $this->createQueryBuilder('pm')
            ->join('pm.sender', 's')->addSelect('s')
            ->where('pm.receiver = :player')
            ->setParameter('player', $player)
            ->orderBy('pm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return PrivateMessage[] */
    public function findSentForPlayer(Player $player, int $limit = 50): array
    {
        return $this->createQueryBuilder('pm')
            ->join('pm.receiver', 'r')->addSelect('r')
            ->where('pm.sender = :player')
            ->setParameter('player', $player)
            ->orderBy('pm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnreadForPlayer(Player $player): int
    {
        return (int) $this->createQueryBuilder('pm')
            ->select('COUNT(pm.id)')
            ->where('pm.receiver = :player')
            ->andWhere('pm.readAt IS NULL')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function enforceLimit(Player $player): void
    {
        $count = (int) $this->createQueryBuilder('pm')
            ->select('COUNT(pm.id)')
            ->where('pm.receiver = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();

        if ($count <= self::MAX_MESSAGES_PER_PLAYER) {
            return;
        }

        $excess = $count - self::MAX_MESSAGES_PER_PLAYER;

        $oldestIds = $this->createQueryBuilder('pm')
            ->select('pm.id')
            ->where('pm.receiver = :player')
            ->setParameter('player', $player)
            ->orderBy('pm.createdAt', 'ASC')
            ->setMaxResults($excess)
            ->getQuery()
            ->getSingleColumnResult();

        if (\count($oldestIds) > 0) {
            $this->createQueryBuilder('pm')
                ->delete()
                ->where('pm.id IN (:ids)')
                ->setParameter('ids', $oldestIds)
                ->getQuery()
                ->execute();
        }
    }
}
