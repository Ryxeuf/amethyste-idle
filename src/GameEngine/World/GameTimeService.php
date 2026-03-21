<?php

declare(strict_types=1);

namespace App\GameEngine\World;

/**
 * Converts real-world time to in-game time.
 *
 * Default ratio: 1 real hour = 1 in-game day (24h).
 * The epoch (in-game midnight) is aligned to Unix epoch (1970-01-01 00:00 UTC).
 */
final class GameTimeService
{
    private const int DEFAULT_TIME_RATIO = 24;

    /** How many in-game hours pass per 1 real hour. */
    private readonly int $timeRatio;

    public function __construct(int $gameTimeRatio = self::DEFAULT_TIME_RATIO)
    {
        $this->timeRatio = $gameTimeRatio;
    }

    /**
     * Returns the current in-game hour (0-23).
     */
    public function getHour(?\DateTimeInterface $realTime = null): int
    {
        $ts = $this->getRealTimestamp($realTime);
        $inGameSeconds = $ts * $this->timeRatio;

        return ((int) ($inGameSeconds / 3600)) % 24;
    }

    /**
     * Returns the current in-game minute (0-59).
     */
    public function getMinute(?\DateTimeInterface $realTime = null): int
    {
        $ts = $this->getRealTimestamp($realTime);
        $inGameSeconds = $ts * $this->timeRatio;

        return ((int) ($inGameSeconds / 60)) % 60;
    }

    /**
     * Returns the time-of-day period.
     *
     * - dawn:  6h-8h
     * - day:   8h-18h
     * - dusk:  18h-20h
     * - night: 20h-6h
     */
    public function getTimeOfDay(?\DateTimeInterface $realTime = null): string
    {
        $hour = $this->getHour($realTime);

        if ($hour >= 8 && $hour < 18) {
            return 'day';
        }
        if ($hour >= 6 && $hour < 8) {
            return 'dawn';
        }
        if ($hour >= 18 && $hour < 20) {
            return 'dusk';
        }

        return 'night';
    }

    /**
     * Returns the current in-game season.
     *
     * Each season lasts 7 real days (= 168 in-game days with default ratio).
     * Cycle: spring -> summer -> autumn -> winter (28 real days total).
     */
    public function getSeason(?\DateTimeInterface $realTime = null): string
    {
        $ts = $this->getRealTimestamp($realTime);
        $realDays = (int) ($ts / 86400);
        $seasonIndex = ((int) ($realDays / 7)) % 4;

        return match ($seasonIndex) {
            0 => 'spring',
            1 => 'summer',
            2 => 'autumn',
            3 => 'winter',
            default => 'spring',
        };
    }

    /**
     * Returns the current in-game day number (1-based, cycles 1-28).
     */
    public function getDay(?\DateTimeInterface $realTime = null): int
    {
        $ts = $this->getRealTimestamp($realTime);
        $inGameSeconds = $ts * $this->timeRatio;
        $inGameDays = (int) ($inGameSeconds / 86400);

        return ($inGameDays % 28) + 1;
    }

    /**
     * Returns the configured time ratio.
     */
    public function getTimeRatio(): int
    {
        return $this->timeRatio;
    }

    /**
     * Returns a full snapshot of the current in-game time.
     *
     * @return array{hour: int, minute: int, timeOfDay: string, season: string, day: int, timeRatio: int}
     */
    public function getSnapshot(?\DateTimeInterface $realTime = null): array
    {
        return [
            'hour' => $this->getHour($realTime),
            'minute' => $this->getMinute($realTime),
            'timeOfDay' => $this->getTimeOfDay($realTime),
            'season' => $this->getSeason($realTime),
            'day' => $this->getDay($realTime),
            'timeRatio' => $this->timeRatio,
        ];
    }

    private function getRealTimestamp(?\DateTimeInterface $realTime): int
    {
        return $realTime?->getTimestamp() ?? time();
    }
}
