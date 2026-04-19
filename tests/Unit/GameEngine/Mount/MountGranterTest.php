<?php

namespace App\Tests\Unit\GameEngine\Mount;

use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Entity\Game\Mount;
use App\GameEngine\Mount\MountGranter;
use App\Repository\PlayerMountRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MountGranterTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerMountRepository&MockObject $repository;
    private MountGranter $granter;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(PlayerMountRepository::class);
        $this->granter = new MountGranter($this->entityManager, $this->repository);
    }

    public function testGrantCreatesPlayerMountWhenAbsent(): void
    {
        $player = new Player();
        $mount = $this->createMount();

        $this->repository->expects($this->once())
            ->method('findOneByPlayerAndMount')
            ->with($player, $mount)
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PlayerMount::class));
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->granter->grant($player, $mount, PlayerMount::SOURCE_PURCHASE);

        $this->assertSame($player, $result->getPlayer());
        $this->assertSame($mount, $result->getMount());
        $this->assertSame(PlayerMount::SOURCE_PURCHASE, $result->getSource());
    }

    public function testGrantIsIdempotentWhenPlayerAlreadyOwnsMount(): void
    {
        $player = new Player();
        $mount = $this->createMount();
        $existing = new PlayerMount($player, $mount, PlayerMount::SOURCE_QUEST);

        $this->repository->expects($this->once())
            ->method('findOneByPlayerAndMount')
            ->with($player, $mount)
            ->willReturn($existing);

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->granter->grant($player, $mount, PlayerMount::SOURCE_PURCHASE);

        $this->assertSame($existing, $result);
        $this->assertSame(PlayerMount::SOURCE_QUEST, $result->getSource(), 'Initial source must be preserved on re-grant');
    }

    public function testGrantRejectsInvalidSource(): void
    {
        $player = new Player();
        $mount = $this->createMount();

        $this->repository->expects($this->once())
            ->method('findOneByPlayerAndMount')
            ->willReturn(null);

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);

        $this->granter->grant($player, $mount, 'gift');
    }

    public function testPlayerOwnsMountReturnsTrueWhenOwned(): void
    {
        $player = new Player();
        $mount = $this->createMount();

        $this->repository->expects($this->once())
            ->method('findOneByPlayerAndMount')
            ->with($player, $mount)
            ->willReturn(new PlayerMount($player, $mount, PlayerMount::SOURCE_DROP));

        $this->assertTrue($this->granter->playerOwnsMount($player, $mount));
    }

    public function testPlayerOwnsMountReturnsFalseWhenAbsent(): void
    {
        $player = new Player();
        $mount = $this->createMount();

        $this->repository->expects($this->once())
            ->method('findOneByPlayerAndMount')
            ->willReturn(null);

        $this->assertFalse($this->granter->playerOwnsMount($player, $mount));
    }

    private function createMount(): Mount
    {
        $mount = new Mount();
        $mount->setSlug('horse_brown');
        $mount->setName('Cheval brun');
        $mount->setDescription('Monture commune');

        return $mount;
    }
}
