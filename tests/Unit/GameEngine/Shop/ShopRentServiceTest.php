<?php

namespace App\Tests\Unit\GameEngine\Shop;

use App\Entity\App\Player;
use App\Entity\App\PlayerShop;
use App\Entity\App\Zone;
use App\Enum\ShopStatus;
use App\GameEngine\Shop\ShopRentService;
use App\Repository\PlayerShopRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ShopRentServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerShopRepository&MockObject $shopRepository;
    private ShopRentService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->shopRepository = $this->createMock(PlayerShopRepository::class);

        $this->service = new ShopRentService($this->entityManager, $this->shopRepository, new NullLogger());
    }

    private function player(int $gils = 0, int $id = 7): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);
        $player->addGils($gils);

        return $player;
    }

    private function shop(Player $owner, int $vault = 0, ?string $dueAt = '-1 hour'): PlayerShop
    {
        $shop = new PlayerShop();
        $shop->setOwner($owner);
        $shop->setZone((new Zone())->setSlug('quartier-des-jardins')->setName('Quartier'));
        $shop->setName('La bonne enclume');
        $shop->creditVault($vault);
        $shop->setRentDueAt(null === $dueAt ? null : new \DateTimeImmutable($dueAt));

        return $shop;
    }

    /**
     * Une echoppe qui vend s'entretient seule : la caisse paie avant la bourse.
     */
    public function testTheVaultPaysBeforeThePurse(): void
    {
        $owner = $this->player(10_000);
        $shop = $this->shop($owner, ShopRentService::RENT_AMOUNT + 500);
        $this->shopRepository->method('findWithRentDue')->willReturn([$shop]);

        $report = $this->service->collectDueRents();

        $this->assertSame(['charged' => 1, 'closed' => 0], $report);
        $this->assertSame(500, $shop->getVaultGils(), 'La caisse est entamee du montant du loyer, pas videe.');
        $this->assertSame(10_000, $owner->getGils(), 'La bourse n\'est pas touchee tant que la caisse suffit.');
    }

    public function testThePurseCoversWhatTheVaultCannot(): void
    {
        $owner = $this->player(10_000);
        $shop = $this->shop($owner, 400);
        $this->shopRepository->method('findWithRentDue')->willReturn([$shop]);

        $this->service->collectDueRents();

        $this->assertSame(0, $shop->getVaultGils());
        $this->assertSame(10_000 - (ShopRentService::RENT_AMOUNT - 400), $owner->getGils());
    }

    /**
     * L'impaye ne confisque rien : le rideau tombe, le stock reste en escrow.
     */
    public function testAnUnpayableRentLowersTheShuttersWithoutSeizing(): void
    {
        $owner = $this->player(50);
        $shop = $this->shop($owner, 0);
        $this->shopRepository->method('findWithRentDue')->willReturn([$shop]);

        $report = $this->service->collectDueRents();

        $this->assertSame(['charged' => 0, 'closed' => 1], $report);
        $this->assertSame(ShopStatus::Arrears, $shop->getStatus());
        $this->assertSame(50, $owner->getGils(), 'Rien n\'est preleve quand rien ne peut l\'etre.');
    }

    /**
     * L'echeance repart de la **precedente** : payer en retard ne doit pas
     * offrir une periode pleine, sinon attendre serait rentable.
     */
    public function testTheNextDueDateFollowsThePreviousOneNotTheMomentOfPayment(): void
    {
        $owner = $this->player(10_000);
        $due = new \DateTimeImmutable('-3 days');
        $shop = $this->shop($owner, 0);
        $shop->setRentDueAt($due);
        $this->shopRepository->method('findWithRentDue')->willReturn([$shop]);

        $this->service->collectDueRents();

        $this->assertSame(
            $due->modify('+' . ShopRentService::RENT_PERIOD_DAYS . ' days')->getTimestamp(),
            $shop->getRentDueAt()?->getTimestamp(),
        );
    }

    public function testPayingClearsArrearsAndRaisesTheShutters(): void
    {
        $owner = $this->player(10_000);
        $shop = $this->shop($owner, 0);
        $shop->setStatus(ShopStatus::Arrears);

        $this->service->payRent($owner, $shop);

        $this->assertSame(ShopStatus::Open, $shop->getStatus());
        $this->assertSame(10_000 - ShopRentService::RENT_AMOUNT, $owner->getGils());
    }

    public function testPayingRefusesWhenNeitherVaultNorPurseCanCover(): void
    {
        $owner = $this->player(10);
        $shop = $this->shop($owner, 0);

        $this->expectExceptionMessage('Il vous faut 1000 Gils pour le loyer.');
        $this->service->payRent($owner, $shop);
    }

    public function testAnotherPlayerCannotPayYourRent(): void
    {
        $shop = $this->shop($this->player(0, 7));

        $this->expectExceptionMessage('Cette echoppe n\'est pas la votre.');
        $this->service->payRent($this->player(10_000, 99), $shop);
    }

    /**
     * La premiere periode est offerte : on ne fait pas payer un loyer le jour
     * meme de l'ouverture, sans avoir rien vendu.
     */
    public function testTheFirstPeriodIsFree(): void
    {
        $shop = $this->shop($this->player(), 0, null);

        $this->service->scheduleFirstRent($shop);

        $this->assertNotNull($shop->getRentDueAt());
        $this->assertGreaterThan(new \DateTimeImmutable('+6 days'), $shop->getRentDueAt());
    }
}
