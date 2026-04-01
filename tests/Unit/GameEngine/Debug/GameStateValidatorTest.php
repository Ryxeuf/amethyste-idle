<?php

namespace App\Tests\Unit\GameEngine\Debug;

use App\GameEngine\Debug\GameStateValidator;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GameStateValidatorTest extends TestCase
{
    private Connection&MockObject $connection;
    private GameStateValidator $validator;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->validator = new GameStateValidator($this->connection);
    }

    public function testCheckGhostFightsDetectsNonExistentFight(): void
    {
        $this->connection->method('fetchAllAssociative')
            ->willReturn([
                [
                    'player_id' => 1,
                    'player_name' => 'Alice',
                    'fight_id' => 999,
                    'fight_exists' => null,
                    'in_progress' => null,
                ],
            ]);

        $result = $this->validator->checkGhostFights();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('does not exist', $result[0]);
        $this->assertStringContainsString('Alice', $result[0]);
    }

    public function testCheckGhostFightsDetectsFinishedFight(): void
    {
        $this->connection->method('fetchAllAssociative')
            ->willReturn([
                [
                    'player_id' => 2,
                    'player_name' => 'Bob',
                    'fight_id' => 10,
                    'fight_exists' => 10,
                    'in_progress' => false,
                ],
            ]);

        $result = $this->validator->checkGhostFights();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('already finished', $result[0]);
        $this->assertStringContainsString('Bob', $result[0]);
    }

    public function testCheckGhostFightsReturnsEmptyWhenClean(): void
    {
        $this->connection->method('fetchAllAssociative')
            ->willReturn([]);

        $result = $this->validator->checkGhostFights();

        $this->assertEmpty($result);
    }

    public function testCheckFightsWithoutLivingMobs(): void
    {
        $this->connection->method('fetchAllAssociative')
            ->willReturn([
                ['fight_id' => 5, 'living_mob_count' => 0],
            ]);

        $result = $this->validator->checkFightsWithoutLivingMobs();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('Fight #5', $result[0]);
        $this->assertStringContainsString('no living mobs', $result[0]);
    }

    public function testCheckOrphanedPlayerItemsDetectsNullItemId(): void
    {
        $this->connection->method('fetchAllAssociative')
            ->willReturn([
                [
                    'player_item_id' => 42,
                    'inventory_id' => 3,
                    'item_id' => null,
                    'item_exists' => null,
                ],
            ]);

        $result = $this->validator->checkOrphanedPlayerItems();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('NULL item_id', $result[0]);
    }

    public function testCheckOrphanedPlayerItemsDetectsDanglingReference(): void
    {
        $this->connection->method('fetchAllAssociative')
            ->willReturn([
                [
                    'player_item_id' => 43,
                    'inventory_id' => 3,
                    'item_id' => 999,
                    'item_exists' => null,
                ],
            ]);

        $result = $this->validator->checkOrphanedPlayerItems();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('does not exist', $result[0]);
    }

    public function testCheckStaleActiveQuests(): void
    {
        $this->connection->method('fetchAllAssociative')
            ->willReturn([
                [
                    'player_quest_id' => 7,
                    'player_id' => 1,
                    'player_name' => 'Alice',
                    'quest_name' => 'Sauver le village',
                    'completed_id' => 12,
                ],
            ]);

        $result = $this->validator->checkStaleActiveQuests();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('Alice', $result[0]);
        $this->assertStringContainsString('Sauver le village', $result[0]);
    }

    public function testCheckPlayersOutOfBoundsDetectsAnomaly(): void
    {
        $callIndex = 0;
        $this->connection->method('fetchAllAssociative')
            ->willReturnCallback(function () use (&$callIndex) {
                ++$callIndex;
                if ($callIndex === 1) {
                    // Players
                    return [
                        [
                            'player_id' => 1,
                            'player_name' => 'Alice',
                            'coordinates' => '999.999',
                            'map_id' => 1,
                            'map_name' => 'Foret',
                            'areaWidth' => 30,
                            'areaHeight' => 30,
                        ],
                    ];
                }

                // Areas for map 1: single area at 0.0
                return [
                    ['map_id' => 1, 'area_coords' => '0.0'],
                ];
            });

        $result = $this->validator->checkPlayersOutOfBounds();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('out of bounds', $result[0]);
        $this->assertStringContainsString('999.999', $result[0]);
    }

    public function testCheckPlayersOutOfBoundsAcceptsValidPosition(): void
    {
        $callIndex = 0;
        $this->connection->method('fetchAllAssociative')
            ->willReturnCallback(function () use (&$callIndex) {
                ++$callIndex;
                if ($callIndex === 1) {
                    return [
                        [
                            'player_id' => 1,
                            'player_name' => 'Alice',
                            'coordinates' => '15.15',
                            'map_id' => 1,
                            'map_name' => 'Foret',
                            'areaWidth' => 30,
                            'areaHeight' => 30,
                        ],
                    ];
                }

                return [
                    ['map_id' => 1, 'area_coords' => '0.0'],
                ];
            });

        $result = $this->validator->checkPlayersOutOfBounds();

        $this->assertEmpty($result);
    }

    public function testValidateAllReturnsAllChecks(): void
    {
        $this->connection->method('fetchAllAssociative')
            ->willReturn([]);

        $results = $this->validator->validateAll();

        $this->assertArrayHasKey('ghost_fights', $results);
        $this->assertArrayHasKey('fights_without_living_mobs', $results);
        $this->assertArrayHasKey('orphaned_player_items', $results);
        $this->assertArrayHasKey('stale_active_quests', $results);
        $this->assertArrayHasKey('players_out_of_bounds', $results);
    }

    public function testRunCheckDelegatesCorrectly(): void
    {
        $this->connection->method('fetchAllAssociative')
            ->willReturn([]);

        $result = $this->validator->runCheck('ghost_fights');

        $this->assertIsArray($result);
    }

    public function testRunCheckThrowsOnUnknownCheck(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validator->runCheck('nonexistent_check');
    }
}
