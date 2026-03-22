<?php

namespace App\Event\Game;

use App\Entity\App\GameEvent;
use Symfony\Contracts\EventDispatcher\Event;

class GameEventActivatedEvent extends Event
{
    final public const NAME = 'event.game.event_activated';

    public function __construct(private readonly GameEvent $gameEvent)
    {
    }

    public function getGameEvent(): GameEvent
    {
        return $this->gameEvent;
    }
}
