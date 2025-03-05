<?php

namespace App\Event\Map;

use App\Entity\App\Player;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerRespawnedEvent extends Event
{
    final public const NAME = "event.map.player.respawned";

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
