<?php

namespace App\Tests\Unit\GameEngine\Mount;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Entity\Game\Monster;
use App\Entity\Game\Mount;
use App\Event\Fight\MobDeadEvent;
use App\GameEngine\Mount\MountAcquisitionService;
use App\GameEngine\Mount\MountAlreadyOwnedException;
use App\GameEngine\Mount\MountDropResolver;
use App\Repository\MountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class MountDropResolverTest extends TestCase
{
    private Monster $monster;
    private Mount $mount;

    protected function setUp(): void
    {
        $this->monster = (new Monster())->setName('Forge Lord');
        $this->mount = (new Mount())
            ->setSlug('direboar')
            ->setName('Sanglier colossal')
            ->setDescription('...')
            ->setDropMonster($this->monster)
            ->setDropProbability(100);
    }

    public function testIgnoresSummonedMobs(): void
    {
        $repository = $this->createMock(MountRepository::class);
        $repository->expects($this->never())->method('findEnabledByDropMonster');
        $acquisition = $this->expectNoGrant();

        $mob = $this->createMock(Mob::class);
        $mob->method('isSummoned')->willReturn(true);

        $this->resolver($repository, $acquisition)->onMobDead(new MobDeadEvent($mob));
    }

    public function testNoCandidatesOrZeroProbabilityOrNoFightSkipsAward(): void
    {
        // Empty candidates
        $repo1 = $this->createMock(MountRepository::class);
        $repo1->method('findEnabledByDropMonster')->willReturn([]);
        $this->resolver($repo1, $this->expectNoGrant())
            ->onMobDead(new MobDeadEvent($this->buildMob($this->buildFight([$this->buildPlayer(false)]))));

        // Zero probability
        $zeroMount = (clone $this->mount)->setDropProbability(0);
        $repo2 = $this->createMock(MountRepository::class);
        $repo2->method('findEnabledByDropMonster')->willReturn([$zeroMount]);
        $this->resolver($repo2, $this->expectNoGrant())
            ->onMobDead(new MobDeadEvent($this->buildMob($this->buildFight([$this->buildPlayer(false)]))));

        // No fight
        $repo3 = $this->createMock(MountRepository::class);
        $repo3->method('findEnabledByDropMonster')->willReturn([$this->mount]);
        $this->resolver($repo3, $this->expectNoGrant())
            ->onMobDead(new MobDeadEvent($this->buildMob(null)));

        // No alive players
        $repo4 = $this->createMock(MountRepository::class);
        $repo4->method('findEnabledByDropMonster')->willReturn([$this->mount]);
        $this->resolver($repo4, $this->expectNoGrant())
            ->onMobDead(new MobDeadEvent($this->buildMob($this->buildFight([$this->buildPlayer(true)]))));
    }

    public function testGuaranteedDropAwardsToAlivePlayerOnly(): void
    {
        $alive = $this->buildPlayer(false);
        $dead = $this->buildPlayer(true);

        $repo = $this->createMock(MountRepository::class);
        $repo->method('findEnabledByDropMonster')->with($this->monster)->willReturn([$this->mount]);

        $acquisition = $this->createMock(MountAcquisitionService::class);
        $acquisition->expects($this->once())
            ->method('grantMount')
            ->with($alive, $this->mount, PlayerMount::SOURCE_DROP);

        $this->resolver($repo, $acquisition)
            ->onMobDead(new MobDeadEvent($this->buildMob($this->buildFight([$dead, $alive]))));
    }

    public function testAlreadyOwnedExceptionIsSwallowed(): void
    {
        $alive = $this->buildPlayer(false);

        $repo = $this->createMock(MountRepository::class);
        $repo->method('findEnabledByDropMonster')->willReturn([$this->mount]);

        $acquisition = $this->createMock(MountAcquisitionService::class);
        $acquisition->expects($this->once())
            ->method('grantMount')
            ->willThrowException(new MountAlreadyOwnedException($alive, $this->mount));

        $this->resolver($repo, $acquisition)
            ->onMobDead(new MobDeadEvent($this->buildMob($this->buildFight([$alive]))));
    }

    private function resolver(MountRepository $repo, MountAcquisitionService $acquisition): MountDropResolver
    {
        return new MountDropResolver($repo, $acquisition);
    }

    private function expectNoGrant(): MountAcquisitionService
    {
        $acquisition = $this->createMock(MountAcquisitionService::class);
        $acquisition->expects($this->never())->method('grantMount');

        return $acquisition;
    }

    private function buildPlayer(bool $dead): Player
    {
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn($dead);

        return $player;
    }

    /** @param list<Player> $players */
    private function buildFight(array $players): Fight
    {
        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection($players));

        return $fight;
    }

    private function buildMob(?Fight $fight): Mob
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('isSummoned')->willReturn(false);
        $mob->method('getMonster')->willReturn($this->monster);
        $mob->method('getFight')->willReturn($fight);

        return $mob;
    }
}
