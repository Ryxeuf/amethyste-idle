<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Map;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\GameEngine\Zone\ZoneImporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ZoneImporterTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $zoneRepository;
    private EntityRepository&MockObject $connectionRepository;
    private EntityRepository&MockObject $mapRepository;
    private ZoneImporter $importer;

    /** @var list<object> */
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->zoneRepository = $this->createMock(EntityRepository::class);
        $this->connectionRepository = $this->createMock(EntityRepository::class);
        $this->mapRepository = $this->createMock(EntityRepository::class);

        $this->entityManager->method('getRepository')->willReturnMap([
            [Zone::class, $this->zoneRepository],
            [ZoneConnection::class, $this->connectionRepository],
            [Map::class, $this->mapRepository],
        ]);

        $this->persisted = [];
        $this->entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });

        $this->importer = new class($this->entityManager) extends ZoneImporter {
            protected function now(): \DateTime
            {
                return new \DateTime('2026-07-24 00:00:00');
            }
        };
    }

    public function testImportCreatesZonesAndBidirectionalConnections(): void
    {
        $this->zoneRepository->method('findOneBy')->willReturn(null);
        $this->connectionRepository->method('findOneBy')->willReturn(null);
        $this->mapRepository->method('findOneBy')->willReturn(null);

        $report = $this->importer->import([
            'zones' => [
                $this->zoneData('village', 'Village', 'city', true),
                $this->zoneData('foret', 'Forêt', 'wilderness', false),
            ],
            'connections' => [
                ['from' => 'village', 'to' => 'foret', 'travel_seconds' => 300, 'bidirectional' => true, 'requires_discovery' => false, 'enabled' => true],
            ],
        ]);

        self::assertSame(2, $report->zonesCreated);
        self::assertSame(0, $report->zonesUpdated);
        // Bidirectionnel -> deux aretes creees.
        self::assertSame(2, $report->connectionsCreated);
        self::assertSame(0, $report->connectionsUpdated);

        $zones = array_filter($this->persisted, static fn (object $e): bool => $e instanceof Zone);
        $connections = array_filter($this->persisted, static fn (object $e): bool => $e instanceof ZoneConnection);
        self::assertCount(2, $zones);
        self::assertCount(2, $connections);

        $village = array_values(array_filter($zones, static fn (Zone $z): bool => 'village' === $z->getSlug()))[0];
        self::assertTrue($village->isSafe());
        self::assertSame(Zone::TYPE_CITY, $village->getType());
    }

    public function testGatherConfigWrappedUnderResources(): void
    {
        $this->zoneRepository->method('findOneBy')->willReturn(null);
        $this->mapRepository->method('findOneBy')->willReturn(null);

        $data = $this->zoneData('mines', 'Mines', 'wilderness', false);
        $data['gather'] = [
            ['slug' => 'filon', 'item' => 'ore-iron', 'profession' => 'mining', 'capacity' => 10, 'respawn_seconds' => 600, 'yield_min' => 1, 'yield_max' => 2],
        ];

        $this->importer->import(['zones' => [$data], 'connections' => []]);

        /** @var Zone $mines */
        $mines = array_values(array_filter($this->persisted, static fn (object $e): bool => $e instanceof Zone))[0];
        self::assertSame(['resources' => $data['gather']], $mines->getGatherConfig());
        self::assertCount(1, $mines->getGatherResources());
    }

    public function testExistingZoneAndConnectionAreUpdatedNotDuplicated(): void
    {
        $existingZone = (new Zone())->setSlug('village');
        $existingZone->setCreatedAt(new \DateTime('2026-01-01'));
        $existingZone->setUpdatedAt(new \DateTime('2026-01-01'));
        $otherZone = (new Zone())->setSlug('foret');
        $otherZone->setCreatedAt(new \DateTime('2026-01-01'));
        $otherZone->setUpdatedAt(new \DateTime('2026-01-01'));

        $this->zoneRepository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria): ?Zone => match ($criteria['slug'] ?? null) {
                'village' => $existingZone,
                'foret' => $otherZone,
                default => null,
            }
        );
        $this->mapRepository->method('findOneBy')->willReturn(null);

        $existingConnection = new ZoneConnection($existingZone, $otherZone, 999);
        $this->connectionRepository->method('findOneBy')->willReturn($existingConnection);

        $report = $this->importer->import([
            'zones' => [
                $this->zoneData('village', 'Village', 'city', true),
                $this->zoneData('foret', 'Forêt', 'wilderness', false),
            ],
            'connections' => [
                ['from' => 'village', 'to' => 'foret', 'travel_seconds' => 300, 'bidirectional' => false, 'requires_discovery' => false, 'enabled' => true],
            ],
        ]);

        self::assertSame(0, $report->zonesCreated);
        self::assertSame(2, $report->zonesUpdated);
        self::assertSame(0, $report->connectionsCreated);
        self::assertSame(1, $report->connectionsUpdated);
        // La duree existante est ecrasee par la valeur declarative.
        self::assertSame(300, $existingConnection->getTravelSeconds());
    }

    public function testDryRunPersistsNothing(): void
    {
        $this->zoneRepository->method('findOneBy')->willReturn(null);
        $this->mapRepository->method('findOneBy')->willReturn(null);
        $this->entityManager->expects(self::never())->method('flush');

        $report = $this->importer->import([
            'zones' => [$this->zoneData('village', 'Village', 'city', true)],
            'connections' => [],
        ], true);

        self::assertSame(1, $report->zonesCreated);
        self::assertSame([], $this->persisted);
    }

    public function testUnknownSourceMapRecordsWarning(): void
    {
        $this->zoneRepository->method('findOneBy')->willReturn(null);
        $this->mapRepository->method('findOneBy')->willReturn(null);

        $data = $this->zoneData('village', 'Village', 'city', true);
        $data['source_map'] = 'Carte fantome';

        $report = $this->importer->import(['zones' => [$data], 'connections' => []]);

        self::assertCount(1, $report->warnings);
        self::assertStringContainsString('Carte fantome', $report->warnings[0]);
    }

    /**
     * @return array<string, mixed>
     */
    private function zoneData(string $slug, string $name, string $type, bool $safe): array
    {
        return [
            'slug' => $slug,
            'name' => $name,
            'name_en' => null,
            'description' => null,
            'description_en' => null,
            'type' => $type,
            'safe' => $safe,
            'enabled' => true,
            'source_map' => null,
            'explore' => null,
            'gather' => null,
        ];
    }
}
