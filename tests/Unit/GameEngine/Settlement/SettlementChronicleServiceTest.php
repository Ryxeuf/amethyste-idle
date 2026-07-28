<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Entity\Game\CodexEntry;
use App\Enum\SettlementRank;
use App\GameEngine\Codex\WorldFactService;
use App\GameEngine\Settlement\SettlementChronicleService;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\Repository\SettlementContributionRepository;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Le serveur garde la trace de qui a bati quoi (FOY-14).
 *
 * Quatre proprietes portent le jalon :
 *
 * 1. **En bien comme en mal** : une ville qui s'endort s'inscrit au meme titre
 *    qu'une ville qui grandit. Un journal qui ne raconterait que les reussites
 *    serait une vitrine, pas une chronique.
 * 2. **La premiere cloture n'ecrit rien** : le seed du monde livre n'est
 *    l'œuvre de personne.
 * 3. **Le credit va au batisseur**, pas au vainqueur de l'election de region.
 * 4. **Une maree non-canon ne laisse pas de trace** (NAR-12) — mais elle
 *    avance quand meme le repere, sinon la maree suivante crediterait des
 *    mouvements que le monde a decide d'oublier.
 */
class SettlementChronicleServiceTest extends TestCase
{
    /** @var list<array{slug: string, title: string, description: string, guild: string|null}> */
    private array $facts = [];

    /** @var list<Settlement> */
    private array $settlements = [];

    /** @var array<string, string|null> */
    private array $leadingGuilds = [];

    protected function setUp(): void
    {
        $this->facts = [];
        $this->settlements = [];
        $this->leadingGuilds = [];
    }

    // =====================================================================
    // Le fait s'ecrit, dans les deux sens
    // =====================================================================

    public function testAPromotionIsEngravedAndCreditedToTheLeadingGuild(): void
    {
        $this->settlement('mines-profondes', 'Mines profondes', SettlementRank::Hamlet, SettlementRank::Town);
        $this->leadingGuilds['mines-profondes'] = 'Les Fondeurs';

        self::assertSame(1, $this->service()->recordTide($this->season()));
        self::assertCount(1, $this->facts);
        self::assertSame('season_reflux_mines-profondes_foyer', $this->facts[0]['slug']);
        self::assertSame('Les Fondeurs', $this->facts[0]['guild']);
        self::assertStringContainsString('hameau', $this->facts[0]['description']);
        self::assertStringContainsString('bourg', $this->facts[0]['description']);
    }

    /**
     * La propriete 1 : la chute se grave aussi. Et elle se raconte **sans
     * accuser** — le message du pilier est « ce lieu s'endort », jamais « vous
     * avez perdu » (FOY-10).
     */
    public function testAFallIsEngravedToo(): void
    {
        $this->settlement('marais-brumeux', 'Marais brumeux', SettlementRank::Town, SettlementRank::Hamlet);
        $this->leadingGuilds['marais-brumeux'] = 'Les Lecteurs';

        self::assertSame(1, $this->service()->recordTide($this->season()));
        self::assertSame('Les Lecteurs', $this->facts[0]['guild']);
        self::assertStringNotContainsString('perdu', $this->facts[0]['description']);
    }

    public function testAnUnchangedSettlementLeavesNothing(): void
    {
        $this->settlement('foret', 'Forêt', SettlementRank::Town, SettlementRank::Town);

        self::assertSame(0, $this->service()->recordTide($this->season()));
        self::assertSame([], $this->facts);
    }

    // =====================================================================
    // La premiere cloture pose le repere sans rien ecrire
    // =====================================================================

    public function testTheFirstTideOnlySetsTheMarkerAndCreditsNobody(): void
    {
        $settlement = $this->settlement('dunes-d-ambre', 'Dunes d\'Ambre', null, SettlementRank::Town);

        self::assertSame(0, $this->service()->recordTide($this->season()));
        self::assertSame([], $this->facts, 'Le seed du monde livre n\'est l\'œuvre de personne.');
        self::assertSame(SettlementRank::Town, $settlement->getTideStartRank());
    }

    public function testTheMarkerRollsForwardSoTheNextTideComparesOneTideOnly(): void
    {
        $settlement = $this->settlement('crete', 'Crête', SettlementRank::Hamlet, SettlementRank::Town);

        $this->service()->recordTide($this->season());

        self::assertSame(SettlementRank::Town, $settlement->getTideStartRank());
        // Deuxieme cloture, rang inchange depuis : plus rien a graver.
        $this->facts = [];
        self::assertSame(0, $this->service()->recordTide($this->season()));
    }

    // =====================================================================
    // Le plancher de l'identite
    // =====================================================================

    /**
     * En dessous du Hameau, un foyer n'a pas encore de nom : « Ruine devenue
     * Campement » est du bruit dans un journal que les joueurs lisent.
     */
    public function testAChangeBelowTheIdentityFloorIsNotNotable(): void
    {
        $this->settlement('mer-de-sel', 'Mer de Sel', SettlementRank::Ruin, SettlementRank::Camp);

        self::assertSame(0, $this->service()->recordTide($this->season()));
    }

    /**
     * Une **chute** se juge sur le rang perdu, pas sur le rang atteint. Sans
     * cette regle, un Bourg retombe au Campement sortirait du journal
     * exactement au moment ou sa disparition compte le plus.
     */
    public function testAFallOutOfTheIdentityRangeIsStillNotable(): void
    {
        $this->settlement('cite-ensevelie', 'Cité ensevelie', SettlementRank::Town, SettlementRank::Camp);

        self::assertSame(1, $this->service()->recordTide($this->season()));
    }

    // =====================================================================
    // Le gate canon (NAR-12)
    // =====================================================================

    public function testANonCanonTideLeavesNoDurableTrace(): void
    {
        $settlement = $this->settlement('vallons', 'Vallons', SettlementRank::Hamlet, SettlementRank::Town);

        self::assertSame(0, $this->service()->recordTide($this->season(canon: false)));
        self::assertSame([], $this->facts);
        // Le repere avance quand meme : la maree suivante ne doit pas crediter
        // un mouvement survenu pendant une maree que le monde a oubliee.
        self::assertSame(SettlementRank::Town, $settlement->getTideStartRank());
    }

    // =====================================================================
    // Le credit
    // =====================================================================

    /**
     * Un chantier porte par des joueurs sans guilde n'attribue aucun nom
     * collectif — mais le fait s'ecrit quand meme : la ville a grandi.
     */
    public function testAChantierWithoutAGuildIsStillEngravedWithoutCredit(): void
    {
        $this->settlement('foret', 'Forêt', SettlementRank::Hamlet, SettlementRank::Town);

        self::assertSame(1, $this->service()->recordTide($this->season()));
        self::assertNull($this->facts[0]['guild']);
    }

    /**
     * Idempotence par slug (convention NAR-07) : une maree, un foyer, un fait.
     */
    public function testTheSlugIsDeterministicPerArcAndZone(): void
    {
        $this->settlement('mines-profondes', 'Mines profondes', SettlementRank::Hamlet, SettlementRank::Town);
        $this->settlement('crete', 'Crête', SettlementRank::Town, SettlementRank::Hamlet);

        $this->service()->recordTide($this->season());

        self::assertSame(
            ['season_reflux_mines-profondes_foyer', 'season_reflux_crete_foyer'],
            array_column($this->facts, 'slug'),
        );
    }

    // =====================================================================
    // Fixtures
    // =====================================================================

    private function settlement(string $zoneSlug, string $zoneName, ?SettlementRank $tideStart, SettlementRank $current): Settlement
    {
        $zone = (new Zone())->setSlug($zoneSlug)->setName($zoneName);
        $settlement = new Settlement($zone);
        $settlement->setRank($current);
        $settlement->setTideStartRank($tideStart);

        $this->settlements[] = $settlement;

        return $settlement;
    }

    private function season(bool $canon = true): InfluenceSeason
    {
        $season = new InfluenceSeason();
        $season->setName('Marée du Reflux');
        $season->setSlug('reflux');
        $season->setCanon($canon);

        return $season;
    }

    private function service(): SettlementChronicleService
    {
        $repository = $this->createMock(SettlementRepository::class);
        $repository->method('findAllRanked')->willReturnCallback(fn (): array => $this->settlements);

        $contributions = $this->createMock(SettlementContributionRepository::class);
        $contributions->method('findLeadingGuildName')->willReturnCallback(
            fn (Settlement $settlement): ?string => $this->leadingGuilds[$settlement->getZone()->getSlug()] ?? null,
        );

        $loader = $this->createMock(SettlementDefinitionLoader::class);
        $loader->method('load')->willReturn([
            'ranks' => ['camp' => 150, 'hamlet' => 1200, 'town' => 8000, 'city' => 25000, 'metropolis' => 60000],
            'decay_rate' => 0.02,
            'dominance_margin' => 0.25,
            'sustain_days' => 28,
            'minimum_type_rank' => SettlementRank::Hamlet,
            'sediment' => [],
            'daily_cap_per_player' => 60,
            'diminishing_threshold' => 40,
            'diminishing_factor' => 0.5,
            'grace_days' => 28,
            'rebuild_multiplier' => 2,
            'seed' => [],
            'without_settlement' => [],
        ]);

        $worldFacts = $this->createMock(WorldFactService::class);
        $worldFacts->method('recordWorldFact')->willReturnCallback(
            function (string $slug, string $title, string $description, ?string $guild = null): CodexEntry {
                $this->facts[] = ['slug' => $slug, 'title' => $title, 'description' => $description, 'guild' => $guild];

                return new CodexEntry();
            },
        );

        return new SettlementChronicleService(
            $repository,
            $contributions,
            $loader,
            $worldFacts,
            $this->createMock(EntityManagerInterface::class),
        );
    }
}
