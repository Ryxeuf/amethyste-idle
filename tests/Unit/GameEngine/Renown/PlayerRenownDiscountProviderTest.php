<?php

namespace App\Tests\Unit\GameEngine\Renown;

use App\Entity\App\Player;
use App\Enum\PlayerRenownTier;
use App\GameEngine\Renown\PlayerRenownDiscountProvider;
use PHPUnit\Framework\TestCase;

class PlayerRenownDiscountProviderTest extends TestCase
{
    private PlayerRenownDiscountProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new PlayerRenownDiscountProvider();
    }

    public function testNoviceGetsNoDiscount(): void
    {
        $player = new Player();
        $player->setRenownScore(0);

        $this->assertSame(0.0, $this->provider->getShopDiscount($player));
    }

    public function testConnuGets2Percent(): void
    {
        $player = new Player();
        $player->setRenownScore(250);

        $this->assertSame(0.02, $this->provider->getShopDiscount($player));
    }

    public function testLegendaireGets10Percent(): void
    {
        $player = new Player();
        $player->setRenownScore(20000);

        $this->assertSame(0.10, $this->provider->getShopDiscount($player));
    }

    public function testShopDiscountMatchesTierEnum(): void
    {
        foreach (PlayerRenownTier::cases() as $tier) {
            $player = new Player();
            $player->setRenownScore($tier->threshold());
            $this->assertSame(
                $tier->shopDiscount(),
                $this->provider->getShopDiscount($player),
                sprintf('Mismatch for tier %s', $tier->value),
            );
        }
    }

    public function testCombineDiscountAddsRates(): void
    {
        $player = new Player();
        $player->setRenownScore(3000); // Honore -> 0.06

        $combined = $this->provider->combineDiscount(0.10, $player);

        $this->assertEqualsWithDelta(0.16, $combined, 1e-9);
    }

    public function testCombineDiscountIsCappedAtMax(): void
    {
        $player = new Player();
        $player->setRenownScore(20000); // Legendaire -> 0.10

        // Base discount = 0.45, renown = 0.10 -> raw combined 0.55 > cap 0.50
        $combined = $this->provider->combineDiscount(0.45, $player);

        $this->assertSame(PlayerRenownDiscountProvider::MAX_COMBINED_DISCOUNT, $combined);
    }

    public function testCombineDiscountClampsNegativeToZero(): void
    {
        $player = new Player();
        $player->setRenownScore(0);

        $combined = $this->provider->combineDiscount(-0.10, $player);

        $this->assertSame(0.0, $combined);
    }

    public function testCombineDiscountWithNoBaseReturnsRenownOnly(): void
    {
        $player = new Player();
        $player->setRenownScore(1000); // Respecte -> 0.04

        $combined = $this->provider->combineDiscount(0.0, $player);

        $this->assertSame(0.04, $combined);
    }
}
