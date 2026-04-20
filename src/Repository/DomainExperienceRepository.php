<?php

namespace App\Repository;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DomainExperience>
 */
class DomainExperienceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainExperience::class);
    }

    /**
     * Charge toutes les DomainExperience d'un joueur avec Domain et Skills en une seule requête.
     * Élimine le N+1 sur getDomain() -> getSkills().
     *
     * @return DomainExperience[]
     */
    public function findByPlayerWithDomainsAndSkills(Player $player): array
    {
        return $this->createQueryBuilder('de')
            ->join('de.domain', 'd')->addSelect('d')
            ->leftJoin('d.skills', 's')->addSelect('s')
            ->where('de.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getResult();
    }

    public function getTotalXpEarned(Player $player): int
    {
        return (int) $this->createQueryBuilder('de')
            ->select('COALESCE(SUM(de.totalExperience), 0)')
            ->andWhere('de.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Top joueurs par XP totale cumulee sur tous les domaines (all-time).
     *
     * @return array<int, array{player: Player, totalXp: int}>
     */
    public function findTopXpEarners(int $limit = 50): array
    {
        /** @var array<int, array{playerId: string|int, totalXp: string|int|null}> $rows */
        $rows = $this->createQueryBuilder('de')
            ->select('IDENTITY(de.player) AS playerId', 'SUM(de.totalExperience) AS totalXp')
            ->groupBy('de.player')
            ->having('SUM(de.totalExperience) > 0')
            ->orderBy('totalXp', 'DESC')
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
                'totalXp' => (int) $row['totalXp'],
            ];
        }

        return $result;
    }

    /**
     * Rang d'un joueur dans le classement all-time par XP totale (1-based).
     * Retourne null si le joueur n'a aucune XP enregistree.
     */
    public function getPlayerXpRank(Player $player): ?int
    {
        $total = $this->getTotalXpEarned($player);
        if ($total <= 0) {
            return null;
        }

        $rows = $this->createQueryBuilder('de')
            ->select('IDENTITY(de.player) AS playerId', 'SUM(de.totalExperience) AS totalXp')
            ->andWhere('de.player != :player')
            ->groupBy('de.player')
            ->having('SUM(de.totalExperience) > :total')
            ->setParameter('player', $player)
            ->setParameter('total', $total)
            ->getQuery()
            ->getArrayResult();

        return \count($rows) + 1;
    }
}
