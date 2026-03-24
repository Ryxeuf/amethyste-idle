<?php

namespace App\Tests\Unit\GameEngine\Party;

use App\Entity\App\Party;
use App\Entity\App\PartyInvitation;
use App\Entity\App\PartyMember;
use App\Entity\App\Player;
use App\GameEngine\Party\PartyManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PartyManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private PartyManager $manager;
    private EntityRepository&MockObject $partyRepo;
    private EntityRepository&MockObject $memberRepo;
    private EntityRepository&MockObject $invitationRepo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->partyRepo = $this->createMock(EntityRepository::class);
        $this->memberRepo = $this->createMock(EntityRepository::class);
        $this->invitationRepo = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')
            ->willReturnCallback(fn (string $class) => match ($class) {
                Party::class => $this->partyRepo,
                PartyMember::class => $this->memberRepo,
                PartyInvitation::class => $this->invitationRepo,
                default => $this->createMock(EntityRepository::class),
            });

        $this->manager = new PartyManager($this->em);
    }

    public function testCreatePartySuccess(): void
    {
        $player = $this->createPlayer(1);

        $this->memberRepo->method('findOneBy')->willReturn(null);

        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->once())->method('flush');

        $party = $this->manager->createParty($player);

        $this->assertSame($player, $party->getLeader());
        $this->assertSame(4, $party->getMaxSize());
        $this->assertSame(1, $party->getMemberCount());
    }

    public function testCreatePartyAlreadyInParty(): void
    {
        $player = $this->createPlayer(1);
        $existingMember = new PartyMember();

        $this->memberRepo->method('findOneBy')->willReturn($existingMember);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('déjà dans un groupe');

        $this->manager->createParty($player);
    }

    public function testInvitePlayerSuccess(): void
    {
        $inviter = $this->createPlayer(1);
        $target = $this->createPlayer(2);

        $party = $this->createPartyWithLeader($inviter);
        $membership = $this->createMembership($party, $inviter);

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
        $this->assertSame($party, $invitation->getParty());
    }

    public function testInvitePlayerNotLeader(): void
    {
        $leader = $this->createPlayer(1);
        $member = $this->createPlayer(2);
        $target = $this->createPlayer(3);

        $party = $this->createPartyWithLeader($leader);
        $membership = $this->createMembership($party, $member);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $member => $membership,
                default => null,
            });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('chef de groupe');

        $this->manager->invitePlayer($member, $target);
    }

    public function testInvitePlayerPartyFull(): void
    {
        $leader = $this->createPlayer(1);
        $target = $this->createPlayer(5);

        $party = $this->createPartyWithLeader($leader);
        $party->setMaxSize(4);
        // Add 4 members to make it full
        for ($i = 1; $i <= 4; ++$i) {
            $m = new PartyMember();
            $m->setPlayer($this->createPlayer($i));
            $party->addMember($m);
        }

        $membership = $this->createMembership($party, $leader);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $leader => $membership,
                $target => null,
                default => null,
            });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('complet');

        $this->manager->invitePlayer($leader, $target);
    }

    public function testAcceptInvitation(): void
    {
        $player = $this->createPlayer(2);
        $leader = $this->createPlayer(1);

        $party = $this->createPartyWithLeader($leader);

        $invitation = new PartyInvitation();
        $invitation->setParty($party);
        $invitation->setPlayer($player);
        $invitation->setInvitedBy($leader);

        $this->memberRepo->method('findOneBy')->willReturn(null);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('remove');
        $this->em->expects($this->once())->method('flush');

        $member = $this->manager->acceptInvitation($player, $invitation);
        $this->assertSame($player, $member->getPlayer());
        $this->assertSame($party, $member->getParty());
    }

    public function testAcceptInvitationWrongPlayer(): void
    {
        $player = $this->createPlayer(2);
        $otherPlayer = $this->createPlayer(3);

        $invitation = new PartyInvitation();
        $invitation->setParty($this->createPartyWithLeader($this->createPlayer(1)));
        $invitation->setPlayer($otherPlayer);
        $invitation->setInvitedBy($this->createPlayer(1));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pas destinée');

        $this->manager->acceptInvitation($player, $invitation);
    }

    public function testLeavePartyAsMember(): void
    {
        $leader = $this->createPlayer(1);
        $member = $this->createPlayer(2);

        $party = $this->createPartyWithLeader($leader);
        $leaderMembership = $this->createMembership($party, $leader);
        $memberMembership = $this->createMembership($party, $member);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $member => $memberMembership,
                default => null,
            });

        $this->em->expects($this->once())->method('remove')->with($memberMembership);
        $this->em->expects($this->once())->method('flush');

        $this->manager->leaveParty($member);
    }

    public function testLeavePartyAsLeaderDisbands(): void
    {
        $leader = $this->createPlayer(1);

        $party = $this->createPartyWithLeader($leader);
        $membership = $this->createMembership($party, $leader);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $leader => $membership,
                default => null,
            });
        $this->invitationRepo->method('findBy')->willReturn([]);

        // remove membership + remove party
        $this->em->expects($this->exactly(2))->method('remove');
        $this->em->expects($this->once())->method('flush');

        $this->manager->leaveParty($leader);
    }

    public function testLeavePartyAsLeaderTransfers(): void
    {
        $leader = $this->createPlayer(1);
        $member = $this->createPlayer(2);

        $party = $this->createPartyWithLeader($leader);
        $leaderMembership = $this->createMembership($party, $leader);
        $memberMembership = $this->createMembership($party, $member);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $leader => $leaderMembership,
                default => null,
            });

        $this->em->expects($this->once())->method('remove')->with($leaderMembership);
        $this->em->expects($this->once())->method('flush');

        $this->manager->leaveParty($leader);

        // Leadership transferred to remaining member
        $this->assertSame($member, $party->getLeader());
    }

    public function testTransferLeader(): void
    {
        $leader = $this->createPlayer(1);
        $member = $this->createPlayer(2);

        $party = $this->createPartyWithLeader($leader);
        $leaderMembership = $this->createMembership($party, $leader);
        $memberMembership = $this->createMembership($party, $member);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $leader => $leaderMembership,
                $member => $memberMembership,
                default => null,
            });

        $this->em->expects($this->once())->method('flush');

        $this->manager->transferLeader($leader, $member);
        $this->assertSame($member, $party->getLeader());
    }

    public function testTransferLeaderNotLeader(): void
    {
        $leader = $this->createPlayer(1);
        $member = $this->createPlayer(2);

        $party = $this->createPartyWithLeader($leader);
        $memberMembership = $this->createMembership($party, $member);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $member => $memberMembership,
                default => null,
            });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('chef de groupe');

        $this->manager->transferLeader($member, $leader);
    }

    public function testDisbandParty(): void
    {
        $leader = $this->createPlayer(1);

        $party = $this->createPartyWithLeader($leader);
        $membership = $this->createMembership($party, $leader);

        $this->memberRepo->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => match ($criteria['player']) {
                $leader => $membership,
                default => null,
            });
        $this->invitationRepo->method('findBy')->willReturn([]);

        $this->em->expects($this->once())->method('remove')->with($party);
        $this->em->expects($this->once())->method('flush');

        $this->manager->disbandParty($leader);
    }

    private function createPlayer(int $id): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getName')->willReturn('Player' . $id);

        return $player;
    }

    private function createPartyWithLeader(Player $leader): Party
    {
        $party = new Party();
        $party->setLeader($leader);
        $party->setMaxSize(4);

        return $party;
    }

    private function createMembership(Party $party, Player $player): PartyMember
    {
        $member = new PartyMember();
        $member->setParty($party);
        $member->setPlayer($player);
        $party->addMember($member);

        return $member;
    }
}
