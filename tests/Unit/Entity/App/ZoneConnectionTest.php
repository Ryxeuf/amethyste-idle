<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use PHPUnit\Framework\TestCase;

class ZoneConnectionTest extends TestCase
{
    private function makeZone(string $slug): Zone
    {
        return (new Zone())->setSlug($slug)->setName(ucfirst($slug));
    }

    public function testCreateConnectionWithDefaultValues(): void
    {
        $village = $this->makeZone('village');
        $forest = $this->makeZone('foret');

        $connection = new ZoneConnection($village, $forest);

        $this->assertSame($village, $connection->getFromZone());
        $this->assertSame($forest, $connection->getToZone());
        $this->assertSame(60, $connection->getTravelSeconds());
        $this->assertFalse($connection->requiresDiscovery());
        $this->assertTrue($connection->isEnabled());
    }

    public function testCreateConnectionWithExplicitTravelDuration(): void
    {
        $connection = new ZoneConnection($this->makeZone('village'), $this->makeZone('crete'), 900);

        $this->assertSame(900, $connection->getTravelSeconds());
    }

    public function testInstantTravelIsAllowed(): void
    {
        $connection = new ZoneConnection($this->makeZone('village'), $this->makeZone('taverne'), 0);

        $this->assertSame(0, $connection->getTravelSeconds());
    }

    public function testSelfLoopIsRejected(): void
    {
        $zone = $this->makeZone('village');

        $this->expectException(\InvalidArgumentException::class);
        new ZoneConnection($zone, $zone);
    }

    public function testNegativeTravelDurationIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ZoneConnection($this->makeZone('village'), $this->makeZone('foret'), -1);
    }

    public function testSetTravelSecondsRejectsNegative(): void
    {
        $connection = new ZoneConnection($this->makeZone('village'), $this->makeZone('foret'));

        $this->expectException(\InvalidArgumentException::class);
        $connection->setTravelSeconds(-10);
    }
}
