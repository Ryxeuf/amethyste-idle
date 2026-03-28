<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\World;

use App\Entity\App\Map;
use App\Enum\WeatherType;
use App\GameEngine\World\GameTimeService;
use App\GameEngine\World\WeatherService;
use PHPUnit\Framework\TestCase;

class WeatherServiceSeasonTest extends TestCase
{
    private function createService(string $season): WeatherService
    {
        $gameTime = $this->createMock(GameTimeService::class);
        $gameTime->method('getSeason')->willReturn($season);

        return new WeatherService($gameTime);
    }

    public function testChangeWeatherReturnsBool(): void
    {
        $service = $this->createService('summer');
        $map = $this->createMock(Map::class);
        $map->method('getCurrentWeather')->willReturn(WeatherType::Sunny);
        $map->expects($this->any())->method('setCurrentWeather');
        $map->expects($this->any())->method('setWeatherChangedAt');

        $result = $service->changeWeather($map);
        $this->assertIsBool($result);
    }

    /**
     * Run many weather rolls in summer and verify no snow appears.
     */
    public function testSummerNeverProducesSnow(): void
    {
        $service = $this->createService('summer');
        $weathersSeen = [];
        $map = $this->createMock(Map::class);
        $map->method('getCurrentWeather')->willReturn(WeatherType::Cloudy);
        $map->expects($this->any())->method('setCurrentWeather')->willReturnCallback(
            function (WeatherType $weather) use (&$weathersSeen): void {
                $weathersSeen[$weather->value] = true;
                $this->assertNotSame(WeatherType::Snow, $weather, 'Snow should never occur in summer');
            }
        );
        $map->expects($this->any())->method('setWeatherChangedAt');

        for ($i = 0; $i < 200; ++$i) {
            $service->changeWeather($map);
        }

        $this->assertNotEmpty($weathersSeen, 'At least one weather change should have occurred');
    }
}
