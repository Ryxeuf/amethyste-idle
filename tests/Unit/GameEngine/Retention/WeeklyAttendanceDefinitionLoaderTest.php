<?php

namespace App\Tests\Unit\GameEngine\Retention;

use App\GameEngine\Retention\WeeklyAttendanceDefinitionLoader;
use App\GameEngine\Retention\WeeklyAttendanceException;
use PHPUnit\Framework\TestCase;

/**
 * La table des paliers echoue **a la lecture** (RET-04).
 *
 * Une table mal ecrite doit faire rougir la CI, pas se decouvrir un lundi matin
 * sur l'ecran d'un joueur dont aucun palier ne se franchit plus.
 */
class WeeklyAttendanceDefinitionLoaderTest extends TestCase
{
    private function loader(): WeeklyAttendanceDefinitionLoader
    {
        return new WeeklyAttendanceDefinitionLoader(\dirname(__DIR__, 4));
    }

    /**
     * La table livree est valide — sans ce test, tous les autres verifieraient
     * un fichier fictif.
     */
    public function testTheShippedTableLoads(): void
    {
        $tiers = $this->loader()->load();

        self::assertNotEmpty($tiers);
        self::assertSame([2, 4, 6], array_map(static fn ($tier): int => $tier->days, $tiers));
    }

    public function testTiersMustBeStrictlyIncreasing(): void
    {
        $this->expectException(WeeklyAttendanceException::class);
        $this->expectExceptionMessageMatches('/strictly increasing/');

        $this->loader()->normalize(['tiers' => [
            ['days' => 4, 'gils' => 100],
            ['days' => 2, 'gils' => 200],
        ]]);
    }

    public function testTwoTiersOnTheSameDayAreRefused(): void
    {
        $this->expectException(WeeklyAttendanceException::class);

        $this->loader()->normalize(['tiers' => [
            ['days' => 2, 'gils' => 100],
            ['days' => 2, 'gils' => 200],
        ]]);
    }

    /**
     * L'interdit du plan, rendu executable : un palier a 7 jours ferait d'un
     * jour manque une perte — c'est une serie deguisee.
     */
    public function testATierAskingForTheWholeWeekIsRefused(): void
    {
        $this->expectException(WeeklyAttendanceException::class);
        $this->expectExceptionMessageMatches('/turns a missed day into a loss/');

        $this->loader()->normalize(['tiers' => [['days' => 7, 'gils' => 100]]]);
    }

    public function testAnEmptyTableIsRefused(): void
    {
        $this->expectException(WeeklyAttendanceException::class);

        $this->loader()->normalize(['tiers' => []]);
    }

    public function testAMissingFileIsRefused(): void
    {
        $this->expectException(WeeklyAttendanceException::class);

        $this->loader()->load('/nowhere/weekly_attendance.yaml');
    }
}
