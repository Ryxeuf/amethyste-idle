<?php

namespace App\Command;

use App\Entity\App\ObjectLayer;
use App\Event\Map\SpotAvailableEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'app:harvest:respawn',
    description: 'Vérifie et marque les spots de récolte dont le cooldown est expiré',
)]
class HarvestRespawnCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('loop', null, InputOption::VALUE_OPTIONAL, 'Boucle continue avec intervalle en secondes', false)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les spots à respawn sans les modifier');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $loop = $input->getOption('loop');
        $dryRun = $input->getOption('dry-run');

        if ($loop !== false) {
            $interval = (int) ($loop ?: 10);
            $io->info("Mode boucle activé (intervalle: {$interval}s)");

            while (true) {
                $this->processRespawns($io, $dryRun);
                sleep($interval);
            }
        }

        $this->processRespawns($io, $dryRun);

        return Command::SUCCESS;
    }

    private function processRespawns(SymfonyStyle $io, bool $dryRun): void
    {
        $spots = $this->entityManager->getRepository(ObjectLayer::class)->findBy([
            'type' => ObjectLayer::TYPE_HARVEST_SPOT,
        ]);

        $respawned = 0;

        foreach ($spots as $spot) {
            if ($spot->getUsedAt() === null) {
                continue;
            }

            if (!$spot->isAvailable()) {
                continue;
            }

            // Le spot a expiré son cooldown → le marquer comme disponible
            if ($dryRun) {
                $io->text("  [dry-run] {$spot->getSlug()} serait respawné");
            } else {
                $spot->setUsedAt(null);
                $this->entityManager->persist($spot);
                $this->eventDispatcher->dispatch(
                    new SpotAvailableEvent($spot),
                    SpotAvailableEvent::NAME
                );
            }
            ++$respawned;
        }

        if ($respawned > 0) {
            if (!$dryRun) {
                $this->entityManager->flush();
            }
            $io->success("{$respawned} spot(s) respawné(s)");
        }
    }
}
