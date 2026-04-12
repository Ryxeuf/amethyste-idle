<?php

namespace App\Tests\Unit\GameEngine\Auction;

use App\Entity\App\AuctionListing;
use App\Entity\App\Inventory;
use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Region;
use App\Entity\Game\Item;
use App\Enum\AuctionStatus;
use App\GameEngine\Auction\AuctionManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuctionManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private AuctionManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->manager = new AuctionManager($this->em);
    }

    public function testCreateListingSuccess(): void
    {
        $seller = $this->createPlayer(1, 1000);
        $item = $this->createPlayerItem();

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $listing = $this->manager->createListing($seller, $item, 100, 1);

        $this->assertSame($seller, $listing->getSeller());
        $this->assertSame($item, $listing->getPlayerItem());
        $this->assertSame(100, $listing->getPricePerUnit());
        $this->assertSame(1, $listing->getQuantity());
        $this->assertSame(5, $listing->getListingFee()); // 5% de 100
        $this->assertSame(AuctionStatus::Active, $listing->getStatus());
        $this->assertSame(995, $seller->getGils()); // 1000 - 5
        $this->assertNull($item->getInventory()); // item retire de l'inventaire
    }

    public function testCreateListingWithRegionTax(): void
    {
        $region = new Region();
        $region->setName('Test')->setSlug('test')->setTaxRate('0.1000');

        $map = $this->createMock(Map::class);
        $map->method('getRegion')->willReturn($region);

        $seller = $this->createPlayer(1, 1000, $map);
        $item = $this->createPlayerItem();

        $this->em->expects($this->once())->method('persist');

        $listing = $this->manager->createListing($seller, $item, 200, 1);

        $this->assertSame('0.1000', $listing->getRegionTaxRate());
        $this->assertSame(10, $listing->getListingFee()); // 5% de 200
    }

    public function testCreateListingInsufficientFunds(): void
    {
        $seller = $this->createPlayer(1, 0);
        $item = $this->createPlayerItem();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fonds insuffisants');

        $this->manager->createListing($seller, $item, 100, 1);
    }

    public function testCreateListingInvalidPrice(): void
    {
        $seller = $this->createPlayer(1, 1000);
        $item = $this->createPlayerItem();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('prix');

        $this->manager->createListing($seller, $item, 0, 1);
    }

    public function testBuyListingSuccess(): void
    {
        $seller = $this->createPlayer(1, 0);
        $buyer = $this->createPlayer(2, 500);
        $item = $this->createPlayerItem();

        $listing = new AuctionListing();
        $listing->setSeller($seller);
        $listing->setPlayerItem($item);
        $listing->setQuantity(1);
        $listing->setPricePerUnit(100);
        $listing->setListingFee(5);
        $listing->setRegionTaxRate('0.0000');
        $listing->setExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $transaction = $this->manager->buyListing($buyer, $listing);

        $this->assertSame(AuctionStatus::Sold, $listing->getStatus());
        $this->assertSame(400, $buyer->getGils()); // 500 - 100
        $this->assertSame(100, $seller->getGils()); // 0 + 100 (pas de taxe region)
        $this->assertSame(100, $transaction->getTotalPrice());
        $this->assertSame(0, $transaction->getRegionTaxAmount());
    }

    public function testBuyListingWithRegionTax(): void
    {
        $seller = $this->createPlayer(1, 0);
        $buyer = $this->createPlayer(2, 1000);
        $item = $this->createPlayerItem();

        $listing = new AuctionListing();
        $listing->setSeller($seller);
        $listing->setPlayerItem($item);
        $listing->setQuantity(2);
        $listing->setPricePerUnit(100);
        $listing->setListingFee(10);
        $listing->setRegionTaxRate('0.1000');
        $listing->setExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->em->expects($this->once())->method('persist');

        $transaction = $this->manager->buyListing($buyer, $listing);

        // total = 200, tax = 20 (10%), seller gets 180
        $this->assertSame(800, $buyer->getGils());
        $this->assertSame(180, $seller->getGils());
        $this->assertSame(200, $transaction->getTotalPrice());
        $this->assertSame(20, $transaction->getRegionTaxAmount());
    }

    public function testBuyListingInsufficientFunds(): void
    {
        $seller = $this->createPlayer(1, 0);
        $buyer = $this->createPlayer(2, 50);
        $item = $this->createPlayerItem();

        $listing = new AuctionListing();
        $listing->setSeller($seller);
        $listing->setPlayerItem($item);
        $listing->setQuantity(1);
        $listing->setPricePerUnit(100);
        $listing->setListingFee(5);
        $listing->setRegionTaxRate('0.0000');
        $listing->setExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fonds insuffisants');

        $this->manager->buyListing($buyer, $listing);
    }

    public function testBuyOwnListingFails(): void
    {
        $seller = $this->createPlayer(1, 1000);
        $item = $this->createPlayerItem();

        $listing = new AuctionListing();
        $listing->setSeller($seller);
        $listing->setPlayerItem($item);
        $listing->setQuantity(1);
        $listing->setPricePerUnit(100);
        $listing->setListingFee(5);
        $listing->setRegionTaxRate('0.0000');
        $listing->setExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('propre annonce');

        $this->manager->buyListing($seller, $listing);
    }

    public function testCancelListingSuccess(): void
    {
        $seller = $this->createPlayer(1, 100);
        $item = $this->createPlayerItem();

        $listing = new AuctionListing();
        $listing->setSeller($seller);
        $listing->setPlayerItem($item);
        $listing->setQuantity(1);
        $listing->setPricePerUnit(100);
        $listing->setListingFee(5);
        $listing->setRegionTaxRate('0.0000');
        $listing->setExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->em->expects($this->once())->method('flush');

        $this->manager->cancelListing($seller, $listing);

        $this->assertSame(AuctionStatus::Cancelled, $listing->getStatus());
        // item returned to seller bag
        $bagInventory = null;
        foreach ($seller->getInventories() as $inv) {
            if ($inv->getType() === Inventory::TYPE_BAG) {
                $bagInventory = $inv;
            }
        }
        $this->assertSame($bagInventory, $item->getInventory());
    }

    public function testCancelListingNotOwner(): void
    {
        $seller = $this->createPlayer(1, 100);
        $other = $this->createPlayer(2, 100);
        $item = $this->createPlayerItem();

        $listing = new AuctionListing();
        $listing->setSeller($seller);
        $listing->setPlayerItem($item);
        $listing->setQuantity(1);
        $listing->setPricePerUnit(100);
        $listing->setListingFee(5);
        $listing->setRegionTaxRate('0.0000');
        $listing->setExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('propres annonces');

        $this->manager->cancelListing($other, $listing);
    }

    private function createPlayer(int $id, int $gils, ?Map $map = null): Player
    {
        $player = new Player();
        $r = new \ReflectionProperty(Player::class, 'id');
        $r->setValue($player, $id);
        $player->setGils($gils);

        if ($map !== null) {
            $player->setMap($map);
        }

        $bag = new Inventory();
        $bag->setType(Inventory::TYPE_BAG);
        $bag->setSize(20);
        $bag->setPlayer($player);

        $r2 = new \ReflectionProperty(Player::class, 'inventories');
        $r2->setValue($player, new ArrayCollection([$bag]));

        return $player;
    }

    private function createPlayerItem(): PlayerItem
    {
        $genericItem = $this->createMock(Item::class);

        $item = new PlayerItem();
        $item->setGenericItem($genericItem);

        return $item;
    }
}
