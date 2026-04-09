<?php

namespace App\Event\Game;

use App\Entity\App\Player;
use Symfony\Contracts\EventDispatcher\Event;

class TutorialCompletedEvent extends Event
{
    final public const NAME = 'event.game.tutorial.completed';

    public function __construct(
        private readonly Player $player,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}
