<?php

namespace App\GameEngine\Quest;

use App\Event\Zone\PlayerTraveledEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Fait progresser les objectifs d'exploration a l'arrivee dans une zone
 * (ZON-22). L'accroche etait le deplacement sur la carte avant le pivot PBBG.
 */
class QuestExploreTrackingListener implements EventSubscriberInterface
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
        $this->playerQuestUpdater->updateExplored($event->getZone());
    }
}
