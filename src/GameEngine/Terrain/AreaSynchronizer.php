<?php

namespace App\GameEngine\Terrain;

use App\Entity\App\Area;
use App\Entity\App\Map;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Synchronizes zone/biome objects from Tiled into Area entities.
 *
 * Tiled objects of type "zone" or "biome" are rectangles with custom properties
 * (biome, weather, music, light_level). This service upserts matching Area records.
 */
class AreaSynchronizer
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Filter zone/biome objects from a parsed object list.
     *
     * @param array<int, array> $objects All parsed objects from TmxParser
     *
     * @return array<int, array> Only zone/biome objects
     */
    public function filterZoneObjects(array $objects): array
    {
        return array_values(array_filter(
            $objects,
            static fn (array $obj): bool => in_array($obj['type'] ?? '', ['zone', 'biome'], true),
        ));
    }

    /**
     * Sync zone/biome objects to Area entities.
     *
     * @param array<int, array> $objects Parsed objects from TmxParser (all types — filtered internally)
     *
     * @return array{synced: int, messages: string[]}
     */
    public function syncZonesFromObjects(array $objects, ?int $mapId = null): array
    {
        $zones = $this->filterZoneObjects($objects);

        if (empty($zones)) {
            return ['synced' => 0, 'messages' => ['No zone/biome objects found in map data.']];
        }

        $mapRepository = $this->entityManager->getRepository(Map::class);
        $map = $mapId !== null
            ? $mapRepository->find($mapId)
            : $mapRepository->findOneBy([], ['id' => 'ASC']);

        if (!$map) {
            return ['synced' => 0, 'messages' => ['No map found — use --map-id to specify the target map.']];
        }

        $areaRepository = $this->entityManager->getRepository(Area::class);
        $synced = 0;
        $messages = [];
        $messages[] = sprintf('Syncing %d zone(s) for map #%d "%s"', count($zones), $map->getId(), $map->getName());

        foreach ($zones as $zone) {
            $result = $this->syncZone($zone, $map, $areaRepository);
            $synced += $result['synced'];
            array_push($messages, ...$result['messages']);
        }

        $this->entityManager->flush();

        return ['synced' => $synced, 'messages' => $messages];
    }

    /**
     * @return array{synced: int, messages: string[]}
     */
    /**
     * @param EntityRepository<Area> $areaRepository
     *
     * @return array{synced: int, messages: string[]}
     */
    private function syncZone(array $zone, Map $map, EntityRepository $areaRepository): array
    {
        $name = $zone['name'] ?: ('zone-' . $zone['x'] . '-' . $zone['y']);
        $slug = $this->slugify($name);
        $props = $zone['properties'] ?? [];

        $biome = $props['biome'] ?? $zone['name'] ?: null;
        $weather = $props['weather'] ?? null;
        $music = $props['music'] ?? null;
        $lightLevel = isset($props['light_level']) ? (float) $props['light_level'] : null;

        // Upsert: find existing area by slug + map, or create new
        $area = $areaRepository->findOneBy(['slug' => $slug, 'map' => $map]);
        $isNew = $area === null;

        if ($isNew) {
            $area = new Area();
            $area->setSlug($slug);
            $area->setMap($map);
            $area->setCoordinates($zone['x'] . '.' . $zone['y']);
            $area->setFullData('{}');
            $area->setCreatedAt(new \DateTime());
        }

        $area->setName($name);
        $area->setBiome($biome);
        $area->setWeather($weather);
        $area->setMusic($music);
        $area->setLightLevel($lightLevel);
        $area->setZoneX($zone['x']);
        $area->setZoneY($zone['y']);
        $area->setZoneWidth($zone['width']);
        $area->setZoneHeight($zone['height']);
        $area->setUpdatedAt(new \DateTime());

        if ($isNew) {
            $this->entityManager->persist($area);
        }

        $action = $isNew ? 'Created' : 'Updated';

        return [
            'synced' => 1,
            'messages' => [sprintf(
                '  %s zone "%s" [%s] at (%d,%d) %dx%d — biome=%s weather=%s music=%s light=%.1f',
                $action,
                $name,
                $slug,
                $zone['x'],
                $zone['y'],
                $zone['width'],
                $zone['height'],
                $biome ?? 'null',
                $weather ?? 'null',
                $music ?? 'null',
                $lightLevel ?? 0.0,
            )],
        ];
    }

    private function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = (string) preg_replace('/[^a-z0-9\-_]/', '-', $slug);
        $slug = (string) preg_replace('/-+/', '-', $slug);

        return trim($slug, '-');
    }
}
