<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerQuestCompleted;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerQuestCompleted>
 */
class PlayerQuestCompletedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerQuestCompleted::class);
    }

    public function countQuestsCompleted(Player $player): int
    {
        return (int) $this->createQueryBuilder('pqc')
            ->select('COUNT(pqc.id)')
            ->andWhere('pqc.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Top joueurs par nombre de quetes completees (all-time).
     *
     * @return array<int, array{player: Player, totalQuests: int}>
     */
    public function findTopQuestCompleters(int $limit = 50): array
    {
        /** @var array<int, array{playerId: string|int, totalQuests: string|int|null}> $rows */
        $rows = $this->createQueryBuilder('pqc')
            ->select('IDENTITY(pqc.player) AS playerId', 'COUNT(pqc.id) AS totalQuests')
            ->groupBy('pqc.player')
            ->having('COUNT(pqc.id) > 0')
            ->orderBy('totalQuests', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        if ([] === $rows) {
            return [];
        }

        $playerIds = array_map(static fn (array $row) => (int) $row['playerId'], $rows);
        $players = $this->getEntityManager()->getRepository(Player::class)->findBy(['id' => $playerIds]);
        $playerById = [];
        foreach ($players as $player) {
            $playerById[$player->getId()] = $player;
        }

        $result = [];
        foreach ($rows as $row) {
            $playerId = (int) $row['playerId'];
            if (!isset($playerById[$playerId])) {
                continue;
            }
            $result[] = [
                'player' => $playerById[$playerId],
                'totalQuests' => (int) $row['totalQuests'],
            ];
        }

        return $result;
    }

    /**
     * Rang d'un joueur dans le classement all-time par quetes completees (1-based).
     * Retourne null si le joueur n'a aucune quete completee.
     */
    public function getPlayerQuestRank(Player $player): ?int
    {
        $total = $this->countQuestsCompleted($player);
        if ($total <= 0) {
            return null;
        }

        $rows = $this->createQueryBuilder('pqc')
            ->select('IDENTITY(pqc.player) AS playerId', 'COUNT(pqc.id) AS totalQuests')
            ->andWhere('pqc.player != :player')
            ->groupBy('pqc.player')
            ->having('COUNT(pqc.id) > :total')
            ->setParameter('player', $player)
            ->setParameter('total', $total)
            ->getQuery()
            ->getArrayResult();

        return \count($rows) + 1;
    }
}
