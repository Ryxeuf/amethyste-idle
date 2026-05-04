<?php

namespace App\Tests\Unit\GameEngine\Mount;

use App\Entity\App\Player;
use App\Entity\Game\Mount;
use App\GameEngine\Mount\MountActivationService;
use App\GameEngine\Mount\MountNotOwnedException;
use App\Repository\PlayerMountRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class MountActivationServiceTest extends TestCase
{
    public function testMountSetsActiveMountWhenPlayerOwnsIt(): void
    {
        $player = new Player();
        $mount = (new Mount())->setSlug('horse_brown')->setName('Cheval')->setDescription('...');

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(PlayerMountRepository::class);

        $repo->expects($this->once())
            ->method('playerOwnsMount')
            ->with($player, $mount)
            ->willReturn(true);
        $em->expects($this->once())->method('flush');

        $service = new MountActivationService($em, $repo);
        $service->mount($player, $mount);

        $this->assertSame($mount, $player->getActiveMount());
    }

    public function testMountSkipsFlushWhenRequested(): void
    {
        $player = new Player();
        $mount = (new Mount())->setSlug('horse_brown')->setName('Cheval')->setDescription('...');

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(PlayerMountRepository::class);
        $repo->method('playerOwnsMount')->willReturn(true);

        $em->expects($this->never())->method('flush');

        $service = new MountActivationService($em, $repo);
        $service->mount($player, $mount, flush: false);

        $this->assertSame($mount, $player->getActiveMount());
    }

    public function testMountThrowsWhenPlayerDoesNotOwnIt(): void
    {
        $player = new Player();
        $mount = (new Mount())->setSlug('chocobo_yellow')->setName('Chocobo')->setDescription('...');

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(PlayerMountRepository::class);
        $repo->method('playerOwnsMount')->willReturn(false);

        $em->expects($this->never())->method('flush');

        $service = new MountActivationService($em, $repo);

        $this->expectException(MountNotOwnedException::class);
        $service->mount($player, $mount);
    }

    public function testMountThrowsWhenMountDisabled(): void
    {
        $player = new Player();
        $mount = (new Mount())->setSlug('wild_boar')->setName('Sanglier')->setDescription('...')->setEnabled(false);

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(PlayerMountRepository::class);
        $repo->expects($this->never())->method('playerOwnsMount');

        $service = new MountActivationService($em, $repo);

        $this->expectException(\DomainException::class);
        $service->mount($player, $mount);
    }

    public function testMountReplacesPreviousActiveMount(): void
    {
        $player = new Player();
        $previous = (new Mount())->setSlug('horse_brown')->setName('Cheval')->setDescription('...');
        $next = (new Mount())->setSlug('chocobo_yellow')->setName('Chocobo')->setDescription('...');
        $player->setActiveMount($previous);

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(PlayerMountRepository::class);
        $repo->method('playerOwnsMount')->willReturn(true);

        $service = new MountActivationService($em, $repo);
        $service->mount($player, $next);

        $this->assertSame($next, $player->getActiveMount());
    }

    public function testUnmountClearsActiveMount(): void
    {
        $player = new Player();
        $mount = (new Mount())->setSlug('horse_brown')->setName('Cheval')->setDescription('...');
        $player->setActiveMount($mount);

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(PlayerMountRepository::class);

        $em->expects($this->once())->method('flush');

        $service = new MountActivationService($em, $repo);
        $service->unmount($player);

        $this->assertNull($player->getActiveMount());
    }

    public function testUnmountIsNoopWhenNoActiveMount(): void
    {
        $player = new Player();

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(PlayerMountRepository::class);

        $em->expects($this->never())->method('flush');

        $service = new MountActivationService($em, $repo);
        $service->unmount($player);

        $this->assertNull($player->getActiveMount());
    }

    public function testUnmountSkipsFlushWhenRequested(): void
    {
        $player = new Player();
        $mount = (new Mount())->setSlug('horse_brown')->setName('Cheval')->setDescription('...');
        $player->setActiveMount($mount);

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(PlayerMountRepository::class);

        $em->expects($this->never())->method('flush');

        $service = new MountActivationService($em, $repo);
        $service->unmount($player, flush: false);

        $this->assertNull($player->getActiveMount());
    }
}
