<?php

namespace App\Tests\Unit\GameEngine\World;

use App\Entity\App\WorldLoadSnapshot;
use App\GameEngine\World\WorldLoadService;
use App\Repository\PlayerRepository;
use App\Repository\WorldLoadSnapshotRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * FOY-17 — la mesure de la charge du monde.
 *
 * Ce que ces tests verrouillent tient en une phrase : **on compte la charge,
 * pas les tetes** (BALANCE § 22.5). Le reste — idempotence du releve, trou dans
 * l'historique, fenetre de maree — sert cette phrase.
 */
class WorldLoadServiceTest extends TestCase
{
    private const DAILY_ENERGY = 150;
    private const TIDE_DAYS = 28;

    private EntityManagerInterface&MockObject $em;
    private WorldLoadSnapshotRepository&MockObject $repository;
    private PlayerRepository&MockObject $playerRepository;

    /** @var list<object> */
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(WorldLoadSnapshotRepository::class);
        $this->playerRepository = $this->createMock(PlayerRepository::class);
        $this->em->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });
    }

    private function service(int $totalEnergySpent = 0): WorldLoadService
    {
        $this->playerRepository->method('sumActionEnergySpent')->willReturn($totalEnergySpent);

        return new WorldLoadService(
            $this->em,
            $this->repository,
            $this->playerRepository,
            self::DAILY_ENERGY,
            self::TIDE_DAYS,
        );
    }

    // -----------------------------------------------------------------
    // Releve journalier
    // -----------------------------------------------------------------

    public function testFirstCaptureCountsTheWholeCumulativeAsTheDaySpend(): void
    {
        $this->repository->method('findLatestBefore')->willReturn(null);
        $this->repository->method('findOneByDay')->willReturn(null);

        $snapshot = $this->service(1_200)->capture(new \DateTimeImmutable('2026-08-05 00:05:00'));

        self::assertSame(1_200, $snapshot->getCumulativeEnergy());
        self::assertSame(1_200, $snapshot->getDailyEnergy());
        self::assertSame('2026-08-05', $snapshot->getDay()->format('Y-m-d'));
        self::assertCount(1, $this->persisted);
    }

    public function testDailySpendIsTheDeltaAgainstThePreviousSnapshot(): void
    {
        $this->repository->method('findLatestBefore')->willReturn($this->snapshot('2026-08-04', 1_000, 400));
        $this->repository->method('findOneByDay')->willReturn(null);

        $snapshot = $this->service(1_450)->capture(new \DateTimeImmutable('2026-08-05 00:05:00'));

        self::assertSame(1_450, $snapshot->getCumulativeEnergy());
        self::assertSame(450, $snapshot->getDailyEnergy());
    }

    /**
     * Rejouer le tick le meme jour reecrit la ligne du jour, il n'en cree pas
     * une seconde.
     */
    public function testCaptureIsIdempotentWithinTheSameDay(): void
    {
        $existing = $this->snapshot('2026-08-05', 1_100, 100);

        $this->repository->method('findLatestBefore')->willReturn($this->snapshot('2026-08-04', 1_000, 400));
        $this->repository->method('findOneByDay')->willReturn($existing);

        $snapshot = $this->service(1_300)->capture(new \DateTimeImmutable('2026-08-05 09:00:00'));

        self::assertSame($existing, $snapshot);
        self::assertSame(300, $snapshot->getDailyEnergy());
        self::assertSame([], $this->persisted, 'Aucune ligne nouvelle : le jour existait deja.');
    }

    /**
     * Un serveur arrete trois jours reprend sur le dernier releve connu.
     *
     * La difference se fait avec le **dernier instantane**, pas avec « hier » :
     * compter zero ferait lire une desertion la ou il n'y a qu'une coupure.
     */
    public function testAGapInHistoryDoesNotReadAsDesertion(): void
    {
        $this->repository->method('findLatestBefore')->willReturn($this->snapshot('2026-08-01', 5_000, 200));
        $this->repository->method('findOneByDay')->willReturn(null);

        $snapshot = $this->service(5_900)->capture(new \DateTimeImmutable('2026-08-05 00:05:00'));

        self::assertSame(900, $snapshot->getDailyEnergy());
    }

    /**
     * Le cumul est monotone : un total qui reculerait (restauration de base,
     * purge de personnages) ne doit pas produire une depense negative.
     */
    public function testARegressingCumulativeNeverYieldsNegativeSpend(): void
    {
        $this->repository->method('findLatestBefore')->willReturn($this->snapshot('2026-08-04', 9_000, 400));
        $this->repository->method('findOneByDay')->willReturn(null);

        $snapshot = $this->service(8_000)->capture(new \DateTimeImmutable('2026-08-05 00:05:00'));

        self::assertSame(0, $snapshot->getDailyEnergy());
    }

    // -----------------------------------------------------------------
    // Population effective
    // -----------------------------------------------------------------

    /**
     * Un joueur regulier depense ~150/jour, soit 4 200 sur une maree : 4 200
     * points mesures valent donc exactement un joueur effectif.
     */
    public function testOneRegularPlayerWorthOfEnergyIsOneEffectivePlayer(): void
    {
        $this->repository->method('findRecent')->willReturn([$this->snapshot('2026-08-05', 4_200, 4_200)]);

        self::assertSame(1.0, $this->service()->effectivePopulation());
    }

    public function testEffectivePopulationSumsTheWholeTide(): void
    {
        $days = [];
        for ($i = 0; $i < self::TIDE_DAYS; ++$i) {
            // 50 joueurs reguliers : 50 x 150 points par jour.
            $days[] = $this->snapshot(sprintf('2026-08-%02d', ($i % 28) + 1), 0, 50 * self::DAILY_ENERGY);
        }
        $this->repository->method('findRecent')->willReturn($days);

        self::assertSame(50.0, $this->service()->effectivePopulation());
    }

    /**
     * La propriete qui emporte la decision (BALANCE § 22.5) : la mesure ne se
     * gonfle pas avec des comptes secondaires, parce qu'elle ne compte pas des
     * comptes. Trois comptes menes a fond par une personne exercent la pression
     * de trois joueurs, et le monde doit se dimensionner pour trois.
     */
    public function testPopulationIgnoresHeadcountAndFollowsSpentEnergy(): void
    {
        $this->repository->method('findRecent')->willReturn([$this->snapshot('2026-08-05', 0, 3 * 4_200)]);

        self::assertSame(3.0, $this->service()->effectivePopulation());
    }

    public function testAnEmptyHistoryYieldsNoPopulation(): void
    {
        $this->repository->method('findRecent')->willReturn([]);

        $service = $this->service();
        self::assertSame(0.0, $service->effectivePopulation());
        self::assertSame(0, $service->measuredDays());
    }

    /**
     * Le nombre de jours mesures justifie la periode de grace : un monde ne se
     * contracte pas sur une fenetre qu'il n'a pas eu le temps de remplir.
     */
    public function testMeasuredDaysReportsAPartialWindow(): void
    {
        $this->repository->method('findRecent')->willReturn([
            $this->snapshot('2026-08-05', 0, 100),
            $this->snapshot('2026-08-04', 0, 100),
        ]);

        self::assertSame(2, $this->service()->measuredDays());
    }

    private function snapshot(string $day, int $cumulative, int $daily): WorldLoadSnapshot
    {
        $snapshot = new WorldLoadSnapshot();
        $snapshot->setDay(new \DateTimeImmutable($day));
        $snapshot->setCumulativeEnergy($cumulative);
        $snapshot->setDailyEnergy($daily);
        $snapshot->setCapturedAt(new \DateTimeImmutable($day . ' 00:05:00'));

        return $snapshot;
    }
}
