<?php

namespace App\Tests\Unit\Enum;

use App\Enum\PlayerRenownTier;
use PHPUnit\Framework\TestCase;

class PlayerRenownTierTest extends TestCase
{
    public function testAllTiersExist(): void
    {
        $this->assertCount(6, PlayerRenownTier::cases());
    }

    public function testThresholdsAreStrictlyIncreasing(): void
    {
        $previous = -1;
        foreach (PlayerRenownTier::cases() as $tier) {
            $this->assertGreaterThan($previous, $tier->threshold(), sprintf('Tier %s must have threshold > previous', $tier->value));
            $previous = $tier->threshold();
        }
    }

    public function testFromScoreAtBoundaries(): void
    {
        $this->assertSame(PlayerRenownTier::Novice, PlayerRenownTier::fromScore(0));
        $this->assertSame(PlayerRenownTier::Novice, PlayerRenownTier::fromScore(249));
        $this->assertSame(PlayerRenownTier::Connu, PlayerRenownTier::fromScore(250));
        $this->assertSame(PlayerRenownTier::Connu, PlayerRenownTier::fromScore(999));
        $this->assertSame(PlayerRenownTier::Respecte, PlayerRenownTier::fromScore(1000));
        $this->assertSame(PlayerRenownTier::Honore, PlayerRenownTier::fromScore(3000));
        $this->assertSame(PlayerRenownTier::Illustre, PlayerRenownTier::fromScore(8000));
        $this->assertSame(PlayerRenownTier::Legendaire, PlayerRenownTier::fromScore(20000));
        $this->assertSame(PlayerRenownTier::Legendaire, PlayerRenownTier::fromScore(99999));
    }

    public function testFromScoreNegativeReturnsNovice(): void
    {
        $this->assertSame(PlayerRenownTier::Novice, PlayerRenownTier::fromScore(-500));
    }

    public function testNextTierChain(): void
    {
        $this->assertSame(PlayerRenownTier::Connu, PlayerRenownTier::Novice->nextTier());
        $this->assertSame(PlayerRenownTier::Respecte, PlayerRenownTier::Connu->nextTier());
        $this->assertSame(PlayerRenownTier::Honore, PlayerRenownTier::Respecte->nextTier());
        $this->assertSame(PlayerRenownTier::Illustre, PlayerRenownTier::Honore->nextTier());
        $this->assertSame(PlayerRenownTier::Legendaire, PlayerRenownTier::Illustre->nextTier());
        $this->assertNull(PlayerRenownTier::Legendaire->nextTier());
    }

    public function testPointsToNextTier(): void
    {
        $this->assertSame(250, PlayerRenownTier::pointsToNextTier(0));
        $this->assertSame(1, PlayerRenownTier::pointsToNextTier(249));
        $this->assertSame(750, PlayerRenownTier::pointsToNextTier(250));
        $this->assertNull(PlayerRenownTier::pointsToNextTier(20000));
        $this->assertNull(PlayerRenownTier::pointsToNextTier(999999));
    }

    public function testLabelsAreFrench(): void
    {
        $this->assertSame('Novice', PlayerRenownTier::Novice->label());
        $this->assertSame('Respecté', PlayerRenownTier::Respecte->label());
        $this->assertSame('Légendaire', PlayerRenownTier::Legendaire->label());
    }

    public function testCssClasses(): void
    {
        foreach (PlayerRenownTier::cases() as $tier) {
            $this->assertStringStartsWith('text-', $tier->cssClass());
        }
    }
}
