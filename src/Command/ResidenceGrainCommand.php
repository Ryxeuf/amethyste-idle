<?php

namespace App\Command;

use App\GameEngine\Housing\ResidenceGrain;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Les cheminees du jour (FOY-20).
 *
 * Idempotente : la cle de jour vit sur la demeure, donc une relance ne depose
 * pas deux fois. La regle du planificateur veut que *rien ne soit rejoue* — un
 * declenchement manque est perdu, et c'est acceptable ici : un grain de
 * residence manque ne se rattrape pas, il ne se double pas non plus.
 */
#[AsCommand(
    name: 'app:house:residence-grain',
    description: 'Dépose le grain de résidence des demeures habitées au foyer de leur zone',
)]
class ResidenceGrainCommand extends Command
{
    public function __construct(
        private readonly ResidenceGrain $grain,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $result = $this->grain->burnHearths();

        $io->writeln(sprintf(
            '%d cheminée(s) ont fumé, %d ignorée(s) (loyer en arrière ou déjà déposé).',
            $result['burned'],
            $result['skipped'],
        ));

        return Command::SUCCESS;
    }
}
