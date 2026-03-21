<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\World;

use App\GameEngine\World\GameTimeService;
use PHPUnit\Framework\TestCase;

class GameTimeServiceTest extends TestCase
{
    public function testGetHourAtMidnight(): void
    {
        $service = new GameTimeService(24);
        // Unix epoch = midnight in-game
        $time = new \DateTimeImmutable('@0');
        $this->assertSame(0, $service->getHour($time));
    }

    public function testGetHourProgresses(): void
    {
        $service = new GameTimeService(24);
        // After 150 real seconds = 150*24 = 3600 in-game seconds = 1 in-game hour
        $time = new \DateTimeImmutable('@150');
        $this->assertSame(1, $service->getHour($time));
    }

    public function testGetMinute(): void
    {
        $service = new GameTimeService(24);
        // After 100 real seconds = 2400 in-game seconds = 40 in-game minutes
        $time = new \DateTimeImmutable('@100');
        $this->assertSame(40, $service->getMinute($time));
    }

    public function testGetTimeOfDayDay(): void
    {
        $service = new GameTimeService(24);
        // 10h in-game = 10*3600/24 = 1500 real seconds from epoch
        $time = new \DateTimeImmutable('@1500');
        $this->assertSame('day', $service->getTimeOfDay($time));
    }

    public function testGetTimeOfDayDawn(): void
    {
        $service = new GameTimeService(24);
        // 6h in-game = 6*3600/24 = 900 real seconds
        $time = new \DateTimeImmutable('@900');
        $this->assertSame('dawn', $service->getTimeOfDay($time));
    }

    public function testGetTimeOfDayDusk(): void
    {
        $service = new GameTimeService(24);
        // 19h in-game = 19*3600/24 = 2850 real seconds
        $time = new \DateTimeImmutable('@2850');
        $this->assertSame('dusk', $service->getTimeOfDay($time));
    }

    public function testGetTimeOfDayNight(): void
    {
        $service = new GameTimeService(24);
        // 22h in-game = 22*3600/24 = 3300 real seconds
        $time = new \DateTimeImmutable('@3300');
        $this->assertSame('night', $service->getTimeOfDay($time));
    }

    public function testGetSeasonCycles(): void
    {
        $service = new GameTimeService(24);

        // Day 0 = spring
        $time = new \DateTimeImmutable('@0');
        $this->assertSame('spring', $service->getSeason($time));

        // Day 7 = summer (7 * 86400 seconds)
        $time = new \DateTimeImmutable('@' . (7 * 86400));
        $this->assertSame('summer', $service->getSeason($time));

        // Day 14 = autumn
        $time = new \DateTimeImmutable('@' . (14 * 86400));
        $this->assertSame('autumn', $service->getSeason($time));

        // Day 21 = winter
        $time = new \DateTimeImmutable('@' . (21 * 86400));
        $this->assertSame('winter', $service->getSeason($time));

        // Day 28 = spring again
        $time = new \DateTimeImmutable('@' . (28 * 86400));
        $this->assertSame('spring', $service->getSeason($time));
    }

    public function testGetDayIsCyclic(): void
    {
        $service = new GameTimeService(24);

        $time = new \DateTimeImmutable('@0');
        $this->assertSame(1, $service->getDay($time));

        // After 1 real hour = 1 in-game day with ratio 24
        $time = new \DateTimeImmutable('@3600');
        $this->assertSame(2, $service->getDay($time));
    }

    public function testGetSnapshotReturnsAllKeys(): void
    {
        $service = new GameTimeService(24);
        $time = new \DateTimeImmutable('@0');
        $snapshot = $service->getSnapshot($time);

        $this->assertArrayHasKey('hour', $snapshot);
        $this->assertArrayHasKey('minute', $snapshot);
        $this->assertArrayHasKey('timeOfDay', $snapshot);
        $this->assertArrayHasKey('season', $snapshot);
        $this->assertArrayHasKey('day', $snapshot);
        $this->assertArrayHasKey('timeRatio', $snapshot);
        $this->assertSame(24, $snapshot['timeRatio']);
    }

    public function testCustomTimeRatio(): void
    {
        // Ratio 12 = 12 in-game hours per 1 real hour
        $service = new GameTimeService(12);
        // After 300 real seconds = 300*12 = 3600 in-game seconds = 1 in-game hour
        $time = new \DateTimeImmutable('@300');
        $this->assertSame(1, $service->getHour($time));
        $this->assertSame(12, $service->getTimeRatio());
    }

    public function testHourWrapsAround24(): void
    {
        $service = new GameTimeService(24);
        // 25 in-game hours should wrap to 1
        // 25*3600/24 = 3750 real seconds
        $time = new \DateTimeImmutable('@3750');
        $this->assertSame(1, $service->getHour($time));
    }
}
