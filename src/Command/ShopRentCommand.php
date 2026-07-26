<?php

namespace App\Command;

use App\GameEngine\Shop\ShopRentService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prelevement des loyers d'echoppe (ECO-11).
 *
 * Le loyer n'est pas qu'un gold sink : c'est le **regulateur du nombre
 * d'echoppes**. Sans lui, la rue se remplirait de vitrines mortes ouvertes puis
 * abandonnees. La caisse paie en premier, la bourse ensuite : une echoppe qui
 * vend s'entretient seule.
 */
#[AsCommand(
    name: 'app:shop:rent',
    description: 'Preleve les loyers des echoppes joueur echues',
)]
class ShopRentCommand extends Command
{
    public function __construct(
        private readonly ShopRentService $rentService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les prelevements sans les appliquer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ((bool) $input->getOption('dry-run')) {
            $io->note('Mode dry-run : aucun prelevement ne sera applique.');

            return Command::SUCCESS;
        }

        $report = $this->rentService->collectDueRents();

        if ($report['charged'] > 0 || $report['closed'] > 0) {
            $io->success(sprintf(
                '%d loyer(s) preleve(s), %d echoppe(s) rideau baisse (rien n\'est confisque).',
                $report['charged'],
                $report['closed'],
            ));
        }

        return Command::SUCCESS;
    }
}
