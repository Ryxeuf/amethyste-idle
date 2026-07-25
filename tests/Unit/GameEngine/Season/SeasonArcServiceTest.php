<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Season;

use App\Entity\App\GameEvent;
use App\Entity\App\InfluenceSeason;
use App\GameEngine\Season\SeasonArcService;
use App\Repository\GameEventRepository;
use PHPUnit\Framework\TestCase;

final class SeasonArcServiceTest extends TestCase
{
    private function beat(string $name, string $start, string $end): GameEvent
    {
        $event = new GameEvent();
        $event->setName($name);
        $event->setStatus(GameEvent::STATUS_SCHEDULED);
        $event->setStartsAt(new \DateTime($start));
        $event->setEndsAt(new \DateTime($end));

        return $event;
    }

    public function testGetActiveBeatReturnsBeatCoveringNow(): void
    {
        $season = new InfluenceSeason();

        $amorce = $this->beat('amorce', '2026-01-01', '2026-01-08');
        $montee = $this->beat('montee', '2026-01-08', '2026-01-15');
        $climax = $this->beat('climax', '2026-01-15', '2026-01-22');

        $repo = $this->createMock(GameEventRepository::class);
        $repo->method('findBySeasonOrdered')->with($season)->willReturn([$amorce, $montee, $climax]);

        $service = new SeasonArcService($repo);

        $active = $service->getActiveBeat($season, new \DateTime('2026-01-18 00:00:00'));
        self::assertSame($climax, $active);
    }

    public function testGetActiveBeatReturnsNullOutsideAllWindows(): void
    {
        $season = new InfluenceSeason();

        $amorce = $this->beat('amorce', '2026-01-01', '2026-01-08');
        $montee = $this->beat('montee', '2026-01-08', '2026-01-15');

        $repo = $this->createMock(GameEventRepository::class);
        $repo->method('findBySeasonOrdered')->willReturn([$amorce, $montee]);

        $service = new SeasonArcService($repo);

        self::assertNull($service->getActiveBeat($season, new \DateTime('2026-02-01 00:00:00')));
    }

    public function testGetBeatsDelegatesToRepository(): void
    {
        $season = new InfluenceSeason();
        $beats = [$this->beat('a', '2026-01-01', '2026-01-08')];

        $repo = $this->createMock(GameEventRepository::class);
        $repo->expects($this->once())->method('findBySeasonOrdered')->with($season)->willReturn($beats);

        $service = new SeasonArcService($repo);

        self::assertSame($beats, $service->getBeats($season));
    }
}
