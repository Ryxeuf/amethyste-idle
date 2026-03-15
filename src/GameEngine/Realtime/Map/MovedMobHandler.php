<?php

namespace App\GameEngine\Realtime\Map;

use App\Event\Map\MobMovedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MovedMobHandler extends MovedHandler implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MobMovedEvent::NAME => 'moveMob',
        ];
    }

    public function moveMob(MobMovedEvent $event): void
    {
        // $this->move('mob', $event->getMob()->getId(), $event->getMob()->getCell()->getId());
    }
}
