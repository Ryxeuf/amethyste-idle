<?php

namespace App\Command;

use App\Entity\App\Player;
use App\GameEngine\Retention\WeeklyCommissionGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Tirage des commissions hebdomadaires personnelles (RET-02).
 *
 * Planifiee le lundi a 00h02, **apres** la rotation des defis de guilde : les
 * deux rendez-vous s'ouvrent le meme lundi, le collectif d'abord.
 *
 * Idempotente : un joueur qui a deja sa commission de la semaine est saute. La
 * rejouer ne redistribue rien — c'est ce qui empeche `--force` de devenir un
 * reroll deguise.
 */
#[AsCommand(
    name: 'app:weekly-commission:rotate',
    description: 'Tire la commission de la semaine de chaque personnage actif',
)]
class WeeklyCommissionRotateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WeeklyCommissionGenerator $generator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', null, InputOption::VALUE_REQUIRED, 'Fenetre d\'activite consideree, en jours', '30');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days = max(1, (int) $input->getOption('days'));
        $since = new \DateTimeImmutable(sprintf('-%d days', $days));

        // Seuls les personnages **actifs** recoivent une commission. En tirer
        // une pour un compte dormant depuis six mois ne retient personne et
        // remplit la table pour rien.
        /** @var list<Player> $players */
        $players = $this->entityManager->getRepository(Player::class)
            ->createQueryBuilder('p')
            ->where('p.lastActivityAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();

        if ($players === []) {
            $io->note('Aucun personnage actif sur la fenetre : rien a tirer.');

            return Command::SUCCESS;
        }

        $report = $this->generator->generateFor($players);

        $io->info(sprintf(
            '%d commission(s) creee(s), %d deja en place, %d expiree(s) des semaines passees.',
            $report['created'],
            $report['skipped'],
            $report['expired'],
        ));

        if ($report['unassigned'] > 0) {
            $io->warning(sprintf(
                '%d commission(s) sans zone de livraison : aucun foyer disponible dans le monde.',
                $report['unassigned'],
            ));
        }

        return Command::SUCCESS;
    }
}
