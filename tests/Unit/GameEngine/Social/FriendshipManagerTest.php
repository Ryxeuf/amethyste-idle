<?php

namespace App\Tests\Unit\GameEngine\Social;

use App\Entity\App\Friendship;
use App\Entity\App\Player;
use App\Enum\FriendshipStatus;
use App\GameEngine\Social\FriendshipManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;

class FriendshipManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private HubInterface&MockObject $hub;
    private LoggerInterface&MockObject $logger;
    private FriendshipManager $manager;
    private EntityRepository&MockObject $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->hub = $this->createMock(HubInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')
            ->with(Friendship::class)
            ->willReturn($this->repo);

        $this->manager = new FriendshipManager($this->em, $this->hub, $this->logger);
    }

    public function testSendRequestCreatesFriendship(): void
    {
        $sender = $this->createPlayer(1);
        $recipient = $this->createPlayer(2);

        $this->repo->method('findOneBy')->willReturn(null);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $friendship = $this->manager->sendRequest($sender, $recipient);

        $this->assertSame($sender, $friendship->getPlayer());
        $this->assertSame($recipient, $friendship->getFriend());
        $this->assertSame(FriendshipStatus::Pending, $friendship->getStatus());
    }

    public function testSendRequestToSelfThrows(): void
    {
        $player = $this->createPlayer(1);

        $this->expectException(\InvalidArgumentException::class);
        $this->manager->sendRequest($player, $player);
    }

    public function testSendRequestDuplicateThrows(): void
    {
        $sender = $this->createPlayer(1);
        $recipient = $this->createPlayer(2);

        $existing = new Friendship();
        $this->repo->method('findOneBy')->willReturn($existing);

        $this->expectException(\InvalidArgumentException::class);
        $this->manager->sendRequest($sender, $recipient);
    }

    public function testAcceptFriendship(): void
    {
        $sender = $this->createPlayer(1);
        $recipient = $this->createPlayer(2);

        $friendship = new Friendship();
        $friendship->setPlayer($sender);
        $friendship->setFriend($recipient);
        $friendship->setStatus(FriendshipStatus::Pending);

        $this->em->expects($this->once())->method('flush');

        $this->manager->accept($friendship, $recipient);

        $this->assertSame(FriendshipStatus::Accepted, $friendship->getStatus());
    }

    public function testAcceptByWrongPlayerThrows(): void
    {
        $sender = $this->createPlayer(1);
        $recipient = $this->createPlayer(2);
        $intruder = $this->createPlayer(3);

        $friendship = new Friendship();
        $friendship->setPlayer($sender);
        $friendship->setFriend($recipient);
        $friendship->setStatus(FriendshipStatus::Pending);

        $this->expectException(\InvalidArgumentException::class);
        $this->manager->accept($friendship, $intruder);
    }

    public function testDeclineFriendship(): void
    {
        $sender = $this->createPlayer(1);
        $recipient = $this->createPlayer(2);

        $friendship = new Friendship();
        $friendship->setPlayer($sender);
        $friendship->setFriend($recipient);
        $friendship->setStatus(FriendshipStatus::Pending);

        $this->em->expects($this->once())->method('remove')->with($friendship);
        $this->em->expects($this->once())->method('flush');

        $this->manager->decline($friendship, $recipient);
    }

    public function testUnfriend(): void
    {
        $player = $this->createPlayer(1);
        $friend = $this->createPlayer(2);

        $friendship = new Friendship();
        $friendship->setPlayer($player);
        $friendship->setFriend($friend);
        $friendship->setStatus(FriendshipStatus::Accepted);

        $this->em->expects($this->once())->method('remove')->with($friendship);
        $this->em->expects($this->once())->method('flush');

        $this->manager->unfriend($friendship, $player);
    }

    public function testUnfriendByUnrelatedPlayerThrows(): void
    {
        $player = $this->createPlayer(1);
        $friend = $this->createPlayer(2);
        $stranger = $this->createPlayer(3);

        $friendship = new Friendship();
        $friendship->setPlayer($player);
        $friendship->setFriend($friend);

        $this->expectException(\InvalidArgumentException::class);
        $this->manager->unfriend($friendship, $stranger);
    }

    public function testIsOnlineWithRecentActivity(): void
    {
        $player = $this->createPlayer(1);
        $player->method('getUpdatedAt')->willReturn(new \DateTime('-2 minutes'));

        $this->assertTrue($this->manager->isOnline($player));
    }

    public function testIsOfflineWithOldActivity(): void
    {
        $player = $this->createPlayer(1);
        $player->method('getUpdatedAt')->willReturn(new \DateTime('-10 minutes'));

        $this->assertFalse($this->manager->isOnline($player));
    }

    private function createPlayer(int $id): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getName')->willReturn('Player' . $id);

        return $player;
    }
}
