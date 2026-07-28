<?php

namespace App\Repository;

use App\Entity\App\WorldLoadSnapshot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorldLoadSnapshot>
 */
class WorldLoadSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorldLoadSnapshot::class);
    }

    public function findOneByDay(\DateTimeImmutable $day): ?WorldLoadSnapshot
    {
        return $this->findOneBy(['day' => $day->setTime(0, 0, 0)]);
    }

    /**
     * Dernier instantane pris strictement avant le jour donne.
     *
     * Sert de base de calcul a la depense du jour : on ne suppose pas que la
     * veille existe. Un serveur arrete trois jours reprend sur le dernier
     * instantane connu plutot que de compter zero.
     */
    public function findLatestBefore(\DateTimeImmutable $day): ?WorldLoadSnapshot
    {
        return $this->createQueryBuilder('s')
            ->where('s.day < :day')
            ->setParameter('day', $day->setTime(0, 0, 0))
            ->orderBy('s.day', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Les instantanes des `$days` derniers jours, du plus recent au plus ancien.
     *
     * @return list<WorldLoadSnapshot>
     */
    public function findRecent(int $days): array
    {
        /** @var list<WorldLoadSnapshot> $result */
        $result = $this->createQueryBuilder('s')
            ->orderBy('s.day', 'DESC')
            ->setMaxResults(max(1, $days))
            ->getQuery()
            ->getResult();

        return $result;
    }
}
