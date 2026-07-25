<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerExpedition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerExpedition>
 */
class PlayerExpeditionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerExpedition::class);
    }

    /**
     * Expedition en cours ou terminee (butin a recuperer) du joueur, ou null.
     * Un seul enregistrement possible par joueur (contrainte UNIQUE).
     */
    public function findForPlayer(Player $player): ?PlayerExpedition
    {
        return $this->findOneBy(['player' => $player]);
    }
}
