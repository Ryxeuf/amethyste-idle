<?php

namespace App\GameEngine\Event;

use App\Entity\App\GameEvent;
use App\Event\Fight\MobDeadEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to MobDeadEvent and tracks kills for invasion mobs.
 * Updates the GameEvent parameters with per-player kill counts.
 */
class InvasionKillTracker implements EventSubscriberInterface
{
    public function __construct(
        private readonly InvasionManager $invasionManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'onMobDead',
        ];
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        $mob = $event->getMob();
        $gameEvent = $mob->getGameEvent();

        if (!$gameEvent || $gameEvent->getType() !== GameEvent::TYPE_INVASION) {
            return;
        }

        $fight = $mob->getFight();
        if (!$fight) {
            return;
        }

        $players = $fight->getPlayers();
        foreach ($players as $player) {
            $this->invasionManager->recordKill($gameEvent, $player);
        }

        $this->entityManager->flush();

        $this->logger->info('[InvasionKillTracker] Recorded kill of "{mob}" in invasion "{event}"', [
            'mob' => $mob->getName(),
            'event' => $gameEvent->getName(),
        ]);
    }
}
