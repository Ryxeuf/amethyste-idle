<?php

namespace App\Service\Quest;

use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use App\GameEngine\Quest\DailyQuestService;
use App\GameEngine\Quest\PlayerQuestHelper;
use App\GameEngine\Quest\QuestGiverResolver;
use App\Helper\PlayerHelper;

/**
 * Construit le payload JSON du journal de quetes pour /api/v1/quests
 * (migration API-first, phase 3.3). Lecture seule : reprend les donnees
 * de l'ecran Twig game/quests (actives, disponibles, terminees, journalieres).
 */
class QuestJournalPayloadBuilder
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerQuestHelper $playerQuestHelper,
        private readonly QuestGiverResolver $questGiverResolver,
        private readonly DailyQuestService $dailyQuestService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(?string $locale = null): array
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            throw new \LogicException('Aucun joueur courant pour construire le journal de quetes.');
        }

        $activeQuests = $this->playerQuestHelper->getCurrentQuests();
        $availableQuests = $this->playerQuestHelper->getAvailableQuests();

        $allQuests = array_merge(
            $availableQuests,
            array_map(fn ($pq) => $pq->getQuest(), $activeQuests),
        );
        $questGivers = $this->questGiverResolver->getQuestGivers($allQuests);

        $active = [];
        foreach ($activeQuests as $playerQuest) {
            $quest = $playerQuest->getQuest();
            $active[] = [
                'playerQuestId' => $playerQuest->getId(),
                'quest' => $this->serializeQuest($quest, $locale),
                'progress' => $this->playerQuestHelper->getPlayerQuestProgress($playerQuest),
                'tracking' => $playerQuest->getTracking(),
                'giver' => $this->serializeGiver($questGivers[$quest->getId()] ?? null),
                'type' => $this->questGiverResolver->getQuestType($quest),
                'chain' => $this->questGiverResolver->getChainInfo($quest),
            ];
        }

        $available = [];
        foreach ($availableQuests as $quest) {
            $available[] = [
                'quest' => $this->serializeQuest($quest, $locale),
                'giver' => $this->serializeGiver($questGivers[$quest->getId()] ?? null),
                'type' => $this->questGiverResolver->getQuestType($quest),
                'chain' => $this->questGiverResolver->getChainInfo($quest),
            ];
        }

        $completed = [];
        foreach ($this->playerQuestHelper->getCompletedQuests() as $completedQuest) {
            $quest = $completedQuest->getQuest();
            $completed[] = [
                'id' => $quest->getId(),
                'name' => $quest->getLocalizedName($locale),
            ];
        }

        return [
            'active' => $active,
            'available' => $available,
            'completed' => $completed,
            'daily' => $this->buildDaily($locale),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDaily(?string $locale): array
    {
        $player = $this->playerHelper->getPlayer();

        $activeDailyQuests = $this->dailyQuestService->getActiveDailyQuests($player);
        $completedDailyQuests = $this->dailyQuestService->getCompletedDailyQuests($player);

        $busyQuestIds = [];
        foreach (array_merge($activeDailyQuests, $completedDailyQuests) as $dailyQuest) {
            $busyQuestIds[] = $dailyQuest->getQuest()->getId();
        }

        $active = [];
        foreach ($activeDailyQuests as $dailyQuest) {
            $active[] = [
                'id' => $dailyQuest->getId(),
                'quest' => $this->serializeQuest($dailyQuest->getQuest(), $locale),
                'progress' => $this->dailyQuestService->getProgress($dailyQuest),
                'tracking' => $dailyQuest->getTracking(),
            ];
        }

        $completed = [];
        foreach ($completedDailyQuests as $dailyQuest) {
            $completed[] = [
                'id' => $dailyQuest->getId(),
                'quest' => [
                    'id' => $dailyQuest->getQuest()->getId(),
                    'name' => $dailyQuest->getQuest()->getLocalizedName($locale),
                ],
            ];
        }

        $available = [];
        foreach ($this->dailyQuestService->getTodayQuests() as $quest) {
            if (\in_array($quest->getId(), $busyQuestIds, true)) {
                continue;
            }
            $available[] = ['quest' => $this->serializeQuest($quest, $locale)];
        }

        return [
            'active' => $active,
            'completed' => $completed,
            'available' => $available,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeQuest(Quest $quest, ?string $locale): array
    {
        return [
            'id' => $quest->getId(),
            'name' => $quest->getLocalizedName($locale),
            'description' => $quest->getLocalizedDescription($locale),
            'requirements' => $quest->getRequirements(),
            'rewards' => $quest->getRewards(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeGiver(?Pnj $pnj): ?array
    {
        if ($pnj === null) {
            return null;
        }

        return [
            'id' => $pnj->getId(),
            'name' => $pnj->getName(),
        ];
    }
}
