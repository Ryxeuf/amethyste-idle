<?php

namespace App\Tests\Unit\GameEngine\Shop;

use App\Entity\App\Guild;
use App\Entity\App\Player;
use App\Entity\App\PlayerShop;
use App\Entity\App\Region;
use App\Entity\App\Zone;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Region\PlayerRegionResolver;
use App\GameEngine\Shop\ShopStallService;
use App\Repository\PlayerShopRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ShopStallServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerShopRepository&MockObject $shopRepository;
    private PlayerRegionResolver&MockObject $regionResolver;
    private TownControlManager&MockObject $townControlManager;
    private ShopStallService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->shopRepository = $this->createMock(PlayerShopRepository::class);
        $this->regionResolver = $this->createMock(PlayerRegionResolver::class);
        $this->townControlManager = $this->createMock(TownControlManager::class);

        $this->service = new ShopStallService(
            $this->entityManager,
            $this->shopRepository,
            $this->regionResolver,
            $this->townControlManager,
            new NullLogger(),
        );
    }

    private function player(int $gils = 0, int $id = 7): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);
        $player->addGils($gils);

        return $player;
    }

    private function shop(Player $owner, int $slots = PlayerShop::DEFAULT_SLOTS): PlayerShop
    {
        $shop = new PlayerShop();
        $shop->setOwner($owner);
        $shop->setZone((new Zone())->setSlug('village-de-lumiere')->setName('Village'));
        $shop->setName('La bonne enclume');
        $shop->setSlotCount($slots);

        return $shop;
    }

    /**
     * Le prix croit : le premier etal supplementaire est accessible, le
     * dixieme se merite. Une progression lineaire aurait laisse les plus
     * riches rafler la place du marche en une transaction.
     */
    public function testTheStallPriceRisesWithEachOneBought(): void
    {
        $owner = $this->player();

        $this->assertSame(ShopStallService::STALL_BASE_PRICE, $this->service->nextStallPrice($this->shop($owner)));
        $this->assertSame(ShopStallService::STALL_BASE_PRICE * 2, $this->service->nextStallPrice($this->shop($owner, PlayerShop::DEFAULT_SLOTS + 1)));
        $this->assertSame(ShopStallService::STALL_BASE_PRICE * 4, $this->service->nextStallPrice($this->shop($owner, PlayerShop::DEFAULT_SLOTS + 3)));
    }

    /**
     * La rarete **est** l'actif : sans plafond par cite, louer un etal ne
     * serait qu'un gold sink de plus.
     */
    public function testTheSquareHasAFiniteNumberOfStalls(): void
    {
        $owner = $this->player();
        $mine = $this->shop($owner);
        $rival = $this->shop($this->player(0, 99), PlayerShop::DEFAULT_SLOTS + 10);

        $this->shopRepository->method('findInZone')->willReturn([$mine, $rival]);

        $this->assertSame(ShopStallService::STALLS_PER_CITY - 10, $this->service->remainingStalls($mine));
    }

    /**
     * Il faut **plusieurs** artisans pour saturer la place : une echoppe
     * plafonne a `MAX_SLOTS`, soit 18 etals loues au maximum sur les 24 de la
     * ville. Aucun joueur ne peut donc monopoliser le marche a lui seul.
     */
    public function testNoSingleShopCanFillTheSquare(): void
    {
        $glutton = $this->shop($this->player(0, 99), PlayerShop::MAX_SLOTS);

        $this->shopRepository->method('findInZone')->willReturn([$glutton]);

        $this->assertGreaterThan(
            0,
            $this->service->remainingStalls($glutton),
            'Une echoppe seule ne doit jamais pouvoir rafler toute la place.',
        );
    }

    public function testLeasingIsRefusedWhenTheSquareIsFull(): void
    {
        $owner = $this->player(1_000_000);
        $shop = $this->shop($owner);

        // Deux rivaux au plafond suffisent a saturer les 24 etals.
        $rivals = [
            $this->shop($this->player(0, 98), PlayerShop::MAX_SLOTS),
            $this->shop($this->player(0, 99), PlayerShop::MAX_SLOTS),
        ];

        $this->shopRepository->method('findInZone')->willReturn(array_merge([$shop], $rivals));

        $this->expectExceptionMessage('aucun etal libre');
        $this->service->leaseStall($owner, $shop);
    }

    /**
     * C'est la que le controle de cite prend des dents : le bail va au tresor
     * de la guilde qui tient la ville.
     */
    public function testTheLeaseGoesToTheRulingGuildTreasury(): void
    {
        $owner = $this->player(1_000_000);
        $shop = $this->shop($owner);
        $guild = new Guild();
        $guild->setName('Les Valeureux');

        $this->shopRepository->method('findInZone')->willReturn([$shop]);
        $this->regionResolver->method('resolveForZone')->willReturn((new Region())->setSlug('lumiere')->setName('Lumiere'));
        $this->townControlManager->method('getControllingGuild')->willReturn($guild);

        $price = $this->service->leaseStall($owner, $shop);

        $this->assertSame(ShopStallService::STALL_BASE_PRICE, $price);
        $this->assertSame(PlayerShop::DEFAULT_SLOTS + 1, $shop->getSlotCount());
        $this->assertSame($price, $guild->getGilsTreasury());
        $this->assertSame(1_000_000 - $price, $owner->getGils());
    }

    /**
     * Une cite sans maitre ne redistribue rien : les Gils sortent du jeu, comme
     * a l'hotel des ventes (ECO-04) et en echoppe (ECO-11).
     */
    public function testWithoutARulingGuildTheLeaseIsBurned(): void
    {
        $owner = $this->player(1_000_000);
        $shop = $this->shop($owner);

        $this->shopRepository->method('findInZone')->willReturn([$shop]);
        $this->regionResolver->method('resolveForZone')->willReturn(null);

        $price = $this->service->leaseStall($owner, $shop);

        $this->assertSame(PlayerShop::DEFAULT_SLOTS + 1, $shop->getSlotCount());
        $this->assertSame(1_000_000 - $price, $owner->getGils(), 'Le joueur paie ; personne n\'encaisse.');
    }

    public function testLeasingIsRefusedWithoutTheFunds(): void
    {
        $owner = $this->player(10);
        $shop = $this->shop($owner);

        $this->shopRepository->method('findInZone')->willReturn([$shop]);

        $this->expectExceptionMessage('Gils pour ce bail');
        $this->service->leaseStall($owner, $shop);
    }

    public function testAShopCannotGrowPastItsMaximum(): void
    {
        $owner = $this->player(1_000_000);
        $shop = $this->shop($owner, PlayerShop::MAX_SLOTS);

        $this->expectExceptionMessage('taille maximale');
        $this->service->leaseStall($owner, $shop);
    }

    public function testAnotherPlayerCannotLeaseForYourShop(): void
    {
        $shop = $this->shop($this->player(0, 7));

        $this->expectExceptionMessage('Cette echoppe n\'est pas la votre.');
        $this->service->leaseStall($this->player(1_000_000, 99), $shop);
    }
}
