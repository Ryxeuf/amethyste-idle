<?php

namespace App\GameEngine\Zone;

/**
 * Action de zone refusee (ZON-08+). Le message est une cle de traduction
 * `game.zone.explore.error.*` (ou equivalente), affichable telle quelle en flash.
 */
class ZoneActionException extends \RuntimeException
{
}
