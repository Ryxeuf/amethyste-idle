<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerReport;
use App\Enum\PlayerReportStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerReport>
 */
class PlayerReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerReport::class);
    }

    /**
     * Compte les rapports soumis par $reporter contre $reported depuis $since.
     */
    public function countRecentReports(Player $reporter, Player $reported, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.reporter = :reporter')
            ->andWhere('r.reportedPlayer = :reported')
            ->andWhere('r.createdAt >= :since')
            ->setParameter('reporter', $reporter)
            ->setParameter('reported', $reported)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return PlayerReport[]
     */
    public function findForAdmin(?PlayerReportStatus $status, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.reporter', 'rp')->addSelect('rp')
            ->leftJoin('r.reportedPlayer', 'tp')->addSelect('tp')
            ->orderBy('r.createdAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('r.status = :status')->setParameter('status', $status);
        }

        return $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countForAdmin(?PlayerReportStatus $status): int
    {
        $qb = $this->createQueryBuilder('r')->select('COUNT(r.id)');
        if ($status !== null) {
            $qb->andWhere('r.status = :status')->setParameter('status', $status);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
