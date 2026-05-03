<?php

namespace App\Tests\Unit\GameEngine\Mount;

use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Entity\Game\Mount;
use App\GameEngine\Mount\MountAcquisitionService;
use App\GameEngine\Mount\MountAlreadyOwnedException;
use App\Repository\PlayerMountRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class MountAcquisitionServiceTest extends TestCase
{
    public function testGrantMountPersistsNewOwnership(): void
    {
        $player = $this->createMock(Player::class);
        $mount = (new Mount())->setSlug('horse_brown')->setName('Cheval')->setDescription('...');

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(PlayerMountRepository::class);

        $repo->expects($this->once())
            ->method('playerOwnsMount')
            ->with($player, $mount)
            ->willReturn(false);
        $em->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(PlayerMount::class));
        $em->expects($this->once())->method('flush');

        $service = new MountAcquisitionService($em, $repo);
        $playerMount = $service->grantMount($player, $mount, PlayerMount::SOURCE_PURCHASE);

        $this->assertSame($player, $playerMount->getPlayer());
        $this->assertSame($mount, $playerMount->getMount());
        $this->assertSame(PlayerMount::SOURCE_PURCHASE, $playerMount->getSource());
    }

    public function testGrantMountSkipsFlushWhenRequested(): void
    {
        $player = $this->createMock(Player::class);
        $mount = (new Mount())->setSlug('horse_brown')->setName('Cheval')->setDescription('...');

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(PlayerMountRepository::class);
        $repo->method('playerOwnsMount')->willReturn(false);

        $em->expects($this->once())->method('persist');
        $em->expects($this->never())->method('flush');

        $service = new MountAcquisitionService($em, $repo);
        $service->grantMount($player, $mount, PlayerMount::SOURCE_DROP, flush: false);
    }

    public function testGrantMountThrowsWhenAlreadyOwned(): void
    {
        $player = $this->createMock(Player::class);
        $mount = (new Mount())->setSlug('chocobo_yellow')->setName('Chocobo')->setDescription('...');

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(PlayerMountRepository::class);
        $repo->method('playerOwnsMount')->willReturn(true);

        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $service = new MountAcquisitionService($em, $repo);

        $this->expectException(MountAlreadyOwnedException::class);
        $service->grantMount($player, $mount, PlayerMount::SOURCE_QUEST);
    }

    public function testGrantMountThrowsWhenMountDisabled(): void
    {
        $player = $this->createMock(Player::class);
        $mount = (new Mount())->setSlug('wild_boar')->setName('Sanglier')->setDescription('...')->setEnabled(false);

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(PlayerMountRepository::class);
        $repo->expects($this->never())->method('playerOwnsMount');

        $service = new MountAcquisitionService($em, $repo);

        $this->expectException(\DomainException::class);
        $service->grantMount($player, $mount, PlayerMount::SOURCE_ADMIN);
    }
}
