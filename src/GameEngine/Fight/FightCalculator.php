<?php

namespace App\GameEngine\Fight;

class FightCalculator
{
    /**
     * Calcule les chances de toucher la cible
     *
     *
     */
    public static function hasAttackHit(int $hitChances): bool
    {
        return random_int(0, 99) < $hitChances;
    }
}
