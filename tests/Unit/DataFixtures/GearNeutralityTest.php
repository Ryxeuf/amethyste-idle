<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * OBJ-03 — la grille d'equipement neutre (GAME_ITEMS §3.2).
 *
 * La grille elementaire ne bouclait pas : t2 couvrait quatre elements, t3 les
 * quatre autres, aucun element n'avait de progression t2 → t3 — et la
 * completer aurait demande 168 pieces, pour une variable qui n'est pas celle
 * du build. La decision actee : **la piece d'equipement ne porte plus
 * d'element** ; ce qui distingue une piece, ce sont ses emplacements de
 * materia. Les 56 pieces elementaires ont fusionne en une piece par forme et
 * par palier.
 */
class GearNeutralityTest extends TestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * Aucune piece d'equipement ne porte d'element — dans les fixtures PHP
     * comme dans les YAML. Une piece elementaire rouvrirait la grille a 168
     * cases que personne ne remplira.
     */
    public function testNoGearPieceCarriesAnElement(): void
    {
        $offenders = [];

        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/ItemFixtures.php');
        preg_match_all("/'type' => '([a-z_]+)'/", $source, $types, \PREG_OFFSET_CAPTURE);
        foreach ($types[1] as $i => [$type, $offset]) {
            if ('gear' !== $type) {
                continue;
            }
            $end = isset($types[1][$i + 1]) ? $types[1][$i + 1][1] : \strlen($source);
            $block = substr($source, $offset, $end - $offset);
            if (preg_match("/'element' => Element::/", $block) && preg_match("/'slug' => '([a-z0-9-]+)'/", $block, $slug)) {
                $offenders[] = $slug[1];
            }
        }

        foreach (glob($this->root() . '/fixtures/game/item/*.yaml') ?: [] as $file) {
            preg_match_all("/^  [a-z0-9_]+ \(extends item\):((?:\n    .*)+)/m", (string) file_get_contents($file), $blocks);
            foreach ($blocks[1] as $block) {
                if (str_contains($block, "type: 'gear'") && preg_match('/\n    element:/', $block) && preg_match("/slug: '([a-z0-9-]+)'/", $block, $slug)) {
                    $offenders[] = $slug[1];
                }
            }
        }

        sort($offenders);
        $this->assertSame(
            [],
            $offenders,
            sprintf('Des pieces d\'equipement portent un element (OBJ-03, GAME_ITEMS §3.2) : %s.', implode(', ', $offenders)),
        );
    }

    /**
     * Les 56 pieces elementaires ne reviennent pas : chaque forme a sa piece
     * neutre par palier, une variante elementaire serait un doublon.
     */
    public function testTheElementalWardrobeStaysGone(): void
    {
        $sources = (string) file_get_contents($this->root() . '/src/DataFixtures/ItemFixtures.php');
        foreach (glob($this->root() . '/fixtures/game/item/*.yaml') ?: [] as $file) {
            $sources .= (string) file_get_contents($file);
        }

        preg_match_all('/t[23]-(?:fire|water|earth|air|metal|beast|light|dark)-[a-z]+/', $sources, $matches);

        $this->assertSame(
            [],
            array_values(array_unique($matches[0])),
            'Des pieces elementaires sont revenues : la grille est neutre, le build vit dans les emplacements de materia (OBJ-03).',
        );
    }

    /**
     * La grille neutre est complete sur ses deux paliers fusionnes : une
     * piece par forme en t2 et en t3 — c'est ce qui remplace les 56.
     */
    public function testTheNeutralGridCoversBothTiers(): void
    {
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/ItemFixtures.php');

        $missing = [];
        foreach (['t2', 't3'] as $tier) {
            foreach (['sword', 'shield', 'helmet', 'chest', 'legs', 'boots', 'gloves'] as $shape) {
                if (!str_contains($source, sprintf("'slug' => '%s-%s'", $tier, $shape))) {
                    $missing[] = sprintf('%s-%s', $tier, $shape);
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            sprintf('La grille neutre a des trous (OBJ-03) : %s.', implode(', ', $missing)),
        );
    }
}
