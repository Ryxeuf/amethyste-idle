<?php

namespace App\GameEngine\Realtime;

use App\Event\Game\GameEventActivatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class GameEventAnnouncementHandler implements EventSubscriberInterface
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

        $update = new Update(
            'event/announce',
            json_encode([
                'topic' => 'event/announce',
                'type' => 'activated',
                'event' => [
                    'id' => $gameEvent->getId(),
                    'name' => $gameEvent->getName(),
                    'type' => $gameEvent->getType(),
                    'typeLabel' => $gameEvent->getTypeLabel(),
                    'description' => $gameEvent->getDescription(),
                    'endsAt' => $gameEvent->getEndsAt()->format('c'),
                    'mapId' => $gameEvent->getMap()?->getId(),
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);

        $this->logger->info('Mercure published event/announce for "{name}" (type: {type})', [
            'name' => $gameEvent->getName(),
            'type' => $gameEvent->getType(),
        ]);
    }
}
