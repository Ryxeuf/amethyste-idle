<?php

namespace App\Tests\Unit\GameEngine\Season;

use App\Entity\App\GameEvent;
use App\Entity\App\InfluenceSeason;
use App\Enum\ConsequenceTide;
use App\GameEngine\Season\ConsequenceTideComposer;
use App\GameEngine\Season\ConsequenceTideDefinitionLoader;
use App\Repository\GameEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * L'arc d'une maree consequence se pose au moment ou le monde la declenche
 * (FOY-15).
 *
 * Les marees ecrites de l'an 1 ont leurs beats en fixtures : on sait d'avance
 * qu'elles arriveront. Une consequence, non — d'ou ce compositeur, qui lit la
 * meme donnee declarative et la pose sur la saison deja programmee.
 */
class ConsequenceTideComposerTest extends TestCase
{
    /**
     * Les fenetres sont **derivees des bornes reelles de la saison**. Les partir
     * d'une date fixe aurait desynchronise l'arc de la maree des que le
     * calendrier aurait glisse d'un jour.
     */
    public function testTheArcIsLaidOnTheSeasonsOwnDates(): void
    {
        $season = $this->season('2026-09-07 00:00:00');
        $persisted = [];

        $count = $this->composer($persisted, [])->compose($season, ConsequenceTide::Paleness);

        self::assertSame(4, $count);
        self::assertCount(4, $persisted);
        self::assertSame('La Pâleur', $season->getTheme());

        self::assertSame('2026-09-07', $persisted[0]->getStartsAt()->format('Y-m-d'));
        self::assertSame('2026-09-14', $persisted[0]->getEndsAt()->format('Y-m-d'));
        self::assertSame('2026-10-05', $persisted[3]->getEndsAt()->format('Y-m-d'));
    }

    public function testTheFourBeatsAreOrderedAndContiguous(): void
    {
        $persisted = [];
        $this->composer($persisted, [])->compose($this->season('2026-09-07 00:00:00'), ConsequenceTide::CrueCall);

        self::assertSame(
            [GameEvent::BEAT_AMORCE, GameEvent::BEAT_MONTEE, GameEvent::BEAT_CLIMAX, GameEvent::BEAT_RESOLUTION],
            array_map(static fn (GameEvent $event) => $event->getBeat(), $persisted),
        );
        self::assertSame([1, 2, 3, 4], array_map(static fn (GameEvent $event) => $event->getBeatOrder(), $persisted));

        for ($i = 1; $i < 4; ++$i) {
            self::assertEquals($persisted[$i - 1]->getEndsAt(), $persisted[$i]->getStartsAt());
        }
    }

    /**
     * Idempotence : un tick rejoue ne doit pas empiler un second arc. Sans cette
     * garde, `getActiveBeat()` aurait rendu le premier de deux beats
     * concurrents — un defaut qui ne se serait vu nulle part.
     */
    public function testASeasonThatAlreadyHasAnArcIsLeftAlone(): void
    {
        $season = $this->season('2026-09-07 00:00:00');
        $persisted = [];

        $count = $this->composer($persisted, [new GameEvent()])->compose($season, ConsequenceTide::Paleness);

        self::assertSame(0, $count);
        self::assertSame([], $persisted);
        self::assertNull($season->getTheme());
    }

    private function season(string $startsAt): InfluenceSeason
    {
        return (new InfluenceSeason())
            ->setName('Saison 4')
            ->setSlug('saison-4')
            ->setSeasonNumber(4)
            ->setStartsAt(new \DateTime($startsAt))
            ->setEndsAt(new \DateTime($startsAt . ' +28 days'));
    }

    /**
     * @param list<GameEvent> $persisted recueille ce que le compositeur ecrit
     * @param list<GameEvent> $existing  beats deja poses sur la saison
     */
    private function composer(array &$persisted, array $existing): ConsequenceTideComposer
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            if ($entity instanceof GameEvent) {
                $persisted[] = $entity;
            }
        });

        $repository = $this->createMock(GameEventRepository::class);
        $repository->method('findBySeasonOrdered')->willReturn($existing);

        return new ConsequenceTideComposer(
            $entityManager,
            $repository,
            new ConsequenceTideDefinitionLoader(\dirname(__DIR__, 4)),
        );
    }
}
