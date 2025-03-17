<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Posiiton du joueur 85 - 35
        $result = $this->cellSearchEngine->find([
            'q' => '*',
            'filter_by' => 'x:[75..95] && y:[25..45]',
        ]);
        dd($result);

        return Command::SUCCESS;
    }
}
