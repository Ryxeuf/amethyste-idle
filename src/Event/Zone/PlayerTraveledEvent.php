<?php

namespace App\Event\Zone;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Emis a chaque arrivee d'un joueur dans une zone (ZON-22).
 *
 * Point d'accroche du modele zone pour tout ce qui se declenchait
 * « en se deplacant » du temps de la carte navigable : progression du
 * tutoriel, decouverte de region, suivi de quetes d'exploration/escorte.
 *
 * A distinguer de {@see ZoneVisitedEvent}, emis uniquement a la **premiere**
 * decouverte d'une zone : celui-ci est emis a **chaque** voyage.
 */
class PlayerTraveledEvent extends Event
{
    final public const NAME = 'event.zone.player.traveled';

    public function __construct(
        private readonly Player $player,
        private readonly Zone $zone,
        private readonly ?Zone $fromZone = null,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * Zone d'arrivee (la zone courante du joueur au moment de l'emission).
     */
    public function getZone(): Zone
    {
        return $this->zone;
    }

    /**
     * Zone de depart, si le joueur en avait une.
     */
    public function getFromZone(): ?Zone
    {
        return $this->fromZone;
    }
}
