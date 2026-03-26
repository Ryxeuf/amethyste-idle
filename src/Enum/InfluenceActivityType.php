<?php

namespace App\Enum;

enum InfluenceActivityType: string
{
    case MobKill = 'mob_kill';
    case Craft = 'craft';
    case Harvest = 'harvest';
    case Fishing = 'fishing';
    case Butchering = 'butchering';
    case Quest = 'quest';
    case Challenge = 'challenge';

    public function label(): string
    {
        return match ($this) {
            self::MobKill => 'Chasse',
            self::Craft => 'Artisanat',
            self::Harvest => 'Cueillette',
            self::Fishing => 'Peche',
            self::Butchering => 'Depecage',
            self::Quest => 'Quete',
            self::Challenge => 'Defi',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MobKill => 'sword',
            self::Craft => 'hammer',
            self::Harvest => 'leaf',
            self::Fishing => 'fish',
            self::Butchering => 'knife',
            self::Quest => 'scroll',
            self::Challenge => 'trophy',
        };
    }
}
