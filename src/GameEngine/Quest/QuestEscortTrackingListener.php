<?php

namespace App\GameEngine\Quest;

use App\Event\Zone\PlayerTraveledEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Valide les objectifs d'escorte lorsque le joueur atteint la zone de
 * destination (ZON-22). L'accroche etait le deplacement sur la carte avant le
 * pivot PBBG.
 */
class QuestEscortTrackingListener implements EventSubscriberInterface
{
    public function __construct(private readonly PlayerQuestUpdater $playerQuestUpdater)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerTraveledEvent::NAME => 'onPlayerTraveled',
        ];
    }

    public function onPlayerTraveled(PlayerTraveledEvent $event): void
    {
        $this->playerQuestUpdater->updateEscort($event->getZone());
    }
}
