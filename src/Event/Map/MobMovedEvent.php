<?php

namespace App\Event\Map;

use App\Entity\App\Mob;
use Symfony\Contracts\EventDispatcher\Event;

class MobMovedEvent extends Event
{
    final public const NAME = "event.map.mob.moved";

    public function __construct(private readonly Mob $mob)
    {
    }

    public function getMob(): Mob
    {
        return $this->mob;
    }
}
