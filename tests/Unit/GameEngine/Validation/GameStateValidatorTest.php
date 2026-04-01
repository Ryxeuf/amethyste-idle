<?php

namespace App\Tests\Unit\GameEngine\Validation;

use App\GameEngine\Validation\GameStateValidator;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class GameStateValidatorTest extends TestCase
{
    private Connection $connection;
    private GameStateValidator $validator;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->validator = new GameStateValidator($this->connection);
    }

    // ─── checkOrphanedFights ───

    public function testOrphanedFightsReturnsEmptyWhenNoAnomalies(): void
    {
        $this->connection->method('fetchAllAssociative')->willReturn([]);

        $result = $this->validator->checkOrphanedFights();

        $this->assertSame([], $result);
    }

    public function testOrphanedFightsDetectsPlayerReferencingMissingFight(): void
    {
        $this->connection->method('fetchAllAssociative')->willReturn([
            ['player_id' => 1, 'player_name' => 'Hero', 'fight_id' => 999],
        ]);

        $result = $this->validator->checkOrphanedFights();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('Player #1', $result[0]);
        $this->assertStringContainsString('non-existent fight #999', $result[0]);
    }

    // ─── checkActiveFightsWithoutAliveMobs ───

    public function testActiveFightsWithoutAliveMobsReturnsEmptyWhenHealthy(): void
    {
        $this->connection->method('fetchAllAssociative')->willReturn([]);

        $result = $this->validator->checkActiveFightsWithoutAliveMobs();

        $this->assertSame([], $result);
    }

    public function testActiveFightsWithoutAliveMobsDetectsAnomaly(): void
    {
        $this->connection->method('fetchAllAssociative')->willReturn([
            ['fight_id' => 42, 'step' => 5, 'total_mobs' => 3, 'alive_mobs' => 0],
        ]);

        $result = $this->validator->checkActiveFightsWithoutAliveMobs();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('Fight #42', $result[0]);
        $this->assertStringContainsString('0 alive mobs', $result[0]);
    }

    // ─── checkOrphanedItems ───

    public function testOrphanedItemsReturnsEmptyWhenHealthy(): void
    {
        $this->connection->method('fetchAllAssociative')->willReturn([]);

        $result = $this->validator->checkOrphanedItems();

        $this->assertSame([], $result);
    }

    public function testOrphanedItemsDetectsOrphan(): void
    {
        $this->connection->method('fetchAllAssociative')->willReturn([
            ['item_id' => 77, 'item_name' => 'Epee rouille'],
        ]);

        $result = $this->validator->checkOrphanedItems();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('PlayerItem #77', $result[0]);
        $this->assertStringContainsString('orphaned', $result[0]);
    }

    // ─── checkPlayersInStaleFights ───

    public function testPlayersInStaleFightsReturnsEmptyWhenHealthy(): void
    {
        $this->connection->method('fetchAllAssociative')->willReturn([]);

        $result = $this->validator->checkPlayersInStaleFights();

        $this->assertSame([], $result);
    }

    public function testPlayersInStaleFightsDetectsAnomaly(): void
    {
        $this->connection->method('fetchAllAssociative')->willReturn([
            ['player_id' => 3, 'player_name' => 'Mage', 'fight_id' => 10, 'in_progress' => false, 'step' => 12],
        ]);

        $result = $this->validator->checkPlayersInStaleFights();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('Player #3', $result[0]);
        $this->assertStringContainsString('terminated fight #10', $result[0]);
    }

    // ─── checkPlayersOutOfBounds ───

    public function testPlayersOutOfBoundsReturnsEmptyWhenNoPlayers(): void
    {
        $this->connection->method('fetchAllAssociative')->willReturn([]);

        $result = $this->validator->checkPlayersOutOfBounds();

        $this->assertSame([], $result);
    }

    public function testPlayersOutOfBoundsDetectsInvalidCoordinatesFormat(): void
    {
        $this->connection
            ->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql) {
                if (str_contains($sql, 'FROM player')) {
                    return [
                        [
                            'player_id' => 1,
                            'player_name' => 'Broken',
                            'coordinates' => 'invalid',
                            'map_id' => 1,
                            'map_name' => 'TestMap',
                            'areaWidth' => 32,
                            'areaHeight' => 32,
                        ],
                    ];
                }

                // Areas query
                return [
                    ['map_id' => 1, 'area_coords' => '0.0', 'areaWidth' => 32, 'areaHeight' => 32],
                ];
            });

        $result = $this->validator->checkPlayersOutOfBounds();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('invalid coordinates format', $result[0]);
    }

    public function testPlayersOutOfBoundsDetectsPlayerOutside(): void
    {
        $this->connection
            ->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql) {
                if (str_contains($sql, 'FROM player')) {
                    return [
                        [
                            'player_id' => 5,
                            'player_name' => 'Lost',
                            'coordinates' => '100.100',
                            'map_id' => 1,
                            'map_name' => 'SmallMap',
                            'areaWidth' => 32,
                            'areaHeight' => 32,
                        ],
                    ];
                }

                // Areas: only one area at 0.0, so valid range is 0-31 x 0-31
                return [
                    ['map_id' => 1, 'area_coords' => '0.0', 'areaWidth' => 32, 'areaHeight' => 32],
                ];
            });

        $result = $this->validator->checkPlayersOutOfBounds();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('Player #5', $result[0]);
        $this->assertStringContainsString('out of bounds', $result[0]);
    }

    public function testPlayersOutOfBoundsAcceptsValidCoordinates(): void
    {
        $this->connection
            ->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql) {
                if (str_contains($sql, 'FROM player')) {
                    return [
                        [
                            'player_id' => 2,
                            'player_name' => 'ValidPlayer',
                            'coordinates' => '15.20',
                            'map_id' => 1,
                            'map_name' => 'TestMap',
                            'areaWidth' => 32,
                            'areaHeight' => 32,
                        ],
                    ];
                }

                return [
                    ['map_id' => 1, 'area_coords' => '0.0', 'areaWidth' => 32, 'areaHeight' => 32],
                ];
            });

        $result = $this->validator->checkPlayersOutOfBounds();

        $this->assertSame([], $result);
    }

    // ─── validateAll ───

    public function testValidateAllRunsAllChecks(): void
    {
        $this->connection->method('fetchAllAssociative')->willReturn([]);

        $results = $this->validator->validateAll();

        $this->assertArrayHasKey('orphaned_fights', $results);
        $this->assertArrayHasKey('fights_without_alive_mobs', $results);
        $this->assertArrayHasKey('orphaned_items', $results);
        $this->assertArrayHasKey('players_out_of_bounds', $results);
        $this->assertArrayHasKey('players_in_stale_fights', $results);
        $this->assertCount(5, $results);
    }
}
