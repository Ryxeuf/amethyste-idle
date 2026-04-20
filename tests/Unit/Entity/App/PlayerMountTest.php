<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Entity\Game\Mount;
use PHPUnit\Framework\TestCase;

class PlayerMountTest extends TestCase
{
    public function testConstructorAssignsFieldsAndStampsAcquiredAt(): void
    {
        $player = $this->createMock(Player::class);
        $mount = new Mount();
        $mount->setSlug('horse_brown')->setName('Cheval brun')->setDescription('...');

        $before = new \DateTimeImmutable();
        $playerMount = new PlayerMount($player, $mount, PlayerMount::SOURCE_PURCHASE);
        $after = new \DateTimeImmutable();

        $this->assertSame($player, $playerMount->getPlayer());
        $this->assertSame($mount, $playerMount->getMount());
        $this->assertSame(PlayerMount::SOURCE_PURCHASE, $playerMount->getSource());
        $this->assertGreaterThanOrEqual($before, $playerMount->getAcquiredAt());
        $this->assertLessThanOrEqual($after, $playerMount->getAcquiredAt());
    }

    public function testConstructorRejectsUnknownSource(): void
    {
        $player = $this->createMock(Player::class);
        $mount = new Mount();
        $mount->setSlug('horse_brown')->setName('Cheval brun')->setDescription('...');

        $this->expectException(\InvalidArgumentException::class);
        new PlayerMount($player, $mount, 'gift');
    }

    public function testGetSourcesContainsAllValidSources(): void
    {
        $this->assertSame(
            [
                PlayerMount::SOURCE_QUEST,
                PlayerMount::SOURCE_DROP,
                PlayerMount::SOURCE_PURCHASE,
                PlayerMount::SOURCE_ACHIEVEMENT,
                PlayerMount::SOURCE_ADMIN,
            ],
            PlayerMount::getSources()
        );
    }

    public function testConstructorAcceptsAllValidSources(): void
    {
        $player = $this->createMock(Player::class);
        $mount = new Mount();
        $mount->setSlug('horse_brown')->setName('Cheval brun')->setDescription('...');

        foreach (PlayerMount::getSources() as $source) {
            $playerMount = new PlayerMount($player, $mount, $source);
            $this->assertSame($source, $playerMount->getSource());
        }
    }
}
