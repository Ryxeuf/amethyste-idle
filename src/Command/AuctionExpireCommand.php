<?php

namespace App\Command;

use App\GameEngine\Auction\AuctionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:auction:expire',
    description: 'Expire les annonces de l\'hotel des ventes dont la duree est ecoulee',
)]
class AuctionExpireCommand extends Command
{
    public function __construct(
        private readonly AuctionManager $auctionManager,
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
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Mode dry-run : aucune modification ne sera appliquee.');
        }

        if ($loop !== false) {
            $interval = (int) ($loop ?: 300);
            $io->info("Mode boucle active (intervalle: {$interval}s)");

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
            $io->text('[dry-run] Verification des annonces expirees...');

            return;
        }

        $count = $this->auctionManager->expireListings();

        if ($count > 0) {
            $io->success(sprintf('%d annonce(s) expiree(s), items retournes aux vendeurs.', $count));
        }
    }
}
