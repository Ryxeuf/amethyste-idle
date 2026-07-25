<?php

namespace App\GameEngine\Zone;

/**
 * Resultat d'un assaut contre un boss de zone asynchrone (ZON-18).
 */
class ZoneBossAssaultResult
{
    public function __construct(
        public readonly int $damageDealt,
        public readonly int $hpCurrent,
        public readonly int $hpMax,
        public readonly int $totalContribution,
        public readonly bool $defeated,
    ) {
    }
}
