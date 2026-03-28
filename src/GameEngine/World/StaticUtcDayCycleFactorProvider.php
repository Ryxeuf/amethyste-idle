<?php

declare(strict_types=1);

namespace App\GameEngine\World;

/** @internal Tests et constructions manuelles */
final class StaticUtcDayCycleFactorProvider implements UtcDayCycleFactorProviderInterface
{
    public function __construct(private readonly float $factor)
    {
    }

    public function getUtcDayCycleFactor(): float
    {
        return $this->factor;
    }
}
