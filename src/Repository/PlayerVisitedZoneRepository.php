<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerVisitedZone;
use App\Entity\App\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerVisitedZone>
 */
class PlayerVisitedZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerVisitedZone::class);
    }

    public function hasVisited(Player $player, Zone $zone): bool
    {
        return null !== $this->findOneBy(['player' => $player, 'zone' => $zone]);
    }

    /**
     * @return list<int>
     */
    public function findVisitedZoneIds(Player $player): array
    {
        /** @var list<array{zoneId: int}> $rows */
        $rows = $this->createQueryBuilder('v')
            ->select('IDENTITY(v.zone) AS zoneId')
            ->andWhere('v.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['zoneId'], $rows);
    }
}
