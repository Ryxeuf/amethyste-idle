<?php

namespace App\GameEngine\Quest;

use App\Event\Game\PnjDialogEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuestTalkToTrackingListener implements EventSubscriberInterface
{
    public function __construct(private readonly PlayerQuestUpdater $playerQuestUpdater)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PnjDialogEvent::NAME => 'onPnjDialog',
        ];
    }

    public function onPnjDialog(PnjDialogEvent $event): void
    {
        $this->playerQuestUpdater->updateTalkedTo($event->getPnj()->getId());
    }
}
