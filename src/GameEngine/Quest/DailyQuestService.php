<?php

namespace App\GameEngine\Quest;

use App\Command\DailyQuestRotateCommand;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerDailyQuest;
use App\Entity\Game\Quest;
use Doctrine\ORM\EntityManagerInterface;

class DailyQuestService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestTrackingFormater $questTrackingFormater,
    ) {
    }

    /**
     * Get today's selected daily quests (from rotation).
     *
     * @return Quest[]
     */
    public function getTodayQuests(): array
    {
        $ids = $this->getTodayQuestIds();
        if (empty($ids)) {
            return [];
        }

        return $this->entityManager->getRepository(Quest::class)
            ->createQueryBuilder('q')
            ->where('q.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('q.dailyPool', 'ASC')
            ->addOrderBy('q.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int[]
     */
    private function getTodayQuestIds(): array
    {
        $param = $this->entityManager->getRepository(Parameter::class)->findOneBy([
            'name' => DailyQuestRotateCommand::PARAMETER_NAME,
        ]);

        if (!$param) {
            return [];
        }

        $data = json_decode($param->getValue(), true);
        if (!\is_array($data)) {
            return [];
        }

        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');

        if (($data['date'] ?? null) !== $today) {
            return [];
        }

        return $data['quest_ids'] ?? [];
    }

    /**
     * Get active daily quests for a player (accepted today, not yet completed).
     *
     * @return PlayerDailyQuest[]
     */
    public function getActiveDailyQuests(Player $player): array
    {
        return $this->entityManager->getRepository(PlayerDailyQuest::class)
            ->createQueryBuilder('pdq')
            ->addSelect('q')
            ->innerJoin('pdq.quest', 'q')
            ->where('pdq.player = :player')
            ->andWhere('pdq.date = :today')
            ->andWhere('pdq.completedAt IS NULL')
            ->setParameter('player', $player)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getResult();
    }

    /**
     * Get daily quests completed today by a player.
     *
     * @return PlayerDailyQuest[]
     */
    public function getCompletedDailyQuests(Player $player): array
    {
        return $this->entityManager->getRepository(PlayerDailyQuest::class)
            ->createQueryBuilder('pdq')
            ->addSelect('q')
            ->innerJoin('pdq.quest', 'q')
            ->where('pdq.player = :player')
            ->andWhere('pdq.date = :today')
            ->andWhere('pdq.completedAt IS NOT NULL')
            ->setParameter('player', $player)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getResult();
    }

    /**
     * Accept a daily quest for the player.
     */
    public function acceptDailyQuest(Player $player, Quest $quest): PlayerDailyQuest
    {
        $tracking = $this->questTrackingFormater->formatTracking($quest);

        $dailyQuest = new PlayerDailyQuest();
        $dailyQuest->setPlayer($player);
        $dailyQuest->setQuest($quest);
        $dailyQuest->setDate(new \DateTimeImmutable('today'));
        $dailyQuest->setTracking($tracking);

        $this->entityManager->persist($dailyQuest);
        $this->entityManager->flush();

        return $dailyQuest;
    }

    /**
     * Find an active daily quest by its ID for a player.
     */
    public function getActivePlayerDailyQuest(Player $player, int $id): ?PlayerDailyQuest
    {
        return $this->entityManager->getRepository(PlayerDailyQuest::class)
            ->createQueryBuilder('pdq')
            ->addSelect('q')
            ->innerJoin('pdq.quest', 'q')
            ->where('pdq.id = :id')
            ->andWhere('pdq.player = :player')
            ->andWhere('pdq.date = :today')
            ->andWhere('pdq.completedAt IS NULL')
            ->setParameter('id', $id)
            ->setParameter('player', $player)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Check if the player already has this daily quest active or completed today.
     */
    public function hasPlayerDailyQuestToday(Player $player, Quest $quest): bool
    {
        $count = $this->entityManager->getRepository(PlayerDailyQuest::class)
            ->createQueryBuilder('pdq')
            ->select('COUNT(pdq.id)')
            ->where('pdq.player = :player')
            ->andWhere('pdq.quest = :quest')
            ->andWhere('pdq.date = :today')
            ->setParameter('player', $player)
            ->setParameter('quest', $quest)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Calculate progress of a daily quest (0-100).
     */
    public function getProgress(PlayerDailyQuest $dailyQuest): int
    {
        $count = 0;
        $necessary = 0;
        $tracking = $dailyQuest->getTracking();

        foreach (['monsters', 'collect', 'craft', 'deliver', 'explore'] as $type) {
            if (isset($tracking[$type])) {
                foreach ($tracking[$type] as $entry) {
                    $count += $entry['count'] ?? 0;
                    $necessary += $entry['necessary'] ?? 0;
                }
            }
        }

        if ($necessary === 0) {
            return 100;
        }

        return (int) round($count / $necessary * 100);
    }

    public function isCompleted(PlayerDailyQuest $dailyQuest): bool
    {
        return $this->getProgress($dailyQuest) === 100;
    }
}
