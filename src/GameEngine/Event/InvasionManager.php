<?php

namespace App\GameEngine\Event;

use App\Entity\App\GameEvent;
use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\Event\Game\GameEventActivatedEvent;
use App\Event\Game\GameEventCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Manages invasion GameEvents: spawns waves of mobs on activation,
 * advances waves on tick, cleans up on completion and distributes rewards.
 *
 * Expected GameEvent parameters for invasion:
 *   {
 *     "mob_slugs": ["goblin", "skeleton"],
 *     "count_per_wave": 3,
 *     "map_id": 2,
 *     "spawn_coordinates": ["10.10", "11.10", "12.10", "10.11", "11.11", "12.11"],
 *     "wave_count": 3,
 *     "wave_interval_seconds": 120,
 *     "kill_objective": 7,
 *     "rewards": {"gold": 100, "xp": 200}
 *   }
 *
 * Runtime state (updated by InvasionManager):
 *   "current_wave", "wave_spawned_at", "kills", "total_kills"
 */
class InvasionManager implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GameEventActivatedEvent::NAME => 'onEventActivated',
            GameEventCompletedEvent::NAME => 'onEventCompleted',
        ];
    }

    public function onEventActivated(GameEventActivatedEvent $event): void
    {
        $gameEvent = $event->getGameEvent();

        if ($gameEvent->getType() !== GameEvent::TYPE_INVASION) {
            return;
        }

        $this->startInvasion($gameEvent);
    }

    public function onEventCompleted(GameEventCompletedEvent $event): void
    {
        $gameEvent = $event->getGameEvent();

        if ($gameEvent->getType() !== GameEvent::TYPE_INVASION) {
            return;
        }

        $this->endInvasion($gameEvent);
    }

    /**
     * Called periodically to advance waves of active invasions.
     *
     * @return int Number of waves spawned
     */
    public function tick(): int
    {
        $activeInvasions = $this->entityManager->getRepository(GameEvent::class)->findBy([
            'type' => GameEvent::TYPE_INVASION,
            'status' => GameEvent::STATUS_ACTIVE,
        ]);

        $wavesSpawned = 0;
        foreach ($activeInvasions as $gameEvent) {
            if ($this->shouldSpawnNextWave($gameEvent)) {
                $this->spawnWave($gameEvent);
                ++$wavesSpawned;
            }
        }

        if ($wavesSpawned > 0) {
            $this->entityManager->flush();
        }

        return $wavesSpawned;
    }

    /**
     * Record a kill for a player during an invasion event.
     */
    public function recordKill(GameEvent $gameEvent, Player $player): void
    {
        $params = $gameEvent->getParameters() ?? [];
        $kills = $params['kills'] ?? [];
        $playerId = (string) $player->getId();

        $kills[$playerId] = ($kills[$playerId] ?? 0) + 1;
        $params['kills'] = $kills;
        $params['total_kills'] = ($params['total_kills'] ?? 0) + 1;

        $gameEvent->setParameters($params);

        $this->publishProgress($gameEvent);
    }

    private function startInvasion(GameEvent $gameEvent): void
    {
        $params = $gameEvent->getParameters();

        if (!$this->validateParameters($params, $gameEvent)) {
            return;
        }

        $params['current_wave'] = 1;
        $params['wave_spawned_at'] = (new \DateTime())->format('c');
        $params['kills'] = [];
        $params['total_kills'] = 0;
        $gameEvent->setParameters($params);

        $this->entityManager->flush();

        $this->spawnWave($gameEvent);

        $this->publishInvasionStart($gameEvent);
    }

    private function endInvasion(GameEvent $gameEvent): void
    {
        $this->despawnInvasionMobs($gameEvent);
        $this->distributeRewards($gameEvent);

        $this->publishInvasionEnd($gameEvent);

        $this->logger->info('[InvasionManager] Invasion "{name}" ended', [
            'name' => $gameEvent->getName(),
        ]);
    }

    private function shouldSpawnNextWave(GameEvent $gameEvent): bool
    {
        $params = $gameEvent->getParameters() ?? [];
        $currentWave = $params['current_wave'] ?? 1;
        $waveCount = $params['wave_count'] ?? 3;
        $intervalSeconds = $params['wave_interval_seconds'] ?? 120;

        if ($currentWave >= $waveCount) {
            return false;
        }

        $lastSpawnedAt = isset($params['wave_spawned_at'])
            ? new \DateTime($params['wave_spawned_at'])
            : null;

        if (!$lastSpawnedAt) {
            return false;
        }

        $elapsed = (new \DateTime())->getTimestamp() - $lastSpawnedAt->getTimestamp();

        return $elapsed >= $intervalSeconds;
    }

    private function spawnWave(GameEvent $gameEvent): void
    {
        $params = $gameEvent->getParameters() ?? [];
        $mobSlugs = $params['mob_slugs'] ?? [];
        $countPerWave = $params['count_per_wave'] ?? 3;
        $mapId = $params['map_id'] ?? null;
        $spawnCoordinates = $params['spawn_coordinates'] ?? [];
        $currentWave = $params['current_wave'] ?? 1;
        $waveCount = $params['wave_count'] ?? 3;

        if (empty($mobSlugs) || !$mapId || empty($spawnCoordinates)) {
            return;
        }

        $map = $this->entityManager->getRepository(Map::class)->find($mapId);
        if (!$map) {
            $this->logger->error('[InvasionManager] Map not found for id {mapId}', ['mapId' => $mapId]);

            return;
        }

        $monsters = $this->entityManager->getRepository(Monster::class)->findBy([
            'slug' => $mobSlugs,
        ]);

        if (empty($monsters)) {
            $this->logger->error('[InvasionManager] No monsters found for slugs', ['slugs' => $mobSlugs]);

            return;
        }

        $spawnedMobs = [];
        for ($i = 0; $i < $countPerWave; ++$i) {
            $monster = $monsters[array_rand($monsters)];
            $coords = $spawnCoordinates[array_rand($spawnCoordinates)];

            $levelBoost = ($currentWave - 1) * 2;

            $mob = new Mob();
            $mob->setMonster($monster);
            $mob->setMap($map);
            $mob->setCoordinates($coords);
            $mob->setLevel($monster->getLevel() + $levelBoost);
            $mob->setLife($monster->getLife());
            $mob->setGameEvent($gameEvent);

            $this->entityManager->persist($mob);
            $spawnedMobs[] = $mob;
        }

        $params['current_wave'] = $currentWave + 1;
        $params['wave_spawned_at'] = (new \DateTime())->format('c');
        $gameEvent->setParameters($params);

        $this->entityManager->flush();

        foreach ($spawnedMobs as $mob) {
            $this->publishMobSpawn($mob, $gameEvent);
        }

        $this->logger->info('[InvasionManager] Spawned wave {wave}/{total} ({count} mobs) for "{name}"', [
            'wave' => $currentWave,
            'total' => $waveCount,
            'count' => $countPerWave,
            'name' => $gameEvent->getName(),
        ]);
    }

    private function despawnInvasionMobs(GameEvent $gameEvent): void
    {
        $mobs = $this->entityManager->getRepository(Mob::class)->findBy([
            'gameEvent' => $gameEvent,
        ]);

        foreach ($mobs as $mob) {
            if ($mob->getFight() !== null) {
                continue;
            }

            $this->publishMobDespawn($mob, $gameEvent);
            $this->entityManager->remove($mob);
        }

        if (\count($mobs) > 0) {
            $this->entityManager->flush();
        }
    }

    private function distributeRewards(GameEvent $gameEvent): void
    {
        $params = $gameEvent->getParameters() ?? [];
        $killObjective = $params['kill_objective'] ?? 0;
        $totalKills = $params['total_kills'] ?? 0;
        $kills = $params['kills'] ?? [];
        $rewards = $params['rewards'] ?? [];

        if ($totalKills < $killObjective || empty($kills) || empty($rewards)) {
            $this->logger->info('[InvasionManager] Objective not met for "{name}" ({kills}/{objective})', [
                'name' => $gameEvent->getName(),
                'kills' => $totalKills,
                'objective' => $killObjective,
            ]);

            return;
        }

        $playerIds = array_map('intval', array_keys($kills));
        $players = $this->entityManager->getRepository(Player::class)->findBy([
            'id' => $playerIds,
        ]);

        $goldReward = $rewards['gold'] ?? 0;
        $xpReward = $rewards['xp'] ?? 0;

        foreach ($players as $player) {
            $playerKills = $kills[(string) $player->getId()] ?? 0;
            if ($playerKills <= 0) {
                continue;
            }

            $share = $playerKills / $totalKills;
            $playerGold = (int) round($goldReward * $share);

            if ($playerGold > 0) {
                $player->addGils($playerGold);
            }

            $this->logger->info('[InvasionManager] Rewarded player {player} with {gold}g ({kills} kills)', [
                'player' => $player->getId(),
                'gold' => $playerGold,
                'kills' => $playerKills,
            ]);
        }

        $this->entityManager->flush();
    }

    private function validateParameters(?array $params, GameEvent $gameEvent): bool
    {
        if (!$params || !isset($params['mob_slugs'], $params['map_id'], $params['spawn_coordinates'])) {
            $this->logger->warning('[InvasionManager] Invasion event missing required parameters', [
                'eventName' => $gameEvent->getName(),
                'params' => $params,
            ]);

            return false;
        }

        return true;
    }

    private function publishInvasionStart(GameEvent $gameEvent): void
    {
        $params = $gameEvent->getParameters() ?? [];

        $update = new Update(
            'map/respawn',
            json_encode([
                'topic' => 'map/respawn',
                'type' => 'invasion_start',
                'event' => [
                    'id' => $gameEvent->getId(),
                    'name' => $gameEvent->getName(),
                    'waveCount' => $params['wave_count'] ?? 3,
                    'killObjective' => $params['kill_objective'] ?? 0,
                ],
                'mapId' => $params['map_id'] ?? null,
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }

    private function publishInvasionEnd(GameEvent $gameEvent): void
    {
        $params = $gameEvent->getParameters() ?? [];

        $update = new Update(
            'map/respawn',
            json_encode([
                'topic' => 'map/respawn',
                'type' => 'invasion_end',
                'event' => [
                    'id' => $gameEvent->getId(),
                    'name' => $gameEvent->getName(),
                    'totalKills' => $params['total_kills'] ?? 0,
                    'killObjective' => $params['kill_objective'] ?? 0,
                    'objectiveMet' => ($params['total_kills'] ?? 0) >= ($params['kill_objective'] ?? 0),
                ],
                'mapId' => $params['map_id'] ?? null,
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }

    private function publishProgress(GameEvent $gameEvent): void
    {
        $params = $gameEvent->getParameters() ?? [];

        $update = new Update(
            'map/respawn',
            json_encode([
                'topic' => 'map/respawn',
                'type' => 'invasion_progress',
                'event' => [
                    'id' => $gameEvent->getId(),
                    'totalKills' => $params['total_kills'] ?? 0,
                    'killObjective' => $params['kill_objective'] ?? 0,
                ],
                'mapId' => $params['map_id'] ?? null,
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }

    private function publishMobSpawn(Mob $mob, GameEvent $gameEvent): void
    {
        [$x, $y] = explode('.', $mob->getCoordinates());

        $update = new Update(
            'map/respawn',
            json_encode([
                'topic' => 'map/respawn',
                'type' => 'invasion_mob_spawn',
                'object' => [
                    'id' => (int) $mob->getId(),
                    'name' => $mob->getName(),
                    'slug' => $mob->getMonster()->getSlug(),
                    'level' => $mob->getLevel(),
                    'x' => (int) $x,
                    'y' => (int) $y,
                    'spriteKey' => 'mob_' . $mob->getMonster()->getSlug(),
                ],
                'eventId' => $gameEvent->getId(),
                'coordinates' => $mob->getCoordinates(),
                'mapId' => $mob->getMap()?->getId(),
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }

    private function publishMobDespawn(Mob $mob, GameEvent $gameEvent): void
    {
        $update = new Update(
            'map/respawn',
            json_encode([
                'topic' => 'map/respawn',
                'type' => 'invasion_mob_despawn',
                'object' => [
                    'id' => $mob->getId(),
                    'name' => $mob->getName(),
                ],
                'eventId' => $gameEvent->getId(),
                'mapId' => $mob->getMap()?->getId(),
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }
}
