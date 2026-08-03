<?php

namespace App\GameEngine\Fight\Calculator;

use App\Entity\Game\Spell;
use App\Enum\CombatLever;
use App\GameEngine\Fight\CombatLeverEffects;
use App\GameEngine\Progression\CombatLeverScale;

class CriticalCalculator
{
    public const CRITICAL_MULTIPLIER = 1.5;

    /**
     * Calcule les chances de critique avec le bonus de domaine.
     *
     * `critical` est un levier **additif en points de pourcentage** (ARC-03b) :
     * c'est deja un taux, et l'exprimer en pourcentage d'un pourcentage serait
     * illisible (GAME_ARCHETYPES § 4.2). Il tombe donc au meme endroit que le
     * bonus de domaine — c'est la meme place dans la formule, pas une seconde.
     */
    public function computeCriticalChance(Spell $spell, int $domainCritical = 0, ?CombatLeverEffects $levers = null, ?CombatLeverScale $scale = null): int
    {
        $fromLevers = 0;
        if ($levers !== null && $scale !== null && !$levers->isEmpty()) {
            $fromLevers = (int) round($levers->pointsFor(CombatLever::Critical, $scale));
        }

        return max(0, min(100, $spell->getCritical() + $domainCritical + $fromLevers));
    }

    /**
     * Determine si l'action est un coup critique.
     */
    public function isCritical(Spell $spell, int $domainCritical = 0, ?CombatLeverEffects $levers = null, ?CombatLeverScale $scale = null): bool
    {
        $chance = $this->computeCriticalChance($spell, $domainCritical, $levers, $scale);

        try {
            return random_int(0, 99) < $chance;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Applique le multiplicateur critique a une valeur.
     *
     * `critical_power` porte sur le **seul critique** — c'est ce qui le
     * distingue de `power`, qui porte sur tous les coups. Deux leviers qui
     * multiplieraient la meme valeur au meme moment seraient un seul levier.
     */
    public function applyCriticalModifier(int $value, ?CombatLeverEffects $levers = null, ?CombatLeverScale $scale = null): int
    {
        $multiplier = self::CRITICAL_MULTIPLIER;
        if ($levers !== null && $scale !== null && !$levers->isEmpty()) {
            $multiplier *= $levers->multiplierFor(CombatLever::CriticalPower, $scale);
        }

        return (int) round($value * $multiplier);
    }
}
