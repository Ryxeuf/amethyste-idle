<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\Settlement;
use App\Entity\App\SettlementContribution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SettlementContribution>
 */
class SettlementContributionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SettlementContribution::class);
    }

    public function findOneFor(Settlement $settlement, Player $player): ?SettlementContribution
    {
        return $this->findOneBy(['settlement' => $settlement, 'player' => $player]);
    }

    /**
     * Les plus gros contributeurs d'un foyer — l'entree de l'ecran de zone
     * (FOY-04) et des credits narratifs de cite.
     *
     * @return list<SettlementContribution>
     */
    public function findTopContributors(Settlement $settlement, int $limit = 10): array
    {
        /** @var list<SettlementContribution> $result */
        $result = $this->createQueryBuilder('c')
            ->where('c.settlement = :settlement')
            ->andWhere('c.grains > 0')
            ->setParameter('settlement', $settlement)
            ->orderBy('c.grains', 'DESC')
            ->addOrderBy('c.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $result;
    }
}
