<?php

declare(strict_types=1);

namespace App\GameEngine\Quest;

use App\Entity\App\PlayerDailyQuest;
use App\Entity\App\PlayerQuest;
use App\Entity\Game\Monster;
use App\Entity\Game\Quest;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Resolves a map of monster slug -> Monster entity for every monster referenced
 * in a journal view (tracking of active quests + requirements of available quests).
 *
 * Enables the template to render each monster name via the `localized_monster_name`
 * Twig filter while keeping the stored tracking JSON untouched.
 */
final class QuestMonsterBySlugResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param iterable<PlayerQuest>      $activeQuests
     * @param iterable<PlayerDailyQuest> $activeDailyQuests
     * @param iterable<Quest>            $availableQuests
     * @param iterable<Quest>            $availableDailyQuests
     *
     * @return array<string, Monster>
     */
    public function resolve(
        iterable $activeQuests,
        iterable $activeDailyQuests,
        iterable $availableQuests,
        iterable $availableDailyQuests,
    ): array {
        $slugs = [];

        foreach ($activeQuests as $playerQuest) {
            $this->collectTrackingSlugs($playerQuest->getTracking(), $slugs);
        }
        foreach ($activeDailyQuests as $dailyQuest) {
            $this->collectTrackingSlugs($dailyQuest->getTracking(), $slugs);
        }
        foreach ($availableQuests as $quest) {
            $this->collectRequirementSlugs($quest->getRequirements(), $slugs);
        }
        foreach ($availableDailyQuests as $quest) {
            $this->collectRequirementSlugs($quest->getRequirements(), $slugs);
        }

        if ($slugs === []) {
            return [];
        }

        $monsters = $this->entityManager->getRepository(Monster::class)
            ->findBy(['slug' => array_keys($slugs)]);

        $map = [];
        foreach ($monsters as $monster) {
            $map[$monster->getSlug()] = $monster;
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $tracking
     * @param array<string, true>  $slugs
     */
    private function collectTrackingSlugs(array $tracking, array &$slugs): void
    {
        if (!isset($tracking['monsters']) || !is_array($tracking['monsters'])) {
            return;
        }
        foreach ($tracking['monsters'] as $entry) {
            if (is_array($entry) && isset($entry['slug']) && is_string($entry['slug']) && $entry['slug'] !== '') {
                $slugs[$entry['slug']] = true;
            }
        }
    }

    /**
     * @param array<string, mixed> $requirements
     * @param array<string, true>  $slugs
     */
    private function collectRequirementSlugs(array $requirements, array &$slugs): void
    {
        if (!isset($requirements['monsters']) || !is_array($requirements['monsters'])) {
            return;
        }
        foreach ($requirements['monsters'] as $entry) {
            if (is_array($entry) && isset($entry['slug']) && is_string($entry['slug']) && $entry['slug'] !== '') {
                $slugs[$entry['slug']] = true;
            }
        }
    }
}
