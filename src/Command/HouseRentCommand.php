<?php

namespace App\Command;

use App\GameEngine\Housing\HousingManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prelevement des loyers d'entretien (tache 129, HOU-04).
 *
 * Le loyer est un **gold sink recurrent** (GAME_PRINCIPLES §4.7). Le
 * prelevement est automatique tant que la bourse suit : un joueur solvable ne
 * doit pas perdre l'usage de sa demeure pour avoir oublie un bouton.
 */
#[AsCommand(
    name: 'app:house:rent',
    description: 'Preleve les loyers d\'entretien des demeures echues',
)]
class HouseRentCommand extends Command
{
    public function __construct(
        private readonly HousingManager $housingManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('loop', null, InputOption::VALUE_OPTIONAL, 'Boucle continue avec intervalle en secondes', false)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les prelevements sans les appliquer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $loop = $input->getOption('loop');
        $dryRun = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Mode dry-run : aucun prelevement ne sera applique.');
        }

        if (false !== $loop) {
            $interval = (int) ($loop ?: 3600);
            $io->info(sprintf('Mode boucle active (intervalle: %ds)', $interval));

            // @phpstan-ignore while.alwaysTrue
            while (true) {
                $this->processRents($io, $dryRun);
                sleep($interval);
            }
        }

        $this->processRents($io, $dryRun);

        return Command::SUCCESS;
    }

    private function processRents(SymfonyStyle $io, bool $dryRun): void
    {
        if ($dryRun) {
            $io->text('[dry-run] Verification des loyers echus...');

            return;
        }

        $report = $this->housingManager->collectDueRents();

        if ($report['charged'] > 0 || $report['unpaid'] > 0) {
            $io->success(sprintf(
                '%d loyer(s) preleve(s), %d demeure(s) en arriere (dormantes, rien n\'est confisque).',
                $report['charged'],
                $report['unpaid'],
            ));
        }
    }
}
