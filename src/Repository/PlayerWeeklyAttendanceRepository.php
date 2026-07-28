<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerWeeklyAttendance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerWeeklyAttendance>
 */
class PlayerWeeklyAttendanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerWeeklyAttendance::class);
    }

    public function findOneForWeek(Player $player, string $weekKey): ?PlayerWeeklyAttendance
    {
        return $this->findOneBy(['player' => $player, 'weekKey' => $weekKey]);
    }
}
