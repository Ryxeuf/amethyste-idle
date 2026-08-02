<?php

namespace App\Event\Game;

use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Player;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Un membre vient de terminer un donjon (modele unique DON-01) — emis par la
 * distribution des recompenses, une fois par membre. Remplace l'ancien
 * `DungeonCompletedEvent` du chemin solo, supprime avec lui (DON-01b).
 */
class GroupDungeonCompletedEvent extends Event
{
    public const NAME = 'game.dungeon.group_completed';

    public function __construct(
        private readonly Player $player,
        private readonly GroupDungeonRun $run,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getRun(): GroupDungeonRun
    {
        return $this->run;
    }
}
