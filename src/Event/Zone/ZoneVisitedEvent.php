<?php

namespace App\Event\Zone;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Emis la premiere fois qu'un joueur decouvre une zone (pivot PBBG, ZON-06).
 * Declenche notamment le deblocage des entrees de Codex `zone_visit` (NAR-05).
 */
class ZoneVisitedEvent extends Event
{
    final public const NAME = 'event.zone.visited';

    public function __construct(
        private readonly Player $player,
        private readonly Zone $zone,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getZone(): Zone
    {
        return $this->zone;
    }
}
