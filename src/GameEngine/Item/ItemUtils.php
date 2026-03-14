<?php

namespace App\GameEngine\Item;

class ItemUtils
{
    final public const DEFAULT_HIT_CHANCES = 75;
    final public const DEFAULT_CRITICAL_CHANCES = 1;
    final public const DEFAULT_ADDITIONAL_DAMAGE = 0;
    final public const DEFAULT_ADDITIONAL_HEAL = 0;

    final public const CRITICAL_MODIFIER = 1.5;

    public static function isActionSuccess(int $hitChances): bool
    {
        try {
            return random_int(0, 99) < $hitChances;
        } catch (\Exception) {
            return false;
        }
    }

    public static function isActionCritical(int $criticalChances): bool
    {
        return self::isActionSuccess($criticalChances);
    }

    public static function getCriticalModified(int $value): int
    {
        return (int) round($value * self::CRITICAL_MODIFIER);
    }
}
