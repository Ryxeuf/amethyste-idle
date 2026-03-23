<?php

namespace App\GameEngine\Quest;

use App\Entity\App\Mob;
use Doctrine\ORM\EntityManagerInterface;

class PlayerQuestUpdater
{
    public function __construct(private readonly PlayerQuestHelper $playerQuestHelper, private readonly EntityManagerInterface $entityManager)
    {
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
                    if ($monster['slug'] === $mob->getMonster()->getSlug()) {
                        ++$tracking['monsters'][$idx]['count'];
                    }
                }
            }
            $quest->setTracking($tracking);
        }

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
                // If coordinates are specified, require exact match; otherwise just being on the map is enough
                if ($entry['coordinates'] !== null && $entry['coordinates'] !== $coordinates) {
                    continue;
                }
                $tracking['explore'][$idx]['count'] = 1;
                $changed = true;
            }
            $quest->setTracking($tracking);
        }

        if ($changed) {
            $this->entityManager->flush();
        }
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

        if ($changed) {
            $this->entityManager->flush();
        }
    }
}
