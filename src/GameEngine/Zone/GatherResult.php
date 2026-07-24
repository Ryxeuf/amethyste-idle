<?php

namespace App\GameEngine\Zone;

/**
 * Resultat d'une recolte (ZON-10). `messageKey` est une cle de traduction
 * `game.zone.gather.result.*`, `messageParams` ses parametres (%item%, %count%).
 */
final readonly class GatherResult
{
    /**
     * @param array<string, string|int> $messageParams
     */
    public function __construct(
        public string $slug,
        public string $itemName,
        public int $quantity,
        public int $remainingStock,
        public string $messageKey = 'game.zone.gather.result.success',
        public array $messageParams = [],
    ) {
    }
}
