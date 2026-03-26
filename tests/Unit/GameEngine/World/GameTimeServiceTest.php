<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\World;

use App\GameEngine\World\GameTimeService;
use App\GameEngine\World\StaticUtcDayCycleFactorProvider;
use PHPUnit\Framework\TestCase;

class GameTimeServiceTest extends TestCase
{
    private function service(float $factor = 1.0): GameTimeService
    {
        return new GameTimeService(new StaticUtcDayCycleFactorProvider($factor));
    }

    public function testGetHourAtUtcMidnightWithFactor1(): void
    {
        $time = new \DateTimeImmutable('2024-06-15 00:00:00', new \DateTimeZone('UTC'));
        $this->assertSame(0, $this->service(1.0)->getHour($time));
        $this->assertSame(0, $this->service(1.0)->getMinute($time));
    }

    public function testGetHourMatchesUtcClockWithFactor1(): void
    {
        $time = new \DateTimeImmutable('2024-06-15 14:32:09', new \DateTimeZone('UTC'));
        $this->assertSame(14, $this->service(1.0)->getHour($time));
        $this->assertSame(32, $this->service(1.0)->getMinute($time));
    }

    public function testFactorHalfSlowsCycleAtNoonUtc(): void
    {
        $time = new \DateTimeImmutable('2024-01-01 12:00:00', new \DateTimeZone('UTC'));
        // 12h UTC * 0.5 = 6h virtuelles
        $this->assertSame(6, $this->service(0.5)->getHour($time));
    }

    public function testFactorTwoDoublesSpeedAtNoonUtc(): void
    {
        $time = new \DateTimeImmutable('2024-01-01 12:00:00', new \DateTimeZone('UTC'));
        $this->assertSame(0, $this->service(2.0)->getHour($time));
        $this->assertSame(0, $this->service(2.0)->getMinute($time));
    }

    public function testGetTimeOfDayDay(): void
    {
        $time = new \DateTimeImmutable('2024-03-10 10:15:00', new \DateTimeZone('UTC'));
        $this->assertSame('day', $this->service(1.0)->getTimeOfDay($time));
    }

    public function testGetTimeOfDayDawn(): void
    {
        $time = new \DateTimeImmutable('2024-03-10 07:00:00', new \DateTimeZone('UTC'));
        $this->assertSame('dawn', $this->service(1.0)->getTimeOfDay($time));
    }

    public function testGetTimeOfDayDusk(): void
    {
        $time = new \DateTimeImmutable('2024-03-10 19:00:00', new \DateTimeZone('UTC'));
        $this->assertSame('dusk', $this->service(1.0)->getTimeOfDay($time));
    }

    public function testGetTimeOfDayNight(): void
    {
        $time = new \DateTimeImmutable('2024-03-10 22:00:00', new \DateTimeZone('UTC'));
        $this->assertSame('night', $this->service(1.0)->getTimeOfDay($time));
    }

    public function testGetSeasonByUtcMonth(): void
    {
        $this->assertSame('winter', $this->service(1.0)->getSeason(new \DateTimeImmutable('2024-01-15 12:00:00', new \DateTimeZone('UTC'))));
        $this->assertSame('spring', $this->service(1.0)->getSeason(new \DateTimeImmutable('2024-04-10 12:00:00', new \DateTimeZone('UTC'))));
        $this->assertSame('summer', $this->service(1.0)->getSeason(new \DateTimeImmutable('2024-07-20 12:00:00', new \DateTimeZone('UTC'))));
        $this->assertSame('autumn', $this->service(1.0)->getSeason(new \DateTimeImmutable('2024-10-05 12:00:00', new \DateTimeZone('UTC'))));
    }

    public function testGetDayCycles28FromDayOfYear(): void
    {
        $time = new \DateTimeImmutable('2024-01-01 12:00:00', new \DateTimeZone('UTC'));
        $this->assertSame(1, $this->service(1.0)->getDay($time));

        $time = new \DateTimeImmutable('2024-01-28 12:00:00', new \DateTimeZone('UTC'));
        $this->assertSame(28, $this->service(1.0)->getDay($time));

        $time = new \DateTimeImmutable('2024-01-29 12:00:00', new \DateTimeZone('UTC'));
        $this->assertSame(1, $this->service(1.0)->getDay($time));
    }

    public function testGetSnapshotReturnsExpectedKeys(): void
    {
        $time = new \DateTimeImmutable('2024-06-01 08:00:00', new \DateTimeZone('UTC'));
        $snapshot = $this->service(0.25)->getSnapshot($time);

        $this->assertArrayHasKey('hour', $snapshot);
        $this->assertArrayHasKey('minute', $snapshot);
        $this->assertArrayHasKey('timeOfDay', $snapshot);
        $this->assertArrayHasKey('season', $snapshot);
        $this->assertArrayHasKey('day', $snapshot);
        $this->assertArrayHasKey('utcDayCycleFactor', $snapshot);
        $this->assertArrayHasKey('utcSecondsSinceMidnight', $snapshot);
        $this->assertSame(0.25, $snapshot['utcDayCycleFactor']);
        $this->assertSame(8 * 3600, $snapshot['utcSecondsSinceMidnight']);
    }

    public function testConvertsLocalWallClockToUtc(): void
    {
        // 15:00 a Paris (ete UTC+2) = 13:00 UTC
        $time = new \DateTimeImmutable('2024-07-15 15:00:00', new \DateTimeZone('Europe/Paris'));
        $this->assertSame(13, $this->service(1.0)->getHour($time));
    }
}
