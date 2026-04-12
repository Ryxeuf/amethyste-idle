<?php

namespace App\GameEngine\Quest;

use App\Entity\App\Mob;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;

class PlayerQuestUpdater
{
    public function __construct(
        private readonly PlayerQuestHelper $playerQuestHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerHelper $playerHelper,
        private readonly DailyQuestService $dailyQuestService,
    ) {
    }

    public function updateMobKilled(Mob $mob): void
    {
        $quests = $this->playerQuestHelper->getCurrentQuests();
        foreach ($quests as $quest) {
            if ($this->playerQuestHelper->isPlayerQuestCompleted($quest)) {
                continue;
            }
            $tracking = $quest->getTracking();
            if (isset($tracking['monsters'])) {
                foreach ($tracking['monsters'] as $idx => $monster) {
                    if ($monster['slug'] === $mob->getMonster()->getSlug() && $monster['count'] < $monster['necessary']) {
                        ++$tracking['monsters'][$idx]['count'];
                    }
                }
            }
            $quest->setTracking($tracking);
        }

        // Update daily quests
        $this->updateDailyQuestTracking(function (array &$tracking) use ($mob) {
            if (!isset($tracking['monsters'])) {
                return false;
            }
            $changed = false;
            foreach ($tracking['monsters'] as $idx => $monster) {
                if ($monster['slug'] === $mob->getMonster()->getSlug() && $monster['count'] < $monster['necessary']) {
                    ++$tracking['monsters'][$idx]['count'];
                    $changed = true;
                }
            }

            return $changed;
        });

        $this->entityManager->flush();
    }

    public function updateItemCollected(string $itemSlug, int $quantity = 1): void
    {
        $this->updateTrackingEntries('collect', $itemSlug, $quantity);
    }

    public function updateItemCrafted(string $itemSlug, int $quantity = 1): void
    {
        $this->updateTrackingEntries('craft', $itemSlug, $quantity);
    }

    /**
     * Called when a player delivers an item to a PNJ during dialog.
     */
    public function updateDelivered(string $itemSlug, int $pnjId, int $quantity = 1): void
    {
        $quests = $this->playerQuestHelper->getCurrentQuests();
        $changed = false;

        foreach ($quests as $quest) {
            if ($this->playerQuestHelper->isPlayerQuestCompleted($quest)) {
                continue;
            }
            $tracking = $quest->getTracking();
            if (!isset($tracking['deliver'])) {
                continue;
            }
            foreach ($tracking['deliver'] as $idx => $entry) {
                if ($entry['item_slug'] === $itemSlug && $entry['pnj_id'] === $pnjId) {
                    $tracking['deliver'][$idx]['count'] = min(
                        $entry['necessary'],
                        ($entry['count'] ?? 0) + $quantity
                    );
                    $changed = true;
                }
            }
            $quest->setTracking($tracking);
        }

        // Update daily quests
        $this->updateDailyQuestTracking(function (array &$tracking) use ($itemSlug, $pnjId, $quantity) {
            if (!isset($tracking['deliver'])) {
                return false;
            }
            $changed = false;
            foreach ($tracking['deliver'] as $idx => $entry) {
                if ($entry['item_slug'] === $itemSlug && $entry['pnj_id'] === $pnjId && $entry['count'] < $entry['necessary']) {
                    $tracking['deliver'][$idx]['count'] = min($entry['necessary'], ($entry['count'] ?? 0) + $quantity);
                    $changed = true;
                }
            }

            return $changed;
        });

        if ($changed) {
            $this->entityManager->flush();
        }
    }

    /**
     * Called when a player reaches specific coordinates on a map.
     */
    public function updateExplored(int $mapId, string $coordinates): void
    {
        $quests = $this->playerQuestHelper->getCurrentQuests();
        $changed = false;

        foreach ($quests as $quest) {
            if ($this->playerQuestHelper->isPlayerQuestCompleted($quest)) {
                continue;
            }
            $tracking = $quest->getTracking();
            if (!isset($tracking['explore'])) {
                continue;
            }
            foreach ($tracking['explore'] as $idx => $entry) {
                if ($entry['map_id'] === $mapId && $entry['count'] >= $entry['necessary']) {
                    continue;
                }
                if ($entry['map_id'] !== $mapId) {
                    continue;
                }
                if ($entry['coordinates'] !== null && $entry['coordinates'] !== $coordinates) {
                    continue;
                }
                $tracking['explore'][$idx]['count'] = 1;
                $changed = true;
            }
            $quest->setTracking($tracking);
        }

        // Update daily quests
        $this->updateDailyQuestTracking(function (array &$tracking) use ($mapId, $coordinates) {
            if (!isset($tracking['explore'])) {
                return false;
            }
            $changed = false;
            foreach ($tracking['explore'] as $idx => $entry) {
                if ($entry['map_id'] !== $mapId || $entry['count'] >= $entry['necessary']) {
                    continue;
                }
                if ($entry['coordinates'] !== null && $entry['coordinates'] !== $coordinates) {
                    continue;
                }
                $tracking['explore'][$idx]['count'] = 1;
                $changed = true;
            }

            return $changed;
        });

        if ($changed) {
            $this->entityManager->flush();
        }
    }

    /**
     * Called when a player talks to a PNJ (for enquête quests).
     */
    public function updateTalkedTo(int $pnjId): void
    {
        $quests = $this->playerQuestHelper->getCurrentQuests();
        $changed = false;

        foreach ($quests as $quest) {
            if ($this->playerQuestHelper->isPlayerQuestCompleted($quest)) {
                continue;
            }
            $tracking = $quest->getTracking();
            if (!isset($tracking['talk_to'])) {
                continue;
            }
            foreach ($tracking['talk_to'] as $idx => $entry) {
                if ($entry['pnj_id'] === $pnjId && $entry['count'] < $entry['necessary']) {
                    $tracking['talk_to'][$idx]['count'] = 1;
                    $changed = true;
                }
            }
            $quest->setTracking($tracking);
        }

        // Update daily quests
        $this->updateDailyQuestTracking(function (array &$tracking) use ($pnjId) {
            if (!isset($tracking['talk_to'])) {
                return false;
            }
            $changed = false;
            foreach ($tracking['talk_to'] as $idx => $entry) {
                if ($entry['pnj_id'] === $pnjId && $entry['count'] < $entry['necessary']) {
                    $tracking['talk_to'][$idx]['count'] = 1;
                    $changed = true;
                }
            }

            return $changed;
        });

        if ($changed) {
            $this->entityManager->flush();
        }
    }

    /**
     * Called when a boss is defeated under challenge conditions.
     */
    public function updateBossChallenge(string $monsterSlug): void
    {
        $quests = $this->playerQuestHelper->getCurrentQuests();
        $changed = false;

        foreach ($quests as $quest) {
            if ($this->playerQuestHelper->isPlayerQuestCompleted($quest)) {
                continue;
            }
            $tracking = $quest->getTracking();
            if (!isset($tracking['boss_challenge'])) {
                continue;
            }
            foreach ($tracking['boss_challenge'] as $idx => $entry) {
                if ($entry['monster_slug'] === $monsterSlug && $entry['count'] < $entry['necessary']) {
                    $tracking['boss_challenge'][$idx]['count'] = 1;
                    $changed = true;
                }
            }
            $quest->setTracking($tracking);
        }

        // Update daily quests
        $this->updateDailyQuestTracking(function (array &$tracking) use ($monsterSlug) {
            if (!isset($tracking['boss_challenge'])) {
                return false;
            }
            $changed = false;
            foreach ($tracking['boss_challenge'] as $idx => $entry) {
                if ($entry['monster_slug'] === $monsterSlug && $entry['count'] < $entry['necessary']) {
                    $tracking['boss_challenge'][$idx]['count'] = 1;
                    $changed = true;
                }
            }

            return $changed;
        });

        if ($changed) {
            $this->entityManager->flush();
        }
    }

    /**
     * Called when a monster is killed in a specific zone (defend quests).
     */
    public function updateDefend(string $monsterSlug, int $mapId): void
    {
        $quests = $this->playerQuestHelper->getCurrentQuests();
        $changed = false;

        foreach ($quests as $quest) {
            if ($this->playerQuestHelper->isPlayerQuestCompleted($quest)) {
                continue;
            }
            $tracking = $quest->getTracking();
            if (!isset($tracking['defend'])) {
                continue;
            }
            foreach ($tracking['defend'] as $idx => $entry) {
                if ($entry['monster_slug'] === $monsterSlug && $entry['map_id'] === $mapId && $entry['count'] < $entry['necessary']) {
                    ++$tracking['defend'][$idx]['count'];
                    $changed = true;
                }
            }
            $quest->setTracking($tracking);
        }

        if ($changed) {
            $this->entityManager->flush();
        }
    }

    /**
     * Called when a player reaches a destination (escort quests).
     */
    public function updateEscort(int $mapId, string $coordinates): void
    {
        $quests = $this->playerQuestHelper->getCurrentQuests();
        $changed = false;

        foreach ($quests as $quest) {
            if ($this->playerQuestHelper->isPlayerQuestCompleted($quest)) {
                continue;
            }
            $tracking = $quest->getTracking();
            if (!isset($tracking['escort'])) {
                continue;
            }
            foreach ($tracking['escort'] as $idx => $entry) {
                if ($entry['destination_map_id'] === $mapId
                    && $entry['destination_coordinates'] === $coordinates
                    && $entry['count'] < $entry['necessary']) {
                    $tracking['escort'][$idx]['count'] = 1;
                    $changed = true;
                }
            }
            $quest->setTracking($tracking);
        }

        if ($changed) {
            $this->entityManager->flush();
        }
    }

    /**
     * Called when a player submits a puzzle answer for a PNJ.
     *
     * @return bool Whether the answer was correct for any quest
     */
    public function updatePuzzle(int $pnjId, string $answerKey): bool
    {
        $quests = $this->playerQuestHelper->getCurrentQuests();
        $correct = false;

        foreach ($quests as $quest) {
            if ($this->playerQuestHelper->isPlayerQuestCompleted($quest)) {
                continue;
            }
            $tracking = $quest->getTracking();
            if (!isset($tracking['puzzle'])) {
                continue;
            }
            foreach ($tracking['puzzle'] as $idx => $entry) {
                if ($entry['pnj_id'] === $pnjId && $entry['count'] < $entry['necessary']) {
                    if (mb_strtolower($entry['answer_key']) === mb_strtolower($answerKey)) {
                        $tracking['puzzle'][$idx]['count'] = 1;
                        $correct = true;
                    }
                }
            }
            $quest->setTracking($tracking);
        }

        if ($correct) {
            $this->entityManager->flush();
        }

        return $correct;
    }

    private function updateTrackingEntries(string $type, string $slug, int $quantity): void
    {
        $quests = $this->playerQuestHelper->getCurrentQuests();
        $changed = false;

        foreach ($quests as $quest) {
            if ($this->playerQuestHelper->isPlayerQuestCompleted($quest)) {
                continue;
            }
            $tracking = $quest->getTracking();
            if (!isset($tracking[$type])) {
                continue;
            }
            foreach ($tracking[$type] as $idx => $entry) {
                if ($entry['slug'] === $slug) {
                    $tracking[$type][$idx]['count'] = min(
                        $entry['necessary'],
                        ($entry['count'] ?? 0) + $quantity
                    );
                    $changed = true;
                }
            }
            $quest->setTracking($tracking);
        }

        // Update daily quests
        $this->updateDailyQuestTracking(function (array &$tracking) use ($type, $slug, $quantity) {
            if (!isset($tracking[$type])) {
                return false;
            }
            $changed = false;
            foreach ($tracking[$type] as $idx => $entry) {
                if ($entry['slug'] === $slug && $entry['count'] < $entry['necessary']) {
                    $tracking[$type][$idx]['count'] = min($entry['necessary'], ($entry['count'] ?? 0) + $quantity);
                    $changed = true;
                }
            }

            return $changed;
        });

        if ($changed) {
            $this->entityManager->flush();
        }
    }

    /**
     * Updates tracking on active daily quests using a callback.
     */
    private function updateDailyQuestTracking(callable $updater): void
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return;
        }

        $dailyQuests = $this->dailyQuestService->getActiveDailyQuests($player);

        foreach ($dailyQuests as $dailyQuest) {
            $tracking = $dailyQuest->getTracking();
            if ($updater($tracking)) {
                $dailyQuest->setTracking($tracking);
            }
        }
    }
}
