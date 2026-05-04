<?php

namespace App\Tests\Unit\GameEngine\Mount;

use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Entity\Game\Mount;
use App\GameEngine\Mount\InsufficientGilsException;
use App\GameEngine\Mount\MountAcquisitionService;
use App\GameEngine\Mount\MountAlreadyOwnedException;
use App\GameEngine\Mount\MountNotPurchasableException;
use App\GameEngine\Mount\MountPurchaseService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class MountPurchaseServiceTest extends TestCase
{
    public function testPurchaseDeductsGilsAndGrantsMount(): void
    {
        $player = new Player();
        $player->setGils(10000);

        $mount = (new Mount())->setSlug('horse_brown')->setName('Cheval')->setDescription('...');
        $mount->setGilCost(5000);

        $em = $this->createMock(EntityManagerInterface::class);
        $acquisition = $this->createMock(MountAcquisitionService::class);

        $playerMount = new PlayerMount($player, $mount, PlayerMount::SOURCE_PURCHASE);
        $acquisition->expects($this->once())
            ->method('grantMount')
            ->with($player, $mount, PlayerMount::SOURCE_PURCHASE, true)
            ->willReturn($playerMount);

        $service = new MountPurchaseService($em, $acquisition);
        $result = $service->purchase($player, $mount);

        $this->assertSame($playerMount, $result);
        $this->assertSame(5000, $player->getGils());
    }

    public function testPurchaseSkipsFlushWhenRequested(): void
    {
        $player = new Player();
        $player->setGils(8000);

        $mount = (new Mount())->setSlug('chocobo')->setName('Chocobo')->setDescription('...');
        $mount->setGilCost(2500);

        $em = $this->createMock(EntityManagerInterface::class);
        $acquisition = $this->createMock(MountAcquisitionService::class);

        $playerMount = new PlayerMount($player, $mount, PlayerMount::SOURCE_PURCHASE);
        $acquisition->expects($this->once())
            ->method('grantMount')
            ->with($player, $mount, PlayerMount::SOURCE_PURCHASE, false)
            ->willReturn($playerMount);

        $service = new MountPurchaseService($em, $acquisition);
        $service->purchase($player, $mount, flush: false);

        $this->assertSame(5500, $player->getGils());
    }

    public function testPurchaseThrowsWhenMountHasNoGilCost(): void
    {
        $player = new Player();
        $player->setGils(99999);

        $mount = (new Mount())->setSlug('quest_mount')->setName('Quest mount')->setDescription('...');
        $mount->setGilCost(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $acquisition = $this->createMock(MountAcquisitionService::class);
        $acquisition->expects($this->never())->method('grantMount');

        $service = new MountPurchaseService($em, $acquisition);

        $this->expectException(MountNotPurchasableException::class);
        $service->purchase($player, $mount);
    }

    public function testPurchaseThrowsWhenGilCostIsZero(): void
    {
        $player = new Player();
        $player->setGils(99999);

        $mount = (new Mount())->setSlug('free_mount')->setName('Free')->setDescription('...');
        $mount->setGilCost(0);

        $em = $this->createMock(EntityManagerInterface::class);
        $acquisition = $this->createMock(MountAcquisitionService::class);
        $acquisition->expects($this->never())->method('grantMount');

        $service = new MountPurchaseService($em, $acquisition);

        $this->expectException(MountNotPurchasableException::class);
        $service->purchase($player, $mount);
    }

    public function testPurchaseThrowsWhenInsufficientGils(): void
    {
        $player = new Player();
        $player->setGils(1000);

        $mount = (new Mount())->setSlug('expensive')->setName('Expensive')->setDescription('...');
        $mount->setGilCost(5000);

        $em = $this->createMock(EntityManagerInterface::class);
        $acquisition = $this->createMock(MountAcquisitionService::class);
        $acquisition->expects($this->never())->method('grantMount');

        $service = new MountPurchaseService($em, $acquisition);

        $this->expectException(InsufficientGilsException::class);
        $service->purchase($player, $mount);

        // Gils unchanged on failure
        $this->assertSame(1000, $player->getGils());
    }

    public function testPurchaseRelaysAlreadyOwnedException(): void
    {
        $player = new Player();
        $player->setGils(10000);

        $mount = (new Mount())->setSlug('horse')->setName('Cheval')->setDescription('...');
        $mount->setGilCost(5000);

        $em = $this->createMock(EntityManagerInterface::class);
        $acquisition = $this->createMock(MountAcquisitionService::class);
        $acquisition->method('grantMount')
            ->willThrowException(new MountAlreadyOwnedException($player, $mount));

        $service = new MountPurchaseService($em, $acquisition);

        $this->expectException(MountAlreadyOwnedException::class);
        $service->purchase($player, $mount);
    }
}
