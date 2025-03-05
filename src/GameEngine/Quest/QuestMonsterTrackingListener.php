<?php

namespace App\GameEngine\Quest;

use App\Event\Fight\MobDeadEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuestMonsterTrackingListener implements EventSubscriberInterface
{
    public function __construct(private readonly PlayerQuestUpdater $playerQuestUpdater)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => "updateMonsterPlayerQuest"
        ];
    }

    public function updateMonsterPlayerQuest(MobDeadEvent $event): void
    {
        $this->playerQuestUpdater->updateMobKilled($event->getMob());
    }
}