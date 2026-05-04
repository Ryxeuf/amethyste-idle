<?php

namespace App\GameEngine\Mount;

use App\Entity\App\Player;

class InsufficientGilsException extends \RuntimeException
{
    public function __construct(
        public readonly Player $player,
        public readonly int $requiredGils,
    ) {
        parent::__construct(sprintf('Le joueur ne dispose pas des %d gils requis.', $requiredGils));
    }
}
