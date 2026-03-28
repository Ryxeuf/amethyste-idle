<?php

namespace App\GameEngine\Dungeon;

use App\Event\Fight\MobDeadEvent;
use App\Event\Game\DungeonCompletedEvent;
use App\Repository\DungeonRunRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DungeonCompletionListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly DungeonRunRepository $dungeonRunRepository,
        private readonly DungeonManager $dungeonManager,
        private readonly EventDispatcherInterface $eventDispatcher,
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

        if (!$mob->getMonster()->isBoss()) {
            return;
        }

        $fight = $mob->getFight();
        if (!$fight) {
            return;
        }

        $player = $fight->getPlayers()->first();
        if ($player === false) {
            return;
        }

        $activeRun = $this->dungeonRunRepository->findActiveRun($player);
        if (!$activeRun) {
            return;
        }

        // Verify the fight is on the dungeon's map
        $dungeon = $activeRun->getDungeon();
        if ($mob->getMap()?->getId() !== $dungeon->getMap()->getId()) {
            return;
        }

        $this->dungeonManager->completeRun($activeRun);

        $this->eventDispatcher->dispatch(
            new DungeonCompletedEvent($player, $activeRun),
            DungeonCompletedEvent::NAME,
        );
    }
}
