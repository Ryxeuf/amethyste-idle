<?php

namespace App\GameEngine\Quest;

use App\Entity\App\PlayerQuest;
use App\Entity\App\PlayerQuestCompleted;
use App\Entity\Game\Quest;
use App\Event\Fight\MobDeadEvent;
use App\Event\Map\PlayerMovedEvent;
use App\Event\Map\SpotHarvestEvent;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class HiddenQuestTriggerListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestTrackingFormater $questTrackingFormater,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerMovedEvent::NAME => 'onPlayerMoved',
            MobDeadEvent::NAME => 'onMobDead',
            SpotHarvestEvent::NAME => 'onSpotHarvest',
        ];
    }

    public function onPlayerMoved(PlayerMovedEvent $event): void
    {
        $player = $event->getPlayer();
        $map = $player->getMap();
        if (!$map) {
            return;
        }

        $this->checkAndTrigger('explore', [
            'map_id' => $map->getId(),
            'coordinates' => $player->getCoordinates(),
        ]);
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        if ($event->getMob()->isSummoned()) {
            return;
        }

        $this->checkAndTrigger('kill', [
            'monster_slug' => $event->getMob()->getMonster()->getSlug(),
        ]);
    }

    public function onSpotHarvest(SpotHarvestEvent $event): void
    {
        foreach ($event->getHarvestedItems() as $playerItem) {
            $this->checkAndTrigger('harvest', [
                'item_slug' => $playerItem->getGenericItem()->getSlug(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function checkAndTrigger(string $eventType, array $context): void
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return;
        }

        $hiddenQuests = $this->entityManager->getRepository(Quest::class)->findBy([
            'isHidden' => true,
            'isDaily' => false,
        ]);

        if (empty($hiddenQuests)) {
            return;
        }

        // Get IDs of active and completed quests to exclude
        $activeQuestIds = array_map(
            fn (PlayerQuest $pq) => $pq->getQuest()->getId(),
            $this->entityManager->getRepository(PlayerQuest::class)->findBy(['player' => $player])
        );
        $completedQuestIds = array_map(
            fn (PlayerQuestCompleted $pqc) => $pqc->getQuest()->getId(),
            $this->entityManager->getRepository(PlayerQuestCompleted::class)->findBy(['player' => $player])
        );
        $excludeIds = array_merge($activeQuestIds, $completedQuestIds);

        foreach ($hiddenQuests as $quest) {
            if (\in_array($quest->getId(), $excludeIds, true)) {
                continue;
            }

            if (!$this->matchesTrigger($quest, $eventType, $context)) {
                continue;
            }

            // Check prerequisites
            $prerequisiteIds = $quest->getPrerequisiteQuests();
            if (!empty($prerequisiteIds)) {
                $completedPrerequisites = $this->entityManager->getRepository(PlayerQuestCompleted::class)->findBy([
                    'player' => $player,
                    'quest' => $prerequisiteIds,
                ]);
                if (\count($completedPrerequisites) < \count($prerequisiteIds)) {
                    continue;
                }
            }

            // Auto-accept the hidden quest
            $tracking = $this->questTrackingFormater->formatTracking($quest);
            $playerQuest = new PlayerQuest();
            $playerQuest->setPlayer($player);
            $playerQuest->setQuest($quest);
            $playerQuest->setTracking($tracking);

            $this->entityManager->persist($playerQuest);
            $this->entityManager->flush();

            // Add to exclude list so we don't trigger it again this tick
            $excludeIds[] = $quest->getId();
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function matchesTrigger(Quest $quest, string $eventType, array $context): bool
    {
        $condition = $quest->getTriggerCondition();
        if (empty($condition) || ($condition['type'] ?? null) !== $eventType) {
            return false;
        }

        return match ($eventType) {
            'explore' => $this->matchesExplore($condition, $context),
            'kill' => $this->matchesKill($condition, $context),
            'harvest' => $this->matchesHarvest($condition, $context),
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $condition
     * @param array<string, mixed> $context
     */
    private function matchesExplore(array $condition, array $context): bool
    {
        if (isset($condition['map_id']) && $condition['map_id'] !== $context['map_id']) {
            return false;
        }

        if (isset($condition['coordinates']) && $condition['coordinates'] !== $context['coordinates']) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $condition
     * @param array<string, mixed> $context
     */
    private function matchesKill(array $condition, array $context): bool
    {
        if (isset($condition['monster_slug']) && $condition['monster_slug'] !== $context['monster_slug']) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $condition
     * @param array<string, mixed> $context
     */
    private function matchesHarvest(array $condition, array $context): bool
    {
        if (isset($condition['item_slug']) && $condition['item_slug'] !== $context['item_slug']) {
            return false;
        }

        return true;
    }
}
