<?php

namespace App\GameEngine\Quest;

use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use Doctrine\ORM\EntityManagerInterface;

class QuestGiverResolver
{
    /** @var array<int, Pnj>|null */
    private ?array $questToPnjMap = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Find the PNJ that gives a specific quest by scanning dialog JSON.
     */
    public function getQuestGiver(Quest $quest): ?Pnj
    {
        $map = $this->buildQuestToPnjMap();

        return $map[$quest->getId()] ?? null;
    }

    /**
     * Get quest givers for multiple quests at once (avoids N+1).
     *
     * @param Quest[] $quests
     *
     * @return array<int, Pnj> Indexed by quest ID
     */
    public function getQuestGivers(array $quests): array
    {
        $map = $this->buildQuestToPnjMap();
        $result = [];

        foreach ($quests as $quest) {
            $questId = $quest->getId();
            if (isset($map[$questId])) {
                $result[$questId] = $map[$questId];
            }
        }

        return $result;
    }

    /**
     * Detect the quest type from its requirements.
     */
    public function getQuestType(Quest $quest): string
    {
        $requirements = $quest->getRequirements();

        if (!empty($requirements['monsters'])) {
            return 'kill';
        }
        if (!empty($requirements['collect'])) {
            return 'collect';
        }
        if (!empty($requirements['deliver'])) {
            return 'deliver';
        }
        if (!empty($requirements['explore'])) {
            return 'explore';
        }
        if (!empty($requirements['talk_to'])) {
            return 'talk_to';
        }
        if (!empty($requirements['boss_challenge'])) {
            return 'boss_challenge';
        }
        if (!empty($requirements['defend'])) {
            return 'defend';
        }
        if (!empty($requirements['escort'])) {
            return 'escort';
        }
        if (!empty($requirements['puzzle'])) {
            return 'puzzle';
        }

        return 'other';
    }

    /**
     * Get chain info for a quest: [position, total] or null if not part of a chain.
     *
     * @return array{position: int, total: int}|null
     */
    public function getChainInfo(Quest $quest): ?array
    {
        if (!$quest->hasPrerequisites()) {
            // Check if this quest IS a prerequisite for another quest
            $isPrerequisiteFor = $this->findQuestsRequiring($quest->getId());
            if (empty($isPrerequisiteFor)) {
                return null;
            }

            // This is the first quest in a chain
            $total = 1 + $this->countChainForward($quest->getId());

            return $total > 1 ? ['position' => 1, 'total' => $total] : null;
        }

        // Walk backward to find chain start
        $chainStart = $this->findChainStart($quest);
        $chainQuests = $this->buildChainFromStart($chainStart);
        $total = \count($chainQuests);

        if ($total <= 1) {
            return null;
        }

        $position = array_search($quest->getId(), $chainQuests, true);

        return $position !== false ? ['position' => $position + 1, 'total' => $total] : null;
    }

    /**
     * @return array<int, Pnj>
     */
    private function buildQuestToPnjMap(): array
    {
        if ($this->questToPnjMap !== null) {
            return $this->questToPnjMap;
        }

        $this->questToPnjMap = [];
        $pnjs = $this->entityManager->getRepository(Pnj::class)->findAll();

        foreach ($pnjs as $pnj) {
            $dialog = $pnj->getDialog();
            $questIds = $this->extractQuestIdsFromDialog($dialog);

            foreach ($questIds as $questId) {
                $this->questToPnjMap[$questId] = $pnj;
            }
        }

        return $this->questToPnjMap;
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
     * @return int[] Quest IDs that require the given quest as prerequisite
     */
    private function findQuestsRequiring(int $questId): array
    {
        $allQuests = $this->entityManager->getRepository(Quest::class)->findAll();
        $result = [];

        foreach ($allQuests as $quest) {
            $prereqs = $quest->getPrerequisiteQuests();
            if (!empty($prereqs) && \in_array($questId, $prereqs, true)) {
                $result[] = $quest->getId();
            }
        }

        return $result;
    }

    private function countChainForward(int $questId): int
    {
        $next = $this->findQuestsRequiring($questId);

        if (empty($next)) {
            return 0;
        }

        // Take the first quest that requires this one (linear chain assumption)
        return 1 + $this->countChainForward($next[0]);
    }

    private function findChainStart(Quest $quest): int
    {
        $prereqs = $quest->getPrerequisiteQuests();

        if (empty($prereqs)) {
            return $quest->getId();
        }

        $prereqQuest = $this->entityManager->getRepository(Quest::class)->find($prereqs[0]);

        return $prereqQuest !== null ? $this->findChainStart($prereqQuest) : $quest->getId();
    }

    /**
     * @return int[] Ordered list of quest IDs in the chain
     */
    private function buildChainFromStart(int $startQuestId): array
    {
        $chain = [$startQuestId];
        $currentId = $startQuestId;

        while (true) {
            $next = $this->findQuestsRequiring($currentId);

            if (empty($next)) {
                break;
            }

            $currentId = $next[0];
            $chain[] = $currentId;
        }

        return $chain;
    }
}
