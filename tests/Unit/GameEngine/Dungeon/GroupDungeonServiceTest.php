<?php

namespace App\Tests\Unit\GameEngine\Dungeon;

use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Party;
use App\Entity\App\PartyMember;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\Game\Dungeon;
use App\GameEngine\Dungeon\GroupDungeonException;
use App\GameEngine\Dungeon\GroupDungeonService;
use App\GameEngine\Party\PartyManager;
use App\Repository\GroupDungeonRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupDungeonServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private GroupDungeonRunRepository&MockObject $runRepository;
    private PartyManager&MockObject $partyManager;
    private GroupDungeonService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->runRepository = $this->createMock(GroupDungeonRunRepository::class);
        $this->partyManager = $this->createMock(PartyManager::class);
        $this->service = new GroupDungeonService($this->entityManager, $this->runRepository, $this->partyManager);
    }

    private function buildPlayer(int $id, ?Zone $zone): Player
    {
        $player = new Player();
        if (null !== $zone) {
            $player->setCurrentZone($zone);
        }
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);

        return $player;
    }

    private function buildZone(int $id = 1): Zone
    {
        $zone = (new Zone())->setSlug('donjon')->setName('Donjon');
        (new \ReflectionProperty(Zone::class, 'id'))->setValue($zone, $id);

        return $zone;
    }

    private function buildDungeon(int $maxPlayers = 4): Dungeon
    {
        $dungeon = new Dungeon();
        $dungeon->setName('Caverne oubliee');
        $dungeon->setMaxPlayers($maxPlayers);

        return $dungeon;
    }

    private function buildParty(Player $leader, array $members): Party
    {
        $party = new Party();
        $party->setLeader($leader);
        foreach ($members as $memberPlayer) {
            $member = new PartyMember();
            $member->setPlayer($memberPlayer);
            $member->setParty($party);
            $party->addMember($member);
        }

        return $party;
    }

    private function membershipFor(Party $party): PartyMember
    {
        $membership = new PartyMember();
        $membership->setParty($party);

        return $membership;
    }

    public function testLaunchCreatesRunWithAllPresentMembers(): void
    {
        $zone = $this->buildZone();
        $leader = $this->buildPlayer(1, $zone);
        $member = $this->buildPlayer(2, $zone);
        $party = $this->buildParty($leader, [$member]);

        $this->partyManager->method('getPlayerMembership')->willReturn($this->membershipFor($party));
        $this->runRepository->method('findActiveForPlayer')->willReturn(null);

        $persisted = null;
        $this->entityManager->expects($this->once())->method('persist')
            ->willReturnCallback(function ($e) use (&$persisted) { $persisted = $e; });
        $this->entityManager->expects($this->once())->method('flush');

        $run = $this->service->launch($leader, $this->buildDungeon());

        $this->assertInstanceOf(GroupDungeonRun::class, $run);
        $this->assertSame($persisted, $run);
        $this->assertSame(GroupDungeonRun::STATUS_IN_PROGRESS, $run->getStatus());
        $this->assertCount(2, $run->getMembers()); // leader + membre
        $this->assertSame($leader, $run->getLeader());
    }

    public function testLaunchRejectsWithoutParty(): void
    {
        $leader = $this->buildPlayer(1, $this->buildZone());
        $this->partyManager->method('getPlayerMembership')->willReturn(null);

        $this->expectException(GroupDungeonException::class);
        $this->service->launch($leader, $this->buildDungeon());
    }

    public function testLaunchRejectsWhenNotLeader(): void
    {
        $zone = $this->buildZone();
        $actualLeader = $this->buildPlayer(9, $zone);
        $wannabe = $this->buildPlayer(1, $zone);
        $party = $this->buildParty($actualLeader, [$wannabe]);

        $this->partyManager->method('getPlayerMembership')->willReturn($this->membershipFor($party));

        $this->expectException(GroupDungeonException::class);
        $this->service->launch($wannabe, $this->buildDungeon());
    }

    public function testLaunchRejectsWhenMemberAbsent(): void
    {
        $zone = $this->buildZone(1);
        $otherZone = $this->buildZone(2);
        $leader = $this->buildPlayer(1, $zone);
        $absent = $this->buildPlayer(2, $otherZone);
        $party = $this->buildParty($leader, [$absent]);

        $this->partyManager->method('getPlayerMembership')->willReturn($this->membershipFor($party));
        $this->runRepository->method('findActiveForPlayer')->willReturn(null);

        $this->expectException(GroupDungeonException::class);
        $this->service->launch($leader, $this->buildDungeon());
    }

    public function testLaunchRejectsWhenMemberAlreadyRunning(): void
    {
        $zone = $this->buildZone();
        $leader = $this->buildPlayer(1, $zone);
        $party = $this->buildParty($leader, []);

        $this->partyManager->method('getPlayerMembership')->willReturn($this->membershipFor($party));
        $existing = new GroupDungeonRun($this->buildDungeon(), $leader, $zone);
        $this->runRepository->method('findActiveForPlayer')->willReturn($existing);

        $this->expectException(GroupDungeonException::class);
        $this->service->launch($leader, $this->buildDungeon());
    }

    public function testLaunchRejectsWhenPartyTooLarge(): void
    {
        $zone = $this->buildZone();
        $leader = $this->buildPlayer(1, $zone);
        $m2 = $this->buildPlayer(2, $zone);
        $m3 = $this->buildPlayer(3, $zone);
        $party = $this->buildParty($leader, [$m2, $m3]); // 3 participants

        $this->partyManager->method('getPlayerMembership')->willReturn($this->membershipFor($party));

        $this->expectException(GroupDungeonException::class);
        $this->service->launch($leader, $this->buildDungeon(2)); // max 2
    }

    public function testAbandonByLeader(): void
    {
        $zone = $this->buildZone();
        $leader = $this->buildPlayer(1, $zone);
        $run = new GroupDungeonRun($this->buildDungeon(), $leader, $zone);

        $this->entityManager->expects($this->once())->method('flush');
        $this->service->abandon($leader, $run);

        $this->assertSame(GroupDungeonRun::STATUS_ABANDONED, $run->getStatus());
        $this->assertNotNull($run->getEndedAt());
    }

    public function testAbandonRejectedForNonLeader(): void
    {
        $zone = $this->buildZone();
        $leader = $this->buildPlayer(1, $zone);
        $other = $this->buildPlayer(2, $zone);
        $run = new GroupDungeonRun($this->buildDungeon(), $leader, $zone);

        $this->expectException(GroupDungeonException::class);
        $this->service->abandon($other, $run);
    }
}
