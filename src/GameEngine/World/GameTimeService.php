<?php

declare(strict_types=1);

namespace App\GameEngine\World;

/**
 * Temps de jeu aligne sur le fuseau UTC : l'heure utilisee pour le cycle jour/nuit est
 * derivee des secondes ecoulees depuis minuit UTC, multipliees par un facteur configurable
 * (defaut 1.0 = meme rythme que l'horloge UTC).
 *
 * Saisons : calendrier gregorien UTC (hemisphere nord).
 * Jour in-game (1-28) : derive du jour de l'annee UTC.
 */
class GameTimeService
{
    public function __construct(
        private readonly UtcDayCycleFactorProviderInterface $utcDayCycleFactorProvider,
    ) {
    }

    /**
     * Secondes depuis minuit UTC (0 .. 86399).
     */
    public function getUtcSecondsSinceMidnight(?\DateTimeInterface $realTime = null): int
    {
        $utc = $this->toUtcImmutable($realTime);
        $midnight = $utc->setTime(0, 0, 0);

        return $utc->getTimestamp() - $midnight->getTimestamp();
    }

    /**
     * Position dans le cycle jour/nuit « jeu » (0 .. 86400), apres application du facteur.
     */
    public function getVirtualSecondsSinceMidnight(?\DateTimeInterface $realTime = null): float
    {
        $factor = $this->utcDayCycleFactorProvider->getUtcDayCycleFactor();
        $offset = $this->getUtcSecondsSinceMidnight($realTime);
        $scaled = fmod($offset * $factor, 86400.0);
        if ($scaled < 0) {
            $scaled += 86400.0;
        }

        return $scaled;
    }

    public function getHour(?\DateTimeInterface $realTime = null): int
    {
        $scaled = $this->getVirtualSecondsSinceMidnight($realTime);

        return (int) floor($scaled / 3600) % 24;
    }

    public function getMinute(?\DateTimeInterface $realTime = null): int
    {
        $scaled = $this->getVirtualSecondsSinceMidnight($realTime);

        return (int) floor(fmod($scaled, 3600.0) / 60) % 60;
    }

    /**
     * - dawn:  6h-8h
     * - day:   8h-18h
     * - dusk:  18h-20h
     * - night: 20h-6h.
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

    public const PHASE_DAY = 'day';
    public const PHASE_NIGHT = 'night';

    /**
     * Phase mecanique jour/nuit (pivot PBBG, ZON-17) : binaire au-dessus du
     * cycle cosmetique fin (`getTimeOfDay`). Jour = 6h-18h ; nuit = 18h-6h
     * (le crepuscule bascule en nuit, l'aube en jour). Sert de curseur de
     * variance aux rencontres, au loot et aux evenements de zone.
     */
    public function getPhase(?\DateTimeInterface $realTime = null): string
    {
        $hour = $this->getHour($realTime);

        return ($hour >= 6 && $hour < 18) ? self::PHASE_DAY : self::PHASE_NIGHT;
    }

    public function isNight(?\DateTimeInterface $realTime = null): bool
    {
        return self::PHASE_NIGHT === $this->getPhase($realTime);
    }

    public function isDay(?\DateTimeInterface $realTime = null): bool
    {
        return self::PHASE_DAY === $this->getPhase($realTime);
    }

    /**
     * Saisons selon le mois UTC (hemisphere nord).
     */
    public function getSeason(?\DateTimeInterface $realTime = null): string
    {
        $m = (int) $this->toUtcImmutable($realTime)->format('n');

        return match (true) {
            \in_array($m, [12, 1, 2], true) => 'winter',
            \in_array($m, [3, 4, 5], true) => 'spring',
            \in_array($m, [6, 7, 8], true) => 'summer',
            default => 'autumn',
        };
    }

    /**
     * Jour in-game 1-28 (cycle sur l'annee UTC).
     */
    public function getDay(?\DateTimeInterface $realTime = null): int
    {
        $doy = (int) $this->toUtcImmutable($realTime)->format('z');

        return ($doy % 28) + 1;
    }

    public function getUtcDayCycleFactor(): float
    {
        return $this->utcDayCycleFactorProvider->getUtcDayCycleFactor();
    }

    /**
     * @return array{
     *     hour: int,
     *     minute: int,
     *     timeOfDay: string,
     *     phase: string,
     *     season: string,
     *     day: int,
     *     utcDayCycleFactor: float,
     *     utcSecondsSinceMidnight: int
     * }
     */
    public function getSnapshot(?\DateTimeInterface $realTime = null): array
    {
        return [
            'hour' => $this->getHour($realTime),
            'minute' => $this->getMinute($realTime),
            'timeOfDay' => $this->getTimeOfDay($realTime),
            'phase' => $this->getPhase($realTime),
            'season' => $this->getSeason($realTime),
            'day' => $this->getDay($realTime),
            'utcDayCycleFactor' => $this->getUtcDayCycleFactor(),
            'utcSecondsSinceMidnight' => $this->getUtcSecondsSinceMidnight($realTime),
        ];
    }

    private function toUtcImmutable(?\DateTimeInterface $realTime): \DateTimeImmutable
    {
        $ts = $realTime?->getTimestamp() ?? time();

        return (new \DateTimeImmutable('@' . $ts))->setTimezone(new \DateTimeZone('UTC'));
    }
}
