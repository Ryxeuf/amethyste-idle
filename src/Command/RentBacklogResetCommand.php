<?php

namespace App\Command;

use App\GameEngine\Economy\RentBacklogResetter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Efface l'arriere de loyers avant l'execution du planificateur
 * (tache 134, jalon F.0).
 *
 * Sans elle, brancher le planificateur prelevererait une semaine de loyer par
 * jour a chaque proprietaire jusqu'a rattraper un retard que personne n'a
 * contracte — voir `RentBacklogResetter` pour le detail du mecanisme.
 *
 * Elle n'est plus une etape manuelle a ne pas oublier :
 * `frankenphp/scheduler-entrypoint.sh` l'appelle avec `--min-periods=2` avant
 * chaque consommation du calendrier. Lancee a la main sans option, elle efface
 * tout l'arriere echu, comme avant. Voir le mode d'emploi dans
 * `docs/LOAD_TESTING_BOTTLENECKS.md` § F.0.
 */
#[AsCommand(
    name: 'app:economy:rent-backlog-reset',
    description: 'Efface l\'arriere de loyers accumule pendant que le planificateur ne tournait pas (jalon F.0)',
)]
class RentBacklogResetCommand extends Command
{
    public function __construct(
        private readonly RentBacklogResetter $resetter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche l\'arriere sans rien modifier');
        $this->addOption(
            'min-periods',
            null,
            InputOption::VALUE_REQUIRED,
            'N\'efface que les echeances en retard d\'au moins N periodes de 7 jours (0 = tout l\'arriere)',
            '0',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $minPeriods = (int) $input->getOption('min-periods');

        if ($minPeriods < 0) {
            $io->error('--min-periods attend un entier positif ou nul.');

            return Command::INVALID;
        }

        $report = $this->resetter->inspect(null, $minPeriods);

        if ($report->isEmpty()) {
            $io->success($minPeriods > 0
                ? sprintf('Aucune echeance en retard d\'au moins %d periode(s) : rien a faire.', $minPeriods)
                : 'Aucune echeance de loyer echue : rien a faire. Le planificateur peut etre branche.');

            return Command::SUCCESS;
        }

        $io->definitionList(
            ['Demeures en retard' => (string) $report->houseCount],
            ['Echoppes en retard' => (string) $report->shopCount],
            ['Pire retard (demeure)' => sprintf('%d periode(s) de 7 jours', $report->worstHousePeriods)],
            ['Pire retard (echoppe)' => sprintf('%d periode(s) de 7 jours', $report->worstShopPeriods)],
        );

        $io->warning(sprintf(
            "Sans remise a zero, brancher le planificateur declencherait jusqu'a %d jours de prelevements\n"
            . "quotidiens consecutifs : chaque execution ne rattrape qu'une periode.",
            $report->dailyChargesAvoided(),
        ));

        if ($dryRun) {
            $io->note('Mode simulation : aucune echeance modifiee.');

            return Command::SUCCESS;
        }

        $this->resetter->reset(null, $minPeriods);

        $io->success(sprintf(
            'Arriere efface : %d demeure(s) et %d echoppe(s) repoussees a maintenant + 7 jours. '
            . 'Le planificateur peut etre branche.',
            $report->houseCount,
            $report->shopCount,
        ));

        return Command::SUCCESS;
    }
}
