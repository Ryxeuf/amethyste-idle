<?php

namespace App\Tests\Unit\GameEngine\Shop;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\PlayerShop;
use App\Entity\App\Region;
use App\Entity\App\ShopListing;
use App\Entity\App\ShopSaleLog;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\Enum\ShopStatus;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Region\PlayerRegionResolver;
use App\GameEngine\Shop\ShopSaleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ShopSaleServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerRegionResolver&MockObject $regionResolver;
    private TownControlManager&MockObject $townControlManager;
    private GuildManager&MockObject $guildManager;
    private ShopSaleService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->regionResolver = $this->createMock(PlayerRegionResolver::class);
        $this->townControlManager = $this->createMock(TownControlManager::class);
        $this->guildManager = $this->createMock(GuildManager::class);

        $this->service = new ShopSaleService(
            $this->entityManager,
            $this->regionResolver,
            $this->townControlManager,
            $this->guildManager,
            new NullLogger(),
        );
    }

    private function zone(string $slug = 'quartier-des-jardins'): Zone
    {
        return (new Zone())->setSlug($slug)->setName('Quartier');
    }

    private function player(int $id, int $gils = 0, ?Zone $zone = null): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);
        $player->setName('Joueur ' . $id);
        $player->addGils($gils);
        if (null !== $zone) {
            $player->setCurrentZone($zone);
        }

        $bag = new Inventory();
        $bag->setType(Inventory::TYPE_BAG);
        $bag->setPlayer($player);
        $player->addInventory($bag);

        return $player;
    }

    private function listing(Player $owner, Zone $zone, int $unitPrice = 500, int $quantity = 1): ShopListing
    {
        $shop = new PlayerShop();
        $shop->setOwner($owner);
        $shop->setZone($zone);
        $shop->setName('La bonne enclume');

        $item = new PlayerItem();
        $item->setGenericItem((new Item())->setName('Epee courte'));

        return new ShopListing($shop, $item, $quantity, $unitPrice);
    }

    private function taxedRegion(float $rate): Region
    {
        $region = new Region();
        $region->setSlug('lumiere');
        $region->setName('Lumiere');
        $region->setTaxRate(number_format($rate, 4, '.', ''));

        return $region;
    }

    /**
     * La vente est asynchrone : les recettes vont a la **caisse**, pas a la
     * bourse du proprietaire, qui n'est pas la pour les recevoir.
     */
    public function testProceedsGoToTheVaultNotTheOwnerPurse(): void
    {
        $zone = $this->zone();
        $owner = $this->player(7);
        $buyer = $this->player(9, 10_000, $zone);
        $listing = $this->listing($owner, $zone, 500, 2);

        $this->regionResolver->method('resolveForZone')->willReturn(null);

        $log = $this->service->buy($buyer, $listing);

        $this->assertSame(1_000, $listing->getShop()->getVaultGils());
        $this->assertSame(0, $owner->getGils(), 'Le proprietaire encaisse a la reconnexion, pas a la vente.');
        $this->assertSame(9_000, $buyer->getGils());
        $this->assertInstanceOf(ShopSaleLog::class, $log);
        $this->assertSame('Epee courte', $log->getItemName());
    }

    public function testTheItemLandsInTheBuyerBag(): void
    {
        $zone = $this->zone();
        $buyer = $this->player(9, 10_000, $zone);
        $listing = $this->listing($this->player(7), $zone);

        $this->regionResolver->method('resolveForZone')->willReturn(null);
        $this->entityManager->expects($this->once())->method('remove')->with($listing);

        $this->service->buy($buyer, $listing);

        $this->assertNotNull($listing->getPlayerItem()->getInventory());
        $this->assertSame($buyer, $listing->getPlayerItem()->getInventory()?->getPlayer());
    }

    /**
     * Une echoppe est une adresse. Acheter a distance en ferait un second
     * hotel des ventes et annulerait le cout de voyage.
     */
    public function testBuyingRequiresBeingInTheShopZone(): void
    {
        $buyer = $this->player(9, 10_000, $this->zone('marais-brumeux'));
        $listing = $this->listing($this->player(7), $this->zone());

        $this->expectExceptionMessage('Rendez-vous sur place');
        $this->service->buy($buyer, $listing);
    }

    public function testAClosedShopSellsNothing(): void
    {
        $zone = $this->zone();
        $buyer = $this->player(9, 10_000, $zone);
        $listing = $this->listing($this->player(7), $zone);
        $listing->getShop()->setStatus(ShopStatus::Closed);

        $this->expectExceptionMessage('Cette echoppe est fermee.');
        $this->service->buy($buyer, $listing);
    }

    public function testAShopInArrearsSellsNothing(): void
    {
        $zone = $this->zone();
        $buyer = $this->player(9, 10_000, $zone);
        $listing = $this->listing($this->player(7), $zone);
        $listing->getShop()->setStatus(ShopStatus::Arrears);

        $this->expectExceptionMessage('Cette echoppe est fermee.');
        $this->service->buy($buyer, $listing);
    }

    public function testAnOwnerCannotBuyFromThemself(): void
    {
        $zone = $this->zone();
        $owner = $this->player(7, 10_000, $zone);
        $listing = $this->listing($owner, $zone);

        $this->expectExceptionMessage('votre propre echoppe');
        $this->service->buy($owner, $listing);
    }

    public function testInsufficientFundsAreRefused(): void
    {
        $zone = $this->zone();
        $buyer = $this->player(9, 10, $zone);
        $listing = $this->listing($this->player(7), $zone);

        $this->regionResolver->method('resolveForZone')->willReturn(null);

        $this->expectExceptionMessage('Fonds insuffisants');
        $this->service->buy($buyer, $listing);
    }

    /**
     * La taxe reutilise `AuctionSettlement` : elle ne doit pas dependre du
     * canal de vente. Sans guilde controlante, elle est **detruite** — le
     * proprietaire touche le net, pas le brut.
     */
    public function testWithoutARulingGuildTheTaxIsBurned(): void
    {
        $zone = $this->zone();
        $buyer = $this->player(9, 10_000, $zone);
        $listing = $this->listing($this->player(7), $zone, 1_000);

        $this->regionResolver->method('resolveForZone')->willReturn($this->taxedRegion(0.10));
        $this->townControlManager->method('getControllingGuild')->willReturn(null);

        $log = $this->service->buy($buyer, $listing);

        $this->assertSame(100, $log->getTaxGils());
        $this->assertSame(900, $log->getNetGils());
        $this->assertSame(900, $listing->getShop()->getVaultGils());
        $this->assertSame(9_000, $buyer->getGils(), 'Sans ristourne, l\'acheteur paie le prix affiche.');
    }

    public function testCollectingTheVaultMovesItToThePurse(): void
    {
        $owner = $this->player(7);
        $shop = $this->listing($owner, $this->zone())->getShop();
        $shop->creditVault(2_500);

        $this->assertSame(2_500, $this->service->collectVault($owner, $shop));
        $this->assertSame(2_500, $owner->getGils());
        $this->assertSame(0, $shop->getVaultGils());
    }

    public function testCollectingAnEmptyVaultIsRefused(): void
    {
        $owner = $this->player(7);
        $shop = $this->listing($owner, $this->zone())->getShop();

        $this->expectExceptionMessage('La caisse est vide.');
        $this->service->collectVault($owner, $shop);
    }

    public function testAnotherPlayerCannotCollectYourVault(): void
    {
        $shop = $this->listing($this->player(7), $this->zone())->getShop();
        $shop->creditVault(1_000);

        $this->expectExceptionMessage('Cette echoppe n\'est pas la votre.');
        $this->service->collectVault($this->player(99), $shop);
    }
}
