<?php

namespace App\Enum;

enum WeatherType: string
{
    case Sunny = 'sunny';
    case Cloudy = 'cloudy';
    case Rain = 'rain';
    case Storm = 'storm';
    case Fog = 'fog';
    case Snow = 'snow';

    public function label(): string
    {
        return match ($this) {
            self::Sunny => 'Ensoleillé',
            self::Cloudy => 'Nuageux',
            self::Rain => 'Pluie',
            self::Storm => 'Orage',
            self::Fog => 'Brouillard',
            self::Snow => 'Neige',
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
