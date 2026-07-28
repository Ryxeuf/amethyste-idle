<?php

namespace App\Repository;

use App\Entity\App\Guild;
use App\Entity\App\GuildMember;
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
     * Ce que les membres d'une guilde ont depose dans un foyer.
     *
     * Sert a repondre, sur l'ecran de zone, a la seule question qui interesse
     * vraiment un joueur de guilde : « qu'est-ce que **nous** avons bati ici ».
     * Un chiffre personnel seul laisse croire qu'on porte la ville a soi tout
     * seul ; le chiffre collectif est ce qui donne envie d'y revenir ensemble.
     */
    public function sumForGuild(Settlement $settlement, Guild $guild): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.grains), 0)')
            ->join(GuildMember::class, 'm', 'WITH', 'm.player = c.player')
            ->where('c.settlement = :settlement')
            ->andWhere('m.guild = :guild')
            ->setParameter('settlement', $settlement)
            ->setParameter('guild', $guild)
            ->getQuery()
            ->getSingleScalarResult();
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
