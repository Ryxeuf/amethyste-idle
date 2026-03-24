<?php

namespace App\GameEngine\Quest;

use App\Entity\App\DailyQuestSelection;
use App\Entity\App\PlayerQuest;
use App\Entity\App\PlayerQuestCompleted;
use App\Entity\Game\Quest;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class PlayerQuestHelper
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return PlayerQuest[]|array
     */
    public function getCurrentQuests(): array
    {
        /** @var EntityRepository $questRepository */
        $questRepository = $this->entityManager->getRepository(PlayerQuest::class);

        $queryBuilder = $questRepository->createQueryBuilder('qp')
            ->addSelect('q')
            ->innerJoin('qp.quest', 'q')
            ->andWhere('qp.player = :player')
            ->setParameter('player', $this->playerHelper->getPlayer());

        return $queryBuilder->getQuery()->getResult();
    }

    public function getQuest(int $id): ?PlayerQuest
    {
        /** @var EntityRepository $questRepository */
        $questRepository = $this->entityManager->getRepository(PlayerQuest::class);

        $queryBuilder = $questRepository->createQueryBuilder('qp')
            ->addSelect('q')
            ->innerJoin('qp.quest', 'q')
            ->andWhere('qp.id = :id')
            ->andWhere('qp.player = :player')
            ->setParameter('id', $id)
            ->setParameter('player', $this->playerHelper->getPlayer());

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getCompletedQuest(int $id): ?PlayerQuestCompleted
    {
        /** @var EntityRepository $questRepository */
        $questRepository = $this->entityManager->getRepository(PlayerQuestCompleted::class);

        $queryBuilder = $questRepository->createQueryBuilder('qp')
            ->addSelect('q')
            ->innerJoin('qp.quest', 'q')
            ->andWhere('qp.id = :id')
            ->andWhere('qp.player = :player')
            ->setParameter('id', $id)
            ->setParameter('player', $this->playerHelper->getPlayer());

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @return PlayerQuestCompleted[]|array
     */
    public function getCompletedQuests(): array
    {
        $questRepository = $this->entityManager->getRepository(PlayerQuestCompleted::class);

        $queryBuilder = $questRepository->createQueryBuilder('qp')
            ->addSelect('q')
            ->innerJoin('qp.quest', 'q')
            ->andWhere('qp.player = :player')
            ->setParameter('player', $this->playerHelper->getPlayer());

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return Quest[]
     */
    public function getAvailableQuests(): array
    {
        $player = $this->playerHelper->getPlayer();

        // Get IDs of active and completed quests
        $activeQuestIds = array_map(
            fn (PlayerQuest $pq) => $pq->getQuest()->getId(),
            $this->getCurrentQuests()
        );
        $completedQuestIds = array_map(
            fn (PlayerQuestCompleted $pqc) => $pqc->getQuest()->getId(),
            $this->getCompletedQuests()
        );
        $excludeIds = array_merge($activeQuestIds, $completedQuestIds);

        // Get all non-daily quests not already active or completed
        $qb = $this->entityManager->getRepository(Quest::class)->createQueryBuilder('q')
            ->where('q.isDaily = false');
        if (!empty($excludeIds)) {
            $qb->andWhere('q.id NOT IN (:excludeIds)')
               ->setParameter('excludeIds', $excludeIds);
        }
        $allQuests = $qb->orderBy('q.name', 'ASC')->getQuery()->getResult();

        // Filter by prerequisites
        return array_values(array_filter($allQuests, function (Quest $quest) use ($completedQuestIds) {
            $prerequisites = $quest->getPrerequisiteQuests();
            if (empty($prerequisites)) {
                return true;
            }

            foreach ($prerequisites as $prereqId) {
                if (!\in_array($prereqId, $completedQuestIds, true)) {
                    return false;
                }
            }

            return true;
        }));
    }

    public function isPlayerQuestCompleted(PlayerQuest $playerQuest): bool
    {
        return $this->getPlayerQuestProgress($playerQuest) === 100;
    }

    public function getPlayerQuestProgress(PlayerQuest $playerQuest): int
    {
        $count = 0;
        $necessary = 0;
        $tracking = $playerQuest->getTracking();

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

    /**
     * @return Quest[]
     */
    public function getTodayDailyQuests(): array
    {
        $today = new \DateTimeImmutable('today');

        $selections = $this->entityManager->getRepository(DailyQuestSelection::class)
            ->createQueryBuilder('dqs')
            ->addSelect('q')
            ->innerJoin('dqs.quest', 'q')
            ->where('dqs.date = :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        return array_map(fn (DailyQuestSelection $s) => $s->getQuest(), $selections);
    }

    public function isDailyQuestCompletedToday(Quest $quest): bool
    {
        $player = $this->playerHelper->getPlayer();
        $today = new \DateTimeImmutable('today');

        $completed = $this->entityManager->getRepository(PlayerQuestCompleted::class)->findOneBy([
            'player' => $player,
            'quest' => $quest,
        ]);

        if (!$completed) {
            return false;
        }

        return $completed->getUpdatedAt() >= $today;
    }

    public function isDailyQuestActive(Quest $quest): bool
    {
        $player = $this->playerHelper->getPlayer();

        return (bool) $this->entityManager->getRepository(PlayerQuest::class)->findOneBy([
            'player' => $player,
            'quest' => $quest,
        ]);
    }
}
