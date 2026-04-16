<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Player;
use PHPUnit\Framework\TestCase;

class PlayerAvatarTest extends TestCase
{
    public function testAvatarAppearanceIsNullByDefault(): void
    {
        $player = new Player();

        $this->assertNull($player->getAvatarAppearance());
        $this->assertFalse($player->hasAvatar());
    }

    public function testSetAvatarAppearance(): void
    {
        $appearance = [
            'body' => 'human_m_light',
            'hair' => 'short_01',
            'hairColor' => '#d6b25e',
            'outfit' => 'starter_tunic',
        ];

        $player = new Player();
        $player->setAvatarAppearance($appearance);

        $this->assertSame($appearance, $player->getAvatarAppearance());
        $this->assertTrue($player->hasAvatar());
    }

    public function testSetAvatarAppearanceSetsUpdatedAt(): void
    {
        $player = new Player();
        $this->assertNull($player->getAvatarUpdatedAt());

        $player->setAvatarAppearance(['body' => 'human_m_light']);

        $this->assertInstanceOf(\DateTimeImmutable::class, $player->getAvatarUpdatedAt());
    }

    public function testSetAvatarAppearanceToNullClearsAvatar(): void
    {
        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);

        $this->assertTrue($player->hasAvatar());

        $player->setAvatarAppearance(null);

        $this->assertFalse($player->hasAvatar());
    }

    public function testAvatarHashIsNullByDefault(): void
    {
        $player = new Player();

        $this->assertNull($player->getAvatarHash());
    }

    public function testSetAvatarHash(): void
    {
        $hash = hash('sha256', 'test-appearance');

        $player = new Player();
        $player->setAvatarHash($hash);

        $this->assertSame($hash, $player->getAvatarHash());
    }

    public function testAvatarVersionDefaultsToOne(): void
    {
        $player = new Player();

        $this->assertSame(1, $player->getAvatarVersion());
    }

    public function testSetAvatarVersion(): void
    {
        $player = new Player();
        $player->setAvatarVersion(2);

        $this->assertSame(2, $player->getAvatarVersion());
    }
}
