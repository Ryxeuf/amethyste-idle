<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * BES-03 — une seule source de faune (GAME_BESTIARY §4).
 *
 * `zones.yaml` est la source unique du peuplement du graphe ; les donjons
 * suivent leur propre chemin (`DungeonMobFixtures`, hors graphe). Le contrat
 * tient trois choses : le peuplement par coordonnees a disparu, la fixture
 * de donjon ne place que sur des cartes de donjon, et aucune espece n'est
 * perdue — tout monstre livre est atteignable, hors mannequins et boss
 * narratifs reserves.
 */
class FaunaSingleSourceTest extends TestCase
{
    /**
     * Especes volontairement hors du monde ouvert : les deux mannequins
     * (combats scriptes de l'acte I) et les boss narratifs reserves
     * (GAME_BESTIARY §6, invariant 7).
     *
     * @var list<string>
     */
    private const RESERVED_SPECIES = [
        'training_dummy_still',
        'training_dummy_sparring',
        'ancient_wyrm',
        'convergence_guardian',
        'the_first_silence',
    ];

    private function fixturesDir(): string
    {
        return \dirname(__DIR__, 3) . '/src/DataFixtures';
    }

    /**
     * Le peuplement par coordonnees et par carte a disparu : `MobFixtures`
     * n'existe plus.
     */
    public function testLegacyMobFixturesIsGone(): void
    {
        $this->assertFileDoesNotExist(
            $this->fixturesDir() . '/MobFixtures.php',
            'MobFixtures place la faune par coordonnees, vestige d\'avant le pivot : zones.yaml est la source unique (BES-03).',
        );
    }

    /**
     * La fixture de donjon ne place que sur des cartes de donjon — elle ne
     * doit jamais redevenir un canal de peuplement du monde ouvert.
     */
    public function testDungeonFixturesOnlyPlaceOnDungeonMaps(): void
    {
        $source = (string) file_get_contents($this->fixturesDir() . '/DungeonMobFixtures.php');

        preg_match_all("/'map' => '([a-z_0-9]+)'/", $source, $maps);
        $this->assertNotEmpty($maps[1], 'Le test ne verifie rien si l\'extraction echoue.');

        foreach (array_unique($maps[1]) as $map) {
            $this->assertStringStartsWith(
                'map_dungeon_',
                $map,
                sprintf('DungeonMobFixtures place sur "%s" : seule une carte de donjon est admise ici.', $map),
            );
        }
    }

    /**
     * Aucune espece perdue a la bascule : tout monstre livre est place dans
     * `zones.yaml` ou dans un donjon, hors liste reservee.
     */
    public function testNoSpeciesIsLost(): void
    {
        $monsterSource = (string) file_get_contents($this->fixturesDir() . '/MonsterFixtures.php');
        preg_match_all("/\n            '([a-z_0-9]+)' => \[/", $monsterSource, $monsters);

        $zoneSource = (string) file_get_contents(\dirname(__DIR__, 3) . '/config/game/zones/world_1.yaml');
        preg_match_all('/monster: ([a-z_0-9]+)/', $zoneSource, $zoned);

        $dungeonSource = (string) file_get_contents($this->fixturesDir() . '/DungeonMobFixtures.php');
        preg_match_all("/'monster' => '([a-z_0-9]+)'/", $dungeonSource, $dungeon);

        $placed = array_merge($zoned[1], $dungeon[1], self::RESERVED_SPECIES);

        $lost = array_values(array_diff($monsters[1], $placed));
        $this->assertSame(
            [],
            $lost,
            sprintf('Especes livrees mais introuvables dans le monde : %s. Les placer dans zones.yaml (leur palier), ou les inscrire aux especes reservees si c\'est un choix.', implode(', ', $lost)),
        );
    }

    /**
     * L'inverse tient aussi : une zone ne place jamais une espece qui
     * n'existe pas.
     */
    public function testEveryPlacedSpeciesExists(): void
    {
        $monsterSource = (string) file_get_contents($this->fixturesDir() . '/MonsterFixtures.php');
        preg_match_all("/\n            '([a-z_0-9]+)' => \[/", $monsterSource, $monsters);

        $zoneSource = (string) file_get_contents(\dirname(__DIR__, 3) . '/config/game/zones/world_1.yaml');
        preg_match_all('/monster: ([a-z_0-9]+)/', $zoneSource, $zoned);

        $ghosts = array_values(array_diff(array_unique($zoned[1]), $monsters[1]));
        $this->assertSame([], $ghosts, sprintf('Zones.yaml place des especes inexistantes : %s.', implode(', ', $ghosts)));
    }
}
