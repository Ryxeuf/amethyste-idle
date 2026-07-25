<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Season;

use App\Entity\App\InfluenceSeason;
use App\Entity\Game\CodexEntry;
use App\GameEngine\Codex\WorldFactService;
use App\GameEngine\Season\SeasonResolutionService;
use PHPUnit\Framework\TestCase;

final class SeasonResolutionServiceTest extends TestCase
{
    private function season(bool $canon = true): InfluenceSeason
    {
        $season = new InfluenceSeason();
        $season->setSlug('saison-1');
        $season->setName('Saison 1');
        $season->setCanon($canon);

        return $season;
    }

    /**
     * @param array<int, array{slug: string, guild: string|null}> $calls captures des appels a recordWorldFact
     */
    private function serviceCapturing(array &$calls): SeasonResolutionService
    {
        $worldFactService = $this->createMock(WorldFactService::class);
        $worldFactService->method('recordWorldFact')->willReturnCallback(
            function (...$args) use (&$calls): CodexEntry {
                $calls[] = ['slug' => $args[0], 'guild' => $args[3] ?? null];

                return new CodexEntry();
            }
        );

        return new SeasonResolutionService($worldFactService);
    }

    public function testCreditsControllingGuildToWorldJournal(): void
    {
        $calls = [];
        $service = $this->serviceCapturing($calls);

        $recorded = $service->resolve($this->season(), ['foret-des-murmures' => 'Les Gardiens']);

        self::assertSame(1, $recorded);
        self::assertCount(1, $calls);
        self::assertSame('season_saison-1_foret-des-murmures_resolution', $calls[0]['slug']);
        self::assertSame('Les Gardiens', $calls[0]['guild']);
    }

    public function testCreditsEachControllingGuildAndSkipsFreeRegions(): void
    {
        $calls = [];
        $service = $this->serviceCapturing($calls);

        $recorded = $service->resolve($this->season(), [
            'foret-des-murmures' => 'Les Gardiens',
            'mines-profondes' => null,
            'marais-brumeux' => 'Ordre du Crépuscule',
        ]);

        self::assertSame(2, $recorded);
        $guilds = array_map(static fn (array $c): ?string => $c['guild'], $calls);
        self::assertSame(['Les Gardiens', 'Ordre du Crépuscule'], $guilds);
    }

    public function testRecordsNeutralFactWhenNoControllingGuild(): void
    {
        $calls = [];
        $service = $this->serviceCapturing($calls);

        $recorded = $service->resolve($this->season(), [
            'foret-des-murmures' => null,
            'mines-profondes' => null,
        ]);

        self::assertSame(1, $recorded);
        self::assertCount(1, $calls);
        // Fait neutre, sans guilde creditee.
        self::assertSame('season_saison-1_resolution', $calls[0]['slug']);
        self::assertNull($calls[0]['guild']);
    }

    public function testRecordsNeutralFactWhenNoRegions(): void
    {
        $calls = [];
        $service = $this->serviceCapturing($calls);

        $recorded = $service->resolve($this->season(), []);

        self::assertSame(1, $recorded);
        self::assertNull($calls[0]['guild']);
    }

    public function testBlankGuildNameIsTreatedAsFree(): void
    {
        $calls = [];
        $service = $this->serviceCapturing($calls);

        $recorded = $service->resolve($this->season(), ['foret-des-murmures' => '   ']);

        // Nom vide => region libre => fait neutre.
        self::assertSame(1, $recorded);
        self::assertNull($calls[0]['guild']);
        self::assertSame('season_saison-1_resolution', $calls[0]['slug']);
    }

    public function testNonCanonSeasonLeavesNoWorldFact(): void
    {
        $calls = [];
        $service = $this->serviceCapturing($calls);

        // Saison NON marquee canon : aucune trace durable, meme avec un vainqueur.
        $recorded = $service->resolve($this->season(canon: false), ['foret-des-murmures' => 'Les Gardiens']);

        self::assertSame(0, $recorded);
        self::assertCount(0, $calls);
    }
}
