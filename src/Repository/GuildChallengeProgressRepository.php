<?php

namespace App\Repository;

use App\Entity\App\GuildChallengeProgress;
use App\Entity\App\WeeklyChallenge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GuildChallengeProgress>
 */
class GuildChallengeProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuildChallengeProgress::class);
    }

    /**
     * Toutes les progressions de guilde attachees aux defis donnes.
     *
     * @param list<WeeklyChallenge> $challenges
     *
     * @return list<GuildChallengeProgress>
     */
    public function findForChallenges(array $challenges): array
    {
        if ($challenges === []) {
            return [];
        }

        /** @var list<GuildChallengeProgress> $result */
        $result = $this->createQueryBuilder('gcp')
            ->where('gcp.challenge IN (:challenges)')
            ->setParameter('challenges', $challenges)
            ->orderBy('gcp.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
