<?php

namespace App\Repository;

use App\Entity\Game\Quest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quest>
 */
class QuestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quest::class);
    }

    /**
     * Recupere les quetes d'un arc narratif, triees par `arcOrder` croissant.
     * Les quetes sans position (`arcOrder = null`) sont rejetees en fin de liste.
     *
     * @return Quest[]
     */
    public function findByStoryArc(string $storyArc): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.storyArc = :storyArc')
            ->setParameter('storyArc', $storyArc)
            ->orderBy('q.arcOrder', 'ASC')
            ->addOrderBy('q.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
