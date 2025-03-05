<?php

namespace App\Event\Fight;

use Symfony\Contracts\EventDispatcher\Event;

class FightLootedEvent extends Event
{
    final public const NAME = "event.fight.looted";

    public function __construct(private readonly int $fightId)
    {
    }

    public function getFightId(): int
    {
        return $this->fightId;
    }
}
