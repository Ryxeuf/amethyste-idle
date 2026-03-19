<?php

namespace App\Enum;

enum Element: string
{
    case None = 'none';
    case Fire = 'fire';
    case Water = 'water';
    case Earth = 'earth';
    case Air = 'air';
    case Light = 'light';
    case Dark = 'dark';
    case Metal = 'metal';
    case Beast = 'beast';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Aucun',
            self::Fire => 'Feu',
            self::Water => 'Eau',
            self::Earth => 'Terre',
            self::Air => 'Air',
            self::Light => 'Lumière',
            self::Dark => 'Ténèbres',
            self::Metal => 'Métal',
            self::Beast => 'Bête',
        };
    }

    /**
     * @return array<string, self>
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->label()] = $case;
        }

        return $choices;
    }
}
