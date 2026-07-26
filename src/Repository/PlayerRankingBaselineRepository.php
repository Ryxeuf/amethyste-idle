<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerRankingBaseline;
use App\Enum\RankingTab;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerRankingBaseline>
 */
class PlayerRankingBaselineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerRankingBaseline::class);
    }

    /**
     * References d'un onglet, indexees par identifiant de joueur.
     *
     * Le classement soustrait la reference a des dizaines de milliers de lignes
     * agregees : une carte en memoire evite une requete par joueur.
     *
     * @return array<int, int>
     */
    public function mapByPlayerId(RankingTab $tab): array
    {
        /** @var array<int, array{playerId: int, baselineValue: string|int}> $rows */
        $rows = $this->createQueryBuilder('b')
            ->select('IDENTITY(b.player) AS playerId', 'b.value AS baselineValue')
            ->andWhere('b.tab = :tab')
            ->setParameter('tab', $tab->value)
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['playerId']] = (int) $row['baselineValue'];
        }

        return $map;
    }

    /**
     * References d'un onglet, indexees par identifiant de joueur, sous forme
     * d'entites — pour la reecriture a la cloture.
     *
     * @return array<int, PlayerRankingBaseline>
     */
    public function findIndexedByPlayerId(RankingTab $tab): array
    {
        $indexed = [];
        foreach ($this->findBy(['tab' => $tab]) as $baseline) {
            $indexed[$baseline->getPlayer()->getId()] = $baseline;
        }

        return $indexed;
    }

    public function findForPlayer(Player $player, RankingTab $tab): ?PlayerRankingBaseline
    {
        return $this->findOneBy(['player' => $player, 'tab' => $tab]);
    }

    public function valueForPlayer(Player $player, RankingTab $tab): int
    {
        return $this->findForPlayer($player, $tab)?->getValue() ?? 0;
    }
}
