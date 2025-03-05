<?php

namespace App\GameEngine\Realtime\Map;

use App\Event\Map\MobRespawnedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RespawnedMobHandler extends RespawnedHandler implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MobRespawnedEvent::NAME => 'respawnMob'
        ];
    }

    public function respawnMob(MobRespawnedEvent $event): void
    {
        $this->respawn('mob', ['id' => $event->getMob()->getId(), 'slug' => $event->getMob()->getMonster()->getSlug()], $event->getMob()->getCell()->getId());
    }
}