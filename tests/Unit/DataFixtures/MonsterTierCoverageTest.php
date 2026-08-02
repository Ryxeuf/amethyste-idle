<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * BES-04 — la faille du milieu est refermee (GAME_BESTIARY §3.1, invariant 4).
 *
 * Le monde se coupait en deux : depart et fin, et rien entre les deux. La
 * cible minimale par palier peuple (T1 a T4) est de 6 communs, 3 elites et
 * 1 boss — comptes sur les especes reellement atteignables (placees dans une
 * zone du graphe ou dans un donjon), jamais sur le seul catalogue.
 */
class MonsterTierCoverageTest extends TestCase
{
    private const MIN_COMMON = 6;
    private const MIN_ELITE = 3;
    private const MIN_BOSS = 1;

    /**
     * @return array<int, array<string, int>> tier => rank => nombre d'especes placees
     */
    private function coverage(): array
    {
        $root = \dirname(__DIR__, 3);

        $monsterSource = (string) file_get_contents($root . '/src/DataFixtures/MonsterFixtures.php');
        preg_match_all("/\n            '([a-z_0-9]+)' => \[/", $monsterSource, $matches, PREG_OFFSET_CAPTURE);
        $entries = $matches[1];

        $monsters = [];
        foreach ($entries as $i => [$slug, $offset]) {
            $end = isset($entries[$i + 1]) ? $entries[$i + 1][1] : \strlen($monsterSource);
            $block = substr($monsterSource, $offset, $end - $offset);
            preg_match("/'tier' => (\d+),/", $block, $tier);
            preg_match("/'rank' => '([a-z]+)'/", $block, $rank);
            $monsters[$slug] = [(int) ($tier[1] ?? -1), $rank[1] ?? 'common'];
        }

        $zoneSource = (string) file_get_contents($root . '/config/game/zones/world_1.yaml');
        preg_match_all('/- \{ monster: ([a-z_0-9]+)/', $zoneSource, $zoned);

        $dungeonSource = (string) file_get_contents($root . '/src/DataFixtures/DungeonMobFixtures.php');
        preg_match_all("/'monster' => '([a-z_0-9]+)'/", $dungeonSource, $dungeon);

        $coverage = [];
        foreach (array_unique(array_merge($zoned[1], $dungeon[1])) as $slug) {
            $this->assertArrayHasKey($slug, $monsters, sprintf('Espece placee inconnue : %s.', $slug));
            [$tier, $rank] = $monsters[$slug];
            $coverage[$tier][$rank] = ($coverage[$tier][$rank] ?? 0) + 1;
        }

        return $coverage;
    }

    /**
     * Chaque palier T1 a T4 porte au moins 6 communs, 3 elites et 1 boss
     * atteignables — c'est ce qui empeche le monde de se recouper en deux.
     */
    public function testNoTierIsHollow(): void
    {
        $coverage = $this->coverage();

        foreach ([1, 2, 3, 4] as $tier) {
            $common = $coverage[$tier]['common'] ?? 0;
            $elite = $coverage[$tier]['elite'] ?? 0;
            $boss = $coverage[$tier]['boss'] ?? 0;

            $this->assertGreaterThanOrEqual(self::MIN_COMMON, $common, sprintf('T%d : %d communs places, cible %d — la faille du milieu revient.', $tier, $common, self::MIN_COMMON));
            $this->assertGreaterThanOrEqual(self::MIN_ELITE, $elite, sprintf('T%d : %d elites placees, cible %d.', $tier, $elite, self::MIN_ELITE));
            $this->assertGreaterThanOrEqual(self::MIN_BOSS, $boss, sprintf('T%d : %d boss place(s), cible %d.', $tier, $boss, self::MIN_BOSS));
        }
    }
}
