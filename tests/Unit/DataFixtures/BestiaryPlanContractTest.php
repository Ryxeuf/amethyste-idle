<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * BES-06 — le contrat du plan bestiaire (GAME_BESTIARY §6).
 *
 * Les huit invariants du cadrage, et qui les tient :
 *
 *  1. Deux axes, pas quatre          → MonsterTierRankTest (les echelles legacy ont disparu)
 *  2. Le palier suit la zone         → MonsterTierRankTest (55 placements contre zones.yaml)
 *  3. T0 est sur                     → ICI (aucune faune hostile dans une zone de palier 0)
 *  4. Aucun palier vide              → MonsterTierCoverageTest (6 communs, 3 elites, 1 boss)
 *  5. Les stats suivent le gabarit   → MonsterStatDerivationTest + MonsterStatTemplateTest
 *  6. Une seule source de faune      → FaunaSingleSourceTest
 *  7. Aucune espece inaccessible     → FaunaSingleSourceTest (liste reservee fermee)
 *  8. Tout monstre porte un element  → MonsterElementTest (MAT-01)
 *
 * Ce fichier ne re-teste pas ce que les autres tiennent deja : il porte le
 * seul invariant qui manquait, et sert d'index au contrat.
 */
class BestiaryPlanContractTest extends TestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function zones(): array
    {
        $config = Yaml::parseFile(\dirname(__DIR__, 3) . '/config/game/zones/world_1.yaml');
        $zones = $config['zones'] ?? null;
        $this->assertIsArray($zones, 'Le YAML des zones doit exposer ses zones.');

        return $zones;
    }

    /**
     * Invariant 3 — T0 est sur : aucune faune hostile dans une zone de
     * palier 0. « Ici, rien ne mord » est une promesse du monde, pas un
     * reglage d'exploration.
     */
    public function testTierZeroZonesCarryNoHostileFauna(): void
    {
        $checked = 0;
        foreach ($this->zones() as $slug => $zone) {
            if ((int) ($zone['tier'] ?? 0) !== 0) {
                continue;
            }
            ++$checked;

            $this->assertArrayNotHasKey(
                'mobs',
                $zone,
                sprintf('La zone "%s" est T0 : elle ne porte aucune faune hostile (GAME_BESTIARY §6, invariant 3).', $slug),
            );
        }

        $this->assertGreaterThan(0, $checked, 'Le test ne verifie rien si aucune zone T0 n\'existe.');
    }

    /**
     * Le miroir de l'invariant 3 : une zone qui place de la faune vit dans
     * un palier reel (T1+) — le palier 0 par defaut ne peut pas etre un
     * oubli silencieux sur une zone peuplee.
     */
    public function testEveryPopulatedZoneLivesInARealTier(): void
    {
        $checked = 0;
        foreach ($this->zones() as $slug => $zone) {
            if (empty($zone['mobs'])) {
                continue;
            }
            ++$checked;

            $this->assertGreaterThanOrEqual(
                1,
                (int) ($zone['tier'] ?? 0),
                sprintf('La zone "%s" place de la faune mais ne declare pas de palier T1+ : tier oublie ?', $slug),
            );
        }

        $this->assertGreaterThan(0, $checked, 'Le test ne verifie rien si aucune zone peuplee n\'existe.');
    }
}
