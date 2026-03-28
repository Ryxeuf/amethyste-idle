<?php

namespace App\Enum;

enum GuildQuestType: string
{
    case Kill = 'kill';
    case Collect = 'collect';
    case Craft = 'craft';

    public function label(): string
    {
        return match ($this) {
            self::Kill => 'Élimination',
            self::Collect => 'Récolte',
            self::Craft => 'Artisanat',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Kill => 'sword',
            self::Collect => 'pickaxe',
            self::Craft => 'anvil',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Kill => 'text-red-400',
            self::Collect => 'text-green-400',
            self::Craft => 'text-blue-400',
        };
    }
}
