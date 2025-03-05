<?php

namespace App\GameEngine\Fight\Handler;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\Game\Spell;

interface MobActionHandlerInterface
{
    public function supports(string $context);

    public function getSpell(Mob $mob): Spell;
}
