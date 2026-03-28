<?php

namespace App\Tests\Unit\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildInvitation;
use App\Entity\App\GuildMember;
use App\Entity\App\Player;
use App\Enum\GuildRank;
use App\GameEngine\Guild\GuildManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GuildManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private GuildManager $manager;
    private EntityRepository&MockObject $guildRepo;
    private EntityRepository&MockObject $memberRepo;
    private EntityRepository&MockObject $invitationRepo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->guildRepo = $this->createMock(EntityRepository::class);
        $this->memberRepo = $this->createMock(EntityRepository::class);
        $this->invitationRepo = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')
            ->willReturnCallback(fn (string $class) => match ($class) {
                Guild::class => $this->guildRepo,
                GuildMember::class => $this->memberRepo,
                GuildInvitation::class => $this->invitationRepo,
                default => $this->createMock(EntityRepository::class),
            });

        $this->manager = new GuildManager($this->em);
    }

    public function testCreateGuildSuccess(): void
    {
        $player = $this->createPlayer(1, 10000);

        $this->memberRepo->method('findOneBy')->willReturn(null);
        $this->guildRepo->method('findOneBy')->willReturn(null);

        $this->em->expects($this->exactly(3))->method('persist');
        $this->em->expects($this->once())->method('flush');

        $guild = $this->manager->createGuild($player, 'Test Guild', 'TST', 'A test guild');

        $this->assertSame('Test Guild', $guild->getName());
        $this->assertSame('TST', $guild->getTag());
        $this->assertSame($player, $guild->getLeader());
    }

    public function testCreateGuildInsufficientGils(): void
    {
        $player = $this->createPlayer(1, 100);

        $this->memberRepo->method('findOneBy')->willReturn(null);
        $this->guildRepo->method('findOneBy')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Gils');

        $this->manager->createGuild($player, 'Test Guild', 'TST');
    }

    public function testCreateGuildAlreadyInGuild(): void
    {
        $player = $this->createPlayer(1, 10000);
        $existingMember = new GuildMember();

        $this->memberRepo->method('findOneBy')->willReturn($existingMember);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('déjà dans une guilde');

        $this->manager->createGuild($player, 'Test Guild', 'TST');
    }

    public function testCreateGuildInvalidTag(): void
    {
        $player = $this->createPlayer(1, 10000);

        $this->memberRepo->method('findOneBy')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('tag');

        $this->manager->createGuild($player, 'Test Guild', 'AB');
    }

    public function testCreateGuildDuplicateName(): void
    {
        $player = $this->createPlayer(1, 10000);
        $existingGuild = new Guild();

        $this->memberRepo->method('findOneBy')->willReturn(null);
        $this->guildRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => isset($criteria['name']) ? $existingGuild : null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('nom de guilde');

        $this->manager->createGuild($player, 'Existing Guild', 'NEW');
    }

    public function testInvitePlayerSuccess(): void
    {
        $inviter = $this->createPlayer(1);
        $target = $this->createPlayer(2);

        $guild = $this->createGuildWithLeader($inviter);
        $membership = $this->createMembership($guild, $inviter, GuildRank::Officer);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $inviter => $membership,
                $target => null,
                default => null,
            });
        $this->invitationRepo->method('findOneBy')->willReturn(null);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $invitation = $this->manager->invitePlayer($inviter, $target);
        $this->assertSame($target, $invitation->getPlayer());
        $this->assertSame($guild, $invitation->getGuild());
    }

    public function testInvitePlayerInsufficientRank(): void
    {
        $inviter = $this->createPlayer(1);
        $target = $this->createPlayer(2);

        $guild = $this->createGuildWithLeader($inviter);
        $membership = $this->createMembership($guild, $inviter, GuildRank::Member);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $inviter => $membership,
                default => null,
            });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('rang requis');

        $this->manager->invitePlayer($inviter, $target);
    }

    public function testAcceptInvitation(): void
    {
        $player = $this->createPlayer(2);
        $guild = new Guild();
        $guild->setName('Test');
        $guild->setTag('TST');

        $invitation = new GuildInvitation();
        $invitation->setGuild($guild);
        $invitation->setPlayer($player);
        $invitation->setInvitedBy($this->createPlayer(1));

        $this->memberRepo->method('findOneBy')->willReturn(null);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('remove');
        $this->em->expects($this->once())->method('flush');

        $member = $this->manager->acceptInvitation($player, $invitation);
        $this->assertSame($player, $member->getPlayer());
        $this->assertSame(GuildRank::Recruit, $member->getRank());
    }

    public function testLeaveGuildAsLeaderFails(): void
    {
        $player = $this->createPlayer(1);
        $guild = $this->createGuildWithLeader($player);
        $membership = $this->createMembership($guild, $player, GuildRank::Leader);

        $this->memberRepo->method('findOneBy')->willReturn($membership);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('chef de guilde');

        $this->manager->leaveGuild($player);
    }

    public function testLeaveGuildAsMember(): void
    {
        $player = $this->createPlayer(2);
        $guild = $this->createGuildWithLeader($this->createPlayer(1));
        $membership = $this->createMembership($guild, $player, GuildRank::Member);

        $this->memberRepo->method('findOneBy')->willReturn($membership);
        $this->em->expects($this->once())->method('remove');
        $this->em->expects($this->once())->method('flush');

        $this->manager->leaveGuild($player);
    }

    public function testLeaveGuildRevokesPrestigeTitle(): void
    {
        $player = $this->createPlayer(2);
        $player->expects($this->once())->method('setPrestigeTitle')->with(null);

        $guild = $this->createGuildWithLeader($this->createPlayer(1));
        $membership = $this->createMembership($guild, $player, GuildRank::Member);

        $this->memberRepo->method('findOneBy')->willReturn($membership);
        $this->em->expects($this->once())->method('remove');
        $this->em->expects($this->once())->method('flush');

        $this->manager->leaveGuild($player);
    }

    public function testKickMemberRevokesPrestigeTitle(): void
    {
        $officer = $this->createPlayer(1);
        $recruit = $this->createPlayer(2);

        $guild = $this->createGuildWithLeader($this->createPlayer(3));
        $officerMembership = $this->createMembership($guild, $officer, GuildRank::Officer);
        $recruitMembership = $this->createMembership($guild, $recruit, GuildRank::Recruit);

        $recruit->expects($this->once())->method('setPrestigeTitle')->with(null);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $officer => $officerMembership,
                default => null,
            });

        $this->em->expects($this->once())->method('remove');
        $this->em->expects($this->once())->method('flush');

        $this->manager->kickMember($officer, $recruitMembership);
    }

    public function testCreateGuildWithColor(): void
    {
        $player = $this->createPlayer(1, 10000);

        $this->memberRepo->method('findOneBy')->willReturn(null);
        $this->guildRepo->method('findOneBy')->willReturn(null);

        $this->em->expects($this->exactly(3))->method('persist');
        $this->em->expects($this->once())->method('flush');

        $guild = $this->manager->createGuild($player, 'Color Guild', 'CLR', null, '#FF5733');

        $this->assertSame('#FF5733', $guild->getColor());
    }

    public function testCreateGuildDefaultColor(): void
    {
        $player = $this->createPlayer(1, 10000);

        $this->memberRepo->method('findOneBy')->willReturn(null);
        $this->guildRepo->method('findOneBy')->willReturn(null);

        $this->em->expects($this->exactly(3))->method('persist');
        $this->em->expects($this->once())->method('flush');

        $guild = $this->manager->createGuild($player, 'Default Guild', 'DFT');

        $this->assertSame('#9333EA', $guild->getColor());
    }

    public function testPromoteMember(): void
    {
        $leader = $this->createPlayer(1);
        $recruit = $this->createPlayer(2);

        $guild = $this->createGuildWithLeader($leader);
        $leaderMembership = $this->createMembership($guild, $leader, GuildRank::Leader);
        $recruitMembership = $this->createMembership($guild, $recruit, GuildRank::Recruit);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $leader => $leaderMembership,
                default => null,
            });

        $this->em->expects($this->once())->method('flush');

        $this->manager->promote($leader, $recruitMembership);
        $this->assertSame(GuildRank::Member, $recruitMembership->getRank());
    }

    public function testDemoteMember(): void
    {
        $leader = $this->createPlayer(1);
        $officer = $this->createPlayer(2);

        $guild = $this->createGuildWithLeader($leader);
        $leaderMembership = $this->createMembership($guild, $leader, GuildRank::Leader);
        $officerMembership = $this->createMembership($guild, $officer, GuildRank::Officer);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $leader => $leaderMembership,
                default => null,
            });

        $this->em->expects($this->once())->method('flush');

        $this->manager->demote($leader, $officerMembership);
        $this->assertSame(GuildRank::Member, $officerMembership->getRank());
    }

    public function testKickMember(): void
    {
        $officer = $this->createPlayer(1);
        $recruit = $this->createPlayer(2);

        $guild = $this->createGuildWithLeader($this->createPlayer(3));
        $officerMembership = $this->createMembership($guild, $officer, GuildRank::Officer);
        $recruitMembership = $this->createMembership($guild, $recruit, GuildRank::Recruit);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $officer => $officerMembership,
                default => null,
            });

        $this->em->expects($this->once())->method('remove');
        $this->em->expects($this->once())->method('flush');

        $this->manager->kickMember($officer, $recruitMembership);
    }

    public function testKickMemberSameRankFails(): void
    {
        $officer1 = $this->createPlayer(1);
        $officer2 = $this->createPlayer(2);

        $guild = $this->createGuildWithLeader($this->createPlayer(3));
        $officer1Membership = $this->createMembership($guild, $officer1, GuildRank::Officer);
        $officer2Membership = $this->createMembership($guild, $officer2, GuildRank::Officer);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $officer1 => $officer1Membership,
                default => null,
            });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('rang égal ou supérieur');

        $this->manager->kickMember($officer1, $officer2Membership);
    }

    private function createPlayer(int $id, int $gils = 0): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getName')->willReturn('Player' . $id);
        $player->method('getGils')->willReturn($gils);
        $player->method('removeGils')->willReturn(true);

        return $player;
    }

    private function createGuildWithLeader(Player $leader): Guild
    {
        $guild = new Guild();
        $guild->setName('Test Guild');
        $guild->setTag('TST');
        $guild->setLeader($leader);

        return $guild;
    }

    private function createMembership(Guild $guild, Player $player, GuildRank $rank): GuildMember
    {
        $member = new GuildMember();
        $member->setGuild($guild);
        $member->setPlayer($player);
        $member->setRank($rank);

        return $member;
    }
}
