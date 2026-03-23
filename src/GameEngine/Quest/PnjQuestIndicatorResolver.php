<?php

namespace App\GameEngine\Quest;

use App\Entity\App\Player;
use App\Entity\App\PlayerQuest;
use App\Entity\App\PlayerQuestCompleted;
use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use Doctrine\ORM\EntityManagerInterface;

class PnjQuestIndicatorResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Resolve quest indicators for a list of PNJs for a given player.
     *
     * @param Pnj[] $pnjs
     *
     * @return array<int, string|null> Indexed by PNJ ID: 'available', 'in_progress', or null
     */
    public function resolveIndicators(array $pnjs, Player $player): array
    {
        $pnjQuestMap = $this->buildPnjToQuestMap($pnjs);

        if (empty($pnjQuestMap)) {
            return [];
        }

        $activeQuestIds = $this->getActiveQuestIds($player);
        $completedQuestIds = $this->getCompletedQuestIds($player);

        $allQuestIds = [];
        foreach ($pnjQuestMap as $questIds) {
            $allQuestIds = array_merge($allQuestIds, $questIds);
        }
        $allQuestIds = array_unique($allQuestIds);

        $quests = $this->entityManager->getRepository(Quest::class)->findBy(['id' => $allQuestIds]);
        $questsById = [];
        foreach ($quests as $quest) {
            $questsById[$quest->getId()] = $quest;
        }

        $indicators = [];
        foreach ($pnjQuestMap as $pnjId => $questIds) {
            $indicator = $this->resolveIndicatorForPnj(
                $questIds,
                $questsById,
                $activeQuestIds,
                $completedQuestIds,
            );
            if ($indicator !== null) {
                $indicators[$pnjId] = $indicator;
            }
        }

        return $indicators;
    }

    /**
     * @param int[]             $questIds
     * @param array<int, Quest> $questsById
     * @param int[]             $activeQuestIds
     * @param int[]             $completedQuestIds
     */
    private function resolveIndicatorForPnj(
        array $questIds,
        array $questsById,
        array $activeQuestIds,
        array $completedQuestIds,
    ): ?string {
        $hasInProgress = false;

        foreach ($questIds as $questId) {
            if (\in_array($questId, $activeQuestIds, true)) {
                $hasInProgress = true;

                continue;
            }

            if (\in_array($questId, $completedQuestIds, true)) {
                continue;
            }

            $quest = $questsById[$questId] ?? null;
            if ($quest === null) {
                continue;
            }

            $prerequisites = $quest->getPrerequisiteQuests();
            if (empty($prerequisites)) {
                return 'available';
            }

            $allMet = true;
            foreach ($prerequisites as $prereqId) {
                if (!\in_array($prereqId, $completedQuestIds, true)) {
                    $allMet = false;
                    break;
                }
            }

            if ($allMet) {
                return 'available';
            }
        }

        return $hasInProgress ? 'in_progress' : null;
    }

    /**
     * Build a map of PNJ ID => quest IDs offered by that PNJ.
     *
     * @param Pnj[] $pnjs
     *
     * @return array<int, int[]>
     */
    private function buildPnjToQuestMap(array $pnjs): array
    {
        $map = [];

        foreach ($pnjs as $pnj) {
            $questIds = $this->extractQuestIdsFromDialog($pnj->getDialog());
            if (!empty($questIds)) {
                $map[$pnj->getId()] = $questIds;
            }
        }

        return $map;
    }

    /**
     * @return int[]
     */
    private function extractQuestIdsFromDialog(array $dialog): array
    {
        $questIds = [];

        foreach ($dialog as $sentence) {
            if (!isset($sentence['choices'])) {
                continue;
            }

            foreach ($sentence['choices'] as $choice) {
                if (($choice['action'] ?? '') === 'quest_offer' && isset($choice['data']['quest'])) {
                    $questIds[] = (int) $choice['data']['quest'];
                }
            }
        }

        return $questIds;
    }

    /**
     * @return int[]
     */
    private function getActiveQuestIds(Player $player): array
    {
        $playerQuests = $this->entityManager->getRepository(PlayerQuest::class)
            ->createQueryBuilder('pq')
            ->select('IDENTITY(pq.quest)')
            ->where('pq.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $playerQuests);
    }

    /**
     * @return int[]
     */
    private function getCompletedQuestIds(Player $player): array
    {
        $completedQuests = $this->entityManager->getRepository(PlayerQuestCompleted::class)
            ->createQueryBuilder('pqc')
            ->select('IDENTITY(pqc.quest)')
            ->where('pqc.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $completedQuests);
    }
}
