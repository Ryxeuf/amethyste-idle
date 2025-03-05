<?php

namespace App\Event\Map;

use App\Entity\App\Player;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerMovedEvent extends Event
{
    final public const NAME = "event.map.player.moved";

    /**
     * PlayerMovedEvent constructor.
     */
    public function __construct(private readonly Player $player)
    {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}
