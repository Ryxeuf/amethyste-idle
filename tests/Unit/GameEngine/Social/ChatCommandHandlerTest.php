<?php

namespace App\Tests\Unit\GameEngine\Social;

use App\Entity\App\ChatMessage;
use App\Entity\App\Map;
use App\Entity\App\Player;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Social\ChatCommandHandler;
use App\GameEngine\Social\ChatManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChatCommandHandlerTest extends TestCase
{
    private ChatManager&MockObject $chatManager;
    private GuildManager&MockObject $guildManager;
    private EntityManagerInterface&MockObject $em;
    private ChatCommandHandler $handler;

    protected function setUp(): void
    {
        $this->chatManager = $this->createMock(ChatManager::class);
        $this->guildManager = $this->createMock(GuildManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->handler = new ChatCommandHandler($this->chatManager, $this->guildManager, $this->em);
    }

    public function testIsCommandReturnsTrueForSlashPrefix(): void
    {
        $this->assertTrue($this->handler->isCommand('/whisper test hello'));
        $this->assertTrue($this->handler->isCommand('/help'));
        $this->assertTrue($this->handler->isCommand('/who'));
    }

    public function testIsCommandReturnsFalseForNormalMessage(): void
    {
        $this->assertFalse($this->handler->isCommand('hello world'));
        $this->assertFalse($this->handler->isCommand(''));
    }

    public function testUnknownCommandReturnsError(): void
    {
        $sender = $this->createPlayer(1, 'Alice');
        $result = $this->handler->handle($sender, '/unknown');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('/unknown', $result['message']);
        $this->assertStringContainsString('/help', $result['message']);
    }

    public function testHelpReturnsCommandList(): void
    {
        $sender = $this->createPlayer(1, 'Alice');
        $result = $this->handler->handle($sender, '/help');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['system']);
        $this->assertStringContainsString('/whisper', $result['message']);
        $this->assertStringContainsString('/zone', $result['message']);
        $this->assertStringContainsString('/emote', $result['message']);
        $this->assertStringContainsString('/who', $result['message']);
    }

    public function testWhisperWithMissingArgs(): void
    {
        $sender = $this->createPlayer(1, 'Alice');

        $result = $this->handler->handle($sender, '/whisper');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Usage', $result['message']);
    }

    public function testWhisperWithMissingMessage(): void
    {
        $sender = $this->createPlayer(1, 'Alice');

        $result = $this->handler->handle($sender, '/whisper Bob');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Usage', $result['message']);
    }

    public function testWhisperToUnknownPlayer(): void
    {
        $sender = $this->createPlayer(1, 'Alice');

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->with(['name' => 'Unknown'])->willReturn(null);
        $this->em->method('getRepository')->with(Player::class)->willReturn($repo);

        $result = $this->handler->handle($sender, '/whisper Unknown hello there');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('introuvable', $result['message']);
    }

    public function testWhisperToSelf(): void
    {
        $sender = $this->createPlayer(1, 'Alice');

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->with(['name' => 'Alice'])->willReturn($sender);
        $this->em->method('getRepository')->with(Player::class)->willReturn($repo);

        $result = $this->handler->handle($sender, '/whisper Alice hello');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('vous-meme', $result['message']);
    }

    public function testWhisperSuccess(): void
    {
        $sender = $this->createPlayer(1, 'Alice');
        $recipient = $this->createPlayer(2, 'Bob');

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->with(['name' => 'Bob'])->willReturn($recipient);
        $this->em->method('getRepository')->with(Player::class)->willReturn($repo);

        $chatMessage = $this->createMock(ChatMessage::class);
        $chatMessage->method('getId')->willReturn(42);

        $this->chatManager->expects($this->once())
            ->method('sendPrivateMessage')
            ->with($sender, $recipient, 'hello there')
            ->willReturn($chatMessage);

        $result = $this->handler->handle($sender, '/whisper Bob hello there');
        $this->assertTrue($result['success']);
        $this->assertSame(42, $result['messageId']);
    }

    public function testWhisperAliasW(): void
    {
        $sender = $this->createPlayer(1, 'Alice');
        $recipient = $this->createPlayer(2, 'Bob');

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn($recipient);
        $this->em->method('getRepository')->willReturn($repo);

        $chatMessage = $this->createMock(ChatMessage::class);
        $chatMessage->method('getId')->willReturn(1);
        $this->chatManager->method('sendPrivateMessage')->willReturn($chatMessage);

        $result = $this->handler->handle($sender, '/w Bob salut');
        $this->assertTrue($result['success']);
    }

    public function testZoneWithEmptyMessage(): void
    {
        $sender = $this->createPlayer(1, 'Alice');
        $result = $this->handler->handle($sender, '/zone');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Usage', $result['message']);
    }

    public function testZoneSuccess(): void
    {
        $sender = $this->createPlayer(1, 'Alice');

        $chatMessage = $this->createMock(ChatMessage::class);
        $chatMessage->method('getId')->willReturn(10);

        $this->chatManager->expects($this->once())
            ->method('sendMapMessage')
            ->with($sender, 'bonjour la zone')
            ->willReturn($chatMessage);

        $result = $this->handler->handle($sender, '/zone bonjour la zone');
        $this->assertTrue($result['success']);
        $this->assertSame(10, $result['messageId']);
    }

    public function testGlobalSuccess(): void
    {
        $sender = $this->createPlayer(1, 'Alice');

        $chatMessage = $this->createMock(ChatMessage::class);
        $chatMessage->method('getId')->willReturn(20);

        $this->chatManager->expects($this->once())
            ->method('sendGlobalMessage')
            ->with($sender, 'salut tout le monde')
            ->willReturn($chatMessage);

        $result = $this->handler->handle($sender, '/global salut tout le monde');
        $this->assertTrue($result['success']);
    }

    public function testEmoteOnMap(): void
    {
        $map = $this->createMock(Map::class);
        $sender = $this->createPlayer(1, 'Alice', $map);

        $chatMessage = $this->createMock(ChatMessage::class);
        $chatMessage->method('getId')->willReturn(30);

        $this->chatManager->expects($this->once())
            ->method('sendMapMessage')
            ->with($sender, '* Alice danse *')
            ->willReturn($chatMessage);

        $result = $this->handler->handle($sender, '/emote danse');
        $this->assertTrue($result['success']);
    }

    public function testEmoteWithoutMap(): void
    {
        $sender = $this->createPlayer(1, 'Alice');

        $chatMessage = $this->createMock(ChatMessage::class);
        $chatMessage->method('getId')->willReturn(31);

        $this->chatManager->expects($this->once())
            ->method('sendGlobalMessage')
            ->with($sender, '* Alice rit *')
            ->willReturn($chatMessage);

        $result = $this->handler->handle($sender, '/me rit');
        $this->assertTrue($result['success']);
    }

    public function testEmoteWithEmptyAction(): void
    {
        $sender = $this->createPlayer(1, 'Alice');
        $result = $this->handler->handle($sender, '/emote');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Usage', $result['message']);
    }

    public function testWhoWithoutMap(): void
    {
        $sender = $this->createPlayer(1, 'Alice');
        $result = $this->handler->handle($sender, '/who');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('aucune carte', $result['message']);
    }

    public function testWhoWithMap(): void
    {
        $map = $this->createMock(Map::class);
        $map->method('getName')->willReturn('Foret');
        $sender = $this->createPlayer(1, 'Alice', $map);

        $player2 = $this->createPlayer(2, 'Bob', $map);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findBy')->with(['map' => $map])->willReturn([$sender, $player2]);
        $this->em->method('getRepository')->with(Player::class)->willReturn($repo);

        $result = $this->handler->handle($sender, '/who');
        $this->assertTrue($result['success']);
        $this->assertTrue($result['system']);
        $this->assertStringContainsString('2 joueur(s)', $result['message']);
        $this->assertStringContainsString('Alice', $result['message']);
        $this->assertStringContainsString('Bob', $result['message']);
        $this->assertStringContainsString('Foret', $result['message']);
    }

    private function createPlayer(int $id, string $name, ?Map $map = null): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getName')->willReturn($name);
        $player->method('getMap')->willReturn($map);

        return $player;
    }
}
