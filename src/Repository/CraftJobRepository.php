<?php

namespace App\Repository;

use App\Entity\App\CraftJob;
use App\Entity\App\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CraftJob>
 */
class CraftJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CraftJob::class);
    }

    public function findActiveForPlayer(Player $player): ?CraftJob
    {
        return $this->findOneBy(['player' => $player]);
    }
}
