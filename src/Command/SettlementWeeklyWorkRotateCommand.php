<?php

namespace App\Command;

use App\GameEngine\Settlement\SettlementWeeklyWorkGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Ouverture des chantiers de la semaine (RET-05).
 *
 * Planifiee le lundi a 00h04, **apres** les defis de guilde et les commissions
 * personnelles : les trois rendez-vous s'ouvrent le meme lundi, et tous lisent
 * la meme clef de semaine ISO. C'est le contrat RET-07 — une seule rotation, pas
 * cinq horloges qui derivent.
 *
 * Idempotente : un foyer qui a deja son chantier de la semaine est saute. La
 * rejouer ne reinitialise rien, et n'efface donc jamais l'effort en cours.
 */
#[AsCommand(
    name: 'app:settlement-work:rotate',
    description: 'Ouvre le chantier de la semaine de chaque foyer',
)]
class SettlementWeeklyWorkRotateCommand extends Command
{
    public function __construct(
        private readonly SettlementWeeklyWorkGenerator $generator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $report = $this->generator->generate();

        if ($report['without_demand'] > 0) {
            $io->note(sprintf('%d foyer(s) sans demande declaree pour leur type : aucun chantier ouvert.', $report['without_demand']));
        }

        $io->success(sprintf(
            '%d chantier(s) ouvert(s), %d deja en place.',
            $report['created'],
            $report['skipped'],
        ));

        return Command::SUCCESS;
    }
}
