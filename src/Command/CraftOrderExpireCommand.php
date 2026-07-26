<?php

namespace App\Command;

use App\GameEngine\Crafting\CraftOrderManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Restitution automatique de l'escrow des commandes echues (ECO-09).
 *
 * Pendant du `app:auction:expire` de l'hotel des ventes. Sans cette commande,
 * une commande que personne ne prend immobilise materiaux et Gils sans limite
 * de temps : l'escrow n'avait aucune sortie automatique.
 */
#[AsCommand(
    name: 'app:craft-order:expire',
    description: 'Restitue materiaux et commission des commandes de craft echues',
)]
class CraftOrderExpireCommand extends Command
{
    public function __construct(
        private readonly CraftOrderManager $orderManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('loop', null, InputOption::VALUE_OPTIONAL, 'Boucle continue avec intervalle en secondes', false)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les expirations sans les appliquer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $loop = $input->getOption('loop');
        $dryRun = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Mode dry-run : aucune modification ne sera appliquee.');
        }

        if (false !== $loop) {
            $interval = (int) ($loop ?: 300);
            $io->info(sprintf('Mode boucle active (intervalle: %ds)', $interval));

            // @phpstan-ignore while.alwaysTrue
            while (true) {
                $this->processExpire($io, $dryRun);
                sleep($interval);
            }
        }

        $this->processExpire($io, $dryRun);

        return Command::SUCCESS;
    }

    private function processExpire(SymfonyStyle $io, bool $dryRun): void
    {
        if ($dryRun) {
            $io->text('[dry-run] Verification des commandes echues...');

            return;
        }

        $report = $this->orderManager->expireOrders();

        if ($report['released'] > 0) {
            $io->success(sprintf(
                '%d commande(s) echue(s), escrow rendu au commanditaire (%d artisan(s) sanctionne(s) pour non-livraison).',
                $report['released'],
                $report['penalised'],
            ));
        }
    }
}
