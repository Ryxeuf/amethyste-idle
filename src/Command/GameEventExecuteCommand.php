<?php

namespace App\Command;

use App\GameEngine\Event\GameEventExecutor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:game-event:execute',
    description: 'Scanne et active/complète les GameEvents planifiés',
)]
class GameEventExecuteCommand extends Command
{
    public function __construct(
        private readonly GameEventExecutor $executor,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $result = $this->executor->execute();

        if ($result['activated'] > 0 || $result['completed'] > 0) {
            $io->success(sprintf(
                '%d événement(s) activé(s), %d terminé(s), %d récurrence(s) créée(s).',
                $result['activated'],
                $result['completed'],
                $result['recurring'],
            ));
        }

        return Command::SUCCESS;
    }
}
