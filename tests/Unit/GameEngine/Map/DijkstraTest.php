<?php

namespace App\Tests\Unit\GameEngine\Map;

use App\Exception\PathNotFoundException;
use App\GameEngine\Map\Dijkstra;
use PHPUnit\Framework\TestCase;

class DijkstraTest extends TestCase
{
    /**
     * Graphe simple : A --1--> B --1--> C
     */
    public function testShortestPathLinear(): void
    {
        $graph = [
            '0.0' => ['0.1' => 1],
            '0.1' => ['0.0' => 1, '0.2' => 1],
            '0.2' => ['0.1' => 1],
        ];

        $dijkstra = new Dijkstra($graph, '0.0');
        $path = $dijkstra->shortestPathTo('0.2');

        $this->assertCount(3, $path);
        $this->assertSame('0.0', $path[0]['node_identifier']);
        $this->assertSame('0.1', $path[1]['node_identifier']);
        $this->assertSame('0.2', $path[2]['node_identifier']);
        $this->assertSame(2, $path[2]['accumulated_weight']);
    }

    /**
     * Graphe en losange avec chemin court par le haut
     *
     *       1.0
     *      / 1  \ 1
     *   0.0      2.0
     *      \ 5  / 1
     *       1.1
     */
    public function testShortestPathDiamond(): void
    {
        $graph = [
            '0.0' => ['1.0' => 1, '1.1' => 5],
            '1.0' => ['0.0' => 1, '2.0' => 1],
            '1.1' => ['0.0' => 5, '2.0' => 1],
            '2.0' => ['1.0' => 1, '1.1' => 1],
        ];

        $dijkstra = new Dijkstra($graph, '0.0');
        $path = $dijkstra->shortestPathTo('2.0');

        // Chemin le plus court : 0.0 -> 1.0 -> 2.0 (coût 2) et pas 0.0 -> 1.1 -> 2.0 (coût 6)
        $this->assertSame(2, $path[count($path) - 1]['accumulated_weight']);
        $this->assertSame('1.0', $path[1]['node_identifier']);
    }

    public function testSourceToItself(): void
    {
        $graph = [
            '5.5' => ['5.6' => 1],
            '5.6' => ['5.5' => 1],
        ];

        $dijkstra = new Dijkstra($graph, '5.5');
        $distances = $dijkstra->getDistances();

        $this->assertSame(0, $distances['5.5']);
        $this->assertSame(1, $distances['5.6']);
    }

    public function testGetAlgorithmTime(): void
    {
        $graph = [
            '0.0' => ['0.1' => 1],
            '0.1' => ['0.0' => 1],
        ];

        $dijkstra = new Dijkstra($graph, '0.0');

        $this->assertIsFloat($dijkstra->getAlgorithmTime());
        $this->assertGreaterThanOrEqual(0, $dijkstra->getAlgorithmTime());
    }

    /**
     * Teste un graphe de type grille 3x3 (simulant une carte de jeu)
     */
    public function testGridGraph(): void
    {
        // Grille 3x3
        $graph = [];
        for ($x = 0; $x < 3; $x++) {
            for ($y = 0; $y < 3; $y++) {
                $node = "$x.$y";
                $graph[$node] = [];
                // Voisin droite
                if ($x + 1 < 3) {
                    $graph[$node][($x + 1) . ".$y"] = 1;
                }
                // Voisin gauche
                if ($x - 1 >= 0) {
                    $graph[$node][($x - 1) . ".$y"] = 1;
                }
                // Voisin bas
                if ($y + 1 < 3) {
                    $graph[$node]["$x." . ($y + 1)] = 1;
                }
                // Voisin haut
                if ($y - 1 >= 0) {
                    $graph[$node]["$x." . ($y - 1)] = 1;
                }
            }
        }

        $dijkstra = new Dijkstra($graph, '0.0');
        $path = $dijkstra->shortestPathTo('2.2');

        // Distance Manhattan de (0,0) à (2,2) = 4
        $this->assertSame(4, $path[count($path) - 1]['accumulated_weight']);
    }
}
