<?php

namespace App\Repository;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementRank;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Settlement>
 */
class SettlementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Settlement::class);
    }

    /**
     * Foyer d'une zone, ou `null` si la zone n'en a pas.
     *
     * `null` n'est pas une anomalie : Lumiere, les Jardins et la Cite ensevelie
     * n'ont **jamais** de foyer, par conception.
     */
    public function findOneByZone(Zone $zone): ?Settlement
    {
        return $this->findOneBy(['zone' => $zone]);
    }

    /**
     * Tous les foyers, du plus fourni au moins fourni.
     *
     * @return list<Settlement>
     */
    public function findAllRanked(): array
    {
        /** @var list<Settlement> $result */
        $result = $this->createQueryBuilder('s')
            ->addSelect('(s.sedimentTrade + s.sedimentWar + s.sedimentLore + s.sedimentRite) AS HIDDEN total')
            ->orderBy('total', 'DESC')
            ->addOrderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Foyers ayant atteint un rang donne — l'entree du quota de Crue (FOY-08).
     *
     * @return list<Settlement>
     */
    public function findByRank(SettlementRank $rank): array
    {
        /** @var list<Settlement> $result */
        $result = $this->createQueryBuilder('s')
            ->where('s.rank = :rank')
            ->setParameter('rank', $rank)
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
