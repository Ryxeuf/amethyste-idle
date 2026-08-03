<?php

namespace App\Command;

use App\Security\VerificationReminder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Les rappels de verification d'e-mail : J+1, J+3, puis silence (ONB-04).
 *
 * Planifiee une fois par jour. Idempotente a l'echelle du jour : le compteur
 * de rappels sur le compte fait foi, rejouer la commande ne renvoie rien a
 * qui a deja recu son palier.
 */
#[AsCommand(
    name: 'app:verification:remind',
    description: 'Envoie les rappels de verification d\'e-mail dus (J+1, J+3, puis silence)',
)]
class VerificationRemindCommand extends Command
{
    public function __construct(
        private readonly VerificationReminder $reminder,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sent = $this->reminder->remindDueAccounts();

        $io->success(sprintf('Rappels envoyes : %d premier(s) (J+1), %d second(s) (J+3).', $sent[1], $sent[2]));

        return Command::SUCCESS;
    }
}
