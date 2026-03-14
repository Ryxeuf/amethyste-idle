<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\Event\Fight\MobDeadEvent;
use App\Event\Fight\PlayerDeadEvent;
use App\GameEngine\Item\ItemUtils;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SpellApplicator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly StatusEffectManager $statusEffectManager,
    ) {
    }

    public function apply(Spell $spell, CharacterInterface $sender, CharacterInterface $target, array $options = []): array
    {
        $domainHeal = $options['heal'] ?? 0;
        $domainDamage = $options['damage'] ?? 0;
        $domainCritical = $options['critical'] ?? 0;
        $fight = $options['fight'] ?? null;

        $messages = [];
        $isCritical = false;

        $heal = $spell->getHeal() !== null && $spell->getHeal() !== 0 ? $domainHeal + $spell->getHeal() : 0;
        $damage = $spell->getDamage() !== null && $spell->getDamage() !== 0 ? $domainDamage + $spell->getDamage() : 0;

        // Critical hit check
        if (ItemUtils::isActionCritical($spell->getCritical() + $domainCritical)) {
            $heal = ItemUtils::getCriticalModified($heal);
            $damage = ItemUtils::getCriticalModified($damage);
            $isCritical = true;
            $messages[] = 'Coup critique !';
        }

        // Elemental resistance (reduce damage if target is a mob with resistances)
        if ($damage > 0 && $target instanceof Mob) {
            $resistance = $target->getMonster()->getElementalResistance($spell->getElement());
            if ($resistance !== 0.0) {
                $damage = (int) round($damage * (1.0 - $resistance));
                $damage = max(0, $damage);
                if ($resistance > 0) {
                    $messages[] = sprintf('%s resiste a %s !', $target->getName(), $spell->getElement());
                } elseif ($resistance < 0) {
                    $messages[] = sprintf('%s est faible face a %s !', $target->getName(), $spell->getElement());
                }
            }
        }

        // Berserk damage modifier on sender
        if ($fight !== null && $this->statusEffectManager->isCharacterBerserk($fight, $sender)) {
            $damage = (int) round($damage * 1.5);
        }

        // Burn damage reduction on sender
        if ($fight !== null && $this->hasBurnEffect($fight, $sender)) {
            $damage = (int) round($damage * 0.75);
        }

        // Shield absorption on target
        if ($damage > 0 && $fight !== null) {
            $shieldAbsorb = $this->getShieldAbsorb($fight, $target);
            if ($shieldAbsorb > 0) {
                $absorbed = min($damage, $shieldAbsorb);
                $damage -= $absorbed;
                $messages[] = sprintf('Le bouclier absorbe %d degats !', $absorbed);
            }
        }

        $life = $target->getLife() - $damage + $heal;
        $life = min($target->getMaxLife(), $life);
        $life = max(0, $life);

        $target->setLife($life);

        if ($target->getLife() > 0) {
            $target->setDiedAt(null);
        } else {
            $target->setDiedAt(new DateTime());
        }

        $this->entityManager->persist($target);
        $this->entityManager->flush();
        $this->entityManager->refresh($target);

        // Apply status effect from spell
        if ($fight !== null && $spell->getStatusEffectSlug() !== null) {
            $statusEffect = $this->entityManager->getRepository(StatusEffect::class)->findOneBy([
                'slug' => $spell->getStatusEffectSlug(),
            ]);

            if ($statusEffect !== null) {
                $this->statusEffectManager->applyStatusEffect($fight, $target, $statusEffect);
                $messages[] = sprintf('%s est affecte par %s !', $target->getName(), $statusEffect->getName());
            }
        }

        if ($target->isDead()) {
            if ($target instanceof Mob) {
                $this->eventDispatcher->dispatch(new MobDeadEvent($target), MobDeadEvent::NAME);
            }
            if ($target instanceof Player) {
                $this->eventDispatcher->dispatch(new PlayerDeadEvent($target), PlayerDeadEvent::NAME);
            }
        }

        return $messages;
    }

    /**
     * Check if character has burn status effect.
     */
    private function hasBurnEffect(Fight $fight, CharacterInterface $character): bool
    {
        $effects = $this->statusEffectManager->getActiveEffects($fight, $character);
        foreach ($effects as $fightEffect) {
            if ($fightEffect->getStatusEffect()->getType() === StatusEffect::TYPE_BURN) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the total shield absorption available for a character.
     */
    private function getShieldAbsorb(Fight $fight, CharacterInterface $character): int
    {
        $effects = $this->statusEffectManager->getActiveEffects($fight, $character);
        $totalAbsorb = 0;

        foreach ($effects as $fightEffect) {
            if ($fightEffect->getStatusEffect()->getType() === StatusEffect::TYPE_SHIELD) {
                $modifiers = $fightEffect->getStatusEffect()->getStatModifier();
                if ($modifiers !== null && isset($modifiers['shield_absorb'])) {
                    $totalAbsorb += (int) $modifiers['shield_absorb'];
                }
            }
        }

        return $totalAbsorb;
    }
}
