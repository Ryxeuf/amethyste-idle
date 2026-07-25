<?php

namespace App\GameEngine\Zone;

use App\Entity\App\GameEvent;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerZoneEventParticipation;
use App\Entity\App\Zone;
use App\Repository\PlayerZoneEventParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Evenements de zone (pivot PBBG, ZON-15).
 *
 * Generalise les world bosses / invasions en evenements annonces, rattaches a
 * une zone (`GameEvent.zone`), a rejoindre dans leur fenetre temporelle
 * [startsAt, endsAt]. Rejoindre coute de l'energie d'action (regle Sprint 8) et
 * enregistre une participation (`PlayerZoneEventParticipation`) — la base de la
 * distribution du loot a la contribution des boss de zone asynchrones (ZON-18).
 *
 * Curseur d'equilibrage (table `parameter`) :
 *  - `zone.energy.cost.event` : cout pour rejoindre un evenement (defaut 10).
 */
class ZoneEventService
{
    public const DEFAULT_COST = 10;
    public const PARAM_COST = 'zone.energy.cost.event';

    private ?int $costCache = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerZoneEventParticipationRepository $participationRepository,
        private readonly ActionEnergyManager $actionEnergyManager,
    ) {
    }

    /**
     * Evenements actifs (dans leur fenetre) rattaches a la zone.
     *
     * @return list<GameEvent>
     */
    public function getActiveEventsForZone(Zone $zone): array
    {
        $now = $this->now();

        /** @var list<GameEvent> $events */
        $events = $this->entityManager->getRepository(GameEvent::class)->createQueryBuilder('e')
            ->andWhere('e.zone = :zone')
            ->andWhere('e.status IN (:statuses)')
            ->setParameter('zone', $zone)
            ->setParameter('statuses', [GameEvent::STATUS_ACTIVE, GameEvent::STATUS_SCHEDULED])
            ->orderBy('e.endsAt', 'ASC')
            ->getQuery()
            ->getResult();

        return array_values(array_filter($events, static fn (GameEvent $e): bool => $e->isActiveAt($now)));
    }

    public function hasJoined(Player $player, GameEvent $event): bool
    {
        return $this->participationRepository->hasJoined($player, $event);
    }

    /**
     * Rejoint un evenement de zone : valide la presence dans la zone et la
     * fenetre, prleve l'energie, enregistre la participation (idempotent).
     *
     * @throws ZoneActionException            si le join est refuse (cle de traduction en message)
     * @throws NotEnoughActionEnergyException si l'energie est insuffisante
     */
    public function join(Player $player, GameEvent $event): PlayerZoneEventParticipation
    {
        $zone = $event->getZone();
        if (null === $zone) {
            throw new ZoneActionException('game.zone.event.error.not_zone_event');
        }
        if ($player->getCurrentZone() !== $zone) {
            throw new ZoneActionException('game.zone.event.error.not_present');
        }
        if (!$event->isActiveAt($this->now())) {
            throw new ZoneActionException('game.zone.event.error.closed');
        }

        $existing = $this->participationRepository->findOneForPlayerAndEvent($player, $event);
        if (null !== $existing) {
            throw new ZoneActionException('game.zone.event.error.already_joined');
        }

        // L'energie n'est prelevee qu'une fois le join garanti possible.
        $this->actionEnergyManager->spend($player, $this->getEventCost(), false);

        $participation = new PlayerZoneEventParticipation($player, $event);
        $this->entityManager->persist($participation);
        $this->entityManager->flush();

        return $participation;
    }

    public function getEventCost(): int
    {
        if (null !== $this->costCache) {
            return $this->costCache;
        }

        $parameter = $this->entityManager->getRepository(Parameter::class)
            ->findOneBy(['name' => self::PARAM_COST]);
        $value = null !== $parameter ? (int) $parameter->getValue() : self::DEFAULT_COST;

        return $this->costCache = $value >= 0 ? $value : self::DEFAULT_COST;
    }

    /**
     * Instant courant — surchargeable en test.
     */
    protected function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
