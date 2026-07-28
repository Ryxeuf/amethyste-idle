<?php

namespace App\GameEngine\Retention;

use App\Enum\WeeklyCommissionReward;

/**
 * Ce qu'une livraison a rendu (RET-02b).
 *
 * Le detail compte : « livree » ne dit rien, alors que « 24 grains au foyer et
 * 2 500 gils » referme la semaine. C'est la moitie du contrat de l'horizon
 * hebdomadaire (GAME_PROGRESSION § 3) — quelque chose qui **finit**, et qu'on
 * voit finir.
 */
final readonly class WeeklyCommissionDeliveryResult
{
    public function __construct(
        public WeeklyCommissionReward $reward,
        public int $grainsDeposited,
        public int $gils,
        public int $energy,
    ) {
    }
}
