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

    public function icon(): string
    {
        return match ($this) {
            self::Sunny => '☀️',
            self::Cloudy => '☁️',
            self::Rain => '🌧️',
            self::Storm => '⛈️',
            self::Fog => '🌫️',
            self::Snow => '❄️',
        };
    }

    /**
     * Poids de probabilité par défaut (plus élevé = plus fréquent).
     */
    public function weight(): int
    {
        return match ($this) {
            self::Sunny => 40,
            self::Cloudy => 25,
            self::Rain => 15,
            self::Storm => 5,
            self::Fog => 10,
            self::Snow => 5,
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
