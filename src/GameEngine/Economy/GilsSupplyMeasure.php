<?php

declare(strict_types=1);

namespace App\GameEngine\Economy;

/**
 * Masse monetaire comptee a l'instant, sans ecriture (ECO-15).
 */
final readonly class GilsSupplyMeasure
{
    public function __construct(
        public int $playerGils,
        public int $guildGils,
        public int $shopGils,
        public int $escrowGils,
        public int $playerCount,
    ) {
    }

    public function total(): int
    {
        return $this->playerGils + $this->guildGils + $this->shopGils + $this->escrowGils;
    }

    public function perCapita(): float
    {
        return $this->playerCount > 0 ? $this->total() / $this->playerCount : 0.0;
    }
}
