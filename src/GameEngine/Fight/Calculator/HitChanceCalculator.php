<?php

namespace App\GameEngine\Fight\Calculator;

use App\Entity\Game\Spell;

class HitChanceCalculator
{
    /**
     * Calcule les chances de toucher en fonction du niveau du sort et du niveau de la cible.
     *
     * Formule : hitBase + (spellLevel - targetLevel) * 2, borne entre 5 et 100.
     */
    public function computeHitChance(Spell $spell, int $targetLevel = 1): int
    {
        $base = $spell->getHit();
        $levelDiff = $spell->getLevel() - $targetLevel;
        $hitChance = $base + ($levelDiff * 2);

        return max(5, min(100, $hitChance));
    }

    /**
     * Determine si l'attaque touche la cible.
     */
    public function hasHit(Spell $spell, int $targetLevel = 1): bool
    {
        $hitChance = $this->computeHitChance($spell, $targetLevel);

        try {
            return random_int(0, 99) < $hitChance;
        } catch (\Exception) {
            return false;
        }
    }
}
