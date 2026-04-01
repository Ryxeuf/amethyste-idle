<?php

namespace App\GameEngine\Validation;

use Doctrine\DBAL\Connection;

/**
 * Validates game state consistency by running diagnostic queries against the database.
 * Each check returns an array of anomaly descriptions (empty = healthy).
 */
class GameStateValidator
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Run all validation checks.
     *
     * @return array<string, list<string>> Check name => list of anomaly descriptions
     */
    public function validateAll(): array
    {
        return [
            'orphaned_fights' => $this->checkOrphanedFights(),
            'fights_without_alive_mobs' => $this->checkActiveFightsWithoutAliveMobs(),
            'orphaned_items' => $this->checkOrphanedItems(),
            'players_out_of_bounds' => $this->checkPlayersOutOfBounds(),
            'players_in_stale_fights' => $this->checkPlayersInStaleFights(),
        ];
    }

    /**
     * Players referencing a fight_id that no longer exists or is already terminated.
     *
     * @return list<string>
     */
    public function checkOrphanedFights(): array
    {
        $sql = <<<'SQL'
            SELECT p.id AS player_id, p.name AS player_name, p.fight_id
            FROM player p
            WHERE p.fight_id IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1 FROM fight f WHERE f.id = p.fight_id
              )
        SQL;

        $rows = $this->connection->fetchAllAssociative($sql);
        $anomalies = [];

        foreach ($rows as $row) {
            $anomalies[] = sprintf(
                'Player #%d "%s" references non-existent fight #%d',
                $row['player_id'],
                $row['player_name'],
                $row['fight_id'],
            );
        }

        return $anomalies;
    }

    /**
     * Active fights (in_progress = true) where all mobs are dead.
     *
     * @return list<string>
     */
    public function checkActiveFightsWithoutAliveMobs(): array
    {
        $sql = <<<'SQL'
            SELECT f.id AS fight_id, f.step,
                   COUNT(m.id) AS total_mobs,
                   COUNT(m.id) FILTER (WHERE m.died_at IS NULL) AS alive_mobs
            FROM fight f
            LEFT JOIN mob m ON m.fight_id = f.id
            WHERE f.in_progress = true
            GROUP BY f.id
            HAVING COUNT(m.id) FILTER (WHERE m.died_at IS NULL) = 0
        SQL;

        $rows = $this->connection->fetchAllAssociative($sql);
        $anomalies = [];

        foreach ($rows as $row) {
            $anomalies[] = sprintf(
                'Fight #%d (step %d) is active but has 0 alive mobs out of %d total',
                $row['fight_id'],
                $row['step'],
                $row['total_mobs'],
            );
        }

        return $anomalies;
    }

    /**
     * PlayerItems that have no inventory, no guild vault, and no mob (truly orphaned).
     *
     * @return list<string>
     */
    public function checkOrphanedItems(): array
    {
        $sql = <<<'SQL'
            SELECT pi.id AS item_id, i.name AS item_name
            FROM player_item pi
            LEFT JOIN item i ON i.id = pi.item_id
            WHERE pi.inventory_id IS NULL
              AND pi.mob_id IS NULL
              AND pi.guild_vault_id IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM slot s WHERE s.item_set_id = pi.id
              )
        SQL;

        $rows = $this->connection->fetchAllAssociative($sql);
        $anomalies = [];

        foreach ($rows as $row) {
            $anomalies[] = sprintf(
                'PlayerItem #%d "%s" is orphaned (no inventory, no mob, no vault, no slot)',
                $row['item_id'],
                $row['item_name'] ?? 'unknown',
            );
        }

        return $anomalies;
    }

    /**
     * Players whose coordinates fall outside any known cell of their current map.
     *
     * @return list<string>
     */
    public function checkPlayersOutOfBounds(): array
    {
        // We check if the player's x.y coordinates correspond to an existing cell
        // in any area of their map. We build the valid bounds from area data.
        $sql = <<<'SQL'
            SELECT p.id AS player_id, p.name AS player_name, p.coordinates, p.map_id,
                   m.name AS map_name, m."areaWidth", m."areaHeight"
            FROM player p
            JOIN map m ON m.id = p.map_id
            WHERE p.map_id IS NOT NULL
        SQL;

        $players = $this->connection->fetchAllAssociative($sql);

        if (empty($players)) {
            return [];
        }

        // Collect unique map IDs and build bounds
        $mapIds = array_unique(array_column($players, 'map_id'));
        $mapBounds = $this->buildMapBounds($mapIds);

        $anomalies = [];

        foreach ($players as $player) {
            $coords = explode('.', $player['coordinates']);
            if (count($coords) !== 2) {
                $anomalies[] = sprintf(
                    'Player #%d "%s" has invalid coordinates format: "%s"',
                    $player['player_id'],
                    $player['player_name'],
                    $player['coordinates'],
                );
                continue;
            }

            $x = (int) $coords[0];
            $y = (int) $coords[1];
            $mapId = $player['map_id'];

            if (!isset($mapBounds[$mapId])) {
                continue; // No area data — can't verify
            }

            $bounds = $mapBounds[$mapId];
            if ($x < $bounds['minX'] || $x > $bounds['maxX'] || $y < $bounds['minY'] || $y > $bounds['maxY']) {
                $anomalies[] = sprintf(
                    'Player #%d "%s" at %d.%d is out of bounds on map #%d "%s" (valid: %d-%d x %d-%d)',
                    $player['player_id'],
                    $player['player_name'],
                    $x,
                    $y,
                    $mapId,
                    $player['map_name'],
                    $bounds['minX'],
                    $bounds['maxX'],
                    $bounds['minY'],
                    $bounds['maxY'],
                );
            }
        }

        return $anomalies;
    }

    /**
     * Players referencing a fight that exists but is no longer in progress.
     *
     * @return list<string>
     */
    public function checkPlayersInStaleFights(): array
    {
        $sql = <<<'SQL'
            SELECT p.id AS player_id, p.name AS player_name, p.fight_id,
                   f.in_progress, f.step
            FROM player p
            JOIN fight f ON f.id = p.fight_id
            WHERE p.fight_id IS NOT NULL
              AND f.in_progress = false
        SQL;

        $rows = $this->connection->fetchAllAssociative($sql);
        $anomalies = [];

        foreach ($rows as $row) {
            $anomalies[] = sprintf(
                'Player #%d "%s" still references terminated fight #%d (step %d)',
                $row['player_id'],
                $row['player_name'],
                $row['fight_id'],
                $row['step'],
            );
        }

        return $anomalies;
    }

    /**
     * Build min/max coordinate bounds for each map from its areas.
     *
     * @param int[] $mapIds
     *
     * @return array<int, array{minX: int, maxX: int, minY: int, maxY: int}>
     */
    private function buildMapBounds(array $mapIds): array
    {
        if (empty($mapIds)) {
            return [];
        }

        $sql = <<<'SQL'
            SELECT a.map_id, a.coordinates AS area_coords,
                   m."areaWidth", m."areaHeight"
            FROM area a
            JOIN map m ON m.id = a.map_id
            WHERE a.map_id IN (?)
        SQL;

        $rows = $this->connection->fetchAllAssociative(
            $sql,
            [$mapIds],
            [Connection::PARAM_INT_ARRAY],
        );

        $bounds = [];

        foreach ($rows as $row) {
            $mapId = $row['map_id'];
            $areaCoords = explode('.', $row['area_coords']);
            $areaX = (int) ($areaCoords[0] ?? 0);
            $areaY = (int) ($areaCoords[1] ?? 0);
            $areaWidth = (int) $row['areaWidth'];
            $areaHeight = (int) $row['areaHeight'];

            $cellMinX = $areaX * $areaWidth;
            $cellMinY = $areaY * $areaHeight;
            $cellMaxX = $cellMinX + $areaWidth - 1;
            $cellMaxY = $cellMinY + $areaHeight - 1;

            if (!isset($bounds[$mapId])) {
                $bounds[$mapId] = [
                    'minX' => $cellMinX,
                    'maxX' => $cellMaxX,
                    'minY' => $cellMinY,
                    'maxY' => $cellMaxY,
                ];
            } else {
                $bounds[$mapId]['minX'] = min($bounds[$mapId]['minX'], $cellMinX);
                $bounds[$mapId]['maxX'] = max($bounds[$mapId]['maxX'], $cellMaxX);
                $bounds[$mapId]['minY'] = min($bounds[$mapId]['minY'], $cellMinY);
                $bounds[$mapId]['maxY'] = max($bounds[$mapId]['maxY'], $cellMaxY);
            }
        }

        return $bounds;
    }
}
