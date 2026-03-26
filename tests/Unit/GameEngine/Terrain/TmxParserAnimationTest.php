<?php

namespace App\Tests\Unit\GameEngine\Terrain;

use App\GameEngine\Terrain\TmxParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class TmxParserAnimationTest extends TestCase
{
    private TmxParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TmxParser();
    }

    public function testParseTilesetsExtractsAnimations(): void
    {
        $tsxContent = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <tileset version="1.10" name="terrain" tilewidth="32" tileheight="32" tilecount="1024" columns="32">
         <image source="terrain.png" width="1024" height="1024"/>
         <tile id="0">
          <animation>
           <frame tileid="175" duration="500"/>
           <frame tileid="176" duration="500"/>
           <frame tileid="177" duration="500"/>
          </animation>
         </tile>
         <tile id="187">
          <animation>
           <frame tileid="187" duration="500"/>
           <frame tileid="188" duration="500"/>
           <frame tileid="187" duration="500"/>
          </animation>
         </tile>
        </tileset>
        XML;

        $tsxPath = sys_get_temp_dir() . '/test_terrain.tsx';
        $imagePath = sys_get_temp_dir() . '/terrain.png';
        file_put_contents($tsxPath, $tsxContent);
        file_put_contents($imagePath, '');

        try {
            $tmxContent = sprintf(
                '<map><tileset firstgid="1" source="%s"/></map>',
                basename($tsxPath)
            );
            $crawler = new Crawler($tmxContent);
            $terrains = $this->parser->parseTilesets($crawler, sys_get_temp_dir());

            $this->assertArrayHasKey('1', $terrains);
            $terrain = $terrains['1'];

            $this->assertArrayHasKey('animations', $terrain);
            $animations = $terrain['animations'];

            $this->assertCount(2, $animations);

            // Tile 0 animation
            $this->assertArrayHasKey(0, $animations);
            $this->assertCount(3, $animations[0]);
            $this->assertSame(175, $animations[0][0]['tileid']);
            $this->assertSame(500, $animations[0][0]['duration']);
            $this->assertSame(176, $animations[0][1]['tileid']);
            $this->assertSame(177, $animations[0][2]['tileid']);

            // Tile 187 animation
            $this->assertArrayHasKey(187, $animations);
            $this->assertCount(3, $animations[187]);
            $this->assertSame(187, $animations[187][0]['tileid']);
            $this->assertSame(188, $animations[187][1]['tileid']);
        } finally {
            @unlink($tsxPath);
            @unlink($imagePath);
        }
    }

    public function testParseTilesetsHandlesNoAnimations(): void
    {
        $tsxContent = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <tileset version="1.10" name="base" tilewidth="32" tileheight="32" tilecount="100" columns="8">
         <image source="base.png" width="256" height="400"/>
        </tileset>
        XML;

        $tsxPath = sys_get_temp_dir() . '/test_base.tsx';
        $imagePath = sys_get_temp_dir() . '/base.png';
        file_put_contents($tsxPath, $tsxContent);
        file_put_contents($imagePath, '');

        try {
            $tmxContent = sprintf(
                '<map><tileset firstgid="1" source="%s"/></map>',
                basename($tsxPath)
            );
            $crawler = new Crawler($tmxContent);
            $terrains = $this->parser->parseTilesets($crawler, sys_get_temp_dir());

            $this->assertArrayHasKey('1', $terrains);
            $this->assertArrayHasKey('animations', $terrains['1']);
            $this->assertEmpty($terrains['1']['animations']);
        } finally {
            @unlink($tsxPath);
            @unlink($imagePath);
        }
    }
}
