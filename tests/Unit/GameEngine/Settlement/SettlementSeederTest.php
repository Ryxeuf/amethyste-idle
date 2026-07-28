<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Settlement\SettlementSeeder;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * Le seed du monde livre (FOY-01, decision A).
 *
 * Trois proprietes valent d'etre tenues par un test, parce qu'elles sont
 * silencieuses quand elles cassent : le seed est **idempotent** (le rejouer sur
 * une base vivante ne doit pas ecraser ce que les joueurs ont bati), il ne pose
 * **aucun type** (une identite decretee dans un fichier n'est plus une identite
 * gagnee), et une zone du seed encore inexistante se **signale** au lieu de
 * disparaitre du rapport.
 */
class SettlementSeederTest extends TestCase
{
    /** @var array<string, Zone> */
    private array $zones = [];

    /** @var list<Settlement> */
    private array $persisted = [];

    /** @var array<string, Settlement> */
    private array $existing = [];

    private int $flushes = 0;

    public function testSeedsEachDeclaredZoneOnce(): void
    {
        $this->zones = ['foret' => new Zone(), 'marais' => new Zone()];

        $report = $this->seeder()->seed();

        self::assertSame(2, $report['created']);
        self::assertSame(0, $report['skipped']);
        self::assertSame([], $report['unknown']);
        self::assertCount(2, $this->persisted);
        self::assertSame(1, $this->flushes);
    }

    public function testStockIsSplitEvenlyAcrossTheFourIndices(): void
    {
        $this->zones = ['foret' => new Zone()];

        $this->seeder()->seed();

        $settlement = $this->persisted[0];
        self::assertSame(2000, $settlement->getTotalSediment());
        foreach (SettlementIndex::cases() as $index) {
            self::assertSame(500, $settlement->getSediment($index));
        }
    }

    /**
     * Le coeur de la decision : un foyer seede a un rang, mais pas de type.
     * L'identite se gagne en jouant.
     */
    public function testSeedGivesARankButNeverAType(): void
    {
        $this->zones = ['foret' => new Zone()];

        $this->seeder()->seed();

        $settlement = $this->persisted[0];
        self::assertSame(SettlementRank::Hamlet, $settlement->getRank());
        self::assertSame(SettlementRank::Hamlet, $settlement->getHighestRank());
        self::assertNull($settlement->getType());
        self::assertNull($settlement->getDominantIndex());
        self::assertNotNull($settlement->getRankedAt());
    }

    /**
     * Rejouer le seed sur un monde vivant est une operation courante (fixtures,
     * import, redeploiement). Elle ne doit jamais rendre son avance a un foyer
     * que les joueurs ont laisse redescendre.
     */
    public function testReplayingTheSeedLeavesExistingSettlementsAlone(): void
    {
        $foret = new Zone();
        $this->zones = ['foret' => $foret, 'marais' => new Zone()];

        $already = new Settlement($foret);
        $already->setRank(SettlementRank::Camp);
        $this->existing = ['foret' => $already];

        $report = $this->seeder()->seed();

        self::assertSame(1, $report['created']);
        self::assertSame(1, $report['skipped']);
        self::assertSame(SettlementRank::Camp, $already->getRank());
        self::assertSame(0, $already->getTotalSediment());
    }

    /**
     * Un seed ecrit en avance d'une zone (les Vallons, ZON-30) n'est pas une
     * erreur fatale — mais il doit se voir dans le rapport, pas s'evaporer.
     */
    public function testAZoneThatDoesNotExistYetIsReportedNotSwallowed(): void
    {
        $this->zones = ['foret' => new Zone()];

        $report = $this->seeder()->seed();

        self::assertSame(1, $report['created']);
        self::assertSame(['marais'], $report['unknown']);
    }

    public function testFlushCanBeDeferredToTheCaller(): void
    {
        $this->zones = ['foret' => new Zone(), 'marais' => new Zone()];

        $this->seeder()->seed(false);

        self::assertCount(2, $this->persisted);
        self::assertSame(0, $this->flushes);
    }

    private function seeder(): SettlementSeeder
    {
        $zoneRepository = $this->createMock(EntityRepository::class);
        $zoneRepository->method('findOneBy')->willReturnCallback(
            fn (array $criteria): ?Zone => $this->zones[$criteria['slug']] ?? null,
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($zoneRepository);
        $entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            self::assertInstanceOf(Settlement::class, $entity);
            $this->persisted[] = $entity;
        });
        $entityManager->method('flush')->willReturnCallback(function (): void {
            ++$this->flushes;
        });

        $settlementRepository = $this->createMock(SettlementRepository::class);
        $settlementRepository->method('findOneByZone')->willReturnCallback(
            fn (Zone $zone): ?Settlement => $this->settlementOf($zone),
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
            'seed' => [
                'foret' => ['rank' => SettlementRank::Hamlet, 'stock' => 2000],
                'marais' => ['rank' => SettlementRank::Camp, 'stock' => 400],
            ],
            'without_settlement' => [],
        ]);

        return new SettlementSeeder($entityManager, $settlementRepository, $loader);
    }

    private function settlementOf(Zone $zone): ?Settlement
    {
        foreach ($this->existing as $slug => $settlement) {
            if (($this->zones[$slug] ?? null) === $zone) {
                return $settlement;
            }
        }

        return null;
    }
}
