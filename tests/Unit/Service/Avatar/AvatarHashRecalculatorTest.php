<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Avatar;

use App\Entity\App\Player;
use App\Service\Avatar\AvatarHashRecalculator;
use App\Service\Avatar\PlayerAvatarPayloadBuilder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AvatarHashRecalculatorTest extends TestCase
{
    private PlayerAvatarPayloadBuilder&MockObject $payloadBuilder;
    private EntityManagerInterface&MockObject $entityManager;
    private AvatarHashRecalculator $recalculator;

    protected function setUp(): void
    {
        $this->payloadBuilder = $this->createMock(PlayerAvatarPayloadBuilder::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->recalculator = new AvatarHashRecalculator($this->payloadBuilder, $this->entityManager);
    }

    public function testRecalculateReturnsFalseWhenPlayerHasNoAvatar(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('hasAvatar')->willReturn(false);

        $this->payloadBuilder->expects($this->never())->method('build');
        $this->entityManager->expects($this->never())->method('flush');

        $this->assertFalse($this->recalculator->recalculate($player));
    }

    public function testRecalculateReturnsFalseWhenPayloadIsNull(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('hasAvatar')->willReturn(true);

        $this->payloadBuilder->method('build')->with($player)->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        $this->assertFalse($this->recalculator->recalculate($player));
    }

    public function testRecalculateReturnsFalseWhenHashUnchanged(): void
    {
        $existingHash = str_repeat('a', 64);

        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);
        $player->setAvatarHash($existingHash);

        $this->payloadBuilder->method('build')->with($player)->willReturn([
            'renderMode' => 'avatar',
            'avatarHash' => $existingHash,
            'avatar' => ['baseSheet' => '/body.png', 'layers' => []],
        ]);

        $this->entityManager->expects($this->never())->method('flush');

        $this->assertFalse($this->recalculator->recalculate($player));
        $this->assertSame($existingHash, $player->getAvatarHash());
    }

    public function testRecalculateUpdatesHashAndPersists(): void
    {
        $oldHash = str_repeat('a', 64);
        $newHash = str_repeat('b', 64);

        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);
        $player->setAvatarHash($oldHash);

        $this->payloadBuilder->method('build')->with($player)->willReturn([
            'renderMode' => 'avatar',
            'avatarHash' => $newHash,
            'avatar' => ['baseSheet' => '/body.png', 'layers' => []],
        ]);

        $this->entityManager->expects($this->once())->method('persist')->with($player);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertTrue($this->recalculator->recalculate($player));
        $this->assertSame($newHash, $player->getAvatarHash());
    }

    public function testRecalculateTouchesAvatarUpdatedAtWhenHashChanges(): void
    {
        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);
        $player->setAvatarHash(str_repeat('a', 64));

        $before = $player->getAvatarUpdatedAt();
        $this->assertNotNull($before);

        usleep(1000);

        $this->payloadBuilder->method('build')->with($player)->willReturn([
            'renderMode' => 'avatar',
            'avatarHash' => str_repeat('b', 64),
            'avatar' => ['baseSheet' => '/body.png', 'layers' => []],
        ]);

        $this->recalculator->recalculate($player);

        $after = $player->getAvatarUpdatedAt();
        $this->assertNotNull($after);
        $this->assertGreaterThan($before, $after);
    }
}
