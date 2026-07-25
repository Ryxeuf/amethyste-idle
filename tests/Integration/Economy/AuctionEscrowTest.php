<?php

namespace App\Tests\Integration\Economy;

use App\Entity\App\AuctionListing;
use App\Entity\App\Inventory;
use App\Entity\App\PlayerItem;
use App\Enum\AuctionStatus;
use App\GameEngine\Auction\AuctionManager;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Escrow d'objet a l'expiration (ECO-16b).
 *
 * L'objet quitte l'inventaire au moment du depot. S'il ne revient pas quand
 * l'annonce expire sans acheteur, il est purement et simplement **detruit** —
 * et silencieusement, puisque rien n'echoue.
 *
 * ECO-16a laissait ce chemin sans test : le couvrir en unitaire demandait de
 * simuler le constructeur de requetes Doctrine, un mock qui casse au premier
 * changement de type de retour de l'ORM. Il est exerce ici contre une vraie
 * base, ou `expireListings` execute sa requete pour de bon.
 */
final class AuctionEscrowTest extends AbstractIntegrationTestCase
{
    public function testExpiredListingReturnsTheItemToItsSeller(): void
    {
        $seller = $this->getPlayer();
        $bag = $this->bagOf($seller);

        $playerItem = $bag->getItems()->first();
        self::assertInstanceOf(PlayerItem::class, $playerItem, 'Le joueur de fixture doit avoir un objet en sac.');

        // Etat d'une annonce deposee : l'objet est sorti de l'inventaire.
        $listing = new AuctionListing();
        $listing->setSeller($seller);
        $listing->setPlayerItem($playerItem);
        $listing->setQuantity(1);
        $listing->setPricePerUnit(100);
        $listing->setListingFee(5);
        $listing->setRegionTaxRate('0.0000');
        $listing->setExpiresAt(new \DateTimeImmutable('-1 hour'));
        $playerItem->setInventory(null);

        $this->em->persist($listing);
        $this->em->flush();

        $expired = $this->getService(AuctionManager::class)->expireListings();

        self::assertGreaterThanOrEqual(1, $expired);
        self::assertSame(AuctionStatus::Expired, $listing->getStatus());
        self::assertNotNull(
            $playerItem->getInventory(),
            'Une annonce expiree sans acheteur doit rendre l\'objet : sinon il est detruit.'
        );
        self::assertSame($seller->getId(), $playerItem->getInventory()?->getPlayer()?->getId());
    }

    private function bagOf(\App\Entity\App\Player $player): Inventory
    {
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->getType() === Inventory::TYPE_BAG) {
                return $inventory;
            }
        }

        self::fail('Le joueur de fixture n\'a pas de sac.');
    }
}
