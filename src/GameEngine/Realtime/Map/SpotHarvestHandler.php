<?php

namespace App\GameEngine\Realtime\Map;

use App\Event\Map\SpotAvailableEvent;
use App\Event\Map\SpotHarvestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class SpotHarvestHandler implements EventSubscriberInterface
{
    public function __construct(private readonly HubInterface $hub)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SpotHarvestEvent::NAME => 'removeSpot',
            SpotAvailableEvent::NAME => 'addSpot',
        ];
    }

    public function removeSpot(SpotHarvestEvent $event)
    {
        $update = new Update(
            'map/spot',
            json_encode(['topic' => 'map/spot', 'type' => 'remove', 'object' => ['id' => $event->getObjectLayer()->getId()], 'cell' => ['id' => $event->getObjectLayer()->getCell()->getId()]], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }

    public function addSpot(SpotAvailableEvent $event)
    {
        $update = new Update(
            'map/spot',
            json_encode(['topic' => 'map/spot', 'type' => 'add', 'object' => ['id' => $event->getObjectLayer()->getId()], 'cell' => ['id' => $event->getObjectLayer()->getCell()->getId()]], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }
}