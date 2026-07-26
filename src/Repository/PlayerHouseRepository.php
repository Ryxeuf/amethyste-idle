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
}
