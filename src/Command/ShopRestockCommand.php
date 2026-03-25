<?php

namespace App\Command;

use App\Entity\App\Pnj;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:shop:restock',
    description: 'Restock les boutiques PNJ dont l\'intervalle de restock est écoulé',
)]
class ShopRestockCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('loop', null, InputOption::VALUE_OPTIONAL, 'Boucle continue avec intervalle en secondes', false)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les restocks sans les appliquer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $loop = $input->getOption('loop');
        $dryRun = $input->getOption('dry-run');

        if ($loop !== false) {
            $interval = (int) ($loop ?: 60);
            $io->info("Mode boucle activé (intervalle: {$interval}s)");

            // @phpstan-ignore while.alwaysTrue
            while (true) {
                $this->processRestock($io, $dryRun);
                sleep($interval);
            }
        }

        $this->processRestock($io, $dryRun);

        return Command::SUCCESS;
    }

    private function processRestock(SymfonyStyle $io, bool $dryRun): void
    {
        $now = new \DateTimeImmutable();

        $pnjs = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Pnj::class, 'p')
            ->where('p.shopStock IS NOT NULL')
            ->getQuery()
            ->getResult();

        $totalRestocked = 0;

        /** @var Pnj $pnj */
        foreach ($pnjs as $pnj) {
            $restocked = $pnj->restockItems($now);
            if ($restocked > 0) {
                if ($dryRun) {
                    $io->text(sprintf('  [dry-run] %s : %d item(s) restockés', $pnj->getName(), $restocked));
                } else {
                    $this->entityManager->persist($pnj);
                }
                $totalRestocked += $restocked;
            }
        }

        if ($totalRestocked > 0) {
            if (!$dryRun) {
                $this->entityManager->flush();
            }
            $io->success(sprintf('%d item(s) restocké(s) dans %d boutique(s)', $totalRestocked, \count($pnjs)));
        }
    }
}
