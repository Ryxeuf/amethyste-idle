<?php

namespace App\GameEngine\Fight\Handler;

use App\ApiResource\FightResource;
use App\Entity\App\Player;

interface PlayerActionHandlerInterface
{
    public function supports(FightResource $fight, string $context);

    public function applyAction(FightResource $fight, Player $sender): bool;
}
