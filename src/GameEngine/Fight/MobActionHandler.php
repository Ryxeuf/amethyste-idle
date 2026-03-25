<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\CharacterInterface;
use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\Event\Fight\ActionEvent;
use App\Event\Fight\MobActionHitEvent;
use App\Event\Fight\MobActionMissEvent;
use App\GameEngine\Fight\Handler\MobActionHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MobActionHandler
{
    private const MAX_SUMMONS_PER_FIGHT = 2;

    public function __construct(
        #[AutowireIterator(tag: MobActionHandlerInterface::class)]
        private readonly iterable $handlers,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SpellApplicator $spellApplicator,
        private readonly LoggerInterface $logger,
        private readonly StatusEffectManager $statusEffectManager,
        private readonly CombatLogger $combatLogger,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Exécute l'action de TOUS les mobs vivants du combat.
     *
     * @return array{messages: string[], dangerAlert: string|null}
     */
    public function doAction(Fight $fight): array
    {
        $result = ['messages' => [], 'dangerAlert' => null];

        foreach ($fight->getMobs() as $mob) {
            if ($mob->isDead()) {
                continue;
            }

            $mobResult = $this->doMobAction($fight, $mob);
            $result['messages'] = array_merge($result['messages'], $mobResult['messages']);
            if ($mobResult['dangerAlert'] !== null) {
                $result['dangerAlert'] = $mobResult['dangerAlert'];
            }

            // Si tous les joueurs sont morts, arrêter
            if ($fight->isTerminated()) {
                break;
            }
        }

        $this->eventDispatcher->dispatch(new ActionEvent($fight->getId()), ActionEvent::NAME);

        return $result;
    }

    /**
     * Exécute l'action d'un mob spécifique.
     *
     * @return array{messages: string[], dangerAlert: string|null}
     */
    private function doMobAction(Fight $fight, Mob $mob): array
    {
        $result = ['messages' => [], 'dangerAlert' => null];

        // Process status effects at start of mob turn
        $statusMessages = $this->statusEffectManager->processStartOfTurn($fight, $mob);
        $result['messages'] = array_merge($result['messages'], $statusMessages);

        // Check if paralyzed or frozen
        if ($this->statusEffectManager->isCharacterParalyzed($fight, $mob)
            || $this->statusEffectManager->isCharacterFrozen($fight, $mob)) {
            $this->logger->debug('[MobActionHandler] Mob is paralyzed/frozen, skipping turn');
            $result['messages'][] = sprintf('%s est immobilise !', $mob->getName());
            $this->combatLogger->logImmobilized($fight, $mob);

            return $result;
        }

        $action = $this->generateAction($fight, $mob);

        // Invocation : court-circuite le flux normal d'attaque
        if ($action === 'summon') {
            $summonMessages = $this->executeSummon($fight, $mob);
            $result['messages'] = array_merge($result['messages'], $summonMessages);

            return $result;
        }

        $spell = $this->resolveSpell($action, $mob);

        $this->logger->debug(sprintf('[MobActionHandler] Spell %s used by mob #%d', $spell->getName(), $mob->getId()));

        // Résoudre la cible selon le rôle du mob
        $target = $this->resolveTarget($fight, $mob, $action);
        if (!$target || $target->isDead()) {
            return $result;
        }

        // Danger alert check
        $dangerAlert = $this->checkDangerAlert($fight, $mob);
        if ($dangerAlert !== null) {
            $result['dangerAlert'] = $dangerAlert;
        }

        if (FightCalculator::hasAttackHit($mob->getMonster()->getHit())) {
            $spellMessages = $this->spellApplicator->apply($spell, $mob, $target, ['fight' => $fight]);
            $result['messages'][] = sprintf('%s utilise %s !', $mob->getName(), $spell->getName());
            $result['messages'] = array_merge($result['messages'], $spellMessages);
            $this->combatLogger->logSpell($fight, $mob, $target, $spell->getName(), true);
            $this->eventDispatcher->dispatch(new MobActionHitEvent($spell->getName()), MobActionHitEvent::NAME);
        } else {
            $result['messages'][] = sprintf('%s rate son attaque !', $mob->getName());
            $this->combatLogger->logSpell($fight, $mob, $target, $spell->getName(), false);
            $this->eventDispatcher->dispatch(new MobActionMissEvent($spell->getName()), MobActionMissEvent::NAME);
        }

        // Decrement cooldowns for mob
        $fight->decrementAllCooldowns();

        return $result;
    }

    /**
     * Résout la cible de l'action du mob.
     * Les soigneurs ciblent l'allié mob le plus blessé ; les autres ciblent un joueur.
     */
    private function resolveTarget(Fight $fight, Mob $mob, string $action): ?CharacterInterface
    {
        // Action de soin : cibler un allié mob blessé
        if ($action === 'heal') {
            $healTarget = $this->findMostWoundedAlly($fight, $mob);
            if ($healTarget !== null) {
                return $healTarget;
            }
        }

        // Par défaut : cibler le premier joueur vivant
        foreach ($fight->getPlayers() as $player) {
            if (!$player->isDead()) {
                return $player;
            }
        }

        return null;
    }

    /**
     * Trouve le mob allié (ou soi-même) le plus blessé (% PV le plus bas).
     * Retourne null si aucun allié n'est blessé.
     */
    private function findMostWoundedAlly(Fight $fight, Mob $currentMob): ?Mob
    {
        $mostWounded = null;
        $lowestHpPercent = 100.0;

        foreach ($fight->getMobs() as $mob) {
            if ($mob->isDead()) {
                continue;
            }

            $hpPercent = ($mob->getLife() / $mob->getMaxLife()) * 100;

            // Ne soigner que si le mob a perdu des PV
            if ($hpPercent < 100.0 && $hpPercent < $lowestHpPercent) {
                $lowestHpPercent = $hpPercent;
                $mostWounded = $mob;
            }
        }

        return $mostWounded;
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
    private function generateAction(Fight $fight, Mob $mob): string
    {
        $monster = $mob->getMonster();
        $aiPattern = $monster->getAiPattern();

        // No AI pattern: basic attack
        if (!$aiPattern) {
            return $this->defaultAi($mob);
        }

        // Healer role: priorité au soin d'un allié blessé
        if (isset($aiPattern['role']) && $aiPattern['role'] === 'healer') {
            $healTarget = $this->findMostWoundedAlly($fight, $mob);
            if ($healTarget !== null && $this->mobHasHealSpell($mob)) {
                $hpPercent = ($healTarget->getLife() / $healTarget->getMaxLife()) * 100;
                // Soigner si un allié est en dessous de 70% PV
                if ($hpPercent < 70) {
                    return 'heal';
                }
            }
        }

        // Check low HP heal behavior (self-heal)
        if (isset($aiPattern['low_hp_heal'])) {
            $threshold = $aiPattern['low_hp_heal']['threshold'] ?? 30;
            $hpPercent = ($mob->getLife() / $mob->getMaxLife()) * 100;
            if ($hpPercent <= $threshold && $this->mobHasHealSpell($mob)) {
                return 'heal';
            }
        }

        // Summon behavior : invoquer des renforts si config présente, limite et cooldown OK
        if (isset($aiPattern['summon']) && $this->canSummon($fight)) {
            $entityKey = 'mob_' . $mob->getId();

            if (!$fight->isSpellOnCooldown($entityKey, '__summon')) {
                $summonChance = $aiPattern['summon']['chance'] ?? 40;
                if (random_int(1, 100) <= $summonChance) {
                    return 'summon';
                }
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
     * Compte le nombre de mobs invoqués encore vivants dans le combat.
     */
    private function getSummonedCount(Fight $fight): int
    {
        $count = 0;
        foreach ($fight->getMobs() as $mob) {
            if ($mob->isSummoned() && !$mob->isDead()) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Vérifie si une invocation est possible (limite globale non atteinte).
     */
    private function canSummon(Fight $fight): bool
    {
        return $this->getSummonedCount($fight) < self::MAX_SUMMONS_PER_FIGHT;
    }

    /**
     * Exécute l'invocation d'un ou plusieurs mobs en combat.
     *
     * @return string[] Messages de combat
     */
    private function executeSummon(Fight $fight, Mob $summoner): array
    {
        $aiPattern = $summoner->getMonster()->getAiPattern();
        $summonConfig = $aiPattern['summon'] ?? null;

        if ($summonConfig === null) {
            return [];
        }

        $monsterSlug = $summonConfig['monster_slug'] ?? null;
        $countPerSummon = $summonConfig['count'] ?? 1;
        $cooldownTurns = $summonConfig['cooldown'] ?? 3;
        $levelOffset = $summonConfig['level_offset'] ?? 0;

        if ($monsterSlug === null) {
            $this->logger->warning('[MobActionHandler] Summon config missing monster_slug');

            return [];
        }

        $monsterDef = $this->entityManager->getRepository(Monster::class)->findOneBy(['slug' => $monsterSlug]);
        if ($monsterDef === null) {
            $this->logger->warning(sprintf('[MobActionHandler] Monster slug "%s" not found for summon', $monsterSlug));

            return [];
        }

        // Limiter au nombre de slots restants
        $slotsAvailable = self::MAX_SUMMONS_PER_FIGHT - $this->getSummonedCount($fight);
        $actualCount = min($countPerSummon, $slotsAvailable);

        if ($actualCount <= 0) {
            return [];
        }

        $messages = [];
        $summonedLevel = max(1, $summoner->getLevel() + $levelOffset);

        for ($i = 0; $i < $actualCount; ++$i) {
            $summonedMob = new Mob();
            $summonedMob->setMonster($monsterDef);
            $summonedMob->setLife($monsterDef->getLife());
            $summonedMob->setLevel($summonedLevel);
            $summonedMob->setSummoned(true);
            $summonedMob->setFight($fight);
            $summonedMob->setMap(null);
            $summonedMob->setCoordinates('0.0');

            $this->entityManager->persist($summonedMob);
            $fight->addMob($summonedMob);
        }

        // Mettre le cooldown d'invocation
        $entityKey = 'mob_' . $summoner->getId();
        $fight->setSpellCooldown($entityKey, '__summon', $cooldownTurns);

        $this->combatLogger->logSummon($fight, $summoner, $monsterDef->getName(), $actualCount);

        $message = $actualCount > 1
            ? sprintf('%s invoque %d %s !', $summoner->getName(), $actualCount, $monsterDef->getName())
            : sprintf('%s invoque un %s !', $summoner->getName(), $monsterDef->getName());
        $messages[] = $message;

        $this->logger->debug(sprintf('[MobActionHandler] Mob #%d summoned %d %s', $summoner->getId(), $actualCount, $monsterSlug));

        return $messages;
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
