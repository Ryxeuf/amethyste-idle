<?php

namespace App\Enum;

enum ItemRarity: string
{
    case Common = 'common';
    case Uncommon = 'uncommon';
    case Rare = 'rare';
    case Epic = 'epic';
    case Legendary = 'legendary';
    case Amethyst = 'amethyst';

    public function label(): string
    {
        return match ($this) {
            self::Common => 'Commun',
            self::Uncommon => 'Peu commun',
            self::Rare => 'Rare',
            self::Epic => 'Épique',
            self::Legendary => 'Légendaire',
            self::Amethyst => 'Améthyste',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Common => 'text-gray-400',
            self::Uncommon => 'text-green-300',
            self::Rare => 'text-blue-300',
            self::Epic => 'text-purple-300',
            self::Legendary => 'text-yellow-400',
            self::Amethyst => 'text-fuchsia-400',
        };
    }

    public function bgClass(): string
    {
        return match ($this) {
            self::Common => 'bg-gray-700/50 text-gray-400',
            self::Uncommon => 'bg-green-900/50 text-green-400',
            self::Rare => 'bg-blue-900/50 text-blue-400',
            self::Epic => 'bg-purple-900/50 text-purple-400',
            self::Legendary => 'bg-yellow-900/50 text-yellow-400',
            self::Amethyst => 'bg-fuchsia-900/50 text-fuchsia-400',
        };
    }

    public function borderClass(): string
    {
        return match ($this) {
            self::Common => 'equip-rarity-common',
            self::Uncommon => 'equip-rarity-uncommon',
            self::Rare => 'equip-rarity-rare',
            self::Epic => 'equip-rarity-epic',
            self::Legendary => 'equip-rarity-legendary',
            self::Amethyst => 'equip-rarity-amethyst',
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
