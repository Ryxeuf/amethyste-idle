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

    public function findOneByPlayerAndMount(Player $player, Mount $mount): ?PlayerMount
    {
        return $this->findOneBy([
            'player' => $player,
            'mount' => $mount,
        ]);
    }

    /**
     * @return PlayerMount[]
     */
    public function findByPlayer(Player $player): array
    {
        return $this->createQueryBuilder('pm')
            ->innerJoin('pm.mount', 'm')
            ->andWhere('pm.player = :player')
            ->setParameter('player', $player)
            ->orderBy('pm.obtainedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
