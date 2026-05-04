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
}
