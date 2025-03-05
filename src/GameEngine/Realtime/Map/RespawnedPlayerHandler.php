<?php

namespace App\GameEngine\Realtime\Map;

use App\Event\Map\MobRespawnedEvent;
use App\Event\Map\PlayerRespawnedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RespawnedPlayerHandler extends RespawnedHandler implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PlayerRespawnedEvent::NAME => 'respawnPlayer'
        ];
    }

    public function respawnPlayer(PlayerRespawnedEvent $event): void
    {
        // $this->respawn('player', ['id' => $event->getPlayer()->getId(), 'class' => $event->getPlayer()->getClassType()], $event->getPlayer()->getCell()->getId());
    }
}