<?php

namespace App\GameEngine\Retention;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Chargement + validation des paliers d'assiduite (RET-04).
 *
 * Meme parti pris que `WeeklyCommissionTemplateLoader` : validation purement
 * structurelle, aucune lecture de base, et **echec a la lecture**. Une table
 * mal ecrite doit faire rougir la CI, pas se decouvrir un lundi matin.
 *
 * Deux invariants sont verifies ici parce qu'ils ne se voient nulle part
 * ailleurs : les seuils sont **strictement croissants** (deux paliers au meme
 * jour se marcheraient dessus, et un palier decroissant se franchirait a
 * l'envers), et aucun n'exige les 7 jours de la semaine — un palier a 7 ferait
 * d'un jour manque une perte, ce que ce plan s'interdit.
 */
class WeeklyAttendanceDefinitionLoader
{
    /**
     * Jours d'une semaine ISO. Un palier ne peut pas les exiger tous : ce
     * serait une serie deguisee, avec sa penalite implicite.
     */
    public const MAX_TIER_DAYS = 6;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/weekly_attendance.yaml';
    }

    /**
     * @return list<WeeklyAttendanceTier>
     *
     * @throws WeeklyAttendanceException
     */
    public function load(?string $path = null): array
    {
        $path ??= $this->defaultFile();

        if (!is_file($path)) {
            throw new WeeklyAttendanceException(sprintf('Attendance tiers not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new WeeklyAttendanceException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new WeeklyAttendanceException(sprintf('Attendance table "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return list<WeeklyAttendanceTier>
     *
     * @throws WeeklyAttendanceException
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $rows = $raw['tiers'] ?? null;
        if (!\is_array($rows) || $rows === []) {
            throw new WeeklyAttendanceException(sprintf('Attendance table "%s" declares no tier.', $source));
        }

        $tiers = [];
        $previous = 0;

        foreach ($rows as $row) {
            if (!\is_array($row)) {
                throw new WeeklyAttendanceException(sprintf('Attendance tier of "%s" must be a mapping.', $source));
            }

            $days = (int) ($row['days'] ?? 0);
            if ($days <= $previous) {
                throw new WeeklyAttendanceException(sprintf('Attendance tiers of "%s" must be strictly increasing; got %d after %d.', $source, $days, $previous));
            }
            if ($days > self::MAX_TIER_DAYS) {
                throw new WeeklyAttendanceException(sprintf('Attendance tier of "%s" asks for %d days: a tier at 7 turns a missed day into a loss.', $source, $days));
            }

            $tiers[] = new WeeklyAttendanceTier(
                $days,
                max(0, (int) ($row['gils'] ?? 0)),
                max(0, (int) ($row['energy'] ?? 0)),
            );
            $previous = $days;
        }

        return $tiers;
    }
}
