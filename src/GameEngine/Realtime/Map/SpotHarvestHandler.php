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

    public function removeSpot(SpotHarvestEvent $event): void
    {
        $objectLayer = $event->getObjectLayer();
        $coords = explode('.', $objectLayer->getCoordinates());
        $update = new Update(
            'map/spot',
            json_encode([
                'topic' => 'map/spot',
                'type' => 'remove',
                'mapId' => $objectLayer->getMap()?->getId(),
                'object' => [
                    'id' => $objectLayer->getId(),
                    'slug' => $objectLayer->getSlug(),
                ],
                'x' => (int) $coords[0],
                'y' => (int) ($coords[1] ?? 0),
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }

    public function addSpot(SpotAvailableEvent $event): void
    {
        $objectLayer = $event->getObjectLayer();
        $coords = explode('.', $objectLayer->getCoordinates());
        $update = new Update(
            'map/spot',
            json_encode([
                'topic' => 'map/spot',
                'type' => 'add',
                'mapId' => $objectLayer->getMap()?->getId(),
                'object' => [
                    'id' => $objectLayer->getId(),
                    'slug' => $objectLayer->getSlug(),
                    'name' => $objectLayer->getName(),
                    'toolType' => $objectLayer->getRequiredToolType(),
                    'nightOnly' => $objectLayer->isNightOnly(),
                ],
                'x' => (int) $coords[0],
                'y' => (int) ($coords[1] ?? 0),
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }
}
