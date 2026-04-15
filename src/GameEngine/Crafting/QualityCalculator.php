<?php

namespace App\GameEngine\Crafting;

class QualityCalculator
{
    public const QUALITY_NORMAL = 'normal';
    public const QUALITY_UNCOMMON = 'uncommon';
    public const QUALITY_RARE = 'rare';
    public const QUALITY_EPIC = 'epic';
    public const QUALITY_LEGENDARY = 'legendary';

    public const QUALITY_TIERS = [
        self::QUALITY_NORMAL,
        self::QUALITY_UNCOMMON,
        self::QUALITY_RARE,
        self::QUALITY_EPIC,
        self::QUALITY_LEGENDARY,
    ];

    /**
     * Determine la qualite d'un objet fabrique.
     *
     * - Qualite de base definie par la recette
     * - Chance d'amelioration : skillLevel * 2% (+ bonus specialisation si applicable)
     * - Craft critique : 5% de chance de sauter un palier
     */
    public function calculateQuality(string $baseQuality, int $skillLevel, int $specializationBonus = 0): string
    {
        $currentIndex = array_search($baseQuality, self::QUALITY_TIERS, true);

        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        // Chance d'amelioration basee sur le niveau : skillLevel * 2% + bonus specialisation
        $upgradeChance = min(100, $skillLevel * 2 + max(0, $specializationBonus));
        $roll = random_int(1, 100);

        if ($roll <= $upgradeChance && $currentIndex < count(self::QUALITY_TIERS) - 1) {
            ++$currentIndex;

            // Craft critique : 5% de chance de sauter un palier supplementaire
            $criticalRoll = random_int(1, 100);
            if ($criticalRoll <= 5 && $currentIndex < count(self::QUALITY_TIERS) - 1) {
                ++$currentIndex;
            }
        }

        return self::QUALITY_TIERS[$currentIndex];
    }

    /**
     * Retourne le libelle francais d'une qualite.
     */
    public static function getQualityLabel(string $quality): string
    {
        return match ($quality) {
            self::QUALITY_NORMAL => 'Normal',
            self::QUALITY_UNCOMMON => 'Peu commun',
            self::QUALITY_RARE => 'Rare',
            self::QUALITY_EPIC => 'Epique',
            self::QUALITY_LEGENDARY => 'Legendaire',
            default => 'Inconnu',
        };
    }

    /**
     * Retourne la classe CSS Tailwind pour la couleur de qualite.
     */
    public static function getQualityColor(string $quality): string
    {
        return match ($quality) {
            self::QUALITY_NORMAL => 'text-gray-400',
            self::QUALITY_UNCOMMON => 'text-green-400',
            self::QUALITY_RARE => 'text-blue-400',
            self::QUALITY_EPIC => 'text-purple-400',
            self::QUALITY_LEGENDARY => 'text-orange-400',
            default => 'text-gray-400',
        };
    }
}
