<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\SearchEngine\CellSearchEngine;

#[AsCommand(name: 'app:debug')]
class DebugCommand extends Command
{
    public function __construct(
        private readonly CellSearchEngine $cellSearchEngine,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('map', 'm', InputOption::VALUE_REQUIRED, 'Map ID', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mapId = (int) $input->getOption('map');
        // Position du joueur 85 - 35, viewport x:[75..95] y:[25..45]
        $cells = $this->cellSearchEngine->getMapCells(85, 35, $mapId);
        dump($cells);

        return Command::SUCCESS;
    }
}
