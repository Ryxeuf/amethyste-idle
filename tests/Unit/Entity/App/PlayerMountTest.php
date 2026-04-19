<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Entity\Game\Mount;
use PHPUnit\Framework\TestCase;

class PlayerMountTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $player = new Player();
        $mount = $this->createMount();

        $before = new \DateTimeImmutable();
        $playerMount = new PlayerMount($player, $mount, PlayerMount::SOURCE_QUEST);
        $after = new \DateTimeImmutable();

        $this->assertSame($player, $playerMount->getPlayer());
        $this->assertSame($mount, $playerMount->getMount());
        $this->assertSame(PlayerMount::SOURCE_QUEST, $playerMount->getSource());
        $this->assertGreaterThanOrEqual($before, $playerMount->getObtainedAt());
        $this->assertLessThanOrEqual($after, $playerMount->getObtainedAt());
    }

    public function testConstructorAcceptsExplicitObtainedAt(): void
    {
        $date = new \DateTimeImmutable('2026-04-01 12:00:00');

        $playerMount = new PlayerMount(new Player(), $this->createMount(), PlayerMount::SOURCE_DROP, $date);

        $this->assertSame($date, $playerMount->getObtainedAt());
    }

    public function testConstructorRejectsInvalidSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PlayerMount(new Player(), $this->createMount(), 'gift');
    }

    public function testGetSourcesReturnsExpectedList(): void
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

    public function testSetSourceAcceptsAllValidSources(): void
    {
        $playerMount = new PlayerMount(new Player(), $this->createMount(), PlayerMount::SOURCE_QUEST);

        foreach (PlayerMount::getSources() as $source) {
            $playerMount->setSource($source);
            $this->assertSame($source, $playerMount->getSource());
        }
    }

    private function createMount(): Mount
    {
        $mount = new Mount();
        $mount->setSlug('horse_brown');
        $mount->setName('Cheval brun');
        $mount->setDescription('Monture commune');

        return $mount;
    }
}
