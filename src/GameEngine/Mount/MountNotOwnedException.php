<?php

namespace App\GameEngine\Mount;

use App\Entity\App\Player;
use App\Entity\Game\Mount;

class MountNotOwnedException extends \RuntimeException
{
    public function __construct(
        public readonly Player $player,
        public readonly Mount $mount,
    ) {
        parent::__construct(sprintf('Le joueur ne possede pas la monture "%s".', $mount->getSlug()));
    }
}
