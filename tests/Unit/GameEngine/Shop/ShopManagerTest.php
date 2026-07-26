<?php

namespace App\Tests\Unit\GameEngine\Shop;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\App\PlayerItem;
use App\Entity\App\PlayerShop;
use App\Entity\App\ShopListing;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\Enum\ShopStatus;
use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Housing\HousingManager;
use App\GameEngine\Shop\ShopManager;
use App\GameEngine\Shop\ShopRentService;
use App\Repository\PlayerShopRepository;
use App\Repository\ShopListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ShopManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerShopRepository&MockObject $shopRepository;
    private ShopListingRepository&MockObject $listingRepository;
    private HousingManager&MockObject $housingManager;
    private CraftingManager&MockObject $craftingManager;
    private ShopRentService&MockObject $rentService;
    private ShopManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->shopRepository = $this->createMock(PlayerShopRepository::class);
        $this->listingRepository = $this->createMock(ShopListingRepository::class);
        $this->housingManager = $this->createMock(HousingManager::class);
        $this->craftingManager = $this->createMock(CraftingManager::class);
        $this->rentService = $this->createMock(ShopRentService::class);

        $this->manager = new ShopManager(
            $this->entityManager,
            $this->shopRepository,
            $this->listingRepository,
            $this->housingManager,
            $this->craftingManager,
            $this->rentService,
            new NullLogger(),
        );
    }

    private function player(int $id = 7): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);

        return $player;
    }

    private function zone(string $slug = 'quartier-des-jardins'): Zone
    {
        return (new Zone())->setSlug($slug)->setName('Quartier');
    }

    private function house(Player $owner, Zone $zone): PlayerHouse
    {
        $house = new PlayerHouse();
        $house->setOwner($owner);
        $house->setZone($zone);
        $house->setName('Chez moi');

        return $house;
    }

    private function shopOf(Player $owner, Zone $zone): PlayerShop
    {
        $shop = new PlayerShop();
        $shop->setOwner($owner);
        $shop->setZone($zone);
        $shop->setName('La bonne enclume');

        return $shop;
    }

    /**
     * L'echoppe est adossee a la demeure : sans terrain, elle n'aurait nulle
     * part ou exister — et le housing cesse d'etre un pur sink cosmetique.
     */
    public function testOpeningRequiresAHouse(): void
    {
        $this->shopRepository->method('findForOwner')->willReturn(null);
        $this->housingManager->method('getHouse')->willReturn(null);

        $this->expectExceptionMessage('Il faut posseder une demeure pour ouvrir une echoppe.');
        $this->manager->open($this->player(), 'La bonne enclume');
    }

    /**
     * Sans niveau de metier, l'echoppe ferait doublon avec le plancher PNJ
     * d'ECO-02 : on vendrait au detail ce que le marchand du coin donne deja.
     */
    public function testOpeningRequiresACraftRank(): void
    {
        $player = $this->player();
        $this->shopRepository->method('findForOwner')->willReturn(null);
        $this->housingManager->method('getHouse')->willReturn($this->house($player, $this->zone()));
        $this->craftingManager->method('getCraftingLevel')->willReturn(2);

        $this->expectExceptionMessage('Il faut le niveau 5 dans un metier d\'artisanat');
        $this->manager->open($player, 'La bonne enclume');
    }

    /**
     * Le rang se lit sur le **meilleur** metier : un maitre joaillier a autant
     * sa place dans la rue qu'un forgeron.
     */
    public function testAnyCraftAtRankIsEnough(): void
    {
        $player = $this->player();
        $zone = $this->zone();
        $this->shopRepository->method('findForOwner')->willReturn(null);
        $this->housingManager->method('getHouse')->willReturn($this->house($player, $zone));
        $this->craftingManager->method('getCraftingLevel')
            ->willReturnCallback(static fn (Player $p, string $craft) => 'joaillier' === $craft ? 6 : 1);

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(PlayerShop::class));

        $shop = $this->manager->open($player, 'Les gemmes de Lyra');

        $this->assertSame($zone, $shop->getZone(), 'L\'echoppe s\'ouvre la ou le joueur a pignon sur rue.');
        $this->assertSame(ShopStatus::Open, $shop->getStatus());
        $this->assertSame(PlayerShop::DEFAULT_SLOTS, $shop->getSlotCount());
    }

    /**
     * La premiere periode est offerte : on ne fait pas payer un loyer avant la
     * premiere vente.
     */
    public function testOpeningSchedulesTheFirstRent(): void
    {
        $player = $this->player();
        $this->shopRepository->method('findForOwner')->willReturn(null);
        $this->housingManager->method('getHouse')->willReturn($this->house($player, $this->zone()));
        $this->craftingManager->method('getCraftingLevel')->willReturn(9);

        $this->rentService->expects($this->once())->method('scheduleFirstRent');

        $this->manager->open($player, 'La bonne enclume');
    }

    public function testOpeningRefusesASecondShop(): void
    {
        $player = $this->player();
        $this->shopRepository->method('findForOwner')->willReturn($this->shopOf($player, $this->zone()));

        $this->expectExceptionMessage('Vous tenez deja une echoppe.');
        $this->manager->open($player, 'La seconde');
    }

    public function testOpeningRefusesAnEmptyName(): void
    {
        $player = $this->player();
        $this->shopRepository->method('findForOwner')->willReturn(null);
        $this->housingManager->method('getHouse')->willReturn($this->house($player, $this->zone()));
        $this->craftingManager->method('getCraftingLevel')->willReturn(9);

        $this->expectExceptionMessage('Donnez un nom a votre echoppe.');
        $this->manager->open($player, '   ');
    }

    /**
     * ECO-01 : garde-fou cote service. L'ecran filtre deja les objets lies,
     * mais une requete forgee ne passe pas par l'ecran.
     */
    public function testABoundItemCannotBeStocked(): void
    {
        $player = $this->player();
        $shop = $this->shopOf($player, $this->zone());

        $item = new PlayerItem();
        $item->setBoundToPlayerId($player->getId());

        $this->expectExceptionMessage('Cet objet est lie a son proprietaire');
        $this->manager->stock($player, $shop, $item, 1, 100);
    }

    public function testStockingMovesTheItemIntoEscrow(): void
    {
        $player = $this->player();
        $shop = $this->shopOf($player, $this->zone());
        $item = $this->itemOwnedBy($player);

        $this->listingRepository->method('countForShop')->willReturn(0);
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(ShopListing::class));

        $listing = $this->manager->stock($player, $shop, $item, 3, 250);

        $this->assertNull($item->getInventory(), 'Sans escrow, l\'artisan pourrait consommer un objet deja expose.');
        $this->assertSame(3, $listing->getQuantity());
        $this->assertSame(750, $listing->getTotalPrice());
    }

    public function testStockingRefusesAnItemTheSellerDoesNotHold(): void
    {
        $player = $this->player();
        $shop = $this->shopOf($player, $this->zone());
        $item = $this->itemOwnedBy($this->player(99));

        $this->expectExceptionMessage('Cet objet n\'est pas dans votre inventaire.');
        $this->manager->stock($player, $shop, $item, 1, 100);
    }

    /**
     * Sans contrainte de place, l'echoppe deviendrait un second hotel des
     * ventes, en moins lisible.
     */
    public function testStockingRefusesWhenEverySlotIsTaken(): void
    {
        $player = $this->player();
        $shop = $this->shopOf($player, $this->zone());
        $item = $this->itemOwnedBy($player);

        $this->listingRepository->method('countForShop')->willReturn(PlayerShop::DEFAULT_SLOTS);

        $this->expectExceptionMessage('emplacements, tous occupes');
        $this->manager->stock($player, $shop, $item, 1, 100);
    }

    public function testStockingRefusesANonPositivePrice(): void
    {
        $player = $this->player();
        $shop = $this->shopOf($player, $this->zone());
        $item = $this->itemOwnedBy($player);

        $this->listingRepository->method('countForShop')->willReturn(0);

        $this->expectExceptionMessage('Le prix doit etre superieur a 0.');
        $this->manager->stock($player, $shop, $item, 1, 0);
    }

    public function testAnotherPlayerCannotStockYourShop(): void
    {
        $shop = $this->shopOf($this->player(7), $this->zone());

        $this->expectExceptionMessage('Cette echoppe n\'est pas la votre.');
        $this->manager->stock($this->player(99), $shop, new PlayerItem(), 1, 100);
    }

    public function testUnstockingReturnsTheItemToTheBag(): void
    {
        $player = $this->player();
        $bag = $this->bagOf($player);
        $shop = $this->shopOf($player, $this->zone());

        $item = new PlayerItem();
        $listing = new ShopListing($shop, $item, 1, 100);

        $this->entityManager->expects($this->once())->method('remove')->with($listing);

        $this->manager->unstock($player, $listing);

        $this->assertSame($bag, $item->getInventory());
    }

    /**
     * La fermeture est un rideau, pas un demenagement : elle ne touche pas au
     * stock. Mais un impaye ne se leve pas d'un clic — le loyer se regle
     * d'abord (ECO-11).
     */
    public function testAShopInArrearsCannotBeReopenedWithoutPaying(): void
    {
        $player = $this->player();
        $shop = $this->shopOf($player, $this->zone());
        $shop->setStatus(ShopStatus::Arrears);

        $this->expectExceptionMessage('Reglez le loyer avant de rouvrir');
        $this->manager->setOpen($player, $shop, true);
    }

    public function testClosingAndReopeningAHealthyShop(): void
    {
        $player = $this->player();
        $shop = $this->shopOf($player, $this->zone());

        $this->manager->setOpen($player, $shop, false);
        $this->assertSame(ShopStatus::Closed, $shop->getStatus());
        $this->assertFalse($shop->sells());

        $this->manager->setOpen($player, $shop, true);
        $this->assertTrue($shop->sells());
    }

    public function testTheVaultEmptiesOnce(): void
    {
        $shop = $this->shopOf($this->player(), $this->zone());
        $shop->creditVault(1_200);
        $shop->creditVault(300);

        $this->assertSame(1_500, $shop->emptyVault());
        $this->assertSame(0, $shop->getVaultGils());
    }

    private function bagOf(Player $player): Inventory
    {
        $bag = new Inventory();
        $bag->setType(Inventory::TYPE_BAG);
        $bag->setPlayer($player);
        $player->addInventory($bag);

        return $bag;
    }

    private function itemOwnedBy(Player $player): PlayerItem
    {
        $item = new PlayerItem();
        $item->setGenericItem((new Item())->setName('Epee courte'));
        $item->setInventory($this->bagOf($player));

        return $item;
    }
}
