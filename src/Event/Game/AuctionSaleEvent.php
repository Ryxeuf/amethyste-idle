<?php

namespace App\Event\Game;

use App\Entity\App\AuctionListing;
use App\Entity\App\AuctionTransaction;
use App\Entity\App\Player;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Une vente conclue a l'hotel des ventes (FAC-02).
 *
 * Emis par `AuctionManager` aux deux points ou une annonce devient une
 * transaction : l'achat direct (`buyListing`) et l'enchere finalisee
 * (`finalizeAuction`). Le vendeur a mis quelque chose sur le marche et le
 * marche l'a pris — c'est le geste marchand, et c'est lui que le routage
 * geste → faction ecoute.
 */
class AuctionSaleEvent extends Event
{
    final public const NAME = 'event.game.auction.sale';

    public function __construct(
        private readonly AuctionListing $listing,
        private readonly AuctionTransaction $transaction,
    ) {
    }

    public function getListing(): AuctionListing
    {
        return $this->listing;
    }

    public function getTransaction(): AuctionTransaction
    {
        return $this->transaction;
    }

    public function getSeller(): Player
    {
        return $this->listing->getSeller();
    }
}
