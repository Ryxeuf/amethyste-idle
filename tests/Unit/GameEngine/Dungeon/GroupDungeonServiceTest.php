<?php

namespace App\Tests\Unit\GameEngine\Dungeon;

use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Party;
use App\Entity\App\PartyMember;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\Game\Dungeon;
use App\GameEngine\Dungeon\DungeonManager;
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
    private DungeonManager&MockObject $dungeonManager;
    private GroupDungeonService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->runRepository = $this->createMock(GroupDungeonRunRepository::class);
        $this->partyManager = $this->createMock(PartyManager::class);
        $this->dungeonManager = $this->createMock(DungeonManager::class);
        // Par defaut, les prerequis du donjon sont satisfaits : chaque test qui
        // les met en cause le declare explicitement.
        $this->dungeonManager->method('meetsLevelRequirement')->willReturn(true);
        $this->dungeonManager->method('getMissingEntryItems')->willReturn([]);
        $this->service = new GroupDungeonService(
            $this->entityManager,
            $this->runRepository,
            $this->partyManager,
            $this->dungeonManager,
        );
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

    /**
     * Donjon de groupe rattache a la zone d'id 1 — celle ou les tests placent le
     * leader par defaut.
     */
    private function buildDungeon(int $maxPlayers = 4, ?Zone $zone = null): Dungeon
    {
        $dungeon = new Dungeon();
        $dungeon->setName('Caverne oubliee');
        $dungeon->setMaxPlayers($maxPlayers);
        $dungeon->setZone($zone ?? $this->buildZone());

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
        $this->expectExceptionMessage('game.zone.dungeon.error.no_party');
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
        $this->expectExceptionMessage('game.zone.dungeon.error.not_leader');
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
        $this->expectExceptionMessage('game.zone.dungeon.error.member_absent');
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
        $this->expectExceptionMessage('game.zone.dungeon.error.already_running');
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
        $this->expectExceptionMessage('game.zone.dungeon.error.too_many');
        $this->service->launch($leader, $this->buildDungeon(2)); // max 2
    }

    /**
     * DON-01 — un seul modele : un donjon a `maxPlayers: 1` se lance seul,
     * sans party, par la meme mecanique que les donjons de groupe. Les
     * prerequis (XP, objets d'entree) s'appliquent a l'identique.
     */
    public function testLaunchAcceptsASoloDungeonAlone(): void
    {
        $zone = $this->buildZone();
        $leader = $this->buildPlayer(1, $zone);

        $this->partyManager->method('getPlayerMembership')->willReturn(null);
        $this->runRepository->method('findActiveForPlayer')->willReturn(null);
        $this->entityManager->expects($this->once())->method('persist');

        $run = $this->service->launch($leader, $this->buildDungeon(1, $zone));

        $this->assertCount(1, $run->getMembers());
        $this->assertSame($leader, $run->getLeader());
    }

    /**
     * `maxPlayers` reste la seule borne de taille : une party de deux ne
     * rentre pas dans un donjon a `maxPlayers: 1`.
     */
    public function testLaunchRejectsAPartyInASoloDungeon(): void
    {
        $zone = $this->buildZone();
        $leader = $this->buildPlayer(1, $zone);
        $member = $this->buildPlayer(2, $zone);
        $party = $this->buildParty($leader, [$member]);

        $this->partyManager->method('getPlayerMembership')->willReturn($this->membershipFor($party));
        $this->runRepository->method('findActiveForPlayer')->willReturn(null);

        $this->expectException(GroupDungeonException::class);
        $this->expectExceptionMessage('game.zone.dungeon.error.too_many');
        $this->service->launch($leader, $this->buildDungeon(1, $zone));
    }

    public function testLaunchRejectsDungeonAttachedToAnotherZone(): void
    {
        $zone = $this->buildZone(1);
        $leader = $this->buildPlayer(1, $zone);
        $party = $this->buildParty($leader, []);

        $this->partyManager->method('getPlayerMembership')->willReturn($this->membershipFor($party));
        $this->runRepository->method('findActiveForPlayer')->willReturn(null);

        $this->expectException(GroupDungeonException::class);
        $this->expectExceptionMessage('game.zone.dungeon.error.wrong_zone');
        $this->service->launch($leader, $this->buildDungeon(4, $this->buildZone(2)));
    }

    public function testLaunchRejectsWhenAMemberLacksExperience(): void
    {
        $zone = $this->buildZone();
        $leader = $this->buildPlayer(1, $zone);
        $member = $this->buildPlayer(2, $zone);
        $party = $this->buildParty($leader, [$member]);

        $this->partyManager->method('getPlayerMembership')->willReturn($this->membershipFor($party));
        $this->runRepository->method('findActiveForPlayer')->willReturn(null);

        $dungeonManager = $this->createMock(DungeonManager::class);
        // Le leader remplit la condition, le membre non : le lancement doit tomber.
        $dungeonManager->method('meetsLevelRequirement')
            ->willReturnCallback(static fn (Player $player): bool => 1 === $player->getId());
        $dungeonManager->method('getMissingEntryItems')->willReturn([]);

        $this->expectException(GroupDungeonException::class);
        $this->expectExceptionMessage('game.zone.dungeon.error.member_experience');
        $this->serviceWith($dungeonManager)->launch($leader, $this->buildDungeon());
    }

    public function testLaunchRejectsWhenAMemberLacksEntryItems(): void
    {
        $zone = $this->buildZone();
        $leader = $this->buildPlayer(1, $zone);
        $member = $this->buildPlayer(2, $zone);
        $party = $this->buildParty($leader, [$member]);

        $this->partyManager->method('getPlayerMembership')->willReturn($this->membershipFor($party));
        $this->runRepository->method('findActiveForPlayer')->willReturn(null);

        $dungeonManager = $this->createMock(DungeonManager::class);
        $dungeonManager->method('meetsLevelRequirement')->willReturn(true);
        $dungeonManager->method('getMissingEntryItems')
            ->willReturnCallback(static fn (Player $player): array => 1 === $player->getId() ? [] : ['Fragment Sylvestre']);

        $this->expectException(GroupDungeonException::class);
        $this->expectExceptionMessage('game.zone.dungeon.error.member_items');
        $this->serviceWith($dungeonManager)->launch($leader, $this->buildDungeon());
    }

    public function testGetLaunchBlockerIsNullWhenLaunchable(): void
    {
        $zone = $this->buildZone();
        $leader = $this->buildPlayer(1, $zone);
        $party = $this->buildParty($leader, []);

        $this->partyManager->method('getPlayerMembership')->willReturn($this->membershipFor($party));
        $this->runRepository->method('findActiveForPlayer')->willReturn(null);

        $this->assertNull($this->service->getLaunchBlocker($leader, $this->buildDungeon()));
    }

    /**
     * L'ecran de zone affiche ce motif : il doit etre exactement celui que
     * `launch()` opposerait, sinon l'UI proposerait un lancement impossible.
     */
    public function testGetLaunchBlockerReportsTheBlockingReason(): void
    {
        $leader = $this->buildPlayer(1, $this->buildZone());
        $this->partyManager->method('getPlayerMembership')->willReturn(null);

        $this->assertSame(
            'game.zone.dungeon.error.no_party',
            $this->service->getLaunchBlocker($leader, $this->buildDungeon()),
        );
    }

    public function testGetLaunchBlockerReportsMissingZone(): void
    {
        $wanderer = $this->buildPlayer(1, null);

        $this->assertSame(
            'game.zone.dungeon.error.no_zone',
            $this->service->getLaunchBlocker($wanderer, $this->buildDungeon()),
        );
    }

    private function serviceWith(DungeonManager&MockObject $dungeonManager): GroupDungeonService
    {
        return new GroupDungeonService(
            $this->entityManager,
            $this->runRepository,
            $this->partyManager,
            $dungeonManager,
        );
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
