<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Quest;
use App\Enum\PlayerRenownTier;
use PHPUnit\Framework\TestCase;

class QuestRenownRequirementTest extends TestCase
{
    public function testNoRenownRequirementByDefault(): void
    {
        $quest = new Quest();

        $this->assertNull($quest->getMinRenownScore());
        $this->assertFalse($quest->hasRenownRequirement());
        $this->assertNull($quest->getRequiredRenownTier());
    }

    public function testQuestIsUnlockedWhenNoRequirement(): void
    {
        $quest = new Quest();

        $this->assertTrue($quest->isUnlockedForRenownScore(0));
        $this->assertTrue($quest->isUnlockedForRenownScore(9999));
    }

    public function testSetMinRenownScoreNormalisesZeroAndNegativeToNull(): void
    {
        $quest = new Quest();

        $quest->setMinRenownScore(0);
        $this->assertNull($quest->getMinRenownScore());
        $this->assertFalse($quest->hasRenownRequirement());

        $quest->setMinRenownScore(-100);
        $this->assertNull($quest->getMinRenownScore());
        $this->assertFalse($quest->hasRenownRequirement());

        $quest->setMinRenownScore(null);
        $this->assertNull($quest->getMinRenownScore());
    }

    public function testQuestWithRequirementIsLockedBelowThreshold(): void
    {
        $quest = new Quest();
        $quest->setMinRenownScore(1000);

        $this->assertTrue($quest->hasRenownRequirement());
        $this->assertFalse($quest->isUnlockedForRenownScore(0));
        $this->assertFalse($quest->isUnlockedForRenownScore(999));
        $this->assertTrue($quest->isUnlockedForRenownScore(1000));
        $this->assertTrue($quest->isUnlockedForRenownScore(5000));
    }

    public function testRequiredTierMatchesThreshold(): void
    {
        $quest = new Quest();

        $quest->setMinRenownScore(PlayerRenownTier::Respecte->threshold());
        $this->assertSame(PlayerRenownTier::Respecte, $quest->getRequiredRenownTier());

        $quest->setMinRenownScore(PlayerRenownTier::Legendaire->threshold());
        $this->assertSame(PlayerRenownTier::Legendaire, $quest->getRequiredRenownTier());
    }

    public function testRequiredTierForIntermediateScoreReturnsLowerTier(): void
    {
        $quest = new Quest();
        // 500 points = palier Connu (threshold 250), pas encore Respecte (1000)
        $quest->setMinRenownScore(500);

        $this->assertSame(PlayerRenownTier::Connu, $quest->getRequiredRenownTier());
    }
}
