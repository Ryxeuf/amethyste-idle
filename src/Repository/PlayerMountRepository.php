<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Entity\Game\Mount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerMount>
 */
class PlayerMountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerMount::class);
    }

    /** @return PlayerMount[] */
    public function findByPlayer(Player $player): array
    {
        return $this->createQueryBuilder('pm')
            ->join('pm.mount', 'm')->addSelect('m')
            ->andWhere('pm.player = :player')
            ->setParameter('player', $player)
            ->orderBy('pm.acquiredAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByPlayerAndMount(Player $player, Mount $mount): ?PlayerMount
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.player = :player')
            ->andWhere('pm.mount = :mount')
            ->setParameter('player', $player)
            ->setParameter('mount', $mount)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function playerOwnsMount(Player $player, Mount $mount): bool
    {
        return null !== $this->findOneByPlayerAndMount($player, $mount);
    }

    /**
     * @return list<int>
     */
    public function findOwnedMountIds(Player $player): array
    {
        $rows = $this->createQueryBuilder('pm')
            ->select('IDENTITY(pm.mount) AS mountId')
            ->andWhere('pm.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getArrayResult();

        $ids = [];
        foreach ($rows as $row) {
            if (isset($row['mountId'])) {
                $ids[] = (int) $row['mountId'];
            }
        }

        return $ids;
    }
}
