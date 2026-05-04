<?php

namespace App\Tests\Unit\GameEngine\Mount;

use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Entity\Game\Mount;
use App\GameEngine\Mount\MountAcquisitionService;
use App\GameEngine\Mount\MountAlreadyOwnedException;
use App\GameEngine\Mount\MountQuestRewardGranter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class MountQuestRewardGranterTest extends TestCase
{
    public function testGrantBySlugDelegatesWithSourceQuest(): void
    {
        $player = new Player();
        $mount = (new Mount())->setSlug('chocobo_yellow')->setName('Chocobo')->setDescription('...');

        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['slug' => 'chocobo_yellow'])
            ->willReturn($mount);
        $em->method('getRepository')->with(Mount::class)->willReturn($repository);

        $acquisition = $this->createMock(MountAcquisitionService::class);
        $acquisition->expects($this->once())
            ->method('grantMount')
            ->with($player, $mount, PlayerMount::SOURCE_QUEST, true)
            ->willReturn(new PlayerMount($player, $mount, PlayerMount::SOURCE_QUEST));

        $service = new MountQuestRewardGranter($em, $acquisition);

        $this->assertSame($mount, $service->grantBySlug($player, 'chocobo_yellow'));
    }

    public function testGrantBySlugReturnsNullWhenSlugUnknown(): void
    {
        $player = new Player();

        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);
        $em->method('getRepository')->willReturn($repository);

        $acquisition = $this->createMock(MountAcquisitionService::class);
        $acquisition->expects($this->never())->method('grantMount');

        $service = new MountQuestRewardGranter($em, $acquisition);

        $this->assertNull($service->grantBySlug($player, 'unknown_slug'));
    }

    public function testGrantBySlugSwallowsAlreadyOwnedAndReturnsNull(): void
    {
        $player = new Player();
        $mount = (new Mount())->setSlug('horse_brown')->setName('Cheval')->setDescription('...');

        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($mount);
        $em->method('getRepository')->willReturn($repository);

        $acquisition = $this->createMock(MountAcquisitionService::class);
        $acquisition->expects($this->once())
            ->method('grantMount')
            ->willThrowException(new MountAlreadyOwnedException($player, $mount));

        $service = new MountQuestRewardGranter($em, $acquisition);

        $this->assertNull($service->grantBySlug($player, 'horse_brown'));
    }

    public function testGrantBySlugRelaysDisabledMountAsDomainException(): void
    {
        $player = new Player();
        $mount = (new Mount())->setSlug('disabled_mount')->setName('X')->setDescription('...');

        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($mount);
        $em->method('getRepository')->willReturn($repository);

        $acquisition = $this->createMock(MountAcquisitionService::class);
        $acquisition->method('grantMount')->willThrowException(new \DomainException('disabled'));

        $service = new MountQuestRewardGranter($em, $acquisition);

        $this->expectException(\DomainException::class);
        $service->grantBySlug($player, 'disabled_mount');
    }

    public function testGrantBySlugForwardsFlushFlag(): void
    {
        $player = new Player();
        $mount = (new Mount())->setSlug('wild_boar')->setName('Sanglier')->setDescription('...');

        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($mount);
        $em->method('getRepository')->willReturn($repository);

        $acquisition = $this->createMock(MountAcquisitionService::class);
        $acquisition->expects($this->once())
            ->method('grantMount')
            ->with($player, $mount, PlayerMount::SOURCE_QUEST, false)
            ->willReturn(new PlayerMount($player, $mount, PlayerMount::SOURCE_QUEST));

        $service = new MountQuestRewardGranter($em, $acquisition);
        $service->grantBySlug($player, 'wild_boar', flush: false);
    }
}
