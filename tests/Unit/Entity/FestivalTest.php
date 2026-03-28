<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\App\Festival;
use PHPUnit\Framework\TestCase;

class FestivalTest extends TestCase
{
    private function makeFestival(string $season, int $startDay, int $endDay): Festival
    {
        $f = new Festival();
        $f->setName('Test Festival');
        $f->setSlug('test');
        $f->setSeason($season);
        $f->setStartDay($startDay);
        $f->setEndDay($endDay);
        $f->setActive(true);

        return $f;
    }

    public function testIsCurrentlyRunningMatchesSeasonAndDay(): void
    {
        $f = $this->makeFestival('spring', 5, 12);

        $this->assertTrue($f->isCurrentlyRunning('spring', 5));
        $this->assertTrue($f->isCurrentlyRunning('spring', 8));
        $this->assertTrue($f->isCurrentlyRunning('spring', 12));
        $this->assertFalse($f->isCurrentlyRunning('spring', 4));
        $this->assertFalse($f->isCurrentlyRunning('spring', 13));
    }

    public function testIsCurrentlyRunningWrongSeason(): void
    {
        $f = $this->makeFestival('winter', 20, 27);

        $this->assertFalse($f->isCurrentlyRunning('summer', 22));
    }

    public function testIsCurrentlyRunningInactive(): void
    {
        $f = $this->makeFestival('spring', 5, 12);
        $f->setActive(false);

        $this->assertFalse($f->isCurrentlyRunning('spring', 8));
    }

    public function testIsCurrentlyRunningWrapAround(): void
    {
        $f = $this->makeFestival('winter', 25, 5);

        $this->assertTrue($f->isCurrentlyRunning('winter', 25));
        $this->assertTrue($f->isCurrentlyRunning('winter', 28));
        $this->assertTrue($f->isCurrentlyRunning('winter', 1));
        $this->assertTrue($f->isCurrentlyRunning('winter', 5));
        $this->assertFalse($f->isCurrentlyRunning('winter', 6));
        $this->assertFalse($f->isCurrentlyRunning('winter', 24));
    }
}
