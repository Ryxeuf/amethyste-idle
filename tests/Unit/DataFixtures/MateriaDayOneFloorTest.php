<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * MAT-04 — le plancher du jour 1 (GAME_MATERIA §3, invariant 3 du §6).
 *
 * « Un joueur qui se specialise dans le feu doit avoir ses premieres materia
 * de feu au premier jour, pas a la premiere semaine. » Les materia ouvertes a
 * 0 point par les arbres de combat sont le plancher du build : chacune a une
 * source **non aleatoire** (une boutique PNJ), a **au plus une liaison** du
 * Fanal. Le palier de distribution suit le nœud, jamais le sort — une materia
 * ouverte a 0 point est au plancher meme si son sort est de niveau 2.
 */
class MateriaDayOneFloorTest extends TestCase
{
    private const FANAL = 'village-de-lumiere';

    /**
     * @return list<string> slugs de materia ouverts a 0 point (derives)
     */
    private function dayOneMateriaSlugs(): array
    {
        $root = \dirname(__DIR__, 3);
        $spells = [];
        $spellSource = (string) file_get_contents($root . '/src/DataFixtures/SpellFixtures.php');
        preg_match_all("/\n            '([a-z_0-9]+)' => \[/", $spellSource, $blocks, PREG_OFFSET_CAPTURE);
        foreach ($blocks[1] as $i => [$key, $offset]) {
            $end = isset($blocks[1][$i + 1]) ? $blocks[1][$i + 1][1] : \strlen($spellSource);
            $body = substr($spellSource, $offset, $end - $offset);
            if (preg_match("/'slug' => '([a-z0-9-]+)'/", $body, $slug)) {
                preg_match("/'level' => (\d+)/", $body, $level);
                $spells[$slug[1]] = (int) ($level[1] ?? 1);
            }
        }

        $skillSource = (string) file_get_contents($root . '/src/DataFixtures/Game/SkillFixtures.php');
        preg_match_all(
            "/'requiredPoints' => (\d+),(.{0,400}?)'actions' => \['materia' => \['unlock' => '([a-z0-9-]+)'\]\]/s",
            $skillSource,
            $nodes,
            PREG_SET_ORDER,
        );

        $slugs = [];
        foreach ($nodes as $node) {
            if ((int) $node[1] !== 0) {
                continue;
            }
            $spellSlug = $node[3];
            $this->assertArrayHasKey($spellSlug, $spells, sprintf('Le nœud a 0 point ouvre un sort inconnu "%s".', $spellSlug));
            $slugs[sprintf('m%d-%s', $spells[$spellSlug], $spellSlug)] = true;
        }

        return array_keys($slugs);
    }

    /**
     * @return array{shops: array<string, list<string>>, neighbours: list<string>}
     */
    private function world(): array
    {
        $config = Yaml::parseFile(\dirname(__DIR__, 3) . '/config/game/zones/world_1.yaml');

        $shops = [];
        foreach ($config['zones'] as $zoneSlug => $zone) {
            foreach ($zone['pnjs'] ?? [] as $pnj) {
                foreach ($pnj['shop_items'] ?? [] as $itemSlug) {
                    $shops[$itemSlug][] = $zoneSlug;
                }
            }
        }

        $neighbours = [];
        foreach ($config['connections'] as $connection) {
            if (($connection['from'] ?? null) === self::FANAL) {
                $neighbours[] = $connection['to'];
            }
            if (($connection['to'] ?? null) === self::FANAL && ($connection['bidirectional'] ?? false)) {
                $neighbours[] = $connection['from'];
            }
        }

        return ['shops' => $shops, 'neighbours' => $neighbours];
    }

    /**
     * Le jour 1 est tenu : chaque materia du plancher a une boutique — jamais
     * de hasard sur le build d'entree — a au plus une liaison du Fanal.
     */
    public function testEveryDayOneMateriaHasANearbyShop(): void
    {
        $dayOne = $this->dayOneMateriaSlugs();
        $this->assertGreaterThanOrEqual(40, \count($dayOne), 'Le plancher du jour 1 doit couvrir les 24 arbres de combat.');

        ['shops' => $shops, 'neighbours' => $neighbours] = $this->world();
        $reachable = array_merge([self::FANAL], $neighbours);

        foreach ($dayOne as $materiaSlug) {
            $this->assertArrayHasKey(
                $materiaSlug,
                $shops,
                sprintf('La materia du jour 1 "%s" n\'est vendue nulle part : le plancher du build depend du hasard (MAT-04).', $materiaSlug),
            );

            $near = array_intersect($shops[$materiaSlug], $reachable);
            $this->assertNotEmpty(
                $near,
                sprintf('"%s" n\'est vendue qu\'a plus d\'une liaison du Fanal (%s).', $materiaSlug, implode(', ', $shops[$materiaSlug])),
            );
        }
    }

    /**
     * Une boutique ne vend jamais une materia qui n'existe pas : tout slug
     * m1-…m5- en rayon correspond a un sort du catalogue derive.
     */
    public function testShopsOnlySellDerivableMateria(): void
    {
        $root = \dirname(__DIR__, 3);
        preg_match_all("/'slug' => '([a-z0-9-]+)'/", (string) file_get_contents($root . '/src/DataFixtures/SpellFixtures.php'), $spellSlugs);

        ['shops' => $shops] = $this->world();
        foreach (array_keys($shops) as $itemSlug) {
            if (!preg_match('/^m(\d)-(.+)$/', (string) $itemSlug, $m)) {
                continue;
            }
            $this->assertContains(
                $m[2],
                $spellSlugs[1],
                sprintf('La boutique vend "%s" mais aucun sort "%s" n\'existe : la materia ne sera jamais derivee.', $itemSlug, $m[2]),
            );
        }
    }
}
