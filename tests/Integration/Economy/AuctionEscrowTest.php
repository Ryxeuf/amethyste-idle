<?php

namespace App\Tests\Integration\Economy;

use App\Entity\App\AuctionListing;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
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
 *
 * L'annonce est deposee via `createListing` plutot que construite a la main :
 * un scenario d'escrow doit partir de l'etat que la production produit, pas
 * d'un etat reconstitue qui pourrait diverger.
 */
final class AuctionEscrowTest extends AbstractIntegrationTestCase
{
    /**
     * Prix valide pour **toutes** les raretes : les bornes par rarete vont de
     * `common` (max 10 000) a `amethyst` (min 5 000), et 5 000 est la seule
     * plage qui les satisfait toutes. Le test ne doit pas dependre de la rarete
     * du premier objet trouve dans le sac de fixture.
     */
    private const SAFE_PRICE = 5_000;

    public function testExpiredListingReturnsTheItemToItsSeller(): void
    {
        $seller = $this->getPlayer();
        $playerItem = $this->firstSellableItem($seller);

        if (null === $playerItem) {
            self::markTestSkipped('Le joueur de fixture n\'a aucun objet echangeable en sac.');
        }

        $seller->addGils(20_000);
        $manager = $this->getService(AuctionManager::class);
        $listing = $manager->createListing($seller, $playerItem, self::SAFE_PRICE);

        self::assertNull($playerItem->getInventory(), 'Le depot doit sortir l\'objet de l\'inventaire.');

        // On ne peut pas attendre 48 h : l'echeance est ramenee dans le passe.
        $listing->setExpiresAt(new \DateTimeImmutable('-1 hour'));
        $this->em->flush();

        $expired = $manager->expireListings();

        self::assertGreaterThanOrEqual(1, $expired);
        self::assertSame(AuctionStatus::Expired, $listing->getStatus());
        self::assertNotNull(
            $playerItem->getInventory(),
            'Une annonce expiree sans acheteur doit rendre l\'objet : sinon il est detruit.'
        );
        self::assertSame($seller->getId(), $playerItem->getInventory()?->getPlayer()?->getId());
    }

    public function testCancelledListingReturnsTheItemToItsSeller(): void
    {
        $seller = $this->getPlayer();
        $playerItem = $this->firstSellableItem($seller);

        if (null === $playerItem) {
            self::markTestSkipped('Le joueur de fixture n\'a aucun objet echangeable en sac.');
        }

        $seller->addGils(20_000);
        $manager = $this->getService(AuctionManager::class);
        $listing = $manager->createListing($seller, $playerItem, self::SAFE_PRICE);

        $manager->cancelListing($seller, $listing);

        self::assertSame(AuctionStatus::Cancelled, $listing->getStatus());
        self::assertNotNull($playerItem->getInventory());
    }

    /**
     * Premier objet du sac que l'hotel des ventes accepterait : les objets lies
     * (ECO-01) sont refuses au depot et ne peuvent pas servir de scenario.
     */
    private function firstSellableItem(Player $player): ?PlayerItem
    {
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->getType() !== Inventory::TYPE_BAG) {
                continue;
            }

            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem instanceof PlayerItem && $playerItem->isExchangeable()) {
                    return $playerItem;
                }
            }
        }

        return null;
    }

    /**
     * Garde-fou de lisibilite : le test ci-dessus ne prouve rien si l'annonce
     * n'a jamais quitte l'etat actif.
     */
    public function testListingIsActiveRightAfterCreation(): void
    {
        $seller = $this->getPlayer();
        $playerItem = $this->firstSellableItem($seller);

        if (null === $playerItem) {
            self::markTestSkipped('Le joueur de fixture n\'a aucun objet echangeable en sac.');
        }

        $seller->addGils(20_000);
        $listing = $this->getService(AuctionManager::class)->createListing($seller, $playerItem, self::SAFE_PRICE);

        self::assertInstanceOf(AuctionListing::class, $listing);
        self::assertTrue($listing->isActive());
    }
}
