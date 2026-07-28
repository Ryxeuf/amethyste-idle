<?php

namespace App\Command;

use App\Entity\App\WeeklyChallenge;
use App\GameEngine\Guild\WeeklyChallengeRotator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rotation hebdomadaire des defis de guilde (RET-01).
 *
 * Planifiee le lundi a 00h00, **avant** la chaine de minuit quotidienne : la
 * semaine s'ouvre d'abord, les quetes du jour et le tick de saison suivent.
 */
#[AsCommand(
    name: 'app:weekly-challenge:rotate',
    description: 'Clot la semaine de defis ecoulee et ouvre la suivante',
)]
class WeeklyChallengeRotateCommand extends Command
{
    public function __construct(
        private readonly WeeklyChallengeRotator $rotator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Rejoue la rotation meme si la semaine a deja ete traitee');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $result = $this->rotator->rotate(null, (bool) $input->getOption('force'));

        if (!$result->rotated) {
            $io->note($result->reason);

            return Command::SUCCESS;
        }

        if ($result->closedChallenges > 0) {
            $io->info(sprintf(
                'Semaine ecoulee cloturee : %d defi(s), %d progression(s) reglee(s), %d point(s) d\'influence verses.',
                $result->closedChallenges,
                $result->settledProgress,
                $result->awardedBonusPoints,
            ));
        }

        if ($result->createdChallenges > 0) {
            $io->info(sprintf('%d defi(s) cree(s) depuis le pool declaratif.', $result->createdChallenges));
        }

        $titles = array_map(
            static fn (WeeklyChallenge $challenge): string => sprintf('%s (%s, cible %d)', $challenge->getTitle(), $challenge->getActivityType()->label(), $challenge->getTarget()),
            $result->activeChallenges,
        );

        $io->success(sprintf(
            'Semaine %s ouverte — %d defi(s) actif(s)%s',
            $result->weekKey,
            \count($result->activeChallenges),
            $titles !== [] ? ":\n  " . implode("\n  ", $titles) : '.',
        ));

        return Command::SUCCESS;
    }
}
