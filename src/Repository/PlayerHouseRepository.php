<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\App\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerHouse>
 */
class PlayerHouseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerHouse::class);
    }

    public function findForOwner(Player $owner): ?PlayerHouse
    {
        return $this->findOneBy(['owner' => $owner]);
    }

    /**
     * Demeures dont le loyer est echu (HOU-04).
     *
     * @return PlayerHouse[]
     */
    public function findWithRentDue(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('h')
            ->join('h.owner', 'o')->addSelect('o')
            ->where('h.rentDueAt <= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    /**
     * Le voisinage : les demeures d'une zone, base de la visite (HOU-03).
     *
     * @return PlayerHouse[]
     */
    public function findInZone(Zone $zone, int $limit = 50): array
    {
        return $this->createQueryBuilder('h')
            ->join('h.owner', 'o')->addSelect('o')
            ->where('h.zone = :zone')
            ->setParameter('zone', $zone)
            ->orderBy('h.purchasedAt', 'ASC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * Le nombre de demeures d'une zone (FOY-18) — un COUNT dedie : reutiliser
     * `findInZone` sous-compterait une Metropole a cause de son plafond de 50.
     */
    public function countInZone(Zone $zone): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->where('h.zone = :zone')
            ->setParameter('zone', $zone)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
