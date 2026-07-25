<?php

namespace App\Tests\Unit\GameEngine\Social;

use App\Entity\App\ChatMessage;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\GameEngine\Social\ChatManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;

/**
 * Couvre les ajouts ZON-14 au chat : canal `zone`, topic Mercure par zone et
 * serialisation du zoneId. La logique d'envoi/historique reprend a l'identique
 * le canal `map` deja eprouve.
 */
class ChatManagerZoneTest extends TestCase
{
    private ChatManager $manager;

    protected function setUp(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);
        $em->method('getRepository')->willReturn($repository);

        $this->manager = new ChatManager(
            $em,
            $this->createMock(HubInterface::class),
            $this->createMock(LoggerInterface::class),
        );
    }

    private function buildZone(int $id): Zone
    {
        $zone = (new Zone())->setSlug('foret')->setName('Foret');
        $ref = new \ReflectionProperty(Zone::class, 'id');
        $ref->setValue($zone, $id);

        return $zone;
    }

    private function buildSender(int $id = 1): Player
    {
        $player = new Player();
        $player->setName('Testeur');
        $ref = new \ReflectionProperty(Player::class, 'id');
        $ref->setValue($player, $id);

        return $player;
    }

    private function buildMessage(Zone $zone): ChatMessage
    {
        $message = new ChatMessage();
        $message->setChannel(ChatMessage::CHANNEL_ZONE);
        $message->setContent('bonjour la zone');
        $message->setSender($this->buildSender());
        $message->setZone($zone);
        $ref = new \ReflectionProperty(ChatMessage::class, 'id');
        $ref->setValue($message, 1);

        return $message;
    }

    public function testZoneChannelConstant(): void
    {
        $this->assertSame('zone', ChatMessage::CHANNEL_ZONE);
    }

    public function testZoneAccessorRoundTrip(): void
    {
        $zone = $this->buildZone(7);
        $message = (new ChatMessage())->setZone($zone);
        $this->assertSame($zone, $message->getZone());
    }

    public function testGetTopicsForZoneMessage(): void
    {
        $message = $this->buildMessage($this->buildZone(42));

        $method = new \ReflectionMethod(ChatManager::class, 'getTopicsForMessage');
        /** @var string[] $topics */
        $topics = $method->invoke($this->manager, $message);

        $this->assertSame(['chat/zone/42'], $topics);
    }

    public function testGetTopicsForZoneMessageWithoutZone(): void
    {
        $message = new ChatMessage();
        $message->setChannel(ChatMessage::CHANNEL_ZONE);
        $message->setSender(new Player());

        $method = new \ReflectionMethod(ChatManager::class, 'getTopicsForMessage');
        $this->assertSame([], $method->invoke($this->manager, $message));
    }

    public function testSerializeIncludesZoneId(): void
    {
        $message = $this->buildMessage($this->buildZone(99));

        $method = new \ReflectionMethod(ChatManager::class, 'serializeMessage');
        /** @var array<string, mixed> $data */
        $data = $method->invoke($this->manager, $message);

        $this->assertSame('zone', $data['channel']);
        $this->assertSame(99, $data['zoneId']);
    }
}
