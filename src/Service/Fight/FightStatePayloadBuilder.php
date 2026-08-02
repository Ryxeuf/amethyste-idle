<?php

namespace App\Service\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use App\GameEngine\Fight\CombatCapacityResolver;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\StatusEffectManager;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;

/**
 * Construit le payload JSON de l'etat d'un combat pour /api/v1/fight
 * (migration API-first, phase 1.1). Strictement en lecture : les transitions
 * victoire/defaite restent gerees par leurs endpoints dedies.
 */
class FightStatePayloadBuilder
{
    public function __construct(
        private readonly StatusEffectManager $statusEffectManager,
        private readonly CombatCapacityResolver $combatCapacityResolver,
        private readonly CombatLogger $combatLogger,
        private readonly FightTurnResolver $turnResolver,
        private readonly PlayerEffectiveStatsCalculator $playerEffectiveStatsCalculator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Fight $fight, Player $currentPlayer, ?string $locale = null): array
    {
        $status = 'active';
        if ($fight->isVictory()) {
            $status = 'victory';
        } elseif ($fight->isDefeat()) {
            $status = 'defeat';
        }

        $isCoop = $fight->isCoopFight();

        return [
            'id' => $fight->getId(),
            'status' => $status,
            'step' => $fight->getStep(),
            'round' => $this->computeRound($fight),
            'isCoop' => $isCoop,
            'isWorldBoss' => $fight->isWorldBossFight(),
            'isMyTurn' => !$isCoop || $this->turnResolver->isPlayerTurn($fight, $currentPlayer->getId()),
            'currentTurnKey' => $fight->getCurrentTurnKey(),
            'players' => $this->buildPlayers($fight, $currentPlayer),
            'mobs' => $this->buildMobs($fight),
            'statusEffects' => $this->buildStatusEffects($fight, $locale),
            'spells' => $this->buildSpells($fight, $currentPlayer, $locale),
            'dangerAlert' => $this->resolveDangerAlert($fight),
            'timeline' => $status === 'active' ? $this->buildTimeline($fight) : [],
            'logs' => $this->buildLogs($fight),
        ];
    }

    private function computeRound(Fight $fight): int
    {
        $turnOrder = $this->turnResolver->getTurnOrder($fight);

        return (int) floor($fight->getStep() / max(1, count($turnOrder))) + 1;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildPlayers(Fight $fight, Player $currentPlayer): array
    {
        $players = [];
        foreach ($fight->getPlayers() as $player) {
            $players[] = [
                'id' => $player->getId(),
                'key' => 'player_' . $player->getId(),
                'name' => $player->getName(),
                'life' => $player->getLife(),
                'maxLife' => $this->playerEffectiveStatsCalculator->getEffectiveMaxLife($player),
                'speed' => $player->getSpeed(),
                'isDead' => $player->isDead(),
                'isMe' => $player->getId() === $currentPlayer->getId(),
            ];
        }

        return $players;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildMobs(Fight $fight): array
    {
        $mobs = [];
        foreach ($fight->getMobs() as $mob) {
            $monster = $mob->getMonster();
            $hpPercent = $mob->getMaxLife() > 0 ? ($mob->getLife() / $mob->getMaxLife()) * 100 : 0;

            $bossPhase = null;
            if ($monster->isBoss() && $monster->getBossPhases()) {
                $bossPhase = $monster->getCurrentBossPhase((int) $hpPercent);
            }

            $mobs[] = [
                'id' => $mob->getId(),
                'key' => 'mob_' . $mob->getId(),
                'name' => $mob->getName(),
                'tier' => $mob->getTier(),
                'rank' => $monster->getRank()->value,
                'life' => $mob->getLife(),
                'maxLife' => $mob->getMaxLife(),
                'speed' => $mob->getSpeed(),
                'isDead' => $mob->isDead(),
                'isBoss' => $monster->isBoss(),
                'isWorldBoss' => $mob->isWorldBoss(),
                'bossPhase' => $bossPhase,
            ];
        }

        return $mobs;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function buildStatusEffects(Fight $fight, ?string $locale): array
    {
        $result = [];

        foreach ($fight->getPlayers() as $player) {
            $result['player_' . $player->getId()] = $this->serializeEffects($fight, $player, $locale);
        }
        foreach ($fight->getMobs() as $mob) {
            $result['mob_' . $mob->getId()] = $this->serializeEffects($fight, $mob, $locale);
        }

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializeEffects(Fight $fight, CharacterInterface $character, ?string $locale): array
    {
        $effects = [];
        foreach ($this->statusEffectManager->getActiveEffects($fight, $character) as $fightEffect) {
            $effect = $fightEffect->getStatusEffect();
            $effects[] = [
                'slug' => $effect->getSlug(),
                'name' => $effect->getLocalizedName($locale),
                'icon' => $effect->getIcon(),
                'isBuff' => $effect->isBuff(),
                'remainingTurns' => $fightEffect->getRemainingTurns(),
            ];
        }

        return $effects;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildSpells(Fight $fight, Player $player, ?string $locale): array
    {
        $entityKey = 'player_' . $player->getId();
        $spells = [];

        foreach ($this->combatCapacityResolver->getEquippedMateriaSpells($player) as $entry) {
            $spell = $entry['spell'];
            $spells[] = [
                'slug' => $spell->getSlug(),
                'name' => $spell->getLocalizedName($locale),
                'description' => $spell->getLocalizedDescription($locale),
                'element' => $spell->getElement()->value,
                'energyCost' => $spell->getEnergyCost(),
                'damage' => $spell->getDamage(),
                'heal' => $spell->getHeal(),
                'cooldown' => $spell->getCooldown(),
                'remainingCooldown' => $fight->getSpellCooldown($entityKey, $spell->getSlug()),
                'elementMatch' => $entry['elementMatch'],
                'linkedBonus' => $entry['linkedBonus'],
                'locked' => $entry['locked'],
            ];
        }

        return $spells;
    }

    private function resolveDangerAlert(Fight $fight): ?string
    {
        foreach ($fight->getMobs() as $mob) {
            if ($mob->isDead()) {
                continue;
            }

            $monster = $mob->getMonster();
            $hpPercent = $mob->getMaxLife() > 0 ? ($mob->getLife() / $mob->getMaxLife()) * 100 : 0;

            if ($monster->isBoss() && $monster->getBossPhases()) {
                $phase = $monster->getCurrentBossPhase((int) $hpPercent);
                if ($phase !== null && isset($phase['danger_message'])) {
                    return $phase['danger_message'];
                }
            }

            $aiPattern = $monster->getAiPattern();
            if ($aiPattern !== null && isset($aiPattern['danger_alert'])) {
                $alertThreshold = $aiPattern['danger_alert']['threshold'] ?? 30;
                if ($hpPercent <= $alertThreshold) {
                    return $aiPattern['danger_alert']['message'] ?? null;
                }
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildTimeline(Fight $fight): array
    {
        if ($this->turnResolver->getTurnOrder($fight) === []) {
            return [];
        }

        $timeline = [];
        foreach ($this->turnResolver->getTimeline($fight, 3) as $slot) {
            $timeline[] = [
                'key' => $slot['key'],
                'type' => $slot['type'],
                'round' => $slot['round'],
            ];
        }

        return $timeline;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildLogs(Fight $fight): array
    {
        $logs = [];
        foreach ($this->combatLogger->getLogsForFight($fight) as $log) {
            $logs[] = [
                'turn' => $log->getTurn(),
                'actorType' => $log->getActorType(),
                'actorId' => $log->getActorId(),
                'actorName' => $log->getActorName(),
                'type' => $log->getType(),
                'message' => $log->getMessage(),
            ];
        }

        return $logs;
    }
}
