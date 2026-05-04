<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Player;
use App\Entity\Game\Mount;
use PHPUnit\Framework\TestCase;

class PlayerActiveMountTest extends TestCase
{
    public function testPlayerActiveMountIsNullByDefault(): void
    {
        $player = new Player();

        $this->assertNull($player->getActiveMount());
    }

    public function testPlayerCanSetActiveMount(): void
    {
        $mount = new Mount();
        $mount->setSlug('horse_brown');
        $mount->setName('Cheval brun');

        $player = new Player();
        $player->setActiveMount($mount);

        $this->assertSame($mount, $player->getActiveMount());
        $this->assertSame('horse_brown', $player->getActiveMount()->getSlug());
    }

    public function testPlayerCanClearActiveMount(): void
    {
        $mount = new Mount();
        $mount->setSlug('wolf_dire');
        $mount->setName('Loup sauvage');

        $player = new Player();
        $player->setActiveMount($mount);
        $this->assertSame($mount, $player->getActiveMount());

        $player->setActiveMount(null);
        $this->assertNull($player->getActiveMount());
    }

    public function testSetActiveMountIsFluent(): void
    {
        $mount = new Mount();
        $mount->setSlug('chocobo_yellow');
        $mount->setName('Chocobo');

        $player = new Player();

        $this->assertSame($player, $player->setActiveMount($mount));
        $this->assertSame($player, $player->setActiveMount(null));
    }

    public function testGetEffectiveSpeedReturnsBaseSpeedWhenUnmounted(): void
    {
        $player = new Player();
        $player->setSpeed(10);

        $this->assertSame(10, $player->getEffectiveSpeed());
    }

    public function testGetEffectiveSpeedAddsMountSpeedBonus(): void
    {
        $mount = new Mount();
        $mount->setSlug('horse_brown');
        $mount->setName('Cheval');
        $mount->setSpeedBonus(50);

        $player = new Player();
        $player->setSpeed(10);
        $player->setActiveMount($mount);

        // 10 + (10 * 50 / 100) = 10 + 5 = 15
        $this->assertSame(15, $player->getEffectiveSpeed());
    }

    public function testGetEffectiveSpeedHandlesZeroBonus(): void
    {
        $mount = new Mount();
        $mount->setSlug('lazy_donkey');
        $mount->setName('Ane paresseux');
        $mount->setSpeedBonus(0);

        $player = new Player();
        $player->setSpeed(8);
        $player->setActiveMount($mount);

        $this->assertSame(8, $player->getEffectiveSpeed());
    }

    public function testGetEffectiveSpeedTruncatesFractionalBonus(): void
    {
        $mount = new Mount();
        $mount->setSlug('chocobo_yellow');
        $mount->setName('Chocobo');
        $mount->setSpeedBonus(33);

        $player = new Player();
        $player->setSpeed(10);
        $player->setActiveMount($mount);

        // 10 + (10 * 33 / 100) = 10 + 3.3 -> int cast = 10 + 3 = 13
        $this->assertSame(13, $player->getEffectiveSpeed());
    }
}
