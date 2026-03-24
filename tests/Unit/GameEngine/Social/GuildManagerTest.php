<?php

namespace App\Tests\Unit\GameEngine\Social;

use App\Entity\App\Guild;
use App\Entity\App\GuildInvitation;
use App\Entity\App\GuildMember;
use App\Entity\App\Player;
use App\Enum\GuildRank;
use App\GameEngine\Social\GuildManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;

class GuildManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private HubInterface&MockObject $hub;
    private LoggerInterface&MockObject $logger;
    private GuildManager $manager;
    private EntityRepository&MockObject $guildRepo;
    private EntityRepository&MockObject $memberRepo;
    private EntityRepository&MockObject $invitationRepo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->hub = $this->createMock(HubInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->guildRepo = $this->createMock(EntityRepository::class);
        $this->memberRepo = $this->createMock(EntityRepository::class);
        $this->invitationRepo = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')
            ->willReturnCallback(function (string $class) {
                return match ($class) {
                    Guild::class => $this->guildRepo,
                    GuildMember::class => $this->memberRepo,
                    GuildInvitation::class => $this->invitationRepo,
                };
            });

        $this->manager = new GuildManager($this->em, $this->hub, $this->logger);
    }

    public function testCreateGuild(): void
    {
        $leader = $this->createPlayer(1, 10000);

        $this->memberRepo->method('findOneBy')->willReturn(null);
        $this->guildRepo->method('findOneBy')->willReturn(null);

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted) {
            $persisted[] = $entity;
        });
        $this->em->expects($this->once())->method('flush');

        $guild = $this->manager->create($leader, 'Test Guild', 'TG', 'A test guild');

        $this->assertSame('Test Guild', $guild->getName());
        $this->assertSame('TG', $guild->getTag());
        $this->assertSame('A test guild', $guild->getDescription());
        $this->assertCount(2, $persisted);
        $this->assertInstanceOf(Guild::class, $persisted[0]);
        $this->assertInstanceOf(GuildMember::class, $persisted[1]);
    }

    public function testCreateGuildWithInsufficientGils(): void
    {
        $leader = $this->createPlayer(1, 1000);

        $this->memberRepo->method('findOneBy')->willReturn(null);
        $this->guildRepo->method('findOneBy')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('5000 gils');
        $this->manager->create($leader, 'Test Guild', 'TG');
    }

    public function testCreateGuildAlreadyInGuild(): void
    {
        $leader = $this->createPlayer(1, 10000);

        $existingMember = $this->createMock(GuildMember::class);
        $existingGuild = $this->createMock(Guild::class);
        $existingMember->method('getGuild')->willReturn($existingGuild);

        $this->memberRepo->method('findOneBy')->willReturn($existingMember);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('déjà membre');
        $this->manager->create($leader, 'Test Guild', 'TG');
    }

    public function testCreateGuildDuplicateName(): void
    {
        $leader = $this->createPlayer(1, 10000);

        $this->memberRepo->method('findOneBy')->willReturn(null);
        $existing = $this->createMock(Guild::class);
        $this->guildRepo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($existing) {
                if (isset($criteria['name'])) {
                    return $existing;
                }

                return null;
            });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('nom de guilde');
        $this->manager->create($leader, 'Existing Guild', 'NEW');
    }

    public function testCreateGuildInvalidTag(): void
    {
        $leader = $this->createPlayer(1, 10000);

        $this->memberRepo->method('findOneBy')->willReturn(null);
        $this->guildRepo->method('findOneBy')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('tag');
        $this->manager->create($leader, 'Test Guild', 'A');
    }

    public function testInvitePlayer(): void
    {
        $inviter = $this->createPlayer(1, 0);
        $target = $this->createPlayer(2, 0);
        $guild = $this->createGuildWithMembers(1);

        $inviterMember = $this->createMock(GuildMember::class);
        $inviterMember->method('getRank')->willReturn(GuildRank::Officer);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($inviterMember, $inviter) {
                if (isset($criteria['guild']) && isset($criteria['player']) && $criteria['player'] === $inviter) {
                    return $inviterMember;
                }

                return null;
            });

        $this->invitationRepo->method('findOneBy')->willReturn(null);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $invitation = $this->manager->invite($guild, $inviter, $target);

        $this->assertSame($guild, $invitation->getGuild());
        $this->assertSame($target, $invitation->getPlayer());
    }

    public function testInviteWithoutPermission(): void
    {
        $inviter = $this->createPlayer(1, 0);
        $target = $this->createPlayer(2, 0);
        $guild = $this->createGuildWithMembers(1);

        $inviterMember = $this->createMock(GuildMember::class);
        $inviterMember->method('getRank')->willReturn(GuildRank::Member);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($inviterMember, $inviter) {
                if (isset($criteria['guild']) && isset($criteria['player']) && $criteria['player'] === $inviter) {
                    return $inviterMember;
                }

                return null;
            });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('droits');
        $this->manager->invite($guild, $inviter, $target);
    }

    public function testLeaveGuild(): void
    {
        $leader = $this->createPlayer(1, 0);
        $member = $this->createPlayer(2, 0);
        $guild = $this->createMock(Guild::class);
        $guild->method('getLeader')->willReturn($leader);

        $membership = $this->createMock(GuildMember::class);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($membership, $member) {
                if (isset($criteria['guild']) && isset($criteria['player']) && $criteria['player'] === $member) {
                    return $membership;
                }

                return null;
            });

        $this->em->expects($this->once())->method('remove')->with($membership);
        $this->em->expects($this->once())->method('flush');

        $this->manager->leave($guild, $member);
    }

    public function testLeaderCannotLeave(): void
    {
        $leader = $this->createPlayer(1, 0);
        $guild = $this->createMock(Guild::class);
        $guild->method('getLeader')->willReturn($leader);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maître de guilde');
        $this->manager->leave($guild, $leader);
    }

    public function testKickMember(): void
    {
        $leader = $this->createPlayer(1, 0);
        $target = $this->createPlayer(2, 0);
        $guild = $this->createMock(Guild::class);
        $guild->method('getLeader')->willReturn($leader);

        $kickerMember = $this->createMock(GuildMember::class);
        $kickerMember->method('getRank')->willReturn(GuildRank::Master);

        $targetMember = $this->createMock(GuildMember::class);
        $targetMember->method('getRank')->willReturn(GuildRank::Member);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($kickerMember, $targetMember, $leader, $target) {
                if ($criteria['player'] === $leader) {
                    return $kickerMember;
                }
                if ($criteria['player'] === $target) {
                    return $targetMember;
                }

                return null;
            });

        $this->em->expects($this->once())->method('remove')->with($targetMember);
        $this->em->expects($this->once())->method('flush');

        $this->manager->kick($guild, $leader, $target);
    }

    public function testCannotKickLeader(): void
    {
        $leader = $this->createPlayer(1, 0);
        $officer = $this->createPlayer(2, 0);
        $guild = $this->createMock(Guild::class);
        $guild->method('getLeader')->willReturn($leader);

        $officerMember = $this->createMock(GuildMember::class);
        $officerMember->method('getRank')->willReturn(GuildRank::Officer);

        $leaderMember = $this->createMock(GuildMember::class);
        $leaderMember->method('getRank')->willReturn(GuildRank::Master);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($officerMember, $leaderMember, $officer, $leader) {
                if ($criteria['player'] === $officer) {
                    return $officerMember;
                }
                if ($criteria['player'] === $leader) {
                    return $leaderMember;
                }

                return null;
            });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maître de guilde');
        $this->manager->kick($guild, $officer, $leader);
    }

    public function testPromoteMember(): void
    {
        $leader = $this->createPlayer(1, 0);
        $target = $this->createPlayer(2, 0);
        $guild = $this->createMock(Guild::class);

        $leaderMember = $this->createMock(GuildMember::class);
        $leaderMember->method('getRank')->willReturn(GuildRank::Master);

        $targetMember = new GuildMember();
        $targetMember->setRank(GuildRank::Recruit);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($leaderMember, $targetMember, $leader, $target) {
                if ($criteria['player'] === $leader) {
                    return $leaderMember;
                }
                if ($criteria['player'] === $target) {
                    return $targetMember;
                }

                return null;
            });

        $this->em->expects($this->once())->method('flush');

        $this->manager->promote($guild, $leader, $target, GuildRank::Officer);

        $this->assertSame(GuildRank::Officer, $targetMember->getRank());
    }

    public function testCannotPromoteToMaster(): void
    {
        $leader = $this->createPlayer(1, 0);
        $target = $this->createPlayer(2, 0);
        $guild = $this->createMock(Guild::class);

        $leaderMember = $this->createMock(GuildMember::class);
        $leaderMember->method('getRank')->willReturn(GuildRank::Master);

        $targetMember = $this->createMock(GuildMember::class);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($leaderMember, $targetMember, $leader, $target) {
                if ($criteria['player'] === $leader) {
                    return $leaderMember;
                }
                if ($criteria['player'] === $target) {
                    return $targetMember;
                }

                return null;
            });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('transfert de leadership');
        $this->manager->promote($guild, $leader, $target, GuildRank::Master);
    }

    public function testTransferLeadership(): void
    {
        $currentLeader = $this->createPlayer(1, 0);
        $newLeader = $this->createPlayer(2, 0);

        $guild = $this->createMock(Guild::class);
        $guild->method('getLeader')->willReturn($currentLeader);
        $guild->expects($this->once())->method('setLeader')->with($newLeader);

        $currentMember = new GuildMember();
        $currentMember->setRank(GuildRank::Master);

        $newMember = new GuildMember();
        $newMember->setRank(GuildRank::Officer);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($currentMember, $newMember, $currentLeader, $newLeader) {
                if ($criteria['player'] === $newLeader) {
                    return $newMember;
                }
                if ($criteria['player'] === $currentLeader) {
                    return $currentMember;
                }

                return null;
            });

        $this->em->expects($this->once())->method('flush');

        $this->manager->transferLeadership($guild, $currentLeader, $newLeader);

        $this->assertSame(GuildRank::Officer, $currentMember->getRank());
        $this->assertSame(GuildRank::Master, $newMember->getRank());
    }

    public function testTransferByNonLeaderThrows(): void
    {
        $leader = $this->createPlayer(1, 0);
        $other = $this->createPlayer(2, 0);
        $target = $this->createPlayer(3, 0);

        $guild = $this->createMock(Guild::class);
        $guild->method('getLeader')->willReturn($leader);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maître de guilde');
        $this->manager->transferLeadership($guild, $other, $target);
    }

    public function testDisbandGuild(): void
    {
        $leader = $this->createPlayer(1, 0);
        $guild = $this->createMock(Guild::class);
        $guild->method('getLeader')->willReturn($leader);

        $this->invitationRepo->method('findBy')->willReturn([]);

        $this->em->expects($this->once())->method('remove')->with($guild);
        $this->em->expects($this->once())->method('flush');

        $this->manager->disband($guild, $leader);
    }

    public function testDisbandByNonLeaderThrows(): void
    {
        $leader = $this->createPlayer(1, 0);
        $member = $this->createPlayer(2, 0);
        $guild = $this->createMock(Guild::class);
        $guild->method('getLeader')->willReturn($leader);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maître de guilde');
        $this->manager->disband($guild, $member);
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

    private function createGuildWithMembers(int $count): Guild&MockObject
    {
        $guild = $this->createMock(Guild::class);
        $members = new ArrayCollection();
        for ($i = 0; $i < $count; ++$i) {
            $members->add($this->createMock(GuildMember::class));
        }
        $guild->method('getMembers')->willReturn($members);
        $guild->method('getLeader')->willReturn($this->createPlayer(99, 0));

        return $guild;
    }
}
