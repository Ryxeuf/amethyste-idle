<?php

namespace App\Repository;

use App\Entity\App\WeeklyOutcrop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeeklyOutcrop>
 */
class WeeklyOutcropRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeeklyOutcrop::class);
    }

    public function findForWeek(string $weekKey): ?WeeklyOutcrop
    {
        return $this->findOneBy(['weekKey' => $weekKey]);
    }

    /**
     * Le dernier affleurement **avant** cette semaine.
     *
     * Sert a la regle de non-repetition : jamais deux semaines de suite la meme
     * zone. Sans elle, un tirage malchanceux pourrait immobiliser la rotation
     * sur une seule region et retirer a la brique la seule chose qu'elle
     * produit — une raison de bouger.
     */
    public function findPrevious(string $weekKey): ?WeeklyOutcrop
    {
        return $this->createQueryBuilder('o')
            ->where('o.weekKey < :week')
            ->setParameter('week', $weekKey)
            ->orderBy('o.weekKey', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
