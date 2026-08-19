<?php

namespace App\Command;

use App\GameEngine\Repertoire\RepertoireUnlocker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Le monde retrouve ce que ses lectures lui ont merite (REP-03).
 *
 * Une commande de calendrier plutot qu'un crochet sur la lecture : le seuil se
 * lit sur l'histoire entiere du monde et sur sa population effective, deux
 * mesures qu'on ne recalcule pas a chaque materia lue.
 *
 * **Elle est idempotente**, ce que la regle du planificateur exige (« rien n'est
 * rejoue ») : un declenchement manque pendant un redemarrage n'est pas
 * rattrape, mais le suivant retrouve tout ce qui etait du — la commande boucle
 * jusqu'a ce que le seuil ne soit plus franchi.
 */
#[AsCommand(
    name: 'app:repertoire:unlock',
    description: 'Retrouve les gestes que les lectures du serveur ont mérités, et les annonce au journal de monde',
)]
class RepertoireUnlockCommand extends Command
{
    public function __construct(
        private readonly RepertoireUnlocker $unlocker,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $recovered = $this->unlocker->unlockDue();

        if ($recovered === []) {
            $io->writeln(sprintf(
                'Rien de retrouvé — %d lecture(s) versée(s), seuil suivant à %d.',
                $this->unlocker->totalReadings(),
                $this->unlocker->thresholdFor(1),
            ));

            return Command::SUCCESS;
        }

        foreach ($recovered as $key) {
            $io->success(sprintf('Geste retrouvé : %s', $key));
        }

        return Command::SUCCESS;
    }
}
