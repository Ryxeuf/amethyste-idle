<?php

declare(strict_types=1);

namespace App\GameEngine\World;

use App\Entity\App\Map;
use App\Enum\WeatherType;

final class WeatherService
{
    /**
     * Weighted probabilities per season.
     * Higher weight = more likely to be selected.
     *
     * @var array<string, array<string, int>>
     */
    private const array SEASON_WEIGHTS = [
        'spring' => [
            'sunny' => 30,
            'cloudy' => 25,
            'rain' => 25,
            'storm' => 10,
            'fog' => 10,
            'snow' => 0,
        ],
        'summer' => [
            'sunny' => 45,
            'cloudy' => 20,
            'rain' => 15,
            'storm' => 15,
            'fog' => 5,
            'snow' => 0,
        ],
        'autumn' => [
            'sunny' => 15,
            'cloudy' => 30,
            'rain' => 25,
            'storm' => 10,
            'fog' => 20,
            'snow' => 0,
        ],
        'winter' => [
            'sunny' => 10,
            'cloudy' => 25,
            'rain' => 10,
            'storm' => 5,
            'fog' => 20,
            'snow' => 30,
        ],
    ];

    public function __construct(
        private readonly GameTimeService $gameTimeService,
    ) {
    }

    /**
     * Randomly changes the weather on a map, weighted by season.
     * Returns the new weather type.
     */
    public function changeWeather(Map $map): WeatherType
    {
        $season = $this->gameTimeService->getSeason();
        $weights = self::SEASON_WEIGHTS[$season] ?? self::SEASON_WEIGHTS['spring'];

        $newWeather = $this->weightedRandom($weights);
        $map->setCurrentWeather($newWeather);
        $map->setWeatherChangedAt(new \DateTimeImmutable());

        return $newWeather;
    }

    public function getCurrentWeather(Map $map): WeatherType
    {
        return $map->getCurrentWeather() ?? WeatherType::Sunny;
    }

    /**
     * @param array<string, int> $weights
     */
    private function weightedRandom(array $weights): WeatherType
    {
        $totalWeight = array_sum($weights);
        $roll = random_int(1, $totalWeight);

        $cumulative = 0;
        foreach ($weights as $weatherValue => $weight) {
            if ($weight <= 0) {
                continue;
            }
            $cumulative += $weight;
            if ($roll <= $cumulative) {
                return WeatherType::from($weatherValue);
            }
        }

        return WeatherType::Sunny;
    }
}
