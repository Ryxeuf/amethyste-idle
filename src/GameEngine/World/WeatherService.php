<?php

namespace App\GameEngine\World;

use App\Entity\App\Map;
use App\Enum\Element;
use App\Enum\WeatherType;

class WeatherService
{
    /**
     * Table des modificateurs élémentaires par météo.
     * Clé = weather.value, sous-clé = element.value, valeur = multiplicateur (1.0 = neutre).
     *
     * @var array<string, array<string, float>>
     */
    private const WEATHER_ELEMENT_MODIFIERS = [
        'rain' => [
            'water' => 1.20,
            'fire' => 0.80,
        ],
        'storm' => [
            'water' => 1.20,
            'air' => 1.20,
            'fire' => 0.80,
            'metal' => 0.90,
        ],
        'snow' => [
            'water' => 1.10,
            'fire' => 0.80,
        ],
        'fog' => [
            'dark' => 1.20,
            'light' => 0.80,
        ],
        'sunny' => [
            'fire' => 1.20,
            'light' => 1.10,
            'water' => 0.90,
        ],
    ];

    /**
     * Retourne le multiplicateur de dégâts élémentaire en fonction de la météo.
     *
     * @return float 1.0 = neutre, > 1.0 = bonus, < 1.0 = malus
     */
    public function getElementalModifier(WeatherType $weather, Element $element): float
    {
        return self::WEATHER_ELEMENT_MODIFIERS[$weather->value][$element->value] ?? 1.0;
    }

    /**
     * Tire une météo aléatoire pondérée et l'applique à la carte.
     *
     * @return bool true si la météo a changé
     */
    public function changeWeather(Map $map): bool
    {
        $newWeather = $this->rollWeather();

        if ($newWeather === $map->getCurrentWeather()) {
            return false;
        }

        $map->setCurrentWeather($newWeather);
        $map->setWeatherChangedAt(new \DateTimeImmutable());

        return true;
    }

    /**
     * Force la météo sur une carte (admin / scripts).
     */
    public function applyWeather(Map $map, WeatherType $weather): void
    {
        $map->setCurrentWeather($weather);
        $map->setWeatherChangedAt(new \DateTimeImmutable());
    }

    /**
     * Tire une météo aléatoire pondérée par les poids de chaque type.
     */
    private function rollWeather(): WeatherType
    {
        $cases = WeatherType::cases();
        $totalWeight = 0;
        foreach ($cases as $case) {
            $totalWeight += $case->weight();
        }

        $roll = random_int(1, $totalWeight);
        $cumulative = 0;
        foreach ($cases as $case) {
            $cumulative += $case->weight();
            if ($roll <= $cumulative) {
                return $case;
            }
        }

        return WeatherType::Sunny;
    }
}
