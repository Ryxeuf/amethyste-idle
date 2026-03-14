<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\Game\Spell;
use App\Event\Fight\ActionEvent;
use App\Event\Fight\MobActionHitEvent;
use App\Event\Fight\MobActionMissEvent;
use App\GameEngine\Fight\Handler\MobActionHandlerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MobActionHandler
{
    /**
     * @var iterable|MobActionHandlerInterface[]
     */
    protected $handlers;

    /**
     * @param MobActionHandlerInterface[]|iterable $handlers
     */
    public function __construct(
        iterable $handlers,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SpellApplicator $spellApplicator,
        private readonly LoggerInterface $logger,
        private readonly StatusEffectManager $statusEffectManager,
    ) {
        $this->handlers = $handlers;
    }

    public function doAction(Fight $fight): void
    {
        $mob = $fight->getMobs()->first();
        if (!$mob || $mob->isDead()) {
            $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);
            return;
        }

        // Process status effects at start of mob turn
        $this->statusEffectManager->processStartOfTurn($fight, $mob);

        // Check if paralyzed or frozen
        if ($this->statusEffectManager->isCharacterParalyzed($fight, $mob)
            || $this->statusEffectManager->isCharacterFrozen($fight, $mob)) {
            $this->logger->debug('[MobActionHandler] Mob is paralyzed/frozen, skipping turn');
            $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);
            return;
        }

        $action = $this->generateAction($fight);
        $spell = $this->getSpell($action, $mob);

        $this->logger->debug(sprintf('[MobActionHandler] Spell %s used by mob #%d', $spell->getName(), $mob->getId()));

        $target = $fight->getPlayers()->first();
        if (!$target || $target->isDead()) {
            $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);
            return;
        }

        if (FightCalculator::hasAttackHit($mob->getMonster()->getHit())) {
            $this->spellApplicator->apply($spell, $mob, $target);
            $this->eventDispatcher->dispatch(new MobActionHitEvent($spell->getName()), MobActionHitEvent::NAME);
        } else {
            $this->eventDispatcher->dispatch(new MobActionMissEvent($spell->getName()), MobActionMissEvent::NAME);
        }

        // Decrement cooldowns for mob
        $fight->decrementAllCooldowns();

        $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);
    }

    private function getSpell(string $action, Mob $mob): Spell
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($action)) {
                return $handler->getSpell($mob);
            }
        }

        throw new Exception('No spell available for this action');
    }

    /**
     * Generate mob action based on AI pattern or simple heuristics.
     *
     * AI pattern JSON format:
     * {
     *   "low_hp_heal": { "threshold": 30, "action": "heal" },
     *   "spell_chance": 40,
     *   "preferred_element": "fire"
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

        // Spell chance
        $spellChance = $aiPattern['spell_chance'] ?? 30;
        if (random_int(1, 100) <= $spellChance && $monster->getSpells()->count() > 0) {
            return 'spell';
        }

        return 'attack';
    }

    /**
     * Default AI when no pattern is set: attack, with occasional spell use.
     */
    private function defaultAi(Mob $mob): string
    {
        // 25% chance to use a spell if available
        if ($mob->getMonster()->getSpells()->count() > 0 && random_int(1, 100) <= 25) {
            return 'spell';
        }

        return 'attack';
    }

    private function mobHasHealSpell(Mob $mob): bool
    {
        foreach ($mob->getMonster()->getSpells() as $spell) {
            if ($spell->getHeal() > 0) {
                return true;
            }
        }

        return false;
    }
}
