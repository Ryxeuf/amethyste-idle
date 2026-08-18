<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\FightStatusEffect;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerStatusEffect;
use App\Entity\CharacterInterface;
use App\Entity\Game\StatusEffect;
use App\Enum\CombatLever;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;
use App\GameEngine\Progression\CombatLeverScale;
use Doctrine\ORM\EntityManagerInterface;

class StatusEffectManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatLogger $combatLogger,
        private readonly PlayerEffectiveStatsCalculator $playerEffectiveStatsCalculator,
        private readonly CombatLeverScale $leverScale,
    ) {
    }

    /**
     * Apply a status effect to a target within a fight.
     *
     * **`grip` et `ward` se rencontrent ici, et nulle part ailleurs** (ARC-03b).
     * `grip` est le levier principal du controle : il porte la duree **et**
     * l'intensite de ce qu'on applique. `ward` est ce qui resiste a
     * l'application — pas ce qui raccourcit un statut deja pose, sinon il ferait
     * double emploi. Les deux se croisent donc sur le **jet d'application**, en
     * un seul point : la chance effective est celle du statut, augmentee par
     * l'emprise de celui qui l'applique et reduite par la sauvegarde de celui
     * qui la subit.
     *
     * Les marques elementaires (ARC-13) passeront par ce meme chemin — c'est ce
     * qui fait que `grip` aura un objet.
     */
    public function applyStatusEffect(Fight $fight, CharacterInterface $target, StatusEffect $effect, ?CombatLeverEffects $casterLevers = null, ?CombatLeverEffects $targetLevers = null): void
    {
        // Check probability
        if (random_int(1, 100) > $this->effectiveChance($effect, $casterLevers, $targetLevers)) {
            return;
        }

        // Determine target type
        $targetType = $this->getTargetType($target);

        // ARC-18b — **une posture chasse la precedente.** L'exclusivite est le
        // garde-fou qui fait de la forme une decision : deux postures cumulees
        // seraient deux capstones portes ensemble, et surtout il n'y aurait
        // plus rien a arbitrer — on les prendrait toutes. On retire donc
        // **avant** de poser, pour qu'aucun instant du combat ne voie deux
        // postures actives.
        if (StanceLaw::isStance($effect)) {
            $this->clearStancesOf($fight, $targetType, $target->getId(), $effect);
        }

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
        $fightStatusEffect->setRemainingTurns(
            StanceLaw::isStance($effect)
                ? StanceLaw::HELD
                : $this->effectiveDuration($effect, $casterLevers)
        );
        $fightStatusEffect->setAppliedAt(new \DateTime());

        $this->entityManager->persist($fightStatusEffect);
        $this->entityManager->flush();
    }

    /**
     * Retirer les postures deja tenues par ce personnage (ARC-18b).
     *
     * Toutes sauf celle qu'on s'apprete a poser : reposer la posture qu'on
     * tient deja doit etre un geste **sans effet**, et non un geste qui la
     * retire puis la remet. Le rafraichissement d'`applyStatusEffect()` s'en
     * charge ensuite, et il ne change rien puisqu'une posture ne se decompte
     * pas.
     */
    private function clearStancesOf(Fight $fight, string $targetType, int $targetId, StatusEffect $incoming): void
    {
        $existing = $this->entityManager->getRepository(FightStatusEffect::class)->findBy([
            'fight' => $fight,
            'targetType' => $targetType,
            'targetId' => $targetId,
        ]);

        $removed = false;
        foreach ($existing as $fightEffect) {
            if (!StanceLaw::isStance($fightEffect->getStatusEffect())) {
                continue;
            }

            if ($fightEffect->getStatusEffect()->getId() === $incoming->getId()) {
                continue;
            }

            $this->entityManager->remove($fightEffect);
            $removed = true;
        }

        if ($removed) {
            $this->entityManager->flush();
        }
    }

    /**
     * Deposer un effet sur des allies, pour la duree de la rencontre (ARC-11b).
     *
     * C'est la loi du depot (`DepositLaw`, GAME_ARCHETYPES § 7 bis) rendue
     * executable : un geste qui touche le groupe **ne reagit pas**, il pose
     * une duree qui court que son lanceur soit connecte ou non. Le depot ne
     * passe **pas** par le jet de chance d'`applyStatusEffect()` — on ne
     * provisionne pas au hasard : ce qui est paye d'un tour est pose.
     *
     * La valeur totale est fixee par le geste ; la duree ne fait que
     * l'**etaler**. Chaque allie recoit la meme part par tour, et c'est la
     * multiplication par les corps — jamais le chiffre affiche — qui fait la
     * valeur d'un depot en groupe.
     *
     * @param iterable<CharacterInterface> $allies
     */
    public function deposit(Fight $fight, iterable $allies, StatusEffect $effect, int $totalValue, ?CombatLeverEffects $casterLevers = null): int
    {
        $duration = DepositLaw::durationFor($this->effectiveDuration($effect, $casterLevers));
        $perTurn = DepositLaw::spreadPerTurn($totalValue, $duration);

        $deposited = 0;
        foreach ($allies as $ally) {
            if ($ally->isDead()) {
                continue;
            }

            $targetType = $this->getTargetType($ally);
            $existing = $this->findExistingEffect($fight, $targetType, $ally->getId(), $effect);

            if ($existing !== null) {
                // Un depot ne s'empile pas : il se **renouvelle**. Empiler
                // ferait d'un tour rejoue le levier le moins cher du jeu.
                $existing->setRemainingTurns($duration);
                $existing->setValuePerTurn($perTurn > 0 ? $perTurn : null);
                $existing->setAppliedAt(new \DateTime());
                $existing->setLastTickTurn(null);
                $this->entityManager->persist($existing);
                ++$deposited;

                continue;
            }

            $fightStatusEffect = new FightStatusEffect();
            $fightStatusEffect->setFight($fight);
            $fightStatusEffect->setTargetType($targetType);
            $fightStatusEffect->setTargetId($ally->getId());
            $fightStatusEffect->setStatusEffect($effect);
            $fightStatusEffect->setRemainingTurns($duration);
            $fightStatusEffect->setValuePerTurn($perTurn > 0 ? $perTurn : null);
            $fightStatusEffect->setAppliedAt(new \DateTime());

            $this->entityManager->persist($fightStatusEffect);
            ++$deposited;
        }

        if ($deposited > 0) {
            $this->entityManager->flush();
        }

        return $deposited;
    }

    /**
     * La chance d'application, emprise et sauvegarde comprises (ARC-03b).
     *
     * **Le plancher ne s'applique qu'a ce que les leviers deplacent.** Un statut
     * qu'aucun levier ne touche garde exactement la chance que sa fiche declare,
     * **y compris zero** : un `chance: 0` est un « jamais » voulu par l'auteur
     * (les gestes qui portent un statut sans jamais l'appliquer d'eux-memes),
     * pas un jet a arrondir. Une premiere redaction bornait a [1, 100] sans
     * distinction et transformait ce « jamais » en 1 % — une valeur de jeu
     * changee en silence, que seul un tirage sur cent revelait.
     *
     * Quand un levier joue, en revanche, le resultat reste borne : `grip` ne
     * rend pas une application certaine et `ward` ne la rend pas impossible,
     * sans quoi la fonction de controle n'aurait plus de risque a prendre.
     */
    private function effectiveChance(StatusEffect $effect, ?CombatLeverEffects $casterLevers, ?CombatLeverEffects $targetLevers): int
    {
        $declared = $effect->getChance();

        $caster = $casterLevers !== null && !$casterLevers->isEmpty();
        $target = $targetLevers !== null && !$targetLevers->isEmpty();
        if (!$caster && !$target) {
            return $declared;
        }

        $chance = (float) $declared;
        if ($caster) {
            $chance *= $casterLevers->multiplierFor(CombatLever::Grip, $this->leverScale);
        }
        if ($target) {
            // `ward` porte un taux positif : il **resiste**, donc on divise
            // l'effet au lieu de le multiplier. Lui donner un taux negatif
            // aurait rendu « +10 % de sauvegarde » illisible dans un arbre.
            $chance /= $targetLevers->multiplierFor(CombatLever::Ward, $this->leverScale);
        }

        // Le plancher suit la valeur declaree : un « jamais » reste un jamais.
        return max(min(1, $declared), min(100, (int) round($chance)));
    }

    /**
     * La duree d'un statut applique, emprise comprise (ARC-03b).
     *
     * `ward` n'entre pas ici : il resiste a l'application, il ne raccourcit pas
     * ce qui a ete pose. Le faire agir aux deux endroits lui donnerait deux
     * places dans la formule pour un seul levier.
     */
    private function effectiveDuration(StatusEffect $effect, ?CombatLeverEffects $casterLevers): int
    {
        $duration = $effect->getDuration();
        if ($casterLevers === null || $casterLevers->isEmpty()) {
            return $duration;
        }

        return max($duration, (int) round($duration * $casterLevers->multiplierFor(CombatLever::Grip, $this->leverScale)));
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

            // ARC-18b — **une posture ne vieillit pas.** Elle ne finit pas en
            // se decomptant : elle finit parce qu'on en pose une autre, ou
            // parce que la rencontre s'acheve (`clearAllEffects()`). Lui
            // donner une duree la ramenerait a une amelioration ordinaire, et
            // la decision qu'elle porte cesserait d'en etre une — il suffirait
            // d'attendre.
            if (StanceLaw::holdsThroughTheTurn($effect)) {
                continue;
            }

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
                $damage = $fightEffect->getValuePerTurn() ?? $effect->getDamagePerTurn();
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
                // ARC-11b : un depot etale sa valeur, donc c'est
                // l'**application** qui dit ce qu'elle rend par tour. `null`
                // veut dire « rien n'a ete etale » — la fiche repond, et rien
                // ne bouge par rapport a avant ce jalon.
                $heal = $fightEffect->getValuePerTurn() ?? $effect->getHealPerTurn();
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
