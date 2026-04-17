<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Realtime\Avatar;

use App\Entity\App\Player;
use App\GameEngine\Realtime\Avatar\AvatarUpdatedPublisher;
use App\Service\Avatar\PlayerAvatarPayloadBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class AvatarUpdatedPublisherTest extends TestCase
{
    private HubInterface&MockObject $hub;
    private PlayerAvatarPayloadBuilder&MockObject $payloadBuilder;
    private AvatarUpdatedPublisher $publisher;

    protected function setUp(): void
    {
        $this->hub = $this->createMock(HubInterface::class);
        $this->payloadBuilder = $this->createMock(PlayerAvatarPayloadBuilder::class);
        $this->publisher = new AvatarUpdatedPublisher(
            $this->hub,
            $this->payloadBuilder,
            new NullLogger(),
        );
    }

    public function testPublishSkipsWhenPayloadIsNull(): void
    {
        $player = new Player();

        $this->payloadBuilder->method('buildForMapEntity')->with($player)->willReturn(null);
        $this->hub->expects($this->never())->method('publish');

        $this->publisher->publish($player);
    }

    public function testPublishSendsUpdateWithAvatarPayload(): void
    {
        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);
        $this->setPrivateId($player, 77);

        $payload = [
            'renderMode' => 'avatar',
            'avatarHash' => str_repeat('a', 64),
            'avatar' => [
                'baseSheet' => '/assets/styles/images/avatar/body/human_m_light.png',
                'layers' => [],
            ],
        ];

        $this->payloadBuilder->method('buildForMapEntity')->with($player)->willReturn($payload);

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update): bool {
                $this->assertSame([AvatarUpdatedPublisher::TOPIC], $update->getTopics());
                $data = json_decode($update->getData(), true);

                return $data['topic'] === AvatarUpdatedPublisher::TOPIC
                    && $data['type'] === AvatarUpdatedPublisher::EVENT_TYPE
                    && $data['playerId'] === 77
                    && $data['avatarHash'] === str_repeat('a', 64)
                    && $data['renderMode'] === 'avatar'
                    && isset($data['avatar']['baseSheet']);
            }));

        $this->publisher->publish($player);
    }

    private function setPrivateId(Player $player, int $id): void
    {
        $ref = new \ReflectionProperty(Player::class, 'id');
        $ref->setValue($player, $id);
    }
}
