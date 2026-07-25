<?php

namespace App\Repository;

use App\Entity\App\GameEvent;
use App\Entity\App\InfluenceSeason;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameEvent>
 */
class GameEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameEvent::class);
    }

    /**
     * Beats d'une saison, ordonnes par position d'arc puis debut de fenetre (NAR-08).
     *
     * @return GameEvent[]
     */
    public function findBySeasonOrdered(InfluenceSeason $season): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.season = :season')
            ->setParameter('season', $season)
            ->orderBy('e.beatOrder', 'ASC')
            ->addOrderBy('e.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
