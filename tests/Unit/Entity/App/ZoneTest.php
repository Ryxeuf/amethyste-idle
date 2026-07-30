<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Map;
use App\Entity\App\Zone;
use PHPUnit\Framework\TestCase;

class ZoneTest extends TestCase
{
    public function testCreateZoneWithDefaultValues(): void
    {
        $zone = new Zone();
        $zone->setSlug('village-de-lumiere');
        $zone->setName('Le Fanal');

        $this->assertSame('village-de-lumiere', $zone->getSlug());
        $this->assertSame('Le Fanal', $zone->getName());
        $this->assertNull($zone->getDescription());
        $this->assertNull($zone->getIllustrationPath());
        $this->assertSame(Zone::TYPE_WILDERNESS, $zone->getType());
        $this->assertFalse($zone->isSafe());
        $this->assertTrue($zone->isEnabled());
        $this->assertNull($zone->getSourceMap());
        $this->assertCount(0, $zone->getConnections());
        $this->assertSame('Le Fanal', (string) $zone);
    }

    public function testSetTypeAcceptsAllValidTypes(): void
    {
        $zone = new Zone();

        foreach (Zone::getTypes() as $type) {
            $zone->setType($type);
            $this->assertSame($type, $zone->getType());
        }
    }

    public function testSetTypeRejectsUnknownType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Zone())->setType('ocean');
    }

    public function testLocalizedNameFallsBackToBaseName(): void
    {
        $zone = new Zone();
        $zone->setName('Forêt des murmures');
        $zone->setNameTranslations(['en' => 'Whispering Forest']);

        $this->assertSame('Whispering Forest', $zone->getLocalizedName('en'));
        $this->assertSame('Forêt des murmures', $zone->getLocalizedName('fr'));
        $this->assertSame('Forêt des murmures', $zone->getLocalizedName(null));
    }

    public function testLocalizedDescriptionFallsBackToBaseDescription(): void
    {
        $zone = new Zone();
        $zone->setDescription('Le hub central.');
        $zone->setDescriptionTranslations(['en' => 'The central hub.']);

        $this->assertSame('The central hub.', $zone->getLocalizedDescription('en'));
        $this->assertSame('Le hub central.', $zone->getLocalizedDescription('fr'));
        $this->assertSame('Le hub central.', $zone->getLocalizedDescription(null));
    }

    public function testTranslationsNormalizeBlankValues(): void
    {
        $zone = new Zone();
        $zone->setNameTranslations(['en' => '  ', 'de' => 'Dorf des Lichts']);

        $this->assertSame(['de' => 'Dorf des Lichts'], $zone->getNameTranslations());
    }

    public function testSourceMapCanBeAttachedAndDetached(): void
    {
        $map = new Map();
        $zone = (new Zone())->setSourceMap($map);
        $this->assertSame($map, $zone->getSourceMap());

        $zone->setSourceMap(null);
        $this->assertNull($zone->getSourceMap());
    }
}
