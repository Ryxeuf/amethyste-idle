<?php

namespace App\Command;

use App\GameEngine\Economy\WeeklyOutcropSelector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Tirage de l'Affleurement de la semaine (RET-06).
 *
 * Planifiee le lundi a 00h06, apres les autres rendez-vous hebdomadaires : tous
 * lisent la meme clef de semaine ISO (contrat RET-07).
 *
 * **La sortie ne nomme jamais le filon tire.** Ce n'est pas une coquetterie :
 * les journaux d'exploitation finissent par etre lus, cites, recopies — et un
 * affleurement annonce devient une ruee au lieu d'une decouverte. La commande
 * dit qu'elle a tire, pas ce qu'elle a tire.
 */
#[AsCommand(
    name: 'app:weekly-outcrop:rotate',
    description: 'Tire l\'affleurement de la semaine (sans le nommer)',
)]
class WeeklyOutcropRotateCommand extends Command
{
    public function __construct(
        private readonly WeeklyOutcropSelector $selector,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $report = $this->selector->select();

        if ($report['skipped']) {
            $io->note('L\'affleurement de la semaine est deja tire.');

            return Command::SUCCESS;
        }

        if ($report['selected'] === null) {
            $io->warning('Aucun filon eligible : aucun affleurement cette semaine.');

            return Command::SUCCESS;
        }

        $io->success(sprintf('Affleurement tire parmi %d filon(s) eligible(s).', $report['candidates']));

        return Command::SUCCESS;
    }
}
