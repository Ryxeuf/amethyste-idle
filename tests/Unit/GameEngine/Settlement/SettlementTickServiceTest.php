<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\Enum\SettlementType;
use App\Event\Zone\SettlementRankChangedEvent;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Settlement\SettlementTickService;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Le tick quotidien des foyers (FOY-03).
 *
 * Ce que ces tests protegent tient en une phrase : **le temps doit couter ce
 * qu'il coute, ni plus ni moins**. Rejouer le tick ne doit rien retirer de plus,
 * l'oublier trois jours ne doit rien pardonner, et la premiere execution sur un
 * monde livre ne doit pas rattraper une anciennete que personne n'a vecue.
 */
class SettlementTickServiceTest extends TestCase
{
    private const NOW = '2026-07-28 00:07:00';

    /** @var list<SettlementRankChangedEvent> */
    private array $dispatched = [];

    /** @var list<Settlement> */
    private array $settlements = [];

    protected function setUp(): void
    {
        $this->dispatched = [];
        $this->settlements = [];
    }

    public function testTheFirstTickOnlyPlantsTheAnchor(): void
    {
        $settlement = $this->settlement(['trade' => 2000], SettlementRank::Hamlet, null);

        $report = $this->service()->tick($this->now());

        // Rattraper toute l'anciennete ferait fondre le seed du monde livre a la
        // premiere execution — un retro-gate par la bande.
        self::assertSame(2000, $settlement->getSediment(SettlementIndex::Trade));
        self::assertSame(0, $report['decayed']);
        self::assertEquals($this->now(), $settlement->getDecayedAt());
    }

    public function testADayCostsTwoPercentOfEachIndex(): void
    {
        $settlement = $this->settlement(
            ['trade' => 2000, 'war' => 500],
            SettlementRank::Hamlet,
            $this->now()->modify('-1 day'),
        );

        $report = $this->service()->tick($this->now());

        self::assertSame(1960, $settlement->getSediment(SettlementIndex::Trade));
        self::assertSame(490, $settlement->getSediment(SettlementIndex::War));
        self::assertSame(50, $report['decayed']);
        self::assertSame(1, $report['processed']);
    }

    /**
     * Le tick est idempotent a la journee : le rejouer ne retire rien de plus.
     * Un calendrier qui declencherait deux fois — ou un administrateur qui
     * relance a la main — ne doit pas faire fondre un foyer deux fois.
     */
    public function testReplayingTheSameDayCostsNothingMore(): void
    {
        $settlement = $this->settlement(
            ['trade' => 2000],
            SettlementRank::Hamlet,
            $this->now()->modify('-1 day'),
        );

        $service = $this->service();
        $service->tick($this->now());
        $report = $service->tick($this->now());

        self::assertSame(1960, $settlement->getSediment(SettlementIndex::Trade));
        self::assertSame(0, $report['processed']);
        self::assertSame(1, $report['skipped']);
    }

    /**
     * L'ancre avance de jours **entiers**. Un tick joue a 23 h 59 puis a 00 h 01
     * ne doit pas manger le reste de la journee, sinon le taux reel derive au
     * gre de l'heure d'execution.
     */
    public function testTheAnchorAdvancesByWholeDaysNotToNow(): void
    {
        $anchor = $this->now()->modify('-3 days')->modify('-6 hours');
        $settlement = $this->settlement(['trade' => 10000], SettlementRank::Town, $anchor);

        $this->service()->tick($this->now());

        self::assertSame(9411, $settlement->getSediment(SettlementIndex::Trade));
        self::assertEquals($anchor->modify('+3 days'), $settlement->getDecayedAt());
    }

    public function testCrossingAThresholdDownwardIsAnnouncedLikeAnyOtherChange(): void
    {
        $settlement = $this->settlement(
            ['trade' => 1220],
            SettlementRank::Hamlet,
            $this->now()->modify('-1 day'),
        );

        $report = $this->service()->tick($this->now());

        self::assertSame(1195, $settlement->getSediment(SettlementIndex::Trade));
        self::assertSame(SettlementRank::Camp, $settlement->getRank());
        self::assertSame(1, $report['demoted']);

        self::assertCount(1, $this->dispatched);
        self::assertSame(SettlementRank::Hamlet, $this->dispatched[0]->getFrom());
        self::assertSame(SettlementRank::Camp, $this->dispatched[0]->getTo());
        self::assertFalse($this->dispatched[0]->isPromotion());
    }

    /**
     * Une descente ne fait pas oublier le sommet : ce qu'on a bati une fois se
     * rebatit moins cher (FOY-10).
     */
    public function testADemotionKeepsTheMemoryOfTheSummit(): void
    {
        $settlement = $this->settlement(
            ['trade' => 1220],
            SettlementRank::Hamlet,
            $this->now()->modify('-1 day'),
        );

        $this->service()->tick($this->now());

        self::assertSame(SettlementRank::Camp, $settlement->getRank());
        self::assertSame(SettlementRank::Hamlet, $settlement->getHighestRank());
    }

    public function testCrossingAThresholdUpwardIsAPromotion(): void
    {
        $settlement = $this->settlement(
            ['trade' => 1300],
            SettlementRank::Ruin,
            $this->now()->modify('-1 day'),
        );

        $report = $this->service()->tick($this->now());

        self::assertSame(SettlementRank::Hamlet, $settlement->getRank());
        self::assertSame(1, $report['promoted']);
        self::assertTrue($this->dispatched[0]->isPromotion());
    }

    /**
     * Le coeur de l'hysteresis : pretendre n'est pas s'installer. Au premier
     * tick, le pretendant est seulement enregistre — la maree doit passer.
     */
    public function testAChallengerDoesNotBecomeAnIdentityOnTheSpot(): void
    {
        $settlement = $this->settlement(
            ['trade' => 30000, 'war' => 100],
            SettlementRank::City,
            $this->now()->modify('-1 day'),
        );

        $report = $this->service()->tick($this->now());

        self::assertSame(SettlementIndex::Trade, $settlement->getDominantCandidate());
        self::assertNull($settlement->getType());
        self::assertSame(0, $report['typed']);
    }

    public function testATideOfDominanceInstallsTheIdentity(): void
    {
        $settlement = $this->settlement(
            ['trade' => 30000, 'war' => 100],
            SettlementRank::City,
            $this->now()->modify('-1 day'),
        );
        $settlement->setDominantCandidate(SettlementIndex::Trade);
        $settlement->setDominantSince($this->now()->modify('-28 days'));

        $report = $this->service()->tick($this->now());

        self::assertSame(SettlementType::Trading, $settlement->getType());
        self::assertSame(1, $report['typed']);
    }

    /**
     * Un jour de moins qu'une maree ne suffit pas. Sans cette exigence, le type
     * clignoterait au gre des semaines et la ville ne serait jamais rien de
     * precis.
     */
    public function testOneDayShortOfATideIsNotEnough(): void
    {
        $settlement = $this->settlement(
            ['trade' => 30000, 'war' => 100],
            SettlementRank::City,
            $this->now()->modify('-1 day'),
        );
        $settlement->setDominantCandidate(SettlementIndex::Trade);
        $settlement->setDominantSince($this->now()->modify('-27 days'));

        $this->service()->tick($this->now());

        self::assertNull($settlement->getType());
    }

    /**
     * Un nouveau pretendant remet le compteur a zero : l'avance accumulee par
     * l'ancien ne se transmet pas. Sinon le type s'installerait au nom du
     * mauvais indice, ce que la seule date `dominantSince` ne permettait pas de
     * distinguer.
     */
    public function testANewChallengerRestartsTheClock(): void
    {
        $settlement = $this->settlement(
            ['trade' => 100, 'war' => 30000],
            SettlementRank::City,
            $this->now()->modify('-1 day'),
        );
        $settlement->setDominantCandidate(SettlementIndex::Trade);
        $settlement->setDominantSince($this->now()->modify('-100 days'));

        $this->service()->tick($this->now());

        self::assertSame(SettlementIndex::War, $settlement->getDominantCandidate());
        self::assertEquals($this->now(), $settlement->getDominantSince());
        self::assertNull($settlement->getType());
    }

    /**
     * Le type installe ne se perd pas en perdant l'avance : il se perd en la
     * cedant a un autre, dans les memes conditions.
     */
    public function testAnInstalledIdentitySurvivesLosingTheLead(): void
    {
        $settlement = $this->settlement(
            ['trade' => 10000, 'war' => 10000],
            SettlementRank::City,
            $this->now()->modify('-1 day'),
        );
        $settlement->setType(SettlementType::Trading);

        $this->service()->tick($this->now());

        self::assertNull($settlement->getDominantCandidate());
        self::assertSame(SettlementType::Trading, $settlement->getType());
    }

    /**
     * Sous le Hameau, pas d'identite. Un Campement qui se souviendrait d'avoir
     * ete un Comptoir afficherait une identite que plus rien ne soutient.
     */
    public function testFallingBelowTheHamletStripsTheIdentity(): void
    {
        $settlement = $this->settlement(
            ['trade' => 1220],
            SettlementRank::Hamlet,
            $this->now()->modify('-1 day'),
        );
        $settlement->setType(SettlementType::Trading);
        $settlement->setDominantCandidate(SettlementIndex::Trade);
        $settlement->setDominantSince($this->now()->modify('-100 days'));

        $report = $this->service()->tick($this->now());

        self::assertSame(SettlementRank::Camp, $settlement->getRank());
        self::assertNull($settlement->getType());
        self::assertNull($settlement->getDominantCandidate());
        self::assertNull($settlement->getDominantSince());
        self::assertSame(1, $report['typed']);
    }

    /**
     * `--force` sert au diagnostic : recalculer rang et type sans attendre le
     * jour suivant. Il ne doit surtout pas faire payer une journee de plus.
     */
    public function testForceRecomputesWithoutChargingADay(): void
    {
        $settlement = $this->settlement(
            ['trade' => 1300],
            SettlementRank::Ruin,
            $this->now(),
        );

        $report = $this->service()->tick($this->now(), true);

        self::assertSame(1300, $settlement->getSediment(SettlementIndex::Trade));
        self::assertSame(0, $report['decayed']);
        self::assertSame(SettlementRank::Hamlet, $settlement->getRank());
        self::assertSame(1, $report['promoted']);
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW);
    }

    /**
     * @param array<string, int> $sediment
     */
    private function settlement(array $sediment, SettlementRank $rank, ?\DateTimeImmutable $decayedAt): Settlement
    {
        $settlement = new Settlement(new Zone());
        $settlement->setRank($rank);
        $settlement->setDecayedAt($decayedAt);

        foreach (SettlementIndex::cases() as $index) {
            $settlement->setSediment($index, $sediment[$index->value] ?? 0);
        }

        $this->settlements[] = $settlement;

        return $settlement;
    }

    private function service(): SettlementTickService
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $repository = $this->createMock(SettlementRepository::class);
        $repository->method('findAll')->willReturnCallback(fn (): array => $this->settlements);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function (object $event): object {
            if ($event instanceof SettlementRankChangedEvent) {
                $this->dispatched[] = $event;
            }

            return $event;
        });

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
            'seed' => [],
            'without_settlement' => [],
        ]);

        return new SettlementTickService($entityManager, $repository, $loader, $dispatcher);
    }
}
