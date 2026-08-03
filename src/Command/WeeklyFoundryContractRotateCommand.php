<?php

namespace App\Command;

use App\GameEngine\Reputation\FoundryContractManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * La rotation du contrat de la Fonderie (FAC-05).
 *
 * Meme lundi que toutes les briques hebdomadaires (RET-07 : un seul point de
 * rotation), minute distincte. Idempotente — la ligne de la semaine existe,
 * elle est rendue telle quelle : rejouer une rotation n'est jamais un reroll.
 */
#[AsCommand(
    name: 'app:weekly-foundry-contract:rotate',
    description: 'Tire (ou retrouve) le contrat d\'approvisionnement de la Fonderie de la semaine',
)]
class WeeklyFoundryContractRotateCommand extends Command
{
    public function __construct(
        private readonly FoundryContractManager $contractManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $contract = $this->contractManager->rotate();

        $io->success(sprintf(
            'Contrat %s : %d x %s a %d gils/unite (+%d essence), reference marche %d.',
            $contract->getWeekKey(),
            $contract->getVolume(),
            $contract->getItemSlug(),
            $contract->getGilsPerUnit(),
            $contract->getEssence(),
            $contract->getReferencePrice(),
        ));

        return Command::SUCCESS;
    }
}
