<?php

namespace App\GameEngine\Zone;

/**
 * Butin d'une expedition recuperee (ZON-13).
 */
class ExpeditionClaimResult
{
    /**
     * @param list<array{name: string, quantity: int}> $items
     */
    public function __construct(
        public readonly string $zoneName,
        public readonly int $gils,
        public readonly array $items,
    ) {
    }
}
