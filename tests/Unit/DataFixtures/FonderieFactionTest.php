<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * La cinquieme maison, et tout ce qui l'attendait (FAC-04a).
 *
 * La Fonderie etait declaree partout avant d'exister : la paire de tension
 * (FAC-01), la route de geste materia_melt (FAC-02) et la consequence
 * buyback_floor_closed (FAC-03) visaient un slug que rien ne semait. Ce test
 * verifie que la faction est la — et que les crochets pointent bien sur elle,
 * pour qu'aucune coquille ne les laisse inertes en silence.
 */
class FonderieFactionTest extends TestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * La faction est semee : le jour 1 la voit dans l'ecran des factions,
     * comme les quatre autres — le contraste exact des Ruelles, elle
     * s'affiche (GAME_WORLD § 12.2).
     */
    public function testTheFoundryIsSeededWithTheOtherFactions(): void
    {
        $fixtures = (string) file_get_contents($this->root() . '/src/DataFixtures/Game/FactionFixtures.php');

        self::assertStringContainsString("'slug' => 'fonderie'", $fixtures, 'La cinquieme maison a disparu des fixtures.');
        self::assertStringContainsString("'name' => 'La Fonderie'", $fixtures);
        self::assertStringContainsString("'ref' => 'faction_fonderie'", $fixtures);
    }

    /**
     * Tout ce qui attendait le slug le vise correctement : la paire de
     * tension, la route de fonte et la consequence hostile s'activent
     * d'elles-memes maintenant que la faction existe.
     */
    public function testEveryDormantHookPointsAtTheSeededSlug(): void
    {
        $config = Yaml::parseFile($this->root() . '/config/game/factions.yaml');
        $fixtures = (string) file_get_contents($this->root() . '/src/DataFixtures/Game/FactionFixtures.php');

        $hooks = [];
        foreach ($config['tension_pairs'] as $pair) {
            $hooks['paire ' . $pair['axis']] = [$pair['left'], $pair['right']];
        }
        foreach ($config['gestures']['routes'] as $gesture => $route) {
            $hooks['route ' . $gesture] = [$route['faction']];
        }
        $hooks['consequences hostiles'] = array_keys($config['hostile']['consequences']);

        foreach ($hooks as $hook => $slugs) {
            foreach ($slugs as $slug) {
                self::assertStringContainsString(
                    sprintf("'slug' => '%s'", $slug),
                    $fixtures,
                    sprintf('Le crochet "%s" vise la faction "%s", que rien ne seme : il restera inerte en silence.', $hook, $slug),
                );
            }
        }
    }

    /**
     * Le comptoir des Mines est declare, en marchand, avec des articles qui
     * existent — et il est bien au carreau des Mines, le siege de la maison.
     */
    public function testTheFoundryCounterStandsAtTheMines(): void
    {
        $world = Yaml::parseFile($this->root() . '/config/game/zones/world_1.yaml');
        $pnjs = $world['zones']['mines-profondes']['pnjs'] ?? [];

        $counter = null;
        foreach ($pnjs as $pnj) {
            if (($pnj['slug'] ?? null) === 'mines-comptoir-de-la-fonderie') {
                $counter = $pnj;
            }
        }

        self::assertIsArray($counter, 'Le comptoir de la Fonderie a quitte le carreau des Mines.');
        self::assertSame('merchant', $counter['class_type'] ?? null);
        self::assertNotEmpty($counter['shop_items'] ?? [], 'Un comptoir sans article n\'est pas un marchand : l\'ecran de vente ne s\'ouvrirait pas.');
    }
}
