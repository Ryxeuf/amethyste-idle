<?php

namespace App\Repository;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\WeeklyChallenge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeeklyChallenge>
 */
class WeeklyChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeeklyChallenge::class);
    }

    /**
     * Defis dont la fenetre recouvre l'intervalle demande — c'est-a-dire ceux
     * qui seront jouables pendant la semaine qui commence.
     *
     * @return list<WeeklyChallenge>
     */
    public function findOverlapping(InfluenceSeason $season, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        /** @var list<WeeklyChallenge> $result */
        $result = $this->createQueryBuilder('wc')
            ->where('wc.season = :season')
            ->andWhere('wc.startsAt <= :to')
            ->andWhere('wc.endsAt >= :from')
            ->setParameter('season', $season)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('wc.startsAt', 'ASC')
            ->addOrderBy('wc.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Defis dont l'echeance tombe dans l'intervalle — la semaine qu'on cloture.
     *
     * Borne des deux cotes a dessein : la cloture ne doit relire que la semaine
     * ecoulee, pas tout l'historique de la saison a chaque lundi.
     *
     * @return list<WeeklyChallenge>
     */
    public function findEndingBetween(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        /** @var list<WeeklyChallenge> $result */
        $result = $this->createQueryBuilder('wc')
            ->where('wc.endsAt >= :from')
            ->andWhere('wc.endsAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('wc.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Plus haut numero de semaine deja pose sur la saison (0 si aucune).
     */
    public function maxWeekNumber(InfluenceSeason $season): int
    {
        $max = $this->createQueryBuilder('wc')
            ->select('MAX(wc.weekNumber)')
            ->where('wc.season = :season')
            ->setParameter('season', $season)
            ->getQuery()
            ->getSingleScalarResult();

        return \is_numeric($max) ? (int) $max : 0;
    }
}
