<?php

namespace App\GameEngine\Realtime;

use App\Event\Game\GameEventActivatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Annonce Mercure d'un evenement de zone (pivot PBBG, ZON-15).
 *
 * Complete l'annonce globale (`GameEventAnnouncementHandler`) d'une annonce
 * ciblee sur la zone (`zone/<id>/event`) quand l'evenement active est rattache
 * a une zone : les joueurs presents la voient apparaitre sur l'ecran de zone.
 */
class ZoneEventAnnouncementHandler implements EventSubscriberInterface
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GameEventActivatedEvent::NAME => 'onGameEventActivated',
        ];
    }

    public function onGameEventActivated(GameEventActivatedEvent $event): void
    {
        $gameEvent = $event->getGameEvent();
        $zone = $gameEvent->getZone();
        if (null === $zone) {
            return;
        }

        $topic = 'zone/' . $zone->getId() . '/event';

        $update = new Update(
            $topic,
            json_encode([
                'topic' => $topic,
                'type' => 'zone_event_activated',
                'event' => [
                    'id' => $gameEvent->getId(),
                    'name' => $gameEvent->getName(),
                    'type' => $gameEvent->getType(),
                    'typeLabel' => $gameEvent->getTypeLabel(),
                    'description' => $gameEvent->getDescription(),
                    'endsAt' => $gameEvent->getEndsAt()->format('c'),
                    'zoneId' => $zone->getId(),
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);

        $this->logger->info('Mercure published zone event announce for "{name}" on zone {zoneId}', [
            'name' => $gameEvent->getName(),
            'zoneId' => $zone->getId(),
        ]);
    }
}
