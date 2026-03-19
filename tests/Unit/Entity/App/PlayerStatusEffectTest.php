<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\PlayerStatusEffect;
use PHPUnit\Framework\TestCase;

class PlayerStatusEffectTest extends TestCase
{
    public function testIsExpiredReturnsTrueWhenPastExpiry(): void
    {
        $effect = new PlayerStatusEffect();
        $expiresAt = new \DateTime('-1 hour');
        $effect->setExpiresAt($expiresAt);

        $this->assertTrue($effect->isExpired());
    }

    public function testIsExpiredReturnsFalseWhenBeforeExpiry(): void
    {
        $effect = new PlayerStatusEffect();
        $expiresAt = new \DateTime('+1 hour');
        $effect->setExpiresAt($expiresAt);

        $this->assertFalse($effect->isExpired());
    }

    public function testGetRemainingSecondsReturnsZeroWhenExpired(): void
    {
        $effect = new PlayerStatusEffect();
        $expiresAt = new \DateTime('-1 hour');
        $effect->setExpiresAt($expiresAt);

        $this->assertSame(0, $effect->getRemainingSeconds());
    }

    public function testGetRemainingSecondsReturnsPositiveValueWhenActive(): void
    {
        $effect = new PlayerStatusEffect();
        $expiresAt = new \DateTime('+300 seconds');
        $effect->setExpiresAt($expiresAt);

        $remaining = $effect->getRemainingSeconds();
        $this->assertGreaterThan(295, $remaining);
        $this->assertLessThanOrEqual(300, $remaining);
    }

    public function testAppliedAtIsSetOnConstruction(): void
    {
        $before = new \DateTime();
        $effect = new PlayerStatusEffect();
        $after = new \DateTime();

        $this->assertGreaterThanOrEqual($before, $effect->getAppliedAt());
        $this->assertLessThanOrEqual($after, $effect->getAppliedAt());
    }
}
