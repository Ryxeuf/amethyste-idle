<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerJournalEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerJournalEntry>
 */
class PlayerJournalEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerJournalEntry::class);
    }

    /**
     * @return PlayerJournalEntry[]
     */
    public function findByPlayer(Player $player, ?string $type = null, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('j')
            ->andWhere('j.player = :player')
            ->setParameter('player', $player)
            ->orderBy('j.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($type !== null) {
            $qb->andWhere('j.type = :type')
                ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByPlayer(Player $player, ?string $type = null): int
    {
        $qb = $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->andWhere('j.player = :player')
            ->setParameter('player', $player);

        if ($type !== null) {
            $qb->andWhere('j.type = :type')
                ->setParameter('type', $type);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function enforceEntryLimit(Player $player): void
    {
        $count = $this->countByPlayer($player);

        if ($count <= PlayerJournalEntry::MAX_ENTRIES_PER_PLAYER) {
            return;
        }

        $excess = $count - PlayerJournalEntry::MAX_ENTRIES_PER_PLAYER;

        $oldestIds = $this->createQueryBuilder('j')
            ->select('j.id')
            ->andWhere('j.player = :player')
            ->setParameter('player', $player)
            ->orderBy('j.createdAt', 'ASC')
            ->setMaxResults($excess)
            ->getQuery()
            ->getSingleColumnResult();

        if (\count($oldestIds) > 0) {
            $this->createQueryBuilder('j')
                ->delete()
                ->andWhere('j.id IN (:ids)')
                ->setParameter('ids', $oldestIds)
                ->getQuery()
                ->execute();
        }
    }
}
