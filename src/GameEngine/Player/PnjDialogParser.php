<?php

namespace App\GameEngine\Player;

use App\Entity\App\DomainExperience;
use App\Entity\App\PlayerItem;
use App\Entity\App\PlayerQuest;
use App\Entity\App\PlayerQuestCompleted;
use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Parses NPC dialog JSON with conditional branching and variable substitution.
 *
 * Supported conditions in "conditional_next":
 *   - quest:              [ids] — player has completed these quests
 *   - quest_not:          [ids] — player has NOT started/completed these quests
 *   - quest_active:       [ids] — player has these quests in progress (not completed)
 *   - quest_choice:       {"questId": "choiceKey"} — player made this choice on completed quest
 *   - has_item:           [slugs] — player owns at least one of these items
 *   - domain_xp_min:      {"domain_id": min_xp} — player has enough XP in domain
 *   - tutorial_step:      [values] — player is on one of these tutorial steps (0-4)
 *   - tutorial_completed: true — player has completed the tutorial
 *
 * Variable substitution in text ({{var}}):
 *   - {{player_name}} — current player name
 *   - {{pnj_name}}    — NPC name (requires setPnj() before parsing)
 */
class PnjDialogParser
{
    private ?Pnj $pnj = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    public function setPnj(Pnj $pnj): void
    {
        $this->pnj = $pnj;
    }

    public function parseDialog(array $dialog): array
    {
        foreach ($dialog as $idx => $sentence) {
            // Resolve conditional branching
            if (isset($sentence['conditional_next'])) {
                foreach ($sentence['conditional_next'] as $nextCondition) {
                    $hasNext = true;
                    if (isset($nextCondition['next_condition'])) {
                        foreach ($nextCondition['next_condition'] as $condition => $value) {
                            $hasNext = $hasNext && $this->evaluateCondition($condition, $value);
                        }
                    }
                    if ($hasNext) {
                        $dialog[$idx]['next'] = $nextCondition['next'];
                        break;
                    }
                }
                unset($dialog[$idx]['conditional_next']);
            }

            // Variable substitution in text
            if (isset($dialog[$idx]['text'])) {
                $dialog[$idx]['text'] = $this->substituteVariables($dialog[$idx]['text']);
            }

            // Variable substitution in choice labels + inject pnj_id for open_shop
            if (isset($dialog[$idx]['choices'])) {
                foreach ($dialog[$idx]['choices'] as $ci => $choice) {
                    if (isset($choice['text'])) {
                        $dialog[$idx]['choices'][$ci]['text'] = $this->substituteVariables($choice['text']);
                    }
                    // Auto-inject pnj_id for open_shop and quest_deliver actions
                    if (isset($choice['action']) && \in_array($choice['action'], ['open_shop', 'quest_deliver'], true) && $this->pnj) {
                        $dialog[$idx]['choices'][$ci]['datas']['pnj_id'] = $this->pnj->getId();
                    }
                }
            }
        }

        return $dialog;
    }

    private function evaluateCondition(string $condition, mixed $value): bool
    {
        return match ($condition) {
            'quest_not' => $this->questNot($value),
            'quest' => $this->quest($value),
            'quest_active' => $this->questActive($value),
            'quest_prerequisites_met' => $this->questPrerequisitesMet($value),
            'quest_choice' => $this->questChoice($value),
            'has_item' => $this->hasItem($value),
            'domain_xp_min' => $this->domainXpMin($value),
            'tutorial_step' => $this->tutorialStep($value),
            'tutorial_completed' => $this->tutorialCompleted(),
            default => true,
        };
    }

    private function substituteVariables(string $text): string
    {
        $player = $this->playerHelper->getPlayer();
        $variables = [
            'player_name' => $player?->getName() ?? 'Aventurier',
            'pnj_name' => $this->pnj?->getName() ?? 'PNJ',
        ];

        return preg_replace_callback('/\{\{(\w+)\}\}/', function (array $matches) use ($variables) {
            return $variables[$matches[1]] ?? $matches[0];
        }, $text);
    }

    private function questNot(array $ids): bool
    {
        $player = $this->playerHelper->getPlayer();
        $currentQuests = $this->entityManager->getRepository(PlayerQuest::class)->findBy(['player' => $player, 'quest' => $ids]);
        $completedQuests = $this->entityManager->getRepository(PlayerQuestCompleted::class)->findBy(['player' => $player, 'quest' => $ids]);

        return (count($currentQuests) + count($completedQuests)) === 0;
    }

    private function quest(array $ids): bool
    {
        $player = $this->playerHelper->getPlayer();
        $completedQuests = $this->entityManager->getRepository(PlayerQuestCompleted::class)->findBy(['player' => $player, 'quest' => $ids]);

        return count($completedQuests) >= count($ids);
    }

    private function questActive(array $ids): bool
    {
        $player = $this->playerHelper->getPlayer();
        $activeQuests = $this->entityManager->getRepository(PlayerQuest::class)->findBy(['player' => $player, 'quest' => $ids]);

        return count($activeQuests) >= count($ids);
    }

    /**
     * Check that all prerequisite quests for the given quest IDs are completed.
     */
    private function questPrerequisitesMet(array $questIds): bool
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return false;
        }

        foreach ($questIds as $questId) {
            $quest = $this->entityManager->getRepository(Quest::class)->find($questId);
            if (!$quest) {
                continue;
            }

            $prerequisites = $quest->getPrerequisiteQuests();
            if (empty($prerequisites)) {
                continue;
            }

            $completedPrereqs = $this->entityManager->getRepository(PlayerQuestCompleted::class)->findBy([
                'player' => $player,
                'quest' => $prerequisites,
            ]);
            if (\count($completedPrereqs) < \count($prerequisites)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check that the player made a specific choice on a completed quest.
     *
     * @param array<int|string, string> $questChoices map of questId => choiceKey
     */
    private function questChoice(array $questChoices): bool
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return false;
        }

        foreach ($questChoices as $questId => $expectedChoice) {
            $completed = $this->entityManager->getRepository(PlayerQuestCompleted::class)->findOneBy([
                'player' => $player,
                'quest' => (int) $questId,
            ]);

            if (!$completed || $completed->getChoiceMade() !== $expectedChoice) {
                return false;
            }
        }

        return true;
    }

    private function hasItem(array $slugs): bool
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return false;
        }

        $qb = $this->entityManager->createQueryBuilder();
        $count = $qb->select('COUNT(pi.id)')
            ->from(PlayerItem::class, 'pi')
            ->join('pi.genericItem', 'i')
            ->join('pi.inventory', 'inv')
            ->where('inv.player = :player')
            ->andWhere('i.slug IN (:slugs)')
            ->setParameter('player', $player)
            ->setParameter('slugs', $slugs)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    private function domainXpMin(array $requirements): bool
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return false;
        }

        foreach ($requirements as $domainId => $minXp) {
            $domainExp = $this->entityManager->getRepository(DomainExperience::class)->findOneBy([
                'player' => $player,
                'domain' => $domainId,
            ]);

            if (!$domainExp || $domainExp->getTotalExperience() < $minXp) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int> $steps Tutorial step values (0-4) to match against
     */
    private function tutorialStep(array $steps): bool
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return false;
        }

        $currentStep = $player->getTutorialStep();

        return null !== $currentStep && \in_array($currentStep, $steps, true);
    }

    private function tutorialCompleted(): bool
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return false;
        }

        return null === $player->getTutorialStep();
    }
}
