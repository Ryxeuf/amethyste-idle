<?php

namespace App\GameEngine\Debug;

use Doctrine\DBAL\Connection;

/**
 * Validates game state coherence in the database.
 * Each check returns an array of anomaly descriptions (empty = OK).
 */
class GameStateValidator
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    private const CHECK_METHODS = [
        'ghost_fights' => 'checkGhostFights',
        'fights_without_living_mobs' => 'checkFightsWithoutLivingMobs',
        'orphaned_player_items' => 'checkOrphanedPlayerItems',
        'stale_active_quests' => 'checkStaleActiveQuests',
        'players_out_of_bounds' => 'checkPlayersOutOfBounds',
    ];

    /**
     * Run all validations and return results keyed by check name.
     *
     * @return array<string, list<string>>
     */
    public function validateAll(): array
    {
        $results = [];
        foreach (self::CHECK_METHODS as $name => $method) {
            $results[$name] = $this->$method();
        }

        return $results;
    }

    /**
     * Run a single check by name.
     *
     * @return list<string>
     */
    public function runCheck(string $checkName): array
    {
        $method = self::CHECK_METHODS[$checkName] ?? null;
        if ($method === null) {
            throw new \InvalidArgumentException(sprintf('Unknown check: "%s"', $checkName));
        }

        return $this->$method();
    }

    /**
     * Players referencing a fight that doesn't exist or is already finished.
     *
     * @return list<string>
     */
    public function checkGhostFights(): array
    {
        $sql = <<<'SQL'
            SELECT p.id AS player_id, p.name AS player_name, p.fight_id,
                   f.id AS fight_exists, f.in_progress
            FROM player p
            LEFT JOIN fight f ON f.id = p.fight_id
            WHERE p.fight_id IS NOT NULL
              AND (f.id IS NULL OR f.in_progress = false)
            SQL;

        $rows = $this->connection->fetchAllAssociative($sql);
        $anomalies = [];

        foreach ($rows as $row) {
            if ($row['fight_exists'] === null) {
                $anomalies[] = sprintf(
                    'Player #%d "%s" references fight #%d which does not exist',
                    $row['player_id'],
                    $row['player_name'],
                    $row['fight_id'],
                );
            } else {
                $anomalies[] = sprintf(
                    'Player #%d "%s" references fight #%d which is already finished (in_progress=false)',
                    $row['player_id'],
                    $row['player_name'],
                    $row['fight_id'],
                );
            }
        }

        return $anomalies;
    }

    /**
     * Active fights (in_progress=true) with no living mobs.
     *
     * @return list<string>
     */
    public function checkFightsWithoutLivingMobs(): array
    {
        $sql = <<<'SQL'
            SELECT f.id AS fight_id, COUNT(m.id) AS living_mob_count
            FROM fight f
            LEFT JOIN mob m ON m.fight_id = f.id AND m.life > 0
            WHERE f.in_progress = true
            GROUP BY f.id
            HAVING COUNT(m.id) = 0
            SQL;

        $rows = $this->connection->fetchAllAssociative($sql);
        $anomalies = [];

        foreach ($rows as $row) {
            $anomalies[] = sprintf(
                'Fight #%d is active (in_progress=true) but has no living mobs',
                $row['fight_id'],
            );
        }

        return $anomalies;
    }

    /**
     * PlayerItems with a null or dangling item reference.
     *
     * @return list<string>
     */
    public function checkOrphanedPlayerItems(): array
    {
        $sql = <<<'SQL'
            SELECT pi.id AS player_item_id, pi.inventory_id, pi.item_id,
                   i.id AS item_exists
            FROM player_item pi
            LEFT JOIN item i ON i.id = pi.item_id
            WHERE pi.item_id IS NULL OR i.id IS NULL
            SQL;

        $rows = $this->connection->fetchAllAssociative($sql);
        $anomalies = [];

        foreach ($rows as $row) {
            if ($row['item_id'] === null) {
                $anomalies[] = sprintf(
                    'PlayerItem #%d in inventory #%d has NULL item_id',
                    $row['player_item_id'],
                    $row['inventory_id'],
                );
            } else {
                $anomalies[] = sprintf(
                    'PlayerItem #%d references item #%d which does not exist',
                    $row['player_item_id'],
                    $row['item_id'],
                );
            }
        }

        return $anomalies;
    }

    /**
     * PlayerQuest entries where the same quest is already in PlayerQuestCompleted
     * for the same player (stale active quest that should have been cleaned up).
     *
     * @return list<string>
     */
    public function checkStaleActiveQuests(): array
    {
        $sql = <<<'SQL'
            SELECT pq.id AS player_quest_id, pq.player_id,
                   p.name AS player_name, q.name AS quest_name,
                   pqc.id AS completed_id
            FROM player_quest pq
            JOIN player p ON p.id = pq.player_id
            JOIN quest q ON q.id = pq.quest_id
            JOIN player_quest_completed pqc ON pqc.player_id = pq.player_id AND pqc.quest_id = pq.quest_id
            SQL;

        $rows = $this->connection->fetchAllAssociative($sql);
        $anomalies = [];

        foreach ($rows as $row) {
            $anomalies[] = sprintf(
                'Player #%d "%s" has active quest "%s" (PlayerQuest #%d) but also has it completed (PlayerQuestCompleted #%d)',
                $row['player_id'],
                $row['player_name'],
                $row['quest_name'],
                $row['player_quest_id'],
                $row['completed_id'],
            );
        }

        return $anomalies;
    }

    /**
     * Players whose coordinates are outside their current map bounds.
     *
     * @return list<string>
     */
    public function checkPlayersOutOfBounds(): array
    {
        // Get all areas for all maps to determine actual bounds
        $sql = <<<'SQL'
            SELECT p.id AS player_id, p.name AS player_name,
                   p.coordinates, p.map_id,
                   m.name AS map_name, m."areaWidth", m."areaHeight"
            FROM player p
            JOIN map m ON m.id = p.map_id
            WHERE p.coordinates IS NOT NULL
            SQL;

        $players = $this->connection->fetchAllAssociative($sql);

        // Preload area coordinates per map to compute actual map pixel bounds
        $areaSql = <<<'SQL'
            SELECT a.map_id, a.coordinates AS area_coords
            FROM area a
            SQL;
        $areaRows = $this->connection->fetchAllAssociative($areaSql);

        $mapBounds = [];
        foreach ($areaRows as $areaRow) {
            $mapId = $areaRow['map_id'];
            if (!isset($mapBounds[$mapId])) {
                $mapBounds[$mapId] = ['minAX' => PHP_INT_MAX, 'minAY' => PHP_INT_MAX, 'maxAX' => 0, 'maxAY' => 0];
            }
            $parts = explode('.', $areaRow['area_coords']);
            $ax = (int) $parts[0];
            $ay = (int) ($parts[1] ?? 0);
            $mapBounds[$mapId]['minAX'] = min($mapBounds[$mapId]['minAX'], $ax);
            $mapBounds[$mapId]['minAY'] = min($mapBounds[$mapId]['minAY'], $ay);
            $mapBounds[$mapId]['maxAX'] = max($mapBounds[$mapId]['maxAX'], $ax);
            $mapBounds[$mapId]['maxAY'] = max($mapBounds[$mapId]['maxAY'], $ay);
        }

        $anomalies = [];

        foreach ($players as $row) {
            $mapId = $row['map_id'];
            if (!isset($mapBounds[$mapId])) {
                continue; // map has no areas, skip
            }

            $coords = explode('.', $row['coordinates']);
            $px = (int) $coords[0];
            $py = (int) ($coords[1] ?? 0);

            $bounds = $mapBounds[$mapId];
            $areaWidth = (int) $row['areaWidth'];
            $areaHeight = (int) $row['areaHeight'];

            $minX = $bounds['minAX'] * $areaWidth;
            $minY = $bounds['minAY'] * $areaHeight;
            $maxX = ($bounds['maxAX'] + 1) * $areaWidth - 1;
            $maxY = ($bounds['maxAY'] + 1) * $areaHeight - 1;

            if ($px < $minX || $px > $maxX || $py < $minY || $py > $maxY) {
                $anomalies[] = sprintf(
                    'Player #%d "%s" at %s is out of bounds for map #%d "%s" (valid: %d-%d x %d-%d)',
                    $row['player_id'],
                    $row['player_name'],
                    $row['coordinates'],
                    $mapId,
                    $row['map_name'],
                    $minX,
                    $maxX,
                    $minY,
                    $maxY,
                );
            }
        }

        return $anomalies;
    }
}
