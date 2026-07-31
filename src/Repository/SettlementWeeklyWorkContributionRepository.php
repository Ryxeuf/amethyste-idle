<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\SettlementWeeklyWork;
use App\Entity\App\SettlementWeeklyWorkContribution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SettlementWeeklyWorkContribution>
 */
class SettlementWeeklyWorkContributionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SettlementWeeklyWorkContribution::class);
    }

    public function findOneFor(SettlementWeeklyWork $work, Player $player): ?SettlementWeeklyWorkContribution
    {
        return $this->findOneBy(['work' => $work, 'player' => $player]);
    }

    /**
     * Ce qu'un joueur a depose sur les chantiers d'une semaine donnee.
     *
     * Tous foyers confondus : le recap du lundi (RET-09) raconte ce que le
     * joueur a fait, pas ou il l'a fait. Un joueur qui a aide deux villes a
     * aide deux villes — les separer transformerait un merci en tableau.
     */
    public function sumUnitsForWeek(Player $player, string $weekKey): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.units), 0)')
            ->join('c.work', 'w')
            ->where('c.player = :player')
            ->andWhere('w.weekKey = :week')
            ->setParameter('player', $player)
            ->setParameter('week', $weekKey)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Les plus gros contributeurs, pour la mention publique.
     *
     * Bornee : nommer tout le monde ne nomme personne, et une liste de quarante
     * lignes sur l'ecran de zone noierait precisement la reconnaissance qu'elle
     * est censee porter.
     *
     * @return list<SettlementWeeklyWorkContribution>
     */
    public function findTopFor(SettlementWeeklyWork $work, int $limit = 5): array
    {
        /** @var list<SettlementWeeklyWorkContribution> $result */
        $result = $this->createQueryBuilder('c')
            ->where('c.work = :work')
            ->andWhere('c.units > 0')
            ->setParameter('work', $work)
            ->orderBy('c.units', 'DESC')
            ->addOrderBy('c.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $result;
    }
}
