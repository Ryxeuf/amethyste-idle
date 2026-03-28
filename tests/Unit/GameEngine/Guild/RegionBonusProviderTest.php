<?php

namespace App\Tests\Unit\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\App\Region;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\RegionBonusProvider;
use App\GameEngine\Guild\TownControlManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegionBonusProviderTest extends TestCase
{
    private GuildManager&MockObject $guildManager;
    private TownControlManager&MockObject $townControlManager;
    private RegionBonusProvider $provider;

    protected function setUp(): void
    {
        $this->guildManager = $this->createMock(GuildManager::class);
        $this->townControlManager = $this->createMock(TownControlManager::class);
        $this->provider = new RegionBonusProvider($this->guildManager, $this->townControlManager);
    }

    public function testGetShopDiscountForControllingGuildMember(): void
    {
        $guild = $this->createGuild(1, 'Alpha');
        $player = $this->createPlayer();
        $region = $this->createRegion('plaines', '0.0500');
        $map = $this->createMap($region);

        $this->townControlManager->method('getControllingGuild')
            ->with($region)
            ->willReturn($guild);

        $this->guildManager->method('getPlayerGuild')
            ->with($player)
            ->willReturn($guild);

        $discount = $this->provider->getShopDiscount($player, $map);

        $this->assertSame(0.10, $discount);
    }

    public function testGetShopDiscountForNonMember(): void
    {
        $controllingGuild = $this->createGuild(1, 'Alpha');
        $playerGuild = $this->createGuild(2, 'Beta');
        $player = $this->createPlayer();
        $region = $this->createRegion('plaines', '0.0500');
        $map = $this->createMap($region);

        $this->townControlManager->method('getControllingGuild')
            ->with($region)
            ->willReturn($controllingGuild);

        $this->guildManager->method('getPlayerGuild')
            ->with($player)
            ->willReturn($playerGuild);

        $discount = $this->provider->getShopDiscount($player, $map);

        $this->assertSame(0.0, $discount);
    }

    public function testGetShopDiscountNoControllingGuild(): void
    {
        $player = $this->createPlayer();
        $region = $this->createRegion('plaines', '0.0500');
        $map = $this->createMap($region);

        $this->townControlManager->method('getControllingGuild')
            ->willReturn(null);

        $discount = $this->provider->getShopDiscount($player, $map);

        $this->assertSame(0.0, $discount);
    }

    public function testGetShopDiscountNoRegion(): void
    {
        $player = $this->createPlayer();
        $map = $this->createMap(null);

        $discount = $this->provider->getShopDiscount($player, $map);

        $this->assertSame(0.0, $discount);
    }

    public function testGetTaxAmountWithControllingGuild(): void
    {
        $guild = $this->createGuild(1, 'Alpha');
        $region = $this->createRegion('plaines', '0.0500');
        $map = $this->createMap($region);

        $this->townControlManager->method('getControllingGuild')
            ->with($region)
            ->willReturn($guild);

        $tax = $this->provider->getTaxAmount(1000, $map);

        $this->assertSame(50, $tax); // 5% of 1000
    }

    public function testGetTaxAmountNoControllingGuild(): void
    {
        $region = $this->createRegion('plaines', '0.0500');
        $map = $this->createMap($region);

        $this->townControlManager->method('getControllingGuild')
            ->willReturn(null);

        $tax = $this->provider->getTaxAmount(1000, $map);

        $this->assertSame(0, $tax);
    }

    public function testGetTaxAmountNoRegion(): void
    {
        $map = $this->createMap(null);

        $tax = $this->provider->getTaxAmount(1000, $map);

        $this->assertSame(0, $tax);
    }

    private function createGuild(int $id, string $name): Guild
    {
        $guild = new Guild();
        $guild->setName($name);
        $guild->setTag(strtoupper(substr($name, 0, 3)));

        $ref = new \ReflectionProperty(Guild::class, 'id');
        $ref->setValue($guild, $id);

        return $guild;
    }

    private function createPlayer(): Player
    {
        $player = new Player();
        $player->setName('TestPlayer');

        return $player;
    }

    private function createRegion(string $slug, string $taxRate): Region
    {
        $region = new Region();
        $region->setName(ucfirst($slug));
        $region->setSlug($slug);
        $region->setTaxRate($taxRate);

        return $region;
    }

    private function createMap(?Region $region): Map
    {
        $map = new Map();
        if ($region !== null) {
            $map->setRegion($region);
        }

        return $map;
    }
}
