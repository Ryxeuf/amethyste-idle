<?php

namespace App\GameEngine\Retention;

/**
 * Un palier d'assiduite : un seuil de jours actifs, et ce qu'il rend (RET-04).
 */
final readonly class WeeklyAttendanceTier
{
    public function __construct(
        public int $days,
        public int $gils,
        public int $energy,
    ) {
    }
}
