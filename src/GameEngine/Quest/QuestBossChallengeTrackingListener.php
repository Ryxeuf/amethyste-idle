<?php

namespace App\GameEngine\Quest;

use App\Event\Fight\MobDeadEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuestBossChallengeTrackingListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly PlayerQuestUpdater $playerQuestUpdater,
        private readonly PlayerQuestHelper $playerQuestHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'onMobDead',
        ];
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        $mob = $event->getMob();
        $fight = $mob->getFight();

        if (!$fight) {
            return;
        }

        $monsterSlug = $mob->getMonster()->getSlug();

        // Check if any active quest has a boss_challenge for this monster
        $quests = $this->playerQuestHelper->getCurrentQuests();
        foreach ($quests as $playerQuest) {
            $tracking = $playerQuest->getTracking();
            if (!isset($tracking['boss_challenge'])) {
                continue;
            }
            foreach ($tracking['boss_challenge'] as $entry) {
                if ($entry['monster_slug'] !== $monsterSlug || $entry['count'] >= $entry['necessary']) {
                    continue;
                }

                // Validate conditions
                if ($this->areConditionsMet($entry['conditions'] ?? [], $fight)) {
                    $this->playerQuestUpdater->updateBossChallenge($monsterSlug);

                    return;
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $conditions
     */
    private function areConditionsMet(array $conditions, \App\Entity\App\Fight $fight): bool
    {
        // no_heal: player must not have used any heal during the fight
        if (!empty($conditions['no_heal'])) {
            if ($fight->getMetadataValue('heal_used', false)) {
                return false;
            }
        }

        // solo: only one player in the fight
        if (!empty($conditions['solo'])) {
            if ($fight->getPlayers()->count() > 1) {
                return false;
            }
        }

        // time_limit: fight must have been completed within N seconds
        if (!empty($conditions['time_limit'])) {
            $createdAt = $fight->getCreatedAt();
            if ($createdAt) {
                $elapsed = time() - $createdAt->getTimestamp();
                if ($elapsed > (int) $conditions['time_limit']) {
                    return false;
                }
            }
        }

        return true;
    }
}
