<?php

namespace App\GameEngine\Quest;

use App\Event\Map\PlayerMovedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuestEscortTrackingListener implements EventSubscriberInterface
{
    public function __construct(private readonly PlayerQuestUpdater $playerQuestUpdater)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerMovedEvent::NAME => 'onPlayerMoved',
        ];
    }

    public function onPlayerMoved(PlayerMovedEvent $event): void
    {
        $player = $event->getPlayer();
        $map = $player->getMap();

        if (!$map) {
            return;
        }

        $this->playerQuestUpdater->updateEscort(
            $map->getId(),
            $player->getCoordinates()
        );
    }
}
