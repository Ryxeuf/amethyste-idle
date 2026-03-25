<?php

namespace App\GameEngine\Terrain;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\Game\Monster;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creates/updates database entities from parsed Tiled object layers.
 */
class EntitySynchronizer
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Sync parsed objects to database entities.
     *
     * @param array<int, array> $objects Parsed object data from TmxParser
     *
     * @return array{synced: int, messages: string[]}
     */
    /**
     * @param array<string, int>|null $cellMovements Pre-built cell movement lookup (optional; if null, validation skipped)
     */
    public function syncEntitiesFromObjects(array $objects, ?int $mapId = null, ?array $cellMovements = null): array
    {
        $synced = 0;
        $messages = [];

        $mapRepository = $this->entityManager->getRepository(Map::class);
        $map = $mapId !== null
            ? $mapRepository->find($mapId)
            : $mapRepository->findOneBy([], ['id' => 'ASC']);

        if (!$map) {
            $messages[] = 'No map found — use --map-id to specify the target map. Skipping entity sync.';

            return ['synced' => 0, 'messages' => $messages];
        }

        $messages[] = sprintf('Using map #%d "%s" for entity sync', $map->getId(), $map->getName());

        foreach ($objects as $obj) {
            $type = $obj['type'] ?? '';

            // Validate placement for entity types that must be on walkable cells
            if (in_array($type, ['mob_spawn', 'harvest_spot', 'spot', 'portal'], true) && $cellMovements !== null) {
                $coords = ($obj['x'] ?? 0) . '.' . ($obj['y'] ?? 0);
                $movement = $cellMovements[$coords] ?? null;
                if ($movement === null || $movement === CellHelper::MOVE_UNREACHABLE) {
                    $messages[] = sprintf('  ! %s "%s" at %s is on a blocked cell (m=%s) — skipped', $type, $obj['name'] ?? '', $coords, $movement ?? 'null');
                    continue;
                }
            }

            $result = match ($type) {
                'portal' => $this->syncPortal($obj, $map),
                'mob_spawn' => $this->syncMobSpawn($obj, $map),
                'harvest_spot', 'spot' => $this->syncHarvestSpot($obj, $map),
                'chest' => $this->syncChest($obj, $map),
                default => null,
            };

            if ($result !== null) {
                $synced += $result['synced'];
                array_push($messages, ...$result['messages']);
            }
        }

        $this->entityManager->flush();

        return ['synced' => $synced, 'messages' => $messages];
    }

    /**
     * @return array{synced: int, messages: string[]}
     */
    private function syncPortal(array $obj, Map $map): array
    {
        $x = $obj['x'];
        $y = $obj['y'];
        $coords = $x . '.' . $y;
        $props = $obj['properties'] ?? [];

        $objectLayer = new ObjectLayer();
        $objectLayer->setName($obj['name'] ?: 'Portal ' . $coords);
        $objectLayer->setSlug('portal-' . $x . '-' . $y);
        $objectLayer->setType(ObjectLayer::TYPE_PORTAL);
        $objectLayer->setCoordinates($coords);
        $objectLayer->setMovement(0);
        $objectLayer->setMap($map);
        $objectLayer->setUsable(true);
        $objectLayer->setItems(null);
        $objectLayer->setActions(null);
        $objectLayer->setCreatedAt(new \DateTime());
        $objectLayer->setUpdatedAt(new \DateTime());

        if (!empty($props['target_map_id'])) {
            $objectLayer->setDestinationMapId((int) $props['target_map_id']);
        }
        if (!empty($props['target_x']) && !empty($props['target_y'])) {
            $objectLayer->setDestinationCoordinates($props['target_x'] . '.' . $props['target_y']);
        }

        $this->entityManager->persist($objectLayer);

        return [
            'synced' => 1,
            'messages' => [sprintf('  + Portal "%s" at %s → map %s at %s',
                $objectLayer->getName(),
                $coords,
                $props['target_map_id'] ?? '?',
                $objectLayer->getDestinationCoordinates() ?? '?'
            )],
        ];
    }

    /**
     * @return array{synced: int, messages: string[]}
     */
    private function syncMobSpawn(array $obj, Map $map): array
    {
        $coords = $obj['x'] . '.' . $obj['y'];
        $props = $obj['properties'] ?? [];
        $monsterSlug = $props['monster_slug'] ?? '';

        if (empty($monsterSlug)) {
            return ['synced' => 0, 'messages' => [sprintf('  mob_spawn at %s has no monster_slug — skipped', $coords)]];
        }

        $monster = $this->entityManager->getRepository(Monster::class)->findOneBy(['slug' => $monsterSlug]);
        if (!$monster) {
            return ['synced' => 0, 'messages' => [sprintf('  Monster "%s" not found — skipped mob_spawn at %s', $monsterSlug, $coords)]];
        }

        $mob = new Mob();
        $mob->setMap($map);
        $mob->setCoordinates($coords);
        $mob->setMonster($monster);
        $mob->setLife($monster->getLife());
        $mob->setLevel($monster->getLevel());
        $mob->setCreatedAt(new \DateTime());
        $mob->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($mob);

        return [
            'synced' => 1,
            'messages' => [sprintf('  + Mob "%s" (level %d) at %s', $monster->getName(), $monster->getLevel(), $coords)],
        ];
    }

    /**
     * @return array{synced: int, messages: string[]}
     */
    private function syncHarvestSpot(array $obj, Map $map): array
    {
        $x = $obj['x'];
        $y = $obj['y'];
        $coords = $x . '.' . $y;
        $props = $obj['properties'] ?? [];

        $objectLayer = new ObjectLayer();
        $objectLayer->setName($obj['name'] ?: 'Spot ' . $coords);
        $objectLayer->setSlug($props['slug'] ?? ('spot-' . $x . '-' . $y));
        $objectLayer->setType(ObjectLayer::TYPE_SPOT);
        $objectLayer->setCoordinates($coords);
        $objectLayer->setMovement(-1);
        $objectLayer->setMap($map);
        $objectLayer->setUsable(true);
        $objectLayer->setCreatedAt(new \DateTime());
        $objectLayer->setUpdatedAt(new \DateTime());

        $objectLayer->setActions([['action' => 'harvest', 'distance' => 1]]);
        if (!empty($props['item_slug'])) {
            $objectLayer->setItems([[
                'slug' => $props['item_slug'],
                'min' => (int) ($props['item_min'] ?? 1),
                'max' => (int) ($props['item_max'] ?? 1),
            ]]);
        } else {
            $objectLayer->setItems(null);
        }

        $this->entityManager->persist($objectLayer);

        return [
            'synced' => 1,
            'messages' => [sprintf('  + Harvest spot "%s" at %s', $objectLayer->getName(), $coords)],
        ];
    }

    /**
     * @return array{synced: int, messages: string[]}
     */
    private function syncChest(array $obj, Map $map): array
    {
        $x = $obj['x'];
        $y = $obj['y'];
        $coords = $x . '.' . $y;
        $props = $obj['properties'] ?? [];

        $objectLayer = new ObjectLayer();
        $objectLayer->setName($obj['name'] ?: 'Chest ' . $coords);
        $objectLayer->setSlug('chest-' . $x . '-' . $y);
        $objectLayer->setType(ObjectLayer::TYPE_CHEST);
        $objectLayer->setCoordinates($coords);
        $objectLayer->setMovement(-1);
        $objectLayer->setMap($map);
        $objectLayer->setUsable(true);
        $objectLayer->setCreatedAt(new \DateTime());
        $objectLayer->setUpdatedAt(new \DateTime());

        if (!empty($props['item_slug'])) {
            $objectLayer->setItems([[
                'slug' => $props['item_slug'],
                'min' => (int) ($props['item_min'] ?? 1),
                'max' => (int) ($props['item_max'] ?? 1),
            ]]);
        }
        $objectLayer->setActions(null);

        $this->entityManager->persist($objectLayer);

        return [
            'synced' => 1,
            'messages' => [sprintf('  + Chest "%s" at %s', $objectLayer->getName(), $coords)],
        ];
    }
}
