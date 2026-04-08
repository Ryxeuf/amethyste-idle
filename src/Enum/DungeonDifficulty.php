<?php

namespace App\Enum;

enum DungeonDifficulty: string
{
    case Normal = 'normal';
    case Heroic = 'heroic';
    case Mythic = 'mythic';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Heroic => 'Heroique',
            self::Mythic => 'Mythique',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Normal => 'text-green-400',
            self::Heroic => 'text-purple-400',
            self::Mythic => 'text-yellow-400',
        };
    }

    public function bgClass(): string
    {
        return match ($this) {
            self::Normal => 'bg-green-900/50 text-green-400',
            self::Heroic => 'bg-purple-900/50 text-purple-400',
            self::Mythic => 'bg-yellow-900/50 text-yellow-400',
        };
    }

    /**
     * Multiplicateur HP des mobs dans le donjon.
     */
    public function statMultiplier(): float
    {
        return match ($this) {
            self::Normal => 1.0,
            self::Heroic => 1.5,
            self::Mythic => 2.5,
        };
    }

    /**
     * Multiplicateur degats des mobs (moins agressif que le HP).
     */
    public function damageMultiplier(): float
    {
        return match ($this) {
            self::Normal => 1.0,
            self::Heroic => 1.25,
            self::Mythic => 1.75,
        };
    }

    /**
     * Multiplicateur de taux de drop en donjon.
     */
    public function dropMultiplier(): float
    {
        return match ($this) {
            self::Normal => 1.0,
            self::Heroic => 1.5,
            self::Mythic => 2.0,
        };
    }

    /**
     * Multiplicateur d'XP (materia) en donjon.
     */
    public function xpMultiplier(): float
    {
        return match ($this) {
            self::Normal => 1.0,
            self::Heroic => 1.5,
            self::Mythic => 2.5,
        };
    }

    /**
     * Cooldown entre deux runs (en secondes).
     */
    public function cooldownSeconds(): int
    {
        return match ($this) {
            self::Normal => 3600,       // 1h
            self::Heroic => 14400,      // 4h
            self::Mythic => 86400,      // 24h
        };
    }
}
