<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Avatar;

use App\Entity\App\Player;
use App\GameEngine\Realtime\Avatar\AvatarUpdatedPublisher;
use App\Helper\GearHelper;
use App\Service\Avatar\AvatarHashGenerator;
use App\Service\Avatar\AvatarHashRecalculator;
use App\Service\Avatar\ItemAvatarSheetResolver;
use App\Service\Avatar\PlayerAvatarPayloadBuilder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AvatarHashRecalculatorTest extends TestCase
{
    private GearHelper&MockObject $gearHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private AvatarUpdatedPublisher&MockObject $publisher;
    private AvatarHashRecalculator $recalculator;
    private PlayerAvatarPayloadBuilder $payloadBuilder;

    protected function setUp(): void
    {
        $this->gearHelper = $this->createMock(GearHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->publisher = $this->createMock(AvatarUpdatedPublisher::class);
        $this->payloadBuilder = new PlayerAvatarPayloadBuilder(
            new AvatarHashGenerator(),
            $this->gearHelper,
            new ItemAvatarSheetResolver(),
        );
        $this->recalculator = new AvatarHashRecalculator(
            $this->payloadBuilder,
            $this->entityManager,
            $this->publisher,
        );
    }

    public function testRecalculateReturnsFalseWhenPlayerHasNoAvatar(): void
    {
        $player = new Player();

        $this->entityManager->expects($this->never())->method('flush');
        $this->publisher->expects($this->never())->method('publish');

        $this->assertFalse($this->recalculator->recalculate($player));
    }

    public function testRecalculateStoresHashOnFirstComputation(): void
    {
        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);
        $this->gearHelper->method('getEquippedGearByLocation')->willReturn(null);

        $this->entityManager->expects($this->once())->method('persist')->with($player);
        $this->entityManager->expects($this->once())->method('flush');
        $this->publisher->expects($this->once())->method('publish')->with($player);

        $this->assertTrue($this->recalculator->recalculate($player));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $player->getAvatarHash());
    }

    public function testRecalculateReturnsFalseWhenHashUnchanged(): void
    {
        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);
        $this->gearHelper->method('getEquippedGearByLocation')->willReturn(null);

        $expected = $this->payloadBuilder->build($player);
        $this->assertNotNull($expected);
        $player->setAvatarHash($expected['avatarHash']);

        $this->entityManager->expects($this->never())->method('flush');
        $this->publisher->expects($this->never())->method('publish');

        $this->assertFalse($this->recalculator->recalculate($player));
        $this->assertSame($expected['avatarHash'], $player->getAvatarHash());
    }

    public function testRecalculateUpdatesHashWhenAppearanceChanges(): void
    {
        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);
        $this->gearHelper->method('getEquippedGearByLocation')->willReturn(null);

        $oldHash = str_repeat('0', 64);
        $player->setAvatarHash($oldHash);

        $this->entityManager->expects($this->once())->method('persist')->with($player);
        $this->entityManager->expects($this->once())->method('flush');
        $this->publisher->expects($this->once())->method('publish')->with($player);

        $this->assertTrue($this->recalculator->recalculate($player));
        $this->assertNotSame($oldHash, $player->getAvatarHash());
    }

    public function testRecalculateTouchesAvatarUpdatedAtWhenHashChanges(): void
    {
        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);
        $player->setAvatarHash(str_repeat('0', 64));

        $this->gearHelper->method('getEquippedGearByLocation')->willReturn(null);

        $before = $player->getAvatarUpdatedAt();
        $this->assertNotNull($before);

        $this->recalculator->recalculate($player);

        $after = $player->getAvatarUpdatedAt();
        $this->assertNotNull($after);
        $this->assertNotSame($before, $after);
    }
}
