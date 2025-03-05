<?php

namespace App\GameEngine\Quest;

use App\Entity\App\Mob;
use App\Entity\App\PlayerQuest;
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
                        $tracking['monsters'][$idx]['count'] += 1;
                    }
                }
            }
            $quest->setTracking($tracking);
        }

        $this->entityManager->flush();
    }
}