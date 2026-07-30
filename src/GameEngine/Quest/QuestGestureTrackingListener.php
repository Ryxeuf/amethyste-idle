<?php

namespace App\GameEngine\Quest;

use App\Event\Game\PlayerGestureEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Fait progresser les objectifs de geste (ONB-12a).
 *
 * Seul point du code ou l'equipement, le sertissage, le combat et l'expedition
 * rencontrent les quetes — et ils l'ignorent : ils annoncent, il ecoute.
 */
class QuestGestureTrackingListener implements EventSubscriberInterface
{
    public function __construct(private readonly PlayerQuestUpdater $playerQuestUpdater)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerGestureEvent::NAME => 'onGesture',
        ];
    }

    public function onGesture(PlayerGestureEvent $event): void
    {
        $this->playerQuestUpdater->updateGesture($event->getGesture(), $event->getTargets());
    }
}
