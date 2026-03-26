<?php

namespace App\Command;

use App\GameEngine\Event\InvasionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:invasion:tick',
    description: 'Avance les vagues des invasions actives (à exécuter toutes les minutes)',
)]
class InvasionTickCommand extends Command
{
    public function __construct(
        private readonly InvasionManager $invasionManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $wavesSpawned = $this->invasionManager->tick();

        if ($wavesSpawned > 0) {
            $io->success(sprintf('%d vague(s) d\'invasion spawnée(s).', $wavesSpawned));
        }

        return Command::SUCCESS;
    }
}
