<?php

namespace App\GameEngine\Fight\Handler;

use App\Entity\App\Mob;
use App\Entity\Game\Spell;

class MobAttackHandler implements MobActionHandlerInterface
{
    public function supports(string $context)
    {
        return 'attack' === $context;
    }

    public function getSpell(Mob $mob): Spell
    {
        return $mob->getAttack();
    }
}
