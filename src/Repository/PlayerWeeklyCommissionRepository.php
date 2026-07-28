<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerWeeklyCommission;
use App\Enum\WeeklyCommissionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerWeeklyCommission>
 */
class PlayerWeeklyCommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerWeeklyCommission::class);
    }

    public function findOneForWeek(Player $player, string $weekKey): ?PlayerWeeklyCommission
    {
        return $this->findOneBy(['player' => $player, 'weekKey' => $weekKey]);
    }

    public function findCurrent(Player $player, string $weekKey): ?PlayerWeeklyCommission
    {
        return $this->findOneBy([
            'player' => $player,
            'weekKey' => $weekKey,
            'status' => WeeklyCommissionStatus::Open,
        ]);
    }

    /**
     * Commissions restees ouvertes sur les semaines precedentes — a expirer.
     *
     * @return list<PlayerWeeklyCommission>
     */
    public function findStaleOpen(string $currentWeekKey): array
    {
        /** @var list<PlayerWeeklyCommission> $result */
        $result = $this->createQueryBuilder('c')
            ->where('c.status = :open')
            ->andWhere('c.weekKey < :week')
            ->setParameter('open', WeeklyCommissionStatus::Open)
            ->setParameter('week', $currentWeekKey)
            ->getQuery()
            ->getResult();

        return $result;
    }
}
