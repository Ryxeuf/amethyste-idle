<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Realtime\Avatar;

use App\Entity\App\Map;
use App\Entity\App\Player;
use App\GameEngine\Realtime\Avatar\AvatarUpdatedPublisher;
use App\Helper\GearHelper;
use App\Service\Avatar\AvatarHashGenerator;
use App\Service\Avatar\ItemAvatarSheetResolver;
use App\Service\Avatar\PlayerAvatarPayloadBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class AvatarUpdatedPublisherTest extends TestCase
{
    private HubInterface&MockObject $hub;
    private GearHelper&MockObject $gearHelper;
    private PlayerAvatarPayloadBuilder $payloadBuilder;
    private AvatarUpdatedPublisher $publisher;

    protected function setUp(): void
    {
        $this->hub = $this->createMock(HubInterface::class);
        $this->gearHelper = $this->createMock(GearHelper::class);
        $this->gearHelper->method('getEquippedGearByLocation')->willReturn(null);

        $this->payloadBuilder = new PlayerAvatarPayloadBuilder(
            new AvatarHashGenerator(),
            $this->gearHelper,
            new ItemAvatarSheetResolver(),
        );

        $this->publisher = new AvatarUpdatedPublisher(
            $this->hub,
            $this->payloadBuilder,
            new NullLogger(),
        );
    }

    public function testPublishSkipsWhenPlayerHasNoAvatar(): void
    {
        $player = new Player();

        $this->hub->expects($this->never())->method('publish');

        $this->publisher->publish($player);
    }

    public function testPublishSendsUpdateWithAvatarPayload(): void
    {
        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);
        $this->setPrivateId($player, 77);

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update): bool {
                $this->assertSame([AvatarUpdatedPublisher::TOPIC], $update->getTopics());
                $data = json_decode($update->getData(), true);

                return $data['topic'] === AvatarUpdatedPublisher::TOPIC
                    && $data['type'] === AvatarUpdatedPublisher::EVENT_TYPE
                    && $data['playerId'] === 77
                    && is_string($data['avatarHash'])
                    && $data['renderMode'] === 'avatar'
                    && isset($data['avatar']['baseSheet'])
                    && str_ends_with($data['avatar']['baseSheet'], 'human_m_light.png');
            }));

        $this->publisher->publish($player);
    }

    public function testPublishIncludesMapIdAndTimestamp(): void
    {
        $map = new Map();
        $this->setPrivateId($map, 42, Map::class);

        $player = new Player();
        $player->setMap($map);
        $player->setAvatarAppearance(['body' => 'human_m_light']);
        $this->setPrivateId($player, 7);

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update): bool {
                $data = json_decode($update->getData(), true);

                return $data['mapId'] === 42
                    && $data['playerId'] === 7
                    && is_string($data['avatarUpdatedAt']);
            }));

        $this->publisher->publish($player);
    }

    private function setPrivateId(object $entity, int $id, ?string $class = null): void
    {
        $ref = new \ReflectionProperty($class ?? $entity::class, 'id');
        $ref->setValue($entity, $id);
    }
}
