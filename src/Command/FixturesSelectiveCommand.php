<?php

namespace App\Command;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

#[AsCommand(
    name: 'app:fixtures:load-selective',
    description: 'Load specific fixture groups (items, monsters, spells, etc.) without full database reset',
)]
class FixturesSelectiveCommand extends Command
{
    private const GROUPS = [
        'items' => ['ItemFixtures'],
        'monsters' => ['MonsterFixtures', 'MonsterItemFixtures'],
        'spells' => ['SpellFixtures'],
        'skills' => ['Game\\SkillFixtures'],
        'domains' => ['DomainFixtures'],
        'mobs' => ['MobFixtures'],
        'pnjs' => ['PnjFixtures'],
        'quests' => ['QuestFixtures', 'PlayerQuestFixtures'],
        'maps' => ['MapFixtures', 'AreaFixtures', 'ObjectLayerFixtures'],
        'players' => ['UserFixtures', 'PlayerFixtures', 'InventoryFixtures'],
        'achievements' => ['AchievementFixtures'],
        'slots' => ['SlotFixtures'],
    ];

    /** @var iterable<Fixture> */
    private readonly iterable $fixtures;

    public function __construct(
        private readonly EntityManagerInterface $em,
        #[TaggedIterator('doctrine.fixture.orm')]
        iterable $fixtures,
    ) {
        $this->fixtures = $fixtures;
        parent::__construct();
    }

    protected function configure(): void
    {
        $groupNames = implode(', ', array_keys(self::GROUPS));
        $this
            ->addArgument('group', InputArgument::REQUIRED, 'Fixture group to load (' . $groupNames . ')')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List available groups')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Purge target tables before loading (WARNING: deletes existing data)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('list')) {
            $io->title('Groupes de fixtures disponibles');
            $rows = [];
            foreach (self::GROUPS as $group => $classes) {
                $rows[] = [$group, implode(', ', $classes)];
            }
            $io->table(['Groupe', 'Fixtures'], $rows);

            return Command::SUCCESS;
        }

        $group = $input->getArgument('group');

        if (!isset(self::GROUPS[$group])) {
            $io->error('Groupe inconnu : "' . $group . '". Groupes disponibles : ' . implode(', ', array_keys(self::GROUPS)));

            return Command::FAILURE;
        }

        $fixtureClassSuffixes = self::GROUPS[$group];
        $selectedFixtures = [];

        foreach ($this->fixtures as $fixture) {
            $className = get_class($fixture);
            foreach ($fixtureClassSuffixes as $suffix) {
                if (str_ends_with($className, $suffix)) {
                    $selectedFixtures[] = $fixture;
                    $io->info('Fixture trouvee : ' . $className);
                    break;
                }
            }
        }

        if (empty($selectedFixtures)) {
            $io->warning('Aucune fixture trouvee pour le groupe "' . $group . '".');

            return Command::FAILURE;
        }

        $purge = $input->getOption('purge');

        if ($purge) {
            $io->warning('Mode purge actif — les tables cibles seront videes avant chargement.');
            if (!$io->confirm('Continuer ?', false)) {
                $io->info('Operation annulee.');

                return Command::SUCCESS;
            }
        }

        $purger = $purge ? new ORMPurger($this->em) : null;
        $executor = new ORMExecutor($this->em, $purger);

        $io->info(sprintf('Chargement de %d fixture(s) du groupe "%s"...', count($selectedFixtures), $group));

        $executor->execute($selectedFixtures, append: !$purge);

        $io->success(sprintf('Groupe "%s" charge avec succes (%d fixtures).', $group, count($selectedFixtures)));

        return Command::SUCCESS;
    }
}
