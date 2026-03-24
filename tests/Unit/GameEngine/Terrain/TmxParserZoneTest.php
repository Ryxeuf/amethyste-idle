<?php

namespace App\Tests\Unit\GameEngine\Terrain;

use App\GameEngine\Terrain\TmxParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class TmxParserZoneTest extends TestCase
{
    private TmxParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TmxParser();
    }

    public function testParseObjectLayersExtractsZoneObjects(): void
    {
        $xml = <<<'XML'
        <map>
            <objectgroup name="zones">
                <object name="Foret Sombre" type="zone" x="0" y="0" width="640" height="480">
                    <properties>
                        <property name="biome" value="forest"/>
                        <property name="weather" value="rain"/>
                        <property name="music" value="forest_theme.ogg"/>
                        <property name="light_level" value="0.8"/>
                    </properties>
                </object>
                <object name="Desert Aride" type="biome" x="640" y="0" width="320" height="320">
                    <properties>
                        <property name="biome" value="desert"/>
                        <property name="weather" value="clear"/>
                        <property name="light_level" value="1.2"/>
                    </properties>
                </object>
            </objectgroup>
        </map>
        XML;

        $crawler = new Crawler($xml);
        $objects = $this->parser->parseObjectLayers($crawler, 32, 32, 40, 30, 0, 0);

        $this->assertCount(2, $objects);

        // First zone
        $this->assertSame('zone', $objects[0]['type']);
        $this->assertSame('Foret Sombre', $objects[0]['name']);
        $this->assertSame(0, $objects[0]['x']);
        $this->assertSame(0, $objects[0]['y']);
        $this->assertSame(20, $objects[0]['width']);  // 640/32
        $this->assertSame(15, $objects[0]['height']); // 480/32
        $this->assertSame('forest', $objects[0]['properties']['biome']);
        $this->assertSame('rain', $objects[0]['properties']['weather']);
        $this->assertSame('forest_theme.ogg', $objects[0]['properties']['music']);
        $this->assertSame('0.8', $objects[0]['properties']['light_level']);

        // Second zone (biome type)
        $this->assertSame('biome', $objects[1]['type']);
        $this->assertSame('Desert Aride', $objects[1]['name']);
        $this->assertSame(20, $objects[1]['x']);  // 640/32
        $this->assertSame(0, $objects[1]['y']);
        $this->assertSame(10, $objects[1]['width']);  // 320/32
        $this->assertSame(10, $objects[1]['height']); // 320/32
        $this->assertSame('desert', $objects[1]['properties']['biome']);
    }

    public function testParseObjectLayersHandlesOffsets(): void
    {
        $xml = <<<'XML'
        <map>
            <objectgroup name="zones">
                <object name="Zone decalee" type="zone" x="64" y="96" width="320" height="160">
                    <properties>
                        <property name="biome" value="plains"/>
                    </properties>
                </object>
            </objectgroup>
        </map>
        XML;

        $crawler = new Crawler($xml);
        // With offsets: offsetX=1, offsetY=2, mapWidth=20, mapHeight=15
        $objects = $this->parser->parseObjectLayers($crawler, 32, 32, 20, 15, 1, 2);

        $this->assertCount(1, $objects);
        // x = floor(64/32) + 1*20 = 2 + 20 = 22
        $this->assertSame(22, $objects[0]['x']);
        // y = floor(96/32) + 2*15 = 3 + 30 = 33
        $this->assertSame(33, $objects[0]['y']);
        $this->assertSame(10, $objects[0]['width']);  // 320/32
        $this->assertSame(5, $objects[0]['height']); // 160/32
    }
}
