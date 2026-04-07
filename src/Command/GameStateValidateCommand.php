<?php

namespace App\Command;

use App\GameEngine\Debug\GameStateValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:game:validate',
    description: 'Validate game state coherence in the database',
)]
class GameStateValidateCommand extends Command
{
    private const LABELS = [
        'ghost_fights' => 'Joueurs references a un combat inexistant ou termine',
        'fights_without_living_mobs' => 'Combats actifs sans mobs vivants',
        'orphaned_player_items' => 'PlayerItems orphelins (item manquant)',
        'stale_active_quests' => 'Quetes actives deja completees',
        'players_out_of_bounds' => 'Joueurs hors limites de la carte',
        'negative_domain_experience' => 'Experience de domaine incoherente (used > total ou valeurs negatives)',
        'equipped_items_wrong_location' => 'Items equipes hors inventaire joueur (vault, mob ou sans inventaire)',
    ];

    public function __construct(
        private readonly GameStateValidator $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('check', null, InputOption::VALUE_REQUIRED, 'Run only a specific check (' . implode(', ', array_keys(self::LABELS)) . ')');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Game State Validation');

        $singleCheck = $input->getOption('check');

        if ($singleCheck !== null) {
            if (!isset(self::LABELS[$singleCheck])) {
                $io->error(sprintf('Unknown check: "%s". Available: %s', $singleCheck, implode(', ', array_keys(self::LABELS))));

                return Command::FAILURE;
            }
            $results = [$singleCheck => $this->validator->runCheck($singleCheck)];
        } else {
            $results = $this->validator->validateAll();
        }

        $totalAnomalies = 0;

        foreach ($results as $checkName => $anomalies) {
            $label = self::LABELS[$checkName] ?? $checkName;
            $count = \count($anomalies);
            $totalAnomalies += $count;

            if ($count === 0) {
                $io->writeln(sprintf('  <info>OK</info>  %s', $label));
            } else {
                $io->writeln(sprintf('  <error>%d</error>  %s', $count, $label));
                foreach ($anomalies as $anomaly) {
                    $io->writeln(sprintf('       - %s', $anomaly));
                }
            }
        }

        $io->newLine();

        if ($totalAnomalies === 0) {
            $io->success('Aucune anomalie detectee. Etat du jeu coherent.');

            return Command::SUCCESS;
        }

        $io->warning(sprintf('%d anomalie(s) detectee(s).', $totalAnomalies));

        return Command::FAILURE;
    }
}
