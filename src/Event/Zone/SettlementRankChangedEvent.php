<?php

namespace App\Event\Zone;

use App\Entity\App\Settlement;
use App\Enum\SettlementRank;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Le rang d'un foyer a change (FOY-03).
 *
 * Emis **dans les deux sens**. Une montee se fete, une descente se dit : c'est
 * la meme information, et la taire a la descente donnerait un monde ou les
 * villes ne font que grandir — l'inverse exact de ce que la decroissance existe
 * pour raconter.
 *
 * Point d'accroche des annonces Mercure (FOY-04), du journal de monde (FOY-14)
 * et de l'annonce d'etiage une maree a l'avance (FOY-10).
 */
class SettlementRankChangedEvent extends Event
{
    final public const NAME = 'event.zone.settlement.rank_changed';

    public function __construct(
        private readonly Settlement $settlement,
        private readonly SettlementRank $from,
        private readonly SettlementRank $to,
    ) {
    }

    public function getSettlement(): Settlement
    {
        return $this->settlement;
    }

    public function getFrom(): SettlementRank
    {
        return $this->from;
    }

    public function getTo(): SettlementRank
    {
        return $this->to;
    }

    public function isPromotion(): bool
    {
        return $this->to->level() > $this->from->level();
    }
}
