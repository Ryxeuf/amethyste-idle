<?php

namespace App\GameEngine\Fight;

use App\Enum\CombatLever;
use App\GameEngine\Progression\CombatLeverScale;

class FightCalculator
{
    /**
     * Calcule les chances de toucher la cible.
     */
    public static function hasAttackHit(int $hitChances): bool
    {
        return random_int(0, 99) < $hitChances;
    }

    /**
     * Le jet de touche, leviers compris (ARC-03b).
     *
     * `hit` est **additif en points de pourcentage** : c'est deja un taux, et il
     * tombe donc au meme endroit que la precision de base et le bonus de
     * domaine. La methode existe pour que ce soit vrai **une seule fois** —
     * chaque appelant qui recalculerait la somme lui-meme serait une seconde
     * place pour le meme levier.
     */
    public static function hasAttackHitWithLevers(int $hitChances, CombatLeverEffects $levers, CombatLeverScale $scale): bool
    {
        if (!$levers->isEmpty()) {
            $hitChances += (int) round($levers->pointsFor(CombatLever::Hit, $scale));
        }

        return self::hasAttackHit($hitChances);
    }
}
