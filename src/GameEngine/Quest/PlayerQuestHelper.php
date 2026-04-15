<?php

namespace App\GameEngine\Quest;

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
        $playerRenownScore = $player?->getRenownScore() ?? 0;

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

        // Get all quests not already active or completed (exclude daily + hidden quests)
        $qb = $this->entityManager->getRepository(Quest::class)->createQueryBuilder('q');
        $qb->where('q.isDaily = false')
           ->andWhere('q.isHidden = false')
           ->andWhere('q.minRenownScore IS NULL OR q.minRenownScore <= :playerRenownScore')
           ->setParameter('playerRenownScore', $playerRenownScore);
        if (!empty($excludeIds)) {
            $qb->andWhere('q.id NOT IN (:excludeIds)')
               ->setParameter('excludeIds', $excludeIds);
        }
        $allQuests = $qb->orderBy('q.name', 'ASC')->getQuery()->getResult();

        // Filter by prerequisites and event availability
        return array_values(array_filter($allQuests, function (Quest $quest) use ($completedQuestIds) {
            // Hide event quests whose event is no longer active
            if ($quest->isEventQuest() && !$quest->isEventActive()) {
                return false;
            }

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

        foreach (['monsters', 'collect', 'craft', 'deliver', 'explore', 'talk_to', 'boss_challenge', 'defend', 'escort', 'puzzle'] as $type) {
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
}
