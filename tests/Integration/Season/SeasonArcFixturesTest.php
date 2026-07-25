<?php

namespace App\Tests\Integration\Season;

use App\Entity\App\GameEvent;
use App\Entity\App\InfluenceSeason;
use App\Repository\GameEventRepository;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Verifie la structure de l'arc de la Saison 1 (NAR-08) : 4 beats ordonnes,
 * fenetres contiguës et contenues dans les bornes de la saison.
 */
final class SeasonArcFixturesTest extends AbstractIntegrationTestCase
{
    private function season(): InfluenceSeason
    {
        $season = $this->em->getRepository(InfluenceSeason::class)->findOneBy(['slug' => 'saison-1']);
        self::assertNotNull($season, 'La Saison 1 doit exister dans les fixtures.');

        return $season;
    }

    public function testSeasonOneHasFourOrderedBeats(): void
    {
        $season = $this->season();

        /** @var GameEventRepository $repository */
        $repository = $this->em->getRepository(GameEvent::class);
        $beats = $repository->findBySeasonOrdered($season);

        self::assertCount(4, $beats);
        self::assertSame([1, 2, 3, 4], array_map(static fn (GameEvent $b): ?int => $b->getBeatOrder(), $beats));
        self::assertSame(
            [GameEvent::BEAT_AMORCE, GameEvent::BEAT_MONTEE, GameEvent::BEAT_CLIMAX, GameEvent::BEAT_RESOLUTION],
            array_map(static fn (GameEvent $b): ?string => $b->getBeat(), $beats),
        );
    }

    public function testBeatWindowsAreContiguousAndWithinSeasonBounds(): void
    {
        $season = $this->season();
        $seasonStart = $season->getStartsAt();
        $seasonEnd = $season->getEndsAt();

        /** @var GameEventRepository $repository */
        $repository = $this->em->getRepository(GameEvent::class);
        $beats = $repository->findBySeasonOrdered($season);

        $previousEnd = null;
        foreach ($beats as $beat) {
            // Chaque fenetre est bien ordonnee (debut < fin).
            self::assertLessThan($beat->getEndsAt(), $beat->getStartsAt());
            // Contenue dans les bornes de la saison.
            self::assertGreaterThanOrEqual($seasonStart, $beat->getStartsAt());
            self::assertLessThanOrEqual($seasonEnd, $beat->getEndsAt());
            // Contiguë et non chevauchante avec le beat precedent.
            if ($previousEnd !== null) {
                self::assertGreaterThanOrEqual($previousEnd, $beat->getStartsAt());
            }
            $previousEnd = $beat->getEndsAt();
        }
    }

    public function testSeasonStoryArcConvention(): void
    {
        self::assertSame('season_saison-1', $this->season()->getStoryArc());
    }
}
