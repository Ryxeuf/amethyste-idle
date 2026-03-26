<?php

namespace App\Event\Game;

use App\Entity\App\Player;
use App\Entity\App\Pnj;
use Symfony\Contracts\EventDispatcher\Event;

class PnjDialogEvent extends Event
{
    final public const NAME = 'event.game.pnj.dialog';

    public function __construct(
        private readonly Player $player,
        private readonly Pnj $pnj,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getPnj(): Pnj
    {
        return $this->pnj;
    }
}
