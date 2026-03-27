<?php

namespace App\Event\Fight;

use App\Entity\App\Player;
use Symfony\Contracts\EventDispatcher\Event;

class CombatFleeEvent extends Event
{
    final public const NAME = 'event.combat.flee';

    public function __construct(
        private readonly Player $player,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}
