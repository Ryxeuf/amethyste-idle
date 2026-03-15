<?php

namespace App\Event\Fight;

use App\Entity\App\Player;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerDeadEvent extends Event
{
    final public const NAME = 'event.player.dead';

    /**
     * @var Player
     */
    protected $player;

    /**
     * PlayerDeadEvent constructor.
     */
    public function __construct(Player $player)
    {
        $this->player = $player;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}
