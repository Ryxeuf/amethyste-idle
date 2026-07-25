<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\InfluenceSeason;
use PHPUnit\Framework\TestCase;

/**
 * Couvre le marqueur « canon » (NAR-12) et la convention d'arc de saison.
 */
final class InfluenceSeasonCanonTest extends TestCase
{
    public function testNotCanonByDefault(): void
    {
        self::assertFalse((new InfluenceSeason())->isCanon());
    }

    public function testSetCanonRoundTrip(): void
    {
        $season = new InfluenceSeason();

        $season->setCanon(true);
        self::assertTrue($season->isCanon());

        $season->setCanon(false);
        self::assertFalse($season->isCanon());
    }

    public function testStoryArcConvention(): void
    {
        $season = (new InfluenceSeason())->setSlug('saison-1');

        self::assertSame('season_saison-1', $season->getStoryArc());
    }
}
