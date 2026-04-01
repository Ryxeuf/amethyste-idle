<?php

namespace App\Command;

use App\GameEngine\Validation\GameStateValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:game:validate',
    description: 'Validate game state consistency in the database (orphaned fights, items, out-of-bounds players, etc.)',
)]
class GameStateValidateCommand extends Command
{
    public function __construct(
        private readonly GameStateValidator $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('check', null, InputOption::VALUE_REQUIRED, 'Run only a specific check (orphaned_fights, fights_without_alive_mobs, orphaned_items, players_out_of_bounds, players_in_stale_fights)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Game State Validation');

        $singleCheck = $input->getOption('check');

        if ($singleCheck !== null) {
            $results = $this->runSingleCheck($singleCheck);
            if ($results === null) {
                $io->error(sprintf('Unknown check: "%s"', $singleCheck));

                return Command::FAILURE;
            }
            $results = [$singleCheck => $results];
        } else {
            $results = $this->validator->validateAll();
        }

        $totalAnomalies = 0;

        foreach ($results as $checkName => $anomalies) {
            $count = count($anomalies);
            $totalAnomalies += $count;

            if ($count === 0) {
                $io->writeln(sprintf('  <info>OK</info>  %s', $this->formatCheckName($checkName)));
            } else {
                $io->writeln(sprintf('  <error>FAIL</error>  %s — %d anomalie(s)', $this->formatCheckName($checkName), $count));
                foreach ($anomalies as $anomaly) {
                    $io->writeln(sprintf('         - %s', $anomaly));
                }
            }
        }

        $io->newLine();

        if ($totalAnomalies === 0) {
            $io->success('Aucune anomalie detectee. L\'etat du jeu est coherent.');

            return Command::SUCCESS;
        }

        $io->error(sprintf('%d anomalie(s) detectee(s). Verifier et corriger manuellement.', $totalAnomalies));

        return Command::FAILURE;
    }

    /**
     * @return list<string>|null
     */
    private function runSingleCheck(string $checkName): ?array
    {
        return match ($checkName) {
            'orphaned_fights' => $this->validator->checkOrphanedFights(),
            'fights_without_alive_mobs' => $this->validator->checkActiveFightsWithoutAliveMobs(),
            'orphaned_items' => $this->validator->checkOrphanedItems(),
            'players_out_of_bounds' => $this->validator->checkPlayersOutOfBounds(),
            'players_in_stale_fights' => $this->validator->checkPlayersInStaleFights(),
            default => null,
        };
    }

    private function formatCheckName(string $checkName): string
    {
        return str_replace('_', ' ', ucfirst($checkName));
    }
}
