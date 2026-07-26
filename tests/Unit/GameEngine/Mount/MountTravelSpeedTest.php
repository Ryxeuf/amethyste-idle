<?php

namespace App\Tests\Unit\GameEngine\Mount;

use App\Entity\App\Player;
use App\Entity\Game\Mount;
use App\GameEngine\Mount\MountTravelSpeed;
use PHPUnit\Framework\TestCase;

class MountTravelSpeedTest extends TestCase
{
    private MountTravelSpeed $speed;

    protected function setUp(): void
    {
        $this->speed = new MountTravelSpeed();
    }

    private function mounted(int $bonus): Player
    {
        $player = new Player();
        $player->setActiveMount((new Mount())->setSpeedBonus($bonus));

        return $player;
    }

    public function testWithoutMountTheTravelTimeIsUntouched(): void
    {
        $this->assertSame(600, $this->speed->effectiveTravelSeconds(new Player(), 600));
        $this->assertSame(0, $this->speed->reductionPercent(new Player(), 600));
    }

    /**
     * Le bonus est une vitesse, pas une remise : +50 % de vitesse fait -33 % de
     * temps. Confondre les deux rendrait une monture a +100 % instantanee.
     */
    public function testSpeedBonusIsAppliedAsASpeedNotAsADiscount(): void
    {
        $this->assertSame(400, $this->speed->effectiveTravelSeconds($this->mounted(50), 600));
        $this->assertSame(33, $this->speed->reductionPercent($this->mounted(50), 600));
    }

    public function testReductionIsCapped(): void
    {
        // 600 * 100/500 = 120 s sans plafond ; le plancher a -50 % ramene a 300.
        $this->assertSame(300, $this->speed->effectiveTravelSeconds($this->mounted(400), 600));
        $this->assertSame(MountTravelSpeed::MAX_REDUCTION_PERCENT, $this->speed->reductionPercent($this->mounted(400), 600));
    }

    public function testInstantConnectionsStayInstant(): void
    {
        $this->assertSame(0, $this->speed->effectiveTravelSeconds($this->mounted(75), 0));
        $this->assertSame(0, $this->speed->reductionPercent($this->mounted(75), 0));
    }

    public function testShortestConnectionsNeverFallToZero(): void
    {
        // Un voyage reste un voyage : le time-gating du pivot ne doit pas
        // pouvoir etre annule par arrondi sur une liaison courte.
        $this->assertSame(2, $this->speed->effectiveTravelSeconds($this->mounted(75), 3));
        $this->assertSame(1, $this->speed->effectiveTravelSeconds($this->mounted(400), 1));
    }

    public function testNegativeBonusIsIgnored(): void
    {
        $this->assertSame(600, $this->speed->effectiveTravelSeconds($this->mounted(-30), 600));
    }
}
