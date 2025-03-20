<?php

namespace App\Command;

use App\GameEngine\Map\Dijkstra;
use App\GameEngine\Map\MovementCalculator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:debug-move', description: 'Add a short description for your command')]
class DebugMoveCommand extends Command
{
    public function __construct(
        private readonly MovementCalculator $movementCalculator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->movementCalculator->loadMap(10);
        $moves = $this->movementCalculator->calculateMovement(85, 35, 94, 30);
        dump($moves);
        die;

        // 0.0_0_-1:0:0:-1
        $cellName = '0.0_0_-1:0:0:-1';
        dump($cellName);
        [$coordinates, $mouvement, $borders] = explode('_', $cellName);
        dump($coordinates, $mouvement, $borders);
        [$x, $y] = explode('.', $coordinates);
        dump("x/y");
        dump($x, $y);
        [$north, $east, $south, $west] = explode(':', $borders);
        dump($north, $east, $south, $west);

        $fs = fopen(__DIR__ . "/../../data/map/world-1-map-1-0-slugs.json", 'r');
        $contentString = fgets($fs);
        fclose($fs);

        $data = json_decode($contentString);
        $map = [];
        $movement = [];

        foreach ($data as $cell) {
            [$coordinates, $distance] = explode('_', $cell);

            if ((int)$distance !== -1) {
                $movement[$coordinates] = (int)$distance === 0 ? 1 : (int)$distance;
            }
        }
        foreach ($data as $cell) {
            [$coordinates, $distance, $borders] = explode('_', $cell);
            [$x, $y] = explode('.', $coordinates);
            [$north, $east, $south, $west] = explode(':', $borders);

            if ((int)$distance !== -1) {
                $map[$coordinates] = [];
                if ((int)$north === 0 && (int)$y-1 >= 0 && isset($movement[$x.'.'.((int)$y-1)])) {
                    $map[$coordinates][$x.'.'.((int)$y-1)] = (int)$movement[$x.'.'.((int)$y-1)];
                }
                if ((int)$south === 0 && (int)$y+1 < 60 && isset($movement[$x.'.'.((int)$y+1)])) {
                    $map[$coordinates][$x.'.'.((int)$y+1)] = (int)$movement[$x.'.'.((int)$y+1)];
                }
                if ((int)$east === 0 && (int)$x+1 < 60 && isset($movement[((int)$x+1).'.'.$y])) {
                    $map[$coordinates][((int)$x+1).'.'.$y] = (int)$movement[((int)$x+1).'.'.$y];
                }
                if ((int)$west === 0 && (int)$x-1 >= 0 && isset($movement[((int)$x-1).'.'.$y])) {
                    $map[$coordinates][((int)$x-1).'.'.$y] = (int)$movement[((int)$x-1).'.'.$y];
                }
            }
        }

        dump($map['1.2']);
        dump($map['3.2']);
        $dijkstra = new Dijkstra($map, '29.19', 0b11);
        $result = $dijkstra->shortestPathTo('30.19');
        dump($result);


        $dijkstra = new Dijkstra($map, '39.19', 0b111);
        $result = $dijkstra->shortestPathTo('40.23');
        dump($result);

        $debug = [];
        foreach ($data as $value) {
            [$coordinates, $mouvement, $borders] = explode('_', $value);
            [$x, $y] = explode('.', $coordinates);
            $debug[$y][$x] = $mouvement === '-1' ? 'x' : 'o';
        }
        foreach ($result as $value) {
            [$x, $y] = explode('.', $value['node_identifier']);
            $debug[$y][$x] = 'v';
        }
        $debugFs = fopen(__DIR__ . "/../../data/map/debug.txt", 'w');
        foreach ($debug as $line) {
            fputs($debugFs, implode(' ', $line). "\n");
        }
        fclose($debugFs);

        return 0;
    }
}
