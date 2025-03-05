<?php

namespace App\GameEngine\Fight\Handler;

use App\Entity\App\Mob;
use App\Entity\Game\Spell;
use Doctrine\ORM\EntityNotFoundException;

class MobAttackHandler implements MobActionHandlerInterface
{
    public function supports(string $context)
    {
        return 'attack' === $context;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function getSpell(Mob $mob): Spell
    {
        if (!$mob->getAttack()) {
            throw new EntityNotFoundException("Mob attack impossible");
        }

        return $mob->getAttack();
    }
}
