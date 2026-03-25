<?php

namespace App\GameEngine\Event;

use App\Entity\App\GameEvent;
use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use App\Event\Game\GameEventActivatedEvent;
use App\Event\Game\GameEventCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Spawns a world boss Mob when a boss_spawn GameEvent activates,
 * and removes it when the event completes (or is cancelled).
 *
 * Expected GameEvent parameters for boss_spawn:
 *   {
 *     "monster_slug": "ancient_dragon",
 *     "map_id": 2,
 *     "coordinates": "15.10",
 *     "level": 30          (optional, defaults to monster base level)
 *   }
 */
class WorldBossManager implements EventSubscriberInterface
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

        if ($gameEvent->getType() !== GameEvent::TYPE_BOSS_SPAWN) {
            return;
        }

        $this->spawnWorldBoss($gameEvent);
    }

    public function onEventCompleted(GameEventCompletedEvent $event): void
    {
        $gameEvent = $event->getGameEvent();

        if ($gameEvent->getType() !== GameEvent::TYPE_BOSS_SPAWN) {
            return;
        }

        $this->despawnWorldBoss($gameEvent);
    }

    private function spawnWorldBoss(GameEvent $gameEvent): void
    {
        $params = $gameEvent->getParameters();

        if (!$params || !isset($params['monster_slug'], $params['map_id'], $params['coordinates'])) {
            $this->logger->warning('[WorldBossManager] boss_spawn event missing required parameters', [
                'eventName' => $gameEvent->getName(),
                'params' => $params,
            ]);

            return;
        }

        $monster = $this->entityManager->getRepository(Monster::class)->findOneBy([
            'slug' => $params['monster_slug'],
        ]);

        if (!$monster) {
            $this->logger->error('[WorldBossManager] Monster not found for slug "{slug}"', [
                'slug' => $params['monster_slug'],
            ]);

            return;
        }

        $map = $this->entityManager->getRepository(Map::class)->find($params['map_id']);

        if (!$map) {
            $this->logger->error('[WorldBossManager] Map not found for id {mapId}', [
                'mapId' => $params['map_id'],
            ]);

            return;
        }

        $mob = new Mob();
        $mob->setMonster($monster);
        $mob->setMap($map);
        $mob->setCoordinates($params['coordinates']);
        $mob->setLevel($params['level'] ?? $monster->getLevel());
        $mob->setLife($monster->getLife());
        $mob->setIsWorldBoss(true);
        $mob->setGameEvent($gameEvent);

        $this->entityManager->persist($mob);
        $this->entityManager->flush();

        $this->publishSpawn($mob);

        $this->logger->info('[WorldBossManager] Spawned world boss "{name}" at {coords} on map {mapId}', [
            'name' => $monster->getName(),
            'coords' => $params['coordinates'],
            'mapId' => $map->getId(),
        ]);
    }

    private function despawnWorldBoss(GameEvent $gameEvent): void
    {
        $mobs = $this->entityManager->getRepository(Mob::class)->findBy([
            'gameEvent' => $gameEvent,
            'isWorldBoss' => true,
        ]);

        foreach ($mobs as $mob) {
            $this->publishDespawn($mob);

            $this->entityManager->remove($mob);

            $this->logger->info('[WorldBossManager] Despawned world boss "{name}" (event completed)', [
                'name' => $mob->getName(),
            ]);
        }

        if (\count($mobs) > 0) {
            $this->entityManager->flush();
        }
    }

    private function publishSpawn(Mob $mob): void
    {
        [$x, $y] = explode('.', $mob->getCoordinates());

        $update = new Update(
            'map/respawn',
            json_encode([
                'topic' => 'map/respawn',
                'type' => 'world_boss_spawn',
                'object' => [
                    'id' => (int) $mob->getId(),
                    'name' => $mob->getName(),
                    'slug' => $mob->getMonster()->getSlug(),
                    'level' => $mob->getLevel(),
                    'x' => (int) $x,
                    'y' => (int) $y,
                    'spriteKey' => 'mob_' . $mob->getMonster()->getSlug(),
                    'isWorldBoss' => true,
                ],
                'coordinates' => $mob->getCoordinates(),
                'mapId' => $mob->getMap()?->getId(),
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }

    private function publishDespawn(Mob $mob): void
    {
        $update = new Update(
            'map/respawn',
            json_encode([
                'topic' => 'map/respawn',
                'type' => 'world_boss_despawn',
                'object' => [
                    'id' => $mob->getId(),
                    'name' => $mob->getName(),
                ],
                'coordinates' => $mob->getCoordinates(),
                'mapId' => $mob->getMap()?->getId(),
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }
}
