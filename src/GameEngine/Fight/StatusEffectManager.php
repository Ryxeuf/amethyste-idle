<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\FightStatusEffect;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerStatusEffect;
use App\Entity\CharacterInterface;
use App\Entity\Game\StatusEffect;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;
use Doctrine\ORM\EntityManagerInterface;

class StatusEffectManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatLogger $combatLogger,
        private readonly PlayerEffectiveStatsCalculator $playerEffectiveStatsCalculator,
    ) {
    }

    /**
     * Apply a status effect to a target within a fight.
     */
    public function applyStatusEffect(Fight $fight, CharacterInterface $target, StatusEffect $effect): void
    {
        // Check probability
        if (random_int(1, 100) > $effect->getChance()) {
            return;
        }

        // Determine target type
        $targetType = $this->getTargetType($target);

        // Check if effect already exists on this target (don't stack, refresh duration)
        $existing = $this->findExistingEffect($fight, $targetType, $target->getId(), $effect);
        if ($existing !== null) {
            $existing->setRemainingTurns($effect->getDuration());
            $existing->setAppliedAt(new \DateTime());
            $existing->setLastTickTurn(null);
            $this->entityManager->persist($existing);
            $this->entityManager->flush();

            return;
        }

        $fightStatusEffect = new FightStatusEffect();
        $fightStatusEffect->setFight($fight);
        $fightStatusEffect->setTargetType($targetType);
        $fightStatusEffect->setTargetId($target->getId());
        $fightStatusEffect->setStatusEffect($effect);
        $fightStatusEffect->setRemainingTurns($effect->getDuration());
        $fightStatusEffect->setAppliedAt(new \DateTime());

        $this->entityManager->persist($fightStatusEffect);
        $this->entityManager->flush();
    }

    /**
     * Process effects at the start of a character's turn.
     * Returns an array of messages describing what happened.
     *
     * @return array<string>
     */
    public function processStartOfTurn(Fight $fight, CharacterInterface $character): array
    {
        $messages = [];
        $activeEffects = $this->getActiveEffects($fight, $character);
        $currentTurn = $fight->getStep();

        foreach ($activeEffects as $fightEffect) {
            $effect = $fightEffect->getStatusEffect();

            // Check frequency: should this effect tick this turn?
            if (!$this->shouldTick($fightEffect, $currentTurn)) {
                $fightEffect->decrementTurn();
                $this->entityManager->persist($fightEffect);

                continue;
            }

            // Record tick turn
            $fightEffect->setLastTickTurn($currentTurn);

            // Damage over time (poison, burn)
            if ($effect->isDamaging()) {
                $damage = $effect->getDamagePerTurn();
                $newLife = max(0, $character->getLife() - $damage);
                $character->setLife($newLife);

                if ($newLife <= 0) {
                    $character->setDiedAt(new \DateTime());
                }

                $this->entityManager->persist($character);
                $messages[] = sprintf(
                    '%s subit %d dégâts de %s.',
                    $this->getCharacterName($character),
                    $damage,
                    $effect->getName()
                );
                $this->combatLogger->logStatusTick($fight, $character, $effect->getName(), $damage, 'damage');
            }

            // Heal over time (regeneration)
            if ($effect->isHealing()) {
                $heal = $effect->getHealPerTurn();
                $cap = $character instanceof Player
                    ? $this->playerEffectiveStatsCalculator->getEffectiveMaxLife($character)
                    : $character->getMaxLife();
                $newLife = min($cap, $character->getLife() + $heal);
                $character->setLife($newLife);

                $this->entityManager->persist($character);
                $messages[] = sprintf(
                    '%s récupère %d points de vie grâce à %s.',
                    $this->getCharacterName($character),
                    $heal,
                    $effect->getName()
                );
                $this->combatLogger->logStatusTick($fight, $character, $effect->getName(), $heal, 'heal');
            }

            // Decrement remaining turns
            $fightEffect->decrementTurn();

            // Invariant métier : durée négative → expirer immédiatement
            if ($fightEffect->getRemainingTurns() < 0) {
                $fightEffect->setRemainingTurns(0);
            }

            $this->entityManager->persist($fightEffect);
        }

        $this->entityManager->flush();

        // Clean expired effects
        $this->cleanExpiredEffects($fight);

        return $messages;
    }

    /**
     * Check if a character is affected by silence (cannot cast spells).
     */
    public function isCharacterSilenced(Fight $fight, CharacterInterface $character): bool
    {
        return $this->hasEffectOfType($fight, $character, StatusEffect::TYPE_SILENCE);
    }

    /**
     * Check if a character is affected by paralysis (cannot act).
     */
    public function isCharacterParalyzed(Fight $fight, CharacterInterface $character): bool
    {
        return $this->hasEffectOfType($fight, $character, StatusEffect::TYPE_PARALYSIS);
    }

    /**
     * Check if a character is affected by freeze (cannot act).
     */
    public function isCharacterFrozen(Fight $fight, CharacterInterface $character): bool
    {
        return $this->hasEffectOfType($fight, $character, StatusEffect::TYPE_FREEZE);
    }

    /**
     * Check if a character has berserk status (increased damage, reduced defense).
     */
    public function isCharacterBerserk(Fight $fight, CharacterInterface $character): bool
    {
        return $this->hasEffectOfType($fight, $character, StatusEffect::TYPE_BERSERK);
    }

    /**
     * Get stat modifiers from all active effects on a character.
     *
     * @return array<string, float> Aggregated stat modifiers
     */
    public function getStatModifiers(Fight $fight, CharacterInterface $character): array
    {
        $modifiers = [];
        $activeEffects = $this->getActiveEffects($fight, $character);

        foreach ($activeEffects as $fightEffect) {
            $effect = $fightEffect->getStatusEffect();
            if ($effect->hasStatModifier()) {
                foreach ($effect->getStatModifier() as $stat => $value) {
                    if (!isset($modifiers[$stat])) {
                        $modifiers[$stat] = 0.0;
                    }
                    $modifiers[$stat] += $value;
                }
            }
        }

        return $modifiers;
    }

    /**
     * Get all active (non-expired) effects on a character in a fight.
     *
     * @return FightStatusEffect[]
     */
    public function getActiveEffects(Fight $fight, CharacterInterface $character): array
    {
        $targetType = $this->getTargetType($character);

        return $this->entityManager->getRepository(FightStatusEffect::class)->findBy([
            'fight' => $fight,
            'targetType' => $targetType,
            'targetId' => $character->getId(),
        ]);
    }

    /**
     * Remove expired effects from a fight.
     */
    public function cleanExpiredEffects(Fight $fight): void
    {
        $allEffects = $this->entityManager->getRepository(FightStatusEffect::class)->findBy([
            'fight' => $fight,
        ]);

        foreach ($allEffects as $fightEffect) {
            if ($fightEffect->isExpired()) {
                $this->entityManager->remove($fightEffect);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Remove all status effects for a fight (cleanup on fight end).
     */
    public function clearAllEffects(Fight $fight): void
    {
        $allEffects = $this->entityManager->getRepository(FightStatusEffect::class)->findBy([
            'fight' => $fight,
        ]);

        foreach ($allEffects as $fightEffect) {
            $this->entityManager->remove($fightEffect);
        }

        $this->entityManager->flush();
    }

    /**
     * Apply a persistent (out-of-combat) status effect to a player.
     */
    public function applyPersistentEffect(Player $player, StatusEffect $effect): ?PlayerStatusEffect
    {
        if ($effect->getRealTimeDuration() === null || $effect->getRealTimeDuration() <= 0) {
            return null;
        }

        // Check probability
        if (random_int(1, 100) > $effect->getChance()) {
            return null;
        }

        // Check if effect already exists (refresh instead of stack)
        $existing = $this->findExistingPersistentEffect($player, $effect);
        if ($existing !== null) {
            $expiresAt = new \DateTime();
            $expiresAt->modify(sprintf('+%d seconds', $effect->getRealTimeDuration()));
            $existing->setExpiresAt($expiresAt);
            $existing->setAppliedAt(new \DateTime());
            $this->entityManager->persist($existing);
            $this->entityManager->flush();

            return $existing;
        }

        $playerEffect = new PlayerStatusEffect();
        $playerEffect->setPlayer($player);
        $playerEffect->setStatusEffect($effect);
        $playerEffect->setAppliedAt(new \DateTime());

        $expiresAt = new \DateTime();
        $expiresAt->modify(sprintf('+%d seconds', $effect->getRealTimeDuration()));
        $playerEffect->setExpiresAt($expiresAt);

        $this->entityManager->persist($playerEffect);
        $this->entityManager->flush();

        return $playerEffect;
    }

    /**
     * Get active persistent effects for a player (non-expired).
     *
     * @return PlayerStatusEffect[]
     */
    public function getActivePersistentEffects(Player $player): array
    {
        $allEffects = $this->entityManager->getRepository(PlayerStatusEffect::class)->findBy([
            'player' => $player,
        ]);

        $active = [];
        foreach ($allEffects as $effect) {
            if (!$effect->isExpired()) {
                $active[] = $effect;
            }
        }

        return $active;
    }

    /**
     * Get persistent stat modifiers for a player (out-of-combat buffs).
     *
     * @return array<string, float>
     */
    public function getPersistentStatModifiers(Player $player): array
    {
        $modifiers = [];
        $activeEffects = $this->getActivePersistentEffects($player);

        foreach ($activeEffects as $playerEffect) {
            $effect = $playerEffect->getStatusEffect();
            if ($effect->hasStatModifier()) {
                foreach ($effect->getStatModifier() as $stat => $value) {
                    if (!isset($modifiers[$stat])) {
                        $modifiers[$stat] = 0.0;
                    }
                    $modifiers[$stat] += $value;
                }
            }
        }

        return $modifiers;
    }

    /**
     * Clean expired persistent effects for a player.
     */
    public function cleanExpiredPersistentEffects(Player $player): void
    {
        $allEffects = $this->entityManager->getRepository(PlayerStatusEffect::class)->findBy([
            'player' => $player,
        ]);

        foreach ($allEffects as $effect) {
            if ($effect->isExpired()) {
                $this->entityManager->remove($effect);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Load persistent effects into a fight when combat starts.
     * Converts active persistent buffs into FightStatusEffects.
     */
    public function loadPersistentEffectsIntoFight(Fight $fight, Player $player): void
    {
        $persistentEffects = $this->getActivePersistentEffects($player);

        foreach ($persistentEffects as $playerEffect) {
            $effect = $playerEffect->getStatusEffect();

            // Only load buffs and HoTs into combat
            if ($effect->getCategory() !== StatusEffect::CATEGORY_BUFF && $effect->getCategory() !== StatusEffect::CATEGORY_HOT) {
                continue;
            }

            // Calculate remaining turns from real-time duration
            $remainingSeconds = $playerEffect->getRemainingSeconds();
            if ($remainingSeconds <= 0) {
                continue;
            }

            // Convert seconds to turns (minimum 1 turn)
            $remainingTurns = max(1, (int) ceil($remainingSeconds / 30));

            $fightEffect = new FightStatusEffect();
            $fightEffect->setFight($fight);
            $fightEffect->setTargetType(FightStatusEffect::TARGET_TYPE_PLAYER);
            $fightEffect->setTargetId($player->getId());
            $fightEffect->setStatusEffect($effect);
            $fightEffect->setRemainingTurns($remainingTurns);
            $fightEffect->setAppliedAt($playerEffect->getAppliedAt());

            $this->entityManager->persist($fightEffect);
        }

        $this->entityManager->flush();
    }

    /**
     * Determine if a fight status effect should tick this turn based on frequency.
     */
    private function shouldTick(FightStatusEffect $fightEffect, int $currentTurn): bool
    {
        $frequency = $fightEffect->getStatusEffect()->getFrequency();

        // No frequency set = tick every turn (default behavior)
        if ($frequency === null || $frequency <= 1) {
            return true;
        }

        $lastTick = $fightEffect->getLastTickTurn();

        // Never ticked = first tick
        if ($lastTick === null) {
            return true;
        }

        // Tick if enough turns have passed
        return ($currentTurn - $lastTick) >= $frequency;
    }

    /**
     * Check if a character has a specific effect type active.
     */
    private function hasEffectOfType(Fight $fight, CharacterInterface $character, string $type): bool
    {
        $activeEffects = $this->getActiveEffects($fight, $character);

        foreach ($activeEffects as $fightEffect) {
            if ($fightEffect->getStatusEffect()->getType() === $type && !$fightEffect->isExpired()) {
                return true;
            }
        }

        return false;
    }

    private function findExistingEffect(Fight $fight, string $targetType, int $targetId, StatusEffect $effect): ?FightStatusEffect
    {
        return $this->entityManager->getRepository(FightStatusEffect::class)->findOneBy([
            'fight' => $fight,
            'targetType' => $targetType,
            'targetId' => $targetId,
            'statusEffect' => $effect,
        ]);
    }

    private function findExistingPersistentEffect(Player $player, StatusEffect $effect): ?PlayerStatusEffect
    {
        $allEffects = $this->entityManager->getRepository(PlayerStatusEffect::class)->findBy([
            'player' => $player,
            'statusEffect' => $effect,
        ]);

        foreach ($allEffects as $playerEffect) {
            if (!$playerEffect->isExpired()) {
                return $playerEffect;
            }
        }

        return null;
    }

    private function getTargetType(CharacterInterface $target): string
    {
        if ($target instanceof Player) {
            return FightStatusEffect::TARGET_TYPE_PLAYER;
        }

        return FightStatusEffect::TARGET_TYPE_MOB;
    }

    private function getCharacterName(CharacterInterface $character): string
    {
        if ($character instanceof Player) {
            return $character->getName();
        }
        if ($character instanceof Mob) {
            return $character->getName();
        }

        return 'Inconnu';
    }
}
