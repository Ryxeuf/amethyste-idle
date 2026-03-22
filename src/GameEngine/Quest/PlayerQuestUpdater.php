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
