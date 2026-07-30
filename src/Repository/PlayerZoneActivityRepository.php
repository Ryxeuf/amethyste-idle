<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerZoneActivity;
use App\Entity\App\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerZoneActivity>
 */
class PlayerZoneActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerZoneActivity::class);
    }

    public function findOneFor(Player $player, Zone $zone): ?PlayerZoneActivity
    {
        return $this->findOneBy(['player' => $player, 'zone' => $zone]);
    }

    /**
     * Les zones ou le joueur a travaille, de la plus a la moins frequentee.
     *
     * L'egalite se departage par l'acte le plus recent : deux zones a egalite
     * decrivent un joueur qui a change d'avis, et c'est le dernier avis qui
     * compte. Sans ce second critere, l'ordre dependrait de l'identifiant —
     * c'est-a-dire de rien.
     *
     * @return list<PlayerZoneActivity>
     */
    public function findBusiestFor(Player $player): array
    {
        return $this->createQueryBuilder('activity')
            ->andWhere('activity.player = :player')
            ->setParameter('player', $player)
            ->orderBy('activity.acts', 'DESC')
            ->addOrderBy('activity.lastActAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
