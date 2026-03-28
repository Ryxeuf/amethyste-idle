<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\World;

use App\GameEngine\World\GameTimeService;
use App\GameEngine\World\StaticUtcDayCycleFactorProvider;
use App\GameEngine\World\WeatherService;
use App\Entity\App\Map;
use App\Enum\WeatherType;
use PHPUnit\Framework\TestCase;

class WeatherServiceSeasonTest extends TestCase
{
    private function createService(string $month): WeatherService
    {
        $time = new \DateTimeImmutable("2024-{$month}-15 12:00:00", new \DateTimeZone('UTC'));
        $gameTime = $this->createMock(GameTimeService::class);
        $season = match (true) {
            \in_array((int) $month, [12, 1, 2], true) => 'winter',
            \in_array((int) $month, [3, 4, 5], true) => 'spring',
            \in_array((int) $month, [6, 7, 8], true) => 'summer',
            default => 'autumn',
        };
        $gameTime->method('getSeason')->willReturn($season);

        return new WeatherService($gameTime);
    }

    public function testChangeWeatherReturnsBool(): void
    {
        $service = $this->createService('06');
        $map = $this->createMock(Map::class);
        $map->method('getCurrentWeather')->willReturn(WeatherType::Sunny);
        $map->expects($this->any())->method('setCurrentWeather');
        $map->expects($this->any())->method('setWeatherChangedAt');

        // The result should be a boolean (could be true or false depending on random roll)
        $result = $service->changeWeather($map);
        $this->assertIsBool($result);
    }

    /**
     * Run many weather rolls in summer and verify no snow appears.
     */
    public function testSummerNeverProducesSnow(): void
    {
        $service = $this->createService('07'); // July = summer
        $map = $this->createMock(Map::class);
        $map->method('getCurrentWeather')->willReturn(null);
        $map->expects($this->any())->method('setCurrentWeather')->willReturnCallback(
            function (WeatherType $weather) {
                $this->assertNotSame(WeatherType::Snow, $weather, 'Snow should never occur in summer');
            }
        );
        $map->expects($this->any())->method('setWeatherChangedAt');

        for ($i = 0; $i < 200; ++$i) {
            $service->changeWeather($map);
        }
    }
}
