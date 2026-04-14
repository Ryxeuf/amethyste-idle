<?php

namespace App\Tests\Unit\Enum;

use App\Enum\KarmaTitle;
use PHPUnit\Framework\TestCase;

class KarmaTitleTest extends TestCase
{
    public function testAllTitlesExist(): void
    {
        $this->assertCount(7, KarmaTitle::cases());
    }

    public function testLabelsAreFrench(): void
    {
        $this->assertSame('Infâme', KarmaTitle::Infame->label());
        $this->assertSame('Novice', KarmaTitle::Novice->label());
        $this->assertSame('Connu', KarmaTitle::Connu->label());
        $this->assertSame('Respecté', KarmaTitle::Respecte->label());
        $this->assertSame('Honoré', KarmaTitle::Honore->label());
        $this->assertSame('Héros', KarmaTitle::Heros->label());
        $this->assertSame('Légendaire', KarmaTitle::Legendaire->label());
    }

    public function testFromScoreNegativeGivesInfame(): void
    {
        $this->assertSame(KarmaTitle::Infame, KarmaTitle::fromScore(-1));
        $this->assertSame(KarmaTitle::Infame, KarmaTitle::fromScore(-9999));
    }

    public function testFromScoreZeroGivesNovice(): void
    {
        $this->assertSame(KarmaTitle::Novice, KarmaTitle::fromScore(0));
    }

    public function testFromScoreBoundaries(): void
    {
        $this->assertSame(KarmaTitle::Novice, KarmaTitle::fromScore(199));
        $this->assertSame(KarmaTitle::Connu, KarmaTitle::fromScore(200));
        $this->assertSame(KarmaTitle::Connu, KarmaTitle::fromScore(999));
        $this->assertSame(KarmaTitle::Respecte, KarmaTitle::fromScore(1000));
        $this->assertSame(KarmaTitle::Respecte, KarmaTitle::fromScore(2999));
        $this->assertSame(KarmaTitle::Honore, KarmaTitle::fromScore(3000));
        $this->assertSame(KarmaTitle::Heros, KarmaTitle::fromScore(8000));
        $this->assertSame(KarmaTitle::Legendaire, KarmaTitle::fromScore(20000));
        $this->assertSame(KarmaTitle::Legendaire, KarmaTitle::fromScore(99999));
    }

    public function testNextTitleChain(): void
    {
        $this->assertSame(KarmaTitle::Novice, KarmaTitle::Infame->nextTitle());
        $this->assertSame(KarmaTitle::Connu, KarmaTitle::Novice->nextTitle());
        $this->assertSame(KarmaTitle::Respecte, KarmaTitle::Connu->nextTitle());
        $this->assertSame(KarmaTitle::Honore, KarmaTitle::Respecte->nextTitle());
        $this->assertSame(KarmaTitle::Heros, KarmaTitle::Honore->nextTitle());
        $this->assertSame(KarmaTitle::Legendaire, KarmaTitle::Heros->nextTitle());
        $this->assertNull(KarmaTitle::Legendaire->nextTitle());
    }

    public function testThresholdsAreAscending(): void
    {
        $ordered = [
            KarmaTitle::Novice,
            KarmaTitle::Connu,
            KarmaTitle::Respecte,
            KarmaTitle::Honore,
            KarmaTitle::Heros,
            KarmaTitle::Legendaire,
        ];

        $previousThreshold = -1;
        foreach ($ordered as $title) {
            $this->assertGreaterThan($previousThreshold, $title->threshold());
            $previousThreshold = $title->threshold();
        }
    }

    public function testCssClassesAreNonEmpty(): void
    {
        foreach (KarmaTitle::cases() as $title) {
            $this->assertNotEmpty($title->cssClass());
        }
    }
}
