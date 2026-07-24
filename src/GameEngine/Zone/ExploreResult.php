<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Fight;

/**
 * Resultat d'une exploration (ZON-08). `messageKey` est une cle de traduction
 * `game.zone.explore.result.*`, `messageParams` ses parametres (%monster%...).
 */
final readonly class ExploreResult
{
    public const EVENT_MOB = 'mob';
    public const EVENT_CHEST = 'chest';
    public const EVENT_HARVEST = 'harvest';
    public const EVENT_PNJ = 'pnj';
    public const EVENT_NOTHING = 'nothing';

    /**
     * @param array<string, string|int> $messageParams
     */
    public function __construct(
        public string $event,
        public string $messageKey,
        public array $messageParams = [],
        public ?Fight $fight = null,
    ) {
    }
}
