<?php

namespace App\Command;

use App\Entity\App\Player;
use App\GameEngine\Zone\PlayerZoneSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Repare la position des joueurs restes sans zone.
 *
 * Un personnage cree avant que la zone devienne la position de reference — ou
 * pose sur une carte sans zone de rattachement, comme la carte de test — n'a
 * pas de `currentZone`. L'ecran de zone affiche alors « Position inconnue » et
 * **aucune** action n'est possible : ni explorer, ni chasser, ni voyager. La
 * migration `Version20260724PlayerCurrentZone` ne rattrapait que les joueurs
 * dont la carte portait une zone.
 *
 * La resolution est celle du jeu (`PlayerZoneSynchronizer::resolveOrAssign`) :
 * carte de rattachement, puis hub, puis zone de depart plausible.
 */
#[AsCommand(
    name: 'app:player:backfill-zone',
    description: 'Attribue une zone de depart aux joueurs sans position (pivot PBBG)',
)]
class PlayerZoneBackfillCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerZoneSynchronizer $playerZoneSynchronizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Liste les joueurs concernes sans rien ecrire');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        /** @var list<Player> $orphans */
        $orphans = $this->entityManager->getRepository(Player::class)
            ->createQueryBuilder('p')
            ->andWhere('p.currentZone IS NULL')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        if ([] === $orphans) {
            $io->success('Tous les joueurs ont une zone.');

            return Command::SUCCESS;
        }

        $rows = [];
        $unresolved = 0;
        foreach ($orphans as $player) {
            $zone = $this->playerZoneSynchronizer->resolveOrAssign($player);
            if (null === $zone) {
                ++$unresolved;
            }
            $rows[] = [$player->getName(), $zone?->getName() ?? '— aucune zone active en base'];
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->title(sprintf('Joueurs sans zone : %d%s', \count($orphans), $dryRun ? ' (dry-run)' : ''));
        $io->table(['Joueur', 'Zone attribuee'], $rows);

        if ($unresolved > 0) {
            $io->warning(sprintf(
                '%d joueur(s) sans zone attribuable : importer le graphe de zones (app:zone:import) puis relancer.',
                $unresolved,
            ));

            return Command::FAILURE;
        }

        $io->success($dryRun ? 'Aucune ecriture (dry-run).' : 'Positions reparees.');

        return Command::SUCCESS;
    }
}
