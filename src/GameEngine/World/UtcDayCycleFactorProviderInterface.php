<?php

declare(strict_types=1);

namespace App\GameEngine\World;

/**
 * Facteur multiplicateur applique aux secondes ecoulees depuis minuit UTC.
 * 1.0 = l'heure « jeu » pour le cycle jour/nuit suit l'horloge UTC (1 s reelle = 1 s de cycle).
 * 0.5 = cycle 2x plus lent qu'en temps reel (une journee de jeu dure 48 h reelles).
 */
interface UtcDayCycleFactorProviderInterface
{
    public function getUtcDayCycleFactor(): float;
}
