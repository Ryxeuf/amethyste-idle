<?php

namespace App\GameEngine\Craft;

enum CraftQuality: string
{
    case NORMAL = 'normal';
    case SUPERIOR = 'superior';
    case EXCEPTIONAL = 'exceptional';
    case MASTERPIECE = 'masterpiece';

    public function getStatMultiplier(): float
    {
        return match ($this) {
            self::NORMAL => 1.0,
            self::SUPERIOR => 1.15,
            self::EXCEPTIONAL => 1.3,
            self::MASTERPIECE => 1.5,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::SUPERIOR => 'Supérieur',
            self::EXCEPTIONAL => 'Exceptionnel',
            self::MASTERPIECE => 'Chef-d\'œuvre',
        };
    }

    /**
     * Tire la qualité en fonction du niveau de compétence (1-100).
     */
    public static function rollQuality(int $skillLevel): self
    {
        $roll = random_int(1, 100);
        $masterpieceChance = max(0, $skillLevel - 80); // 0-20%
        $exceptionalChance = max(0, $skillLevel - 40); // 0-60%
        $superiorChance = max(0, $skillLevel - 10);    // 0-90%

        if ($roll <= $masterpieceChance) {
            return self::MASTERPIECE;
        }
        if ($roll <= $exceptionalChance) {
            return self::EXCEPTIONAL;
        }
        if ($roll <= $superiorChance) {
            return self::SUPERIOR;
        }

        return self::NORMAL;
    }
}
