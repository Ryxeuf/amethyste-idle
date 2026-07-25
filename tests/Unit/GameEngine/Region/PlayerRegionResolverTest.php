<?php

namespace App\Tests\Unit\GameEngine\Region;

use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\App\Region;
use App\Entity\App\Zone;
use App\GameEngine\Region\PlayerRegionResolver;
use PHPUnit\Framework\TestCase;

/**
 * ECO-03 — la region d'un joueur se derive de sa **zone** (regle projet #7).
 */
class PlayerRegionResolverTest extends TestCase
{
    private PlayerRegionResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new PlayerRegionResolver();
    }

    public function testZoneRegionWinsOverTheMapRegion(): void
    {
        // `Player::map` n'est plus mise a jour par le voyage depuis le pivot :
        // elle reste figee sur la carte de depart. Si elle primait, la region
        // resolue serait celle d'ou le joueur vient, pas celle ou il est.
        $zoneRegion = $this->region('nord');
        $player = $this->player($this->region('sud'), $zoneRegion);

        self::assertSame($zoneRegion, $this->resolver->resolve($player));
    }

    public function testMapRegionIsUsedWhenThePlayerHasNoZone(): void
    {
        $mapRegion = $this->region('sud');

        self::assertSame($mapRegion, $this->resolver->resolve($this->player($mapRegion, null)));
    }

    public function testPlayerWithoutPositionHasNoRegion(): void
    {
        self::assertNull($this->resolver->resolve($this->player(null, null)));
        self::assertNull($this->resolver->resolve(null));
    }

    public function testZoneWithoutSourceMapFallsBackToTheMapRegion(): void
    {
        $mapRegion = $this->region('sud');
        $player = $this->player($mapRegion, null);
        $player->setCurrentZone(new Zone());

        self::assertSame($mapRegion, $this->resolver->resolve($player));
    }

    public function testSameMarketComparesSlugsNotIdentity(): void
    {
        // Deux instances non persistees de la meme region portent toutes deux un
        // identifiant nul : les comparer par id les confondrait avec n'importe
        // quelle autre region non persistee.
        self::assertTrue($this->resolver->isSameMarket($this->region('nord'), $this->region('nord')));
        self::assertFalse($this->resolver->isSameMarket($this->region('nord'), $this->region('sud')));
    }

    public function testAbsenceOfRegionOnBothSidesIsASharedMarket(): void
    {
        self::assertTrue($this->resolver->isSameMarket(null, null));
        self::assertFalse($this->resolver->isSameMarket($this->region('nord'), null));
        self::assertFalse($this->resolver->isSameMarket(null, $this->region('nord')));
    }

    private function region(string $slug): Region
    {
        $region = new Region();
        $region->setName($slug)->setSlug($slug);

        return $region;
    }

    private function player(?Region $mapRegion, ?Region $zoneRegion): Player
    {
        $player = new Player();

        if (null !== $mapRegion) {
            $player->setMap($this->mapWithRegion($mapRegion));
        }

        if (null !== $zoneRegion) {
            $player->setCurrentZone((new Zone())->setSourceMap($this->mapWithRegion($zoneRegion)));
        }

        return $player;
    }

    private function mapWithRegion(Region $region): Map
    {
        $map = $this->createMock(Map::class);
        $map->method('getRegion')->willReturn($region);

        return $map;
    }
}
