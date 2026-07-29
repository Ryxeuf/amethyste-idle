<?php

namespace App\GameEngine\Settlement;

/**
 * Adoption de doctrine refusee (FOY-13). Le message est une cle de traduction
 * `game.zone.doctrine.error.*`, affichable telle quelle en flash.
 */
class SettlementDoctrineException extends \RuntimeException
{
}
