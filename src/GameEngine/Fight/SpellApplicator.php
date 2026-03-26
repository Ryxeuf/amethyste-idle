<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\Enum\Element;
use App\Event\Fight\MobDeadEvent;
use App\Event\Fight\PlayerDeadEvent;
use App\GameEngine\Fight\Calculator\CriticalCalculator;
use App\GameEngine\Fight\Calculator\DamageCalculator;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;
use App\GameEngine\World\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SpellApplicator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly StatusEffectManager $statusEffectManager,
        private readonly CombatLogger $combatLogger,
        private readonly DamageCalculator $damageCalculator,
        private readonly CriticalCalculator $criticalCalculator,
        private readonly WeatherService $weatherService,
        private readonly PlayerEffectiveStatsCalculator $playerEffectiveStatsCalculator,
    ) {
    }

    public function apply(Spell $spell, CharacterInterface $sender, CharacterInterface $target, array $options = []): array
    {
        $domainHeal = $options['heal'] ?? 0;
        $domainDamage = $options['damage'] ?? 0;
        $domainCritical = $options['critical'] ?? 0;
        $fight = $options['fight'] ?? null;

        $messages = [];

        $effectiveMaxLife = $target instanceof Player
            ? $this->playerEffectiveStatsCalculator->getEffectiveMaxLife($target)
            : null;

        $damage = $this->damageCalculator->computeBaseDamage($spell, $domainDamage, $target, $effectiveMaxLife);
        $heal = $this->damageCalculator->computeBaseHeal($spell, $domainHeal, $target, $effectiveMaxLife);

        // Critical hit check
        if ($this->criticalCalculator->isCritical($spell, $domainCritical)) {
            $heal = $this->criticalCalculator->applyCriticalModifier($heal);
            $damage = $this->criticalCalculator->applyCriticalModifier($damage);
            $messages[] = 'Coup critique !';
            if ($fight !== null) {
                $this->combatLogger->logCritical($fight, $sender);
            }
        }

        // Elemental resistance (reduce damage if target is a mob with resistances)
        if ($damage > 0 && $target instanceof Mob) {
            $result = $this->damageCalculator->applyElementalResistance($damage, $spell, $target);
            $damage = $result['damage'];
            if ($result['resisted']) {
                $messages[] = sprintf('%s resiste a %s !', $target->getName(), $spell->getElement()->value);
                if ($fight !== null) {
                    $this->combatLogger->logResist($fight, $target, $spell->getElement()->value);
                }
            } elseif ($result['weak']) {
                $messages[] = sprintf('%s est faible face a %s !', $target->getName(), $spell->getElement()->value);
            }
        }

        // Weather elemental modifier
        if ($damage > 0 && $fight !== null && $spell->getElement() !== Element::None) {
            $weather = $this->resolveWeather($fight);
            if ($weather !== null) {
                $modifier = $this->weatherService->getElementalModifier($weather, $spell->getElement());
                if ($modifier !== 1.0) {
                    $damage = $this->damageCalculator->applyWeatherModifier($damage, $modifier);
                    if ($modifier > 1.0) {
                        $messages[] = sprintf('La meteo renforce %s !', $spell->getElement()->label());
                    } else {
                        $messages[] = sprintf('La meteo affaiblit %s !', $spell->getElement()->label());
                    }
                }
            }
        }

        // Berserk damage modifier on sender
        if ($fight !== null && $this->statusEffectManager->isCharacterBerserk($fight, $sender)) {
            $damage = $this->damageCalculator->applyBerserkModifier($damage);
        }

        // Burn damage reduction on sender
        if ($fight !== null && $this->hasBurnEffect($fight, $sender)) {
            $damage = $this->damageCalculator->applyBurnReduction($damage);
        }

        // Shield absorption on target
        if ($damage > 0 && $fight !== null) {
            $shieldAbsorb = $this->getShieldAbsorb($fight, $target);
            if ($shieldAbsorb > 0) {
                $result = $this->damageCalculator->applyShieldAbsorption($damage, $shieldAbsorb);
                $damage = $result['damage'];
                $messages[] = sprintf('Le bouclier absorbe %d degats !', $result['absorbed']);
                $this->combatLogger->logShield($fight, $target, $result['absorbed']);
            }
        }

        $life = $target->getLife() - $damage + $heal;
        $capMax = $target instanceof Player
            ? $this->playerEffectiveStatsCalculator->getEffectiveMaxLife($target)
            : $target->getMaxLife();
        $life = min($capMax, $life);
        $life = max(0, $life);

        $target->setLife($life);

        if ($target->getLife() > 0) {
            $target->setDiedAt(null);
        } else {
            $target->setDiedAt(new \DateTime());
        }

        // Log damage and heal
        if ($fight !== null) {
            if ($damage > 0) {
                $this->combatLogger->logDamage($fight, $target, $damage, $spell->getName());
            }
            if ($heal > 0) {
                $this->combatLogger->logHeal($fight, $target, $heal, $spell->getName());
            }
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
                $this->combatLogger->logStatusApply($fight, $target, $statusEffect->getName());
            }
        }

        if ($target->isDead()) {
            if ($fight !== null) {
                $this->combatLogger->logDeath($fight, $target);
            }
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
     * Resolve the current weather from the fight's map.
     */
    private function resolveWeather(Fight $fight): ?\App\Enum\WeatherType
    {
        $player = $fight->getPlayers()->first();
        if ($player === false) {
            return null;
        }

        $map = $player->getMap();

        return $map?->getCurrentWeather();
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
