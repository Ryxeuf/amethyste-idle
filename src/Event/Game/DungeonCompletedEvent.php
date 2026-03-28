<?php

namespace App\Event\Game;

use App\Entity\App\DungeonRun;
use App\Entity\App\Player;
use Symfony\Contracts\EventDispatcher\Event;

class DungeonCompletedEvent extends Event
{
    final public const NAME = 'event.game.dungeon.completed';

    public function __construct(
        private readonly Player $player,
        private readonly DungeonRun $dungeonRun,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getDungeonRun(): DungeonRun
    {
        return $this->dungeonRun;
    }
}
