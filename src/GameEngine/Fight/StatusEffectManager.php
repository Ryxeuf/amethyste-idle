<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\FightStatusEffect;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use App\Entity\Game\StatusEffect;
use Doctrine\ORM\EntityManagerInterface;

class StatusEffectManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
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
        $targetType = $this->getTargetType($character);
        $activeEffects = $this->getActiveEffects($fight, $character);

        foreach ($activeEffects as $fightEffect) {
            $effect = $fightEffect->getStatusEffect();

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
            }

            // Heal over time (regeneration)
            if ($effect->isHealing()) {
                $heal = $effect->getHealPerTurn();
                $newLife = min($character->getMaxLife(), $character->getLife() + $heal);
                $character->setLife($newLife);

                $this->entityManager->persist($character);
                $messages[] = sprintf(
                    '%s récupère %d points de vie grâce à %s.',
                    $this->getCharacterName($character),
                    $heal,
                    $effect->getName()
                );
            }

            // Decrement remaining turns
            $fightEffect->decrementTurn();
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
