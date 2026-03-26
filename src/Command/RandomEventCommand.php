<?php

namespace App\Command;

use App\GameEngine\Event\RandomEventGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:events:random',
    description: 'Tente de generer un evenement aleatoire dans le monde',
)]
class RandomEventCommand extends Command
{
    public function __construct(
        private readonly RandomEventGenerator $generator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('chance', null, InputOption::VALUE_REQUIRED, 'Probabilite de creation (0-100)', '30')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forcer la creation (ignore la probabilite)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $force = $input->getOption('force');
        $chance = $force ? 100 : (int) $input->getOption('chance');

        $event = $this->generator->tryGenerate($chance);

        if ($event !== null) {
            $io->success(sprintf(
                'Evenement aleatoire cree : "%s" (%s) — expire a %s',
                $event->getName(),
                $event->getTypeLabel(),
                $event->getEndsAt()->format('H:i'),
            ));
        } else {
            $io->note('Aucun evenement cree (probabilite non atteinte ou evenement deja actif).');
        }

        return Command::SUCCESS;
    }
}
