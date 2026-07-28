<?php

namespace App\Command;

use App\GameEngine\Settlement\SettlementTickService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Tick quotidien des foyers (FOY-03).
 *
 * Planifiee apres le tick de saison : la maree se clot d'abord, les foyers
 * s'amincissent ensuite. L'inverse ferait descendre un foyer juste avant que la
 * saison ne compte ce qu'il valait.
 *
 * Idempotente a la journee — la rejouer ne retire rien de plus. `--force` sert
 * au diagnostic : il recalcule rang et type sans attendre le jour suivant, ce
 * qui permet de verifier un chiffrage sans avancer l'horloge.
 */
#[AsCommand(
    name: 'app:settlement:tick',
    description: 'Fait decroitre les foyers, recalcule leur rang et installe leur type',
)]
class SettlementTickCommand extends Command
{
    public function __construct(
        private readonly SettlementTickService $tickService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Recalcule rang et type meme si le jour n\'a pas change');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $report = $this->tickService->tick(null, (bool) $input->getOption('force'));

        if ($report['processed'] === 0) {
            $io->note(sprintf('Aucun foyer a traiter (%d deja a jour).', $report['skipped']));

            return Command::SUCCESS;
        }

        $io->info(sprintf(
            '%d foyer(s) traite(s), %d grain(s) perdu(s) par decroissance.',
            $report['processed'],
            $report['decayed'],
        ));

        if ($report['promoted'] > 0 || $report['demoted'] > 0) {
            $io->info(sprintf('%d montee(s) de rang, %d descente(s).', $report['promoted'], $report['demoted']));
        }

        if ($report['typed'] > 0) {
            $io->info(sprintf('%d foyer(s) ont pris ou change d\'identite.', $report['typed']));
        }

        return Command::SUCCESS;
    }
}
