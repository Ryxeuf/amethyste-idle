<?php

namespace App\Command;

use App\Entity\App\Parameter;
use App\Entity\Game\Quest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:daily-quest:rotate',
    description: 'Sélectionne les quêtes quotidiennes du jour depuis le pool',
)]
class DailyQuestRotateCommand extends Command
{
    public const string PARAMETER_NAME = 'daily_quest_ids';
    public const int SELECTION_COUNT = 3;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');

        // Check if already rotated today
        $param = $this->entityManager->getRepository(Parameter::class)->findOneBy([
            'name' => self::PARAMETER_NAME,
        ]);

        if ($param) {
            $data = json_decode($param->getValue(), true);
            if (\is_array($data) && ($data['date'] ?? null) === $today) {
                $io->note(sprintf('Rotation déjà effectuée pour le %s (%d quêtes)', $today, \count($data['quest_ids'] ?? [])));

                return Command::SUCCESS;
            }
        }

        // Get all daily quests
        $dailyQuests = $this->entityManager->getRepository(Quest::class)
            ->createQueryBuilder('q')
            ->where('q.isDaily = true')
            ->getQuery()
            ->getResult();

        if (empty($dailyQuests)) {
            $io->warning('Aucune quête quotidienne trouvée dans le pool.');

            return Command::SUCCESS;
        }

        // Shuffle and pick SELECTION_COUNT quests (or all if pool is smaller)
        shuffle($dailyQuests);
        $selected = \array_slice($dailyQuests, 0, min(self::SELECTION_COUNT, \count($dailyQuests)));
        $selectedIds = array_map(fn (Quest $q) => $q->getId(), $selected);

        // Store selection
        $value = json_encode(['date' => $today, 'quest_ids' => $selectedIds]);

        if (!$param) {
            $param = new Parameter();
            $param->setName(self::PARAMETER_NAME);
            $param->setCreatedAt(new \DateTime());
            $this->entityManager->persist($param);
        }
        $param->setValue($value);
        $param->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        $selectedNames = array_map(fn (Quest $q) => $q->getName(), $selected);
        $io->success(sprintf(
            'Rotation quotidienne du %s : %d quête(s) sélectionnée(s) — %s',
            $today,
            \count($selected),
            implode(', ', $selectedNames),
        ));

        return Command::SUCCESS;
    }
}
