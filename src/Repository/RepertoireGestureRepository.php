<?php

namespace App\Repository;

use App\Entity\App\RepertoireGesture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RepertoireGesture>
 */
class RepertoireGestureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RepertoireGesture::class);
    }

    /**
     * Les cles deja retrouvees, dans l'ordre de decouverte.
     *
     * @return list<string>
     */
    public function recoveredKeys(): array
    {
        return array_map(
            static fn (RepertoireGesture $gesture): string => $gesture->getGestureKey(),
            $this->findBy([], ['discoveryRank' => 'ASC']),
        );
    }

    public function recoveredCount(): int
    {
        return (int) $this->createQueryBuilder('g')->select('COUNT(g.id)')->getQuery()->getSingleScalarResult();
    }
}
