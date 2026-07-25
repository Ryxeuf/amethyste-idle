<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\App\GameEvent;
use App\Entity\App\InfluenceSeason;
use PHPUnit\Framework\TestCase;

/**
 * Beats d'arc de saison sur GameEvent (NAR-08) : fenetres temporelles et
 * rattachement a une saison.
 */
final class GameEventBeatTest extends TestCase
{
    private function scheduledEvent(string $start, string $end): GameEvent
    {
        $event = new GameEvent();
        $event->setStatus(GameEvent::STATUS_SCHEDULED);
        $event->setStartsAt(new \DateTime($start));
        $event->setEndsAt(new \DateTime($end));

        return $event;
    }

    public function testIsActiveAtInsideWindow(): void
    {
        $event = $this->scheduledEvent('2026-01-10 00:00:00', '2026-01-20 00:00:00');

        self::assertTrue($event->isActiveAt(new \DateTime('2026-01-15 12:00:00')));
        self::assertFalse($event->isActiveAt(new \DateTime('2026-01-05 00:00:00')));
        self::assertFalse($event->isActiveAt(new \DateTime('2026-01-25 00:00:00')));
    }

    public function testStatusActiveIsAlwaysActive(): void
    {
        $event = $this->scheduledEvent('2999-01-01 00:00:00', '2999-02-01 00:00:00');
        $event->setStatus(GameEvent::STATUS_ACTIVE);

        // Fenetre dans un futur lointain, mais statut force actif.
        self::assertTrue($event->isActiveAt(new \DateTime('2026-01-01 00:00:00')));
    }

    public function testCancelledOrCompletedNeverActiveByWindow(): void
    {
        $event = $this->scheduledEvent('2026-01-10 00:00:00', '2026-01-20 00:00:00');
        $event->setStatus(GameEvent::STATUS_COMPLETED);

        self::assertFalse($event->isActiveAt(new \DateTime('2026-01-15 00:00:00')));
    }

    public function testSeasonBeatFields(): void
    {
        $event = new GameEvent();
        self::assertFalse($event->isSeasonBeat());
        self::assertNull($event->getBeat());
        self::assertNull($event->getBeatOrder());

        $season = (new InfluenceSeason())->setSlug('saison-1');
        $event->setSeason($season);
        $event->setBeat(GameEvent::BEAT_CLIMAX);
        $event->setBeatOrder(3);

        self::assertTrue($event->isSeasonBeat());
        self::assertSame($season, $event->getSeason());
        self::assertSame(GameEvent::BEAT_CLIMAX, $event->getBeat());
        self::assertSame(3, $event->getBeatOrder());
    }
}
