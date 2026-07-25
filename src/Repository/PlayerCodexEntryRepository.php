<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerCodexEntry;
use App\Entity\Game\CodexEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerCodexEntry>
 */
class PlayerCodexEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerCodexEntry::class);
    }

    public function hasUnlocked(Player $player, CodexEntry $entry): bool
    {
        return null !== $this->findOneBy(['player' => $player, 'codexEntry' => $entry]);
    }

    /**
     * Entrees debloquees par un joueur.
     *
     * @return PlayerCodexEntry[]
     */
    public function findByPlayer(Player $player): array
    {
        return $this->createQueryBuilder('pce')
            ->andWhere('pce.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getResult();
    }

    /**
     * IDs des entrees de Codex debloquees par un joueur (pour marquer l'ecran Codex).
     *
     * @return list<int>
     */
    public function unlockedEntryIds(Player $player): array
    {
        /** @var list<array{id: int}> $rows */
        $rows = $this->createQueryBuilder('pce')
            ->select('IDENTITY(pce.codexEntry) AS id')
            ->andWhere('pce.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }
}
