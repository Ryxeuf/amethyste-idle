<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\Game\Spell;
use App\Event\Fight\ActionEvent;
use App\Event\Fight\MobActionHitEvent;
use App\Event\Fight\MobActionMissEvent;
use App\GameEngine\Fight\Handler\MobActionHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MobActionHandler
{
    public function __construct(
        #[AutowireIterator(tag: MobActionHandlerInterface::class)]
        private readonly iterable $handlers,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SpellApplicator $spellApplicator,
        private readonly LoggerInterface $logger,
        private readonly StatusEffectManager $statusEffectManager,
    ) {
    }

    /**
     * @return array{messages: string[], dangerAlert: string|null}
     */
    public function doAction(Fight $fight): array
    {
        $result = ['messages' => [], 'dangerAlert' => null];
        $mob = $fight->getMobs()->first();
        if (!$mob || $mob->isDead()) {
            $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);

            return $result;
        }

        // Process status effects at start of mob turn
        $statusMessages = $this->statusEffectManager->processStartOfTurn($fight, $mob);
        $result['messages'] = array_merge($result['messages'], $statusMessages);

        // Check if paralyzed or frozen
        if ($this->statusEffectManager->isCharacterParalyzed($fight, $mob)
            || $this->statusEffectManager->isCharacterFrozen($fight, $mob)) {
            $this->logger->debug('[MobActionHandler] Mob is paralyzed/frozen, skipping turn');
            $result['messages'][] = sprintf('%s est immobilise !', $mob->getName());
            $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);

            return $result;
        }

        $action = $this->generateAction($fight);
        $spell = $this->resolveSpell($action, $mob);

        $this->logger->debug(sprintf('[MobActionHandler] Spell %s used by mob #%d', $spell->getName(), $mob->getId()));

        $target = $fight->getPlayers()->first();
        if (!$target || $target->isDead()) {
            $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);

            return $result;
        }

        // Danger alert check: warn player about upcoming powerful attack
        $dangerAlert = $this->checkDangerAlert($fight, $mob);
        if ($dangerAlert !== null) {
            $result['dangerAlert'] = $dangerAlert;
        }

        if (FightCalculator::hasAttackHit($mob->getMonster()->getHit())) {
            $spellMessages = $this->spellApplicator->apply($spell, $mob, $target, ['fight' => $fight]);
            $result['messages'][] = sprintf('%s utilise %s !', $mob->getName(), $spell->getName());
            $result['messages'] = array_merge($result['messages'], $spellMessages);
            $this->eventDispatcher->dispatch(new MobActionHitEvent($spell->getName()), MobActionHitEvent::NAME);
        } else {
            $result['messages'][] = sprintf('%s rate son attaque !', $mob->getName());
            $this->eventDispatcher->dispatch(new MobActionMissEvent($spell->getName()), MobActionMissEvent::NAME);
        }

        // Decrement cooldowns for mob
        $fight->decrementAllCooldowns();

        $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);

        return $result;
    }

    /**
     * Resolve the actual spell to use based on action type.
     */
    private function resolveSpell(string $action, Mob $mob): Spell
    {
        // For 'spell' action, pick from the monster's spell pool
        if ($action === 'spell' && $mob->getMonster()->getSpells()->count() > 0) {
            $spells = $mob->getMonster()->getSpells()->toArray();

            return $spells[array_rand($spells)];
        }

        // For 'heal' action, find a healing spell
        if ($action === 'heal') {
            foreach ($mob->getMonster()->getSpells() as $spell) {
                if ($spell->getHeal() !== null && $spell->getHeal() > 0) {
                    return $spell;
                }
            }
        }

        // Fallback to handlers (attack handler)
        foreach ($this->handlers as $handler) {
            if ($handler->supports($action)) {
                return $handler->getSpell($mob);
            }
        }

        // Ultimate fallback: basic attack
        return $mob->getAttack();
    }

    /**
     * Generate mob action based on AI pattern.
     *
     * AI pattern JSON format:
     * {
     *   "low_hp_heal": { "threshold": 30, "action": "heal" },
     *   "spell_chance": 40,
     *   "preferred_element": "fire",
     *   "sequence": ["attack", "attack", "spell"],
     *   "danger_alert": { "threshold": 30, "message": "...", "spell": "..." },
     *   "role": "healer"
     * }
     */
    private function generateAction(Fight $fight): string
    {
        $mob = $fight->getMobs()->first();
        if (!$mob) {
            return 'attack';
        }

        $monster = $mob->getMonster();
        $aiPattern = $monster->getAiPattern();

        // No AI pattern: basic attack
        if (!$aiPattern) {
            return $this->defaultAi($mob);
        }

        // Check low HP heal behavior
        if (isset($aiPattern['low_hp_heal'])) {
            $threshold = $aiPattern['low_hp_heal']['threshold'] ?? 30;
            $hpPercent = ($mob->getLife() / $mob->getMaxLife()) * 100;
            if ($hpPercent <= $threshold && $this->mobHasHealSpell($mob)) {
                return 'heal';
            }
        }

        // Boss phase-based behavior
        if ($monster->isBoss() && $monster->getBossPhases()) {
            $hpPercent = ($mob->getLife() / $mob->getMaxLife()) * 100;
            $phase = $monster->getCurrentBossPhase((int) $hpPercent);
            if ($phase && isset($phase['action'])) {
                return $phase['action'];
            }
        }

        // Sequential pattern: follow a predefined action sequence
        if (isset($aiPattern['sequence'])) {
            $sequence = $aiPattern['sequence'];
            $step = $fight->getStep();
            $index = $step % count($sequence);
            $sequenceAction = $sequence[$index];

            // If the sequence says 'spell' but no spells available, fall back to attack
            if ($sequenceAction === 'spell' && $monster->getSpells()->count() === 0) {
                return 'attack';
            }

            return $sequenceAction;
        }

        // Spell chance
        $spellChance = $aiPattern['spell_chance'] ?? 30;
        if (random_int(1, 100) <= $spellChance && $monster->getSpells()->count() > 0) {
            return 'spell';
        }

        return 'attack';
    }

    /**
     * Default AI when no pattern is set.
     */
    private function defaultAi(Mob $mob): string
    {
        if ($mob->getMonster()->getSpells()->count() > 0 && random_int(1, 100) <= 25) {
            return 'spell';
        }

        return 'attack';
    }

    private function mobHasHealSpell(Mob $mob): bool
    {
        foreach ($mob->getMonster()->getSpells() as $spell) {
            if ($spell->getHeal() !== null && $spell->getHeal() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the mob should display a danger alert.
     */
    private function checkDangerAlert(Fight $fight, Mob $mob): ?string
    {
        $monster = $mob->getMonster();
        $aiPattern = $monster->getAiPattern();

        if ($aiPattern === null) {
            return null;
        }

        $hpPercent = ($mob->getLife() / $mob->getMaxLife()) * 100;

        // Boss phase danger messages
        if ($monster->isBoss() && $monster->getBossPhases()) {
            $phase = $monster->getCurrentBossPhase((int) $hpPercent);
            if ($phase && isset($phase['danger_message'])) {
                return $phase['danger_message'];
            }
        }

        // AI pattern danger alert
        if (isset($aiPattern['danger_alert'])) {
            $alertThreshold = $aiPattern['danger_alert']['threshold'] ?? 30;
            if ($hpPercent <= $alertThreshold) {
                return $aiPattern['danger_alert']['message'] ?? null;
            }
        }

        return null;
    }
}
