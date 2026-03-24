<?php

namespace App\Command;

use App\Entity\App\DailyQuestSelection;
use App\Entity\Game\Quest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:daily-quest:reset',
    description: 'Select 3 random daily quests for today',
)]
class DailyQuestResetCommand extends Command
{
    private const DAILY_QUEST_COUNT = 3;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $today = new \DateTimeImmutable('today');

        // Check if today's selection already exists
        $existing = $this->entityManager->getRepository(DailyQuestSelection::class)->findBy([
            'date' => $today,
        ]);

        if (\count($existing) > 0) {
            $io->info(sprintf('Daily quests already selected for %s (%d quests).', $today->format('Y-m-d'), \count($existing)));

            return Command::SUCCESS;
        }

        // Get all daily quests
        $dailyQuests = $this->entityManager->getRepository(Quest::class)->findBy([
            'isDaily' => true,
        ]);

        if (\count($dailyQuests) === 0) {
            $io->warning('No daily quests found in database.');

            return Command::SUCCESS;
        }

        // Shuffle and pick up to DAILY_QUEST_COUNT
        shuffle($dailyQuests);
        $selected = \array_slice($dailyQuests, 0, min(self::DAILY_QUEST_COUNT, \count($dailyQuests)));

        // Persist selections
        foreach ($selected as $quest) {
            $selection = new DailyQuestSelection();
            $selection->setQuest($quest);
            $selection->setDate($today);
            $this->entityManager->persist($selection);
        }

        $this->entityManager->flush();

        $names = array_map(fn (Quest $q) => $q->getName(), $selected);
        $io->success(sprintf('Daily quests for %s: %s', $today->format('Y-m-d'), implode(', ', $names)));

        return Command::SUCCESS;
    }
}
