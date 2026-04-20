<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerBestiary;
use App\Entity\Game\Monster;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerBestiary>
 */
class PlayerBestiaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerBestiary::class);
    }

    /** @return PlayerBestiary[] */
    public function findByPlayer(Player $player): array
    {
        return $this->createQueryBuilder('pb')
            ->join('pb.monster', 'm')->addSelect('m')
            ->leftJoin('m.monsterItems', 'mi')->addSelect('mi')
            ->leftJoin('mi.item', 'i')->addSelect('i')
            ->andWhere('pb.player = :player')
            ->setParameter('player', $player)
            ->orderBy('pb.killCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByPlayerAndMonster(Player $player, Monster $monster): ?PlayerBestiary
    {
        return $this->createQueryBuilder('pb')
            ->andWhere('pb.player = :player')
            ->andWhere('pb.monster = :monster')
            ->setParameter('player', $player)
            ->setParameter('monster', $monster)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countDiscoveredMonsters(Player $player): int
    {
        return (int) $this->createQueryBuilder('pb')
            ->select('COUNT(pb.id)')
            ->andWhere('pb.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalKills(Player $player): int
    {
        return (int) $this->createQueryBuilder('pb')
            ->select('SUM(pb.killCount)')
            ->andWhere('pb.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Top joueurs par nombre total de mobs tues (all-time).
     *
     * @return array<int, array{player: Player, totalKills: int}>
     */
    public function findTopKillers(int $limit = 50): array
    {
        /** @var array<int, array{playerId: string|int, totalKills: string|int|null}> $rows */
        $rows = $this->createQueryBuilder('pb')
            ->select('IDENTITY(pb.player) AS playerId', 'SUM(pb.killCount) AS totalKills')
            ->groupBy('pb.player')
            ->having('SUM(pb.killCount) > 0')
            ->orderBy('totalKills', 'DESC')
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
                'totalKills' => (int) $row['totalKills'],
            ];
        }

        return $result;
    }

    /**
     * Rang d'un joueur dans le classement all-time par mobs tues (1-based).
     * Retourne null si le joueur n'a aucun kill enregistre.
     */
    public function getPlayerKillRank(Player $player): ?int
    {
        $totalKills = $this->getTotalKills($player);
        if ($totalKills <= 0) {
            return null;
        }

        $rows = $this->createQueryBuilder('pb')
            ->select('IDENTITY(pb.player) AS playerId', 'SUM(pb.killCount) AS totalKills')
            ->andWhere('pb.player != :player')
            ->groupBy('pb.player')
            ->having('SUM(pb.killCount) > :kills')
            ->setParameter('player', $player)
            ->setParameter('kills', $totalKills)
            ->getQuery()
            ->getArrayResult();

        return \count($rows) + 1;
    }
}
