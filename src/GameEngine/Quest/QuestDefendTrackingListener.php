<?php

namespace App\GameEngine\Quest;

use App\Event\Fight\MobDeadEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuestDefendTrackingListener implements EventSubscriberInterface
{
    public function __construct(private readonly PlayerQuestUpdater $playerQuestUpdater)
    {
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

        if ($mob->isSummoned()) {
            return;
        }

        $map = $mob->getMap();
        if (!$map) {
            return;
        }

        $this->playerQuestUpdater->updateDefend(
            $mob->getMonster()->getSlug(),
            $map->getId()
        );
    }
}
