<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\Enum\CombatLever;
use App\Enum\Element;
use App\Enum\SpellScope;
use App\Event\Fight\MobDeadEvent;
use App\Event\Fight\PlayerDeadEvent;
use App\GameEngine\Fight\Calculator\CriticalCalculator;
use App\GameEngine\Fight\Calculator\DamageCalculator;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;
use App\GameEngine\Progression\CombatLeverScale;
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
        private readonly CombatLeverScale $leverScale,
    ) {
    }

    public function apply(Spell $spell, CharacterInterface $sender, CharacterInterface $target, array $options = []): array
    {
        $domainHeal = $options['heal'] ?? 0;
        $domainDamage = $options['damage'] ?? 0;
        $domainCritical = $options['critical'] ?? 0;
        $fight = $options['fight'] ?? null;

        // ARC-03b — les leviers traversent le calcul, chacun n'etant lu qu'a
        // sa place. Deux porteurs, parce que les leviers offensifs sont ceux
        // de l'attaquant et les defensifs ceux de la cible : les confondre
        // ferait profiter un attaquant de la garde de son adversaire.
        $levers = $options['levers'] ?? CombatLeverEffects::none();
        $targetLevers = $options['targetLevers'] ?? CombatLeverEffects::none();

        $messages = [];

        $effectiveMaxLife = $target instanceof Player
            ? $this->playerEffectiveStatsCalculator->getEffectiveMaxLife($target)
            : null;

        $damage = $this->damageCalculator->computeBaseDamage($spell, $domainDamage, $target, $effectiveMaxLife);
        $heal = $this->damageCalculator->computeBaseHeal($spell, $domainHeal, $target, $effectiveMaxLife);

        // `dodge` — **avant tout calcul de degats**. Poser l'esquive ici et non
        // apres la resistance est la moitie de ce qui la distingue de `guard` :
        // ce qui est evite n'est pas reduit, il n'a pas lieu. Le soin passe :
        // on n'esquive pas ce qui vous soigne.
        if ($damage > 0 && $this->damageCalculator->isDodged($targetLevers, $this->leverScale)) {
            $damage = 0;
            $messages[] = sprintf('%s esquive !', $target->getName());
        }

        // `power` et `mending` — multiplicatifs sur la valeur de base.
        $damage = $this->damageCalculator->applyPower($damage, $levers, $this->leverScale);
        $heal = $this->damageCalculator->applyMending($heal, $levers, $this->leverScale);

        // Dungeon difficulty: scale mob damage output
        if ($damage > 0 && $sender instanceof Mob && $fight !== null) {
            $damageMultiplier = (float) $fight->getMetadataValue('difficulty_damage_multiplier', 1.0);
            if ($damageMultiplier > 1.0) {
                $damage = (int) round($damage * $damageMultiplier);
            }
        }

        // Critical hit check
        if ($this->criticalCalculator->isCritical($spell, $domainCritical, $levers, $this->leverScale)) {
            $heal = $this->criticalCalculator->applyCriticalModifier($heal, $levers, $this->leverScale);
            $damage = $this->criticalCalculator->applyCriticalModifier($damage, $levers, $this->leverScale);
            $messages[] = 'Coup critique !';
            if ($fight !== null) {
                $this->combatLogger->logCritical($fight, $sender);
            }
        }

        // Elemental resistance (reduce damage if target is a mob with resistances)
        // `pierce` en ignore une part — avant elle, jamais apres.
        if ($damage > 0 && $target instanceof Mob) {
            $pierce = $levers->isEmpty() ? 0.0 : $levers->pointsFor(CombatLever::Pierce, $this->leverScale);
            $result = $this->damageCalculator->applyElementalResistance($damage, $spell, $target, $pierce);
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

        // `guard` — **apres** la resistance, et apres tout ce que l'attaquant
        // apporte (meteo, berserk, brulure) : c'est la derniere reduction que
        // le corps de la cible oppose, avant le bouclier qui est un tampon
        // exterieur. La borner ici plutot que dans le bloc de resistance la
        // rend valable aussi quand la cible est un joueur, qui n'a pas de
        // resistance elementaire.
        $damage = $this->damageCalculator->applyGuard($damage, $targetLevers, $this->leverScale);

        // Invariant métier : les dégâts ne peuvent jamais être négatifs
        $damage = max(0, $damage);

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

        // ONB-11 — le second mannequin **ne peut pas tuer**.
        //
        // Le joueur doit voir sa barre descendre pour comprendre a quoi servent
        // les soins, mais l'apprentissage ne peut pas se solder par une mort. Le
        // plancher est pose **ici** et non dans le calcul des degats : il doit
        // valoir quel que soit le chemin — attaque, effet de statut, riposte —
        // sans quoi il suffirait d'un chemin oublie pour tuer un debutant.
        //
        // Un plancher a 1 et non a 0 : a 0 le personnage est mort, et le combat
        // se terminerait exactement comme on voulait l'eviter.
        if ($life < 1 && $this->isStruckByTrainingDummy($sender)) {
            $life = 1;
        }

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
                $intent = $spell->resolveIntent($statusEffect->getType());
                $scope = $spell->resolveScope($statusEffect->getType());

                // ARC-11b-b — l'intention complete est **ici**, et nulle part
                // avant : c'est le type du statut qui distingue une entrave
                // d'une protection, et il vient d'etre charge. `grip` et `ward`
                // ne se croisent donc que sur une entrave — un bouclier ne se
                // rallonge pas parce que son porteur a achete le levier
                // principal du controle.
                $aimedLevers = $levers->aimedAt($intent);
                $aimedTargetLevers = $targetLevers->aimedAt($intent);

                if (DepositLaw::deposits($intent, $scope)) {
                    // ARC-11b — la loi du depot. Le geste ne reagit pas : il
                    // pose une duree qui court **que son lanceur soit connecte
                    // ou non**, sur tous les allies s'il touche le groupe.
                    // C'est ce qui rend l'entretien jouable dans un donjon
                    // semi-synchrone, ou un soin reactif est une mecanique
                    // morte.
                    $allies = $scope === SpellScope::Group
                        ? $this->alliesOf($fight, $sender)
                        : [$target];

                    $total = $this->depositedValue($statusEffect);
                    $deposited = $this->statusEffectManager->deposit($fight, $allies, $statusEffect, $total, $aimedLevers);

                    if ($deposited > 0) {
                        $messages[] = $scope === SpellScope::Group
                            ? sprintf('%s se depose sur le groupe.', $statusEffect->getName())
                            : sprintf('%s se depose sur %s.', $statusEffect->getName(), $target->getName());
                        $this->combatLogger->logStatusApply($fight, $target, $statusEffect->getName());
                    }
                } else {
                    // `grip` de celui qui applique, `ward` de celui qui subit :
                    // les deux se croisent sur le jet d'application, et nulle part
                    // ailleurs (ARC-03b) — ni sur autre chose qu'une entrave
                    // (ARC-11b-b).
                    $this->statusEffectManager->applyStatusEffect($fight, $target, $statusEffect, $aimedLevers, $aimedTargetLevers);
                    $messages[] = sprintf('%s est affecte par %s !', $target->getName(), $statusEffect->getName());
                    $this->combatLogger->logStatusApply($fight, $target, $statusEffect->getName());
                }
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
    /**
     * Le coup vient-il d'un mannequin d'entrainement ? (ONB-11).
     *
     * Le plancher a 1 PV ne s'applique qu'aux coups d'un mannequin — jamais a
     * ceux d'un vrai monstre. Attacher la clemence a **l'agresseur** et non a la
     * cible est ce qui empeche la mesure de fuir : un joueur qui sort du Fanal
     * meurt comme tout le monde, des le premier loup.
     */
    private function isStruckByTrainingDummy(CharacterInterface $sender): bool
    {
        return $sender instanceof Mob && $sender->getMonster()->isTrainingDummy();
    }

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

    /**
     * Les allies sur lesquels un depot de groupe se pose (ARC-11b).
     *
     * Un depot lance par un joueur couvre les joueurs de la rencontre ; lance
     * par un monstre, il couvre les monstres. Le lanceur en fait partie : un
     * geste qui protege le groupe protege celui qui le lance, sinon
     * l'archetype d'encaisse paierait un tour pour couvrir tout le monde sauf
     * lui.
     *
     * @return list<CharacterInterface>
     */
    private function alliesOf(Fight $fight, CharacterInterface $sender): array
    {
        $allies = [];

        if ($sender instanceof Mob) {
            foreach ($fight->getMobs() as $mob) {
                $allies[] = $mob;
            }

            return $allies;
        }

        foreach ($fight->getPlayers() as $player) {
            $allies[] = $player;
        }

        return $allies;
    }

    /**
     * La valeur **totale** que ce depot vaut, avant etalement.
     *
     * Elle vient de la fiche de l'effet : `healPerTurn` (ou `damagePerTurn`)
     * multiplie par la duree declaree, c'est-a-dire ce que l'effet rendait
     * deja sur toute sa vie. C'est ce qui fait qu'**aucune valeur de jeu ne
     * bouge** : le depot rend le meme total qu'avant, il le rend seulement
     * selon la duree opposable plutot que selon la duree declaree.
     */
    private function depositedValue(StatusEffect $effect): int
    {
        $perTurn = $effect->isHealing()
            ? (int) $effect->getHealPerTurn()
            : (int) $effect->getDamagePerTurn();

        return $perTurn * max(1, $effect->getDuration());
    }
}
