<?php

namespace App\Repository;

use App\Entity\App\GilsSupplySnapshot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GilsSupplySnapshot>
 */
class GilsSupplySnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GilsSupplySnapshot::class);
    }

    public function latest(): ?GilsSupplySnapshot
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.capturedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Releve le plus recent anterieur a une date.
     *
     * Sert a comparer « maintenant » a « il y a N jours » sans supposer qu'un
     * releve existe pile a cette date : une journee sans tache planifiee ne doit
     * pas rendre la comparaison impossible, seulement moins precise.
     */
    public function latestBefore(\DateTimeImmutable $before): ?GilsSupplySnapshot
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.capturedAt <= :before')
            ->setParameter('before', $before)
            ->orderBy('s.capturedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Releves des N derniers jours, du plus ancien au plus recent.
     *
     * @return list<GilsSupplySnapshot>
     */
    public function since(\DateTimeImmutable $since): array
    {
        /** @var list<GilsSupplySnapshot> $rows */
        $rows = $this->createQueryBuilder('s')
            ->andWhere('s.capturedAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('s.capturedAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }
}
