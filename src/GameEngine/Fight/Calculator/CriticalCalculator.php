<?php

namespace App\GameEngine\Fight\Calculator;

use App\Entity\Game\Spell;

class CriticalCalculator
{
    public const CRITICAL_MULTIPLIER = 1.5;

    /**
     * Calcule les chances de critique avec le bonus de domaine.
     */
    public function computeCriticalChance(Spell $spell, int $domainCritical = 0): int
    {
        return max(0, min(100, $spell->getCritical() + $domainCritical));
    }

    /**
     * Determine si l'action est un coup critique.
     */
    public function isCritical(Spell $spell, int $domainCritical = 0): bool
    {
        $chance = $this->computeCriticalChance($spell, $domainCritical);

        try {
            return random_int(0, 99) < $chance;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Applique le multiplicateur critique a une valeur.
     */
    public function applyCriticalModifier(int $value): int
    {
        return (int) round($value * self::CRITICAL_MULTIPLIER);
    }
}
