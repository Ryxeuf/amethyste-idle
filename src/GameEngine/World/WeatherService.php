<?php

namespace App\GameEngine\World;

use App\Entity\App\Map;
use App\Enum\WeatherType;

class WeatherService
{
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
