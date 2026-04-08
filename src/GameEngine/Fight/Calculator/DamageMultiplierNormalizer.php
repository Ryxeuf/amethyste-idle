<?php

namespace App\GameEngine\Fight\Calculator;

/**
 * Normalise les multiplicateurs de dégâts pour éviter les écarts > 30% entre builds.
 *
 * Deux mécanismes :
 * 1. Les bonus d'équipement (element match, linked materia, gear) sont sommés
 *    additivement puis soumis à un soft cap avec rendements décroissants.
 * 2. Les multiplicateurs de synergie élémentaire sont soumis à un soft cap
 *    séparé pour réduire l'écart entre la meilleure et la pire combo.
 */
class DamageMultiplierNormalizer
{
    /**
     * Seuil au-delà duquel les bonus d'équipement subissent des rendements décroissants.
     * Les bonus jusqu'à +40% sont pleinement effectifs.
     */
    public const BONUS_SOFT_CAP = 0.4;

    /**
     * Facteur d'efficacité des bonus au-delà du soft cap.
     * Au-delà de 40%, chaque point de bonus ne compte qu'à 50%.
     */
    public const BONUS_DIMINISH_FACTOR = 0.5;

    /**
     * Seuil au-delà duquel les synergies élémentaires subissent des rendements décroissants.
     * Un bonus de synergie jusqu'à +50% (multiplicateur 1.5) est pleinement effectif.
     */
    public const SYNERGY_SOFT_CAP = 0.5;

    /**
     * Facteur d'efficacité des synergies au-delà du soft cap.
     * Au-delà de +50%, chaque point de bonus de synergie ne compte qu'à 30%.
     */
    public const SYNERGY_DIMINISH_FACTOR = 0.3;

    /**
     * Normalise un total de bonus d'équipement (somme additive des %).
     *
     * @param float $totalBonusPercent somme des bonus individuels (ex: 0.25 + 0.15 + 0.10 = 0.50)
     *
     * @return float le bonus effectif après soft cap (ex: 0.45)
     */
    public function normalizeBonus(float $totalBonusPercent): float
    {
        if ($totalBonusPercent <= 0.0) {
            return 0.0;
        }

        if ($totalBonusPercent <= self::BONUS_SOFT_CAP) {
            return $totalBonusPercent;
        }

        $excess = $totalBonusPercent - self::BONUS_SOFT_CAP;

        return self::BONUS_SOFT_CAP + $excess * self::BONUS_DIMINISH_FACTOR;
    }

    /**
     * Normalise un multiplicateur de synergie élémentaire.
     *
     * @param float $multiplier le multiplicateur brut (ex: 2.5 pour Eclipse)
     *
     * @return float le multiplicateur effectif après soft cap (ex: 1.80)
     */
    public function normalizeSynergy(float $multiplier): float
    {
        if ($multiplier <= 1.0) {
            return $multiplier;
        }

        $bonus = $multiplier - 1.0;

        if ($bonus <= self::SYNERGY_SOFT_CAP) {
            return $multiplier;
        }

        $excess = $bonus - self::SYNERGY_SOFT_CAP;

        return 1.0 + self::SYNERGY_SOFT_CAP + $excess * self::SYNERGY_DIMINISH_FACTOR;
    }
}
