<?php

namespace App\Command;

use App\GameEngine\World\PnjRoutineService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:pnj:routine',
    description: 'Déplace les PNJ selon leurs horaires de routine (PnjSchedule)',
)]
class PnjRoutineCommand extends Command
{
    public function __construct(
        private readonly PnjRoutineService $pnjRoutineService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $result = $this->pnjRoutineService->tick();

        if ($result['moved'] > 0) {
            $io->success(sprintf(
                '%d PNJ déplacé(s) sur %d schedule(s) pour cette heure.',
                $result['moved'],
                $result['total'],
            ));
        }

        return Command::SUCCESS;
    }
}
