<?php

namespace App\Repository;

use App\Entity\App\GameEvent;
use App\Entity\App\Player;
use App\Entity\App\PlayerZoneEventParticipation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerZoneEventParticipation>
 */
class PlayerZoneEventParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerZoneEventParticipation::class);
    }

    public function findOneForPlayerAndEvent(Player $player, GameEvent $gameEvent): ?PlayerZoneEventParticipation
    {
        return $this->findOneBy(['player' => $player, 'gameEvent' => $gameEvent]);
    }

    public function hasJoined(Player $player, GameEvent $gameEvent): bool
    {
        return null !== $this->findOneForPlayerAndEvent($player, $gameEvent);
    }

    /**
     * Participations d'un evenement triees par contribution decroissante
     * (base de la distribution du loot a la contribution, ZON-18).
     *
     * @return list<PlayerZoneEventParticipation>
     */
    public function findByEventOrderedByContribution(GameEvent $gameEvent): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.gameEvent = :event')
            ->setParameter('event', $gameEvent)
            ->orderBy('p.contribution', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
