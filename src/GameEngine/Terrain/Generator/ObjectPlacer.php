<?php

namespace App\GameEngine\Terrain\Generator;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\Game\Monster;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Place automatiquement des entites (mobs, spots de recolte, portails, zones)
 * sur une carte generee proceduralement.
 */
class ObjectPlacer
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Place des spawns de mobs sur les cellules walkables.
     *
     * @param int $difficulty Niveau de difficulte (1-10)
     */
    public function placeMobSpawns(Map $map, BiomeDefinition $biome, int $difficulty, int $seed): int
    {
        $walkable = $this->getWalkableCells($map);
        if ($walkable === []) {
            return 0;
        }

        $availableMobs = $biome->getAvailableMobs();
        $eligibleMobs = array_filter(
            $availableMobs,
            fn (array $m) => $difficulty >= $m['minDifficulty'] && $difficulty <= $m['maxDifficulty']
        );

        if ($eligibleMobs === []) {
            // Fallback : prendre tous les mobs du biome
            $eligibleMobs = $availableMobs;
        }

        $eligibleMobs = array_values($eligibleMobs);

        // 8-15 spawns selon la taille de la carte
        $totalCells = \count($walkable);
        $spawnCount = max(8, min(15, (int) ($totalCells * 0.01)));

        // Repartir uniformement via selection espacee deterministe
        $rng = $seed ^ 0xDEAD;
        $walkable = $this->deterministicShuffle($walkable, $rng);

        // Espacer les spawns : au moins 3 cases d'ecart
        $placed = [];
        $count = 0;

        foreach ($walkable as [$x, $y]) {
            if ($count >= $spawnCount) {
                break;
            }

            if ($this->isTooCloseToExisting($x, $y, $placed, 3)) {
                continue;
            }

            $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;
            $mobDef = $eligibleMobs[$rng % \count($eligibleMobs)];

            $monster = $this->em->getRepository(Monster::class)->findOneBy(['slug' => $mobDef['slug']]);
            if (!$monster) {
                continue;
            }

            // Niveau : difficulte +/- 1
            $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;
            $level = max(1, $difficulty + ($rng % 3) - 1);

            $mob = new Mob();
            $mob->setMonster($monster);
            $mob->setMap($map);
            $mob->setCoordinates($x . '.' . $y);
            $mob->setLevel($level);
            $mob->setLife($monster->getLife());

            $this->em->persist($mob);
            $placed[] = [$x, $y];
            ++$count;
        }

        return $count;
    }

    /**
     * Place des spots de recolte sur les cellules walkables.
     */
    public function placeHarvestSpots(Map $map, BiomeDefinition $biome, int $seed): int
    {
        $harvestItems = $biome->getHarvestItems();
        if ($harvestItems === []) {
            return 0;
        }

        $walkable = $this->getWalkableCells($map);
        if ($walkable === []) {
            return 0;
        }

        // 5-10 spots selon la taille
        $totalCells = \count($walkable);
        $spotCount = max(5, min(10, (int) ($totalCells * 0.005)));

        $rng = $seed ^ 0xBEEF;
        $walkable = $this->deterministicShuffle($walkable, $rng);

        $placed = [];
        $count = 0;

        foreach ($walkable as [$x, $y]) {
            if ($count >= $spotCount) {
                break;
            }

            if ($this->isTooCloseToExisting($x, $y, $placed, 4)) {
                continue;
            }

            $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;
            $itemSlug = $harvestItems[$rng % \count($harvestItems)];

            $spot = new ObjectLayer();
            $spot->setType(ObjectLayer::TYPE_HARVEST_SPOT);
            $spot->setName('Spot ' . str_replace('-', ' ', $itemSlug));
            $spot->setSlug('harvest-gen-' . $itemSlug . '-' . $x . '-' . $y);
            $spot->setMap($map);
            $spot->setCoordinates($x . '.' . $y);
            $spot->setRespawnDelay(300);
            $spot->setUsedAt(null);
            $spot->setItems([$itemSlug]);
            $spot->setActions(null);

            $this->em->persist($spot);
            $placed[] = [$x, $y];
            ++$count;
        }

        return $count;
    }

    /**
     * Place des portails aux bords de la carte sur des cellules walkables.
     *
     * @param Map[] $adjacentMaps [north => Map|null, south => Map|null, east => Map|null, west => Map|null]
     */
    public function placePortals(Map $map, array $adjacentMaps, int $seed): int
    {
        $width = $map->getAreaWidth();
        $height = $map->getAreaHeight();
        $fullData = $this->getFullDataCells($map);

        $rng = $seed ^ 0xCAFE;
        $count = 0;

        $sides = [
            'north' => fn () => $this->findWalkableBorderCell($fullData, 0, $width - 1, 0, 0, $rng),
            'south' => fn () => $this->findWalkableBorderCell($fullData, 0, $width - 1, $height - 1, $height - 1, $rng),
            'west' => fn () => $this->findWalkableBorderCell($fullData, 0, 0, 0, $height - 1, $rng),
            'east' => fn () => $this->findWalkableBorderCell($fullData, $width - 1, $width - 1, 0, $height - 1, $rng),
        ];

        foreach ($sides as $direction => $finder) {
            $adjMap = $adjacentMaps[$direction] ?? null;
            if (!$adjMap) {
                continue;
            }

            $cell = $finder();
            if ($cell === null) {
                continue;
            }

            [$px, $py] = $cell;

            // Calculer les coordonnees cible (bord oppose)
            $destCoords = match ($direction) {
                'north' => $px . '.' . ($adjMap->getAreaHeight() - 1),
                'south' => $px . '.0',
                'west' => ($adjMap->getAreaWidth() - 1) . '.' . $py,
                'east' => '0.' . $py,
            };

            $portal = new ObjectLayer();
            $portal->setType(ObjectLayer::TYPE_PORTAL);
            $portal->setName('Passage ' . $direction);
            $portal->setSlug('portal-gen-' . $direction . '-' . $px . '-' . $py);
            $portal->setMap($map);
            $portal->setCoordinates($px . '.' . $py);
            $portal->setDestinationMapId($adjMap->getId());
            $portal->setDestinationCoordinates($destCoords);
            $portal->setUsedAt(null);
            $portal->setItems(null);
            $portal->setActions(null);

            $this->em->persist($portal);
            ++$count;
        }

        return $count;
    }

    /**
     * Place des rectangles de zone (biome, weather, music, light_level) sur l'Area.
     */
    public function placeZones(Map $map, BiomeDefinition $biome): int
    {
        $area = $map->getAreas()->first();
        if (!$area) {
            return 0;
        }

        // Zone principale : couvre toute la carte
        $area->setBiome($biome->getSlug());
        $area->setWeather($biome->getWeather());
        $area->setMusic($biome->getMusic());

        return 1;
    }

    /**
     * Verifie que toutes les cellules walkables forment un graphe connexe.
     * Si des ilots isoles sont detectes, creuse des passages pour les connecter.
     *
     * @return int Nombre de passages creuses
     */
    public function ensureConnectivity(Map $map): int
    {
        $area = $map->getAreas()->first();
        if (!$area) {
            return 0;
        }

        $fullData = $area->getFullDataArray();
        $cells = $fullData['cells'] ?? [];
        $width = $fullData['width'] ?? 0;
        $height = $fullData['height'] ?? 0;

        if ($width === 0 || $height === 0) {
            return 0;
        }

        // Identifier les composantes connexes de cellules walkables
        $visited = [];
        /** @var list<list<array{0: int, 1: int}>> $components */
        $components = [];

        for ($x = 0; $x < $width; ++$x) {
            for ($y = 0; $y < $height; ++$y) {
                $key = $x . '.' . $y;
                if (isset($visited[$key])) {
                    continue;
                }
                $movement = $cells[$x][$y]['mouvement'] ?? CellHelper::MOVE_UNREACHABLE;
                if ($movement === CellHelper::MOVE_UNREACHABLE) {
                    continue;
                }

                // BFS pour trouver toute la composante
                $component = [];
                $queue = [[$x, $y]];
                $visited[$key] = true;

                while ($queue !== []) {
                    [$cx, $cy] = array_shift($queue);
                    $component[] = [$cx, $cy];

                    foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dx, $dy]) {
                        $nx = $cx + $dx;
                        $ny = $cy + $dy;
                        $nKey = $nx . '.' . $ny;

                        if ($nx < 0 || $nx >= $width || $ny < 0 || $ny >= $height) {
                            continue;
                        }
                        if (isset($visited[$nKey])) {
                            continue;
                        }
                        $nMov = $cells[$nx][$ny]['mouvement'] ?? CellHelper::MOVE_UNREACHABLE;
                        if ($nMov === CellHelper::MOVE_UNREACHABLE) {
                            continue;
                        }

                        $visited[$nKey] = true;
                        $queue[] = [$nx, $ny];
                    }
                }

                $components[] = $component;
            }
        }

        if (\count($components) <= 1) {
            return 0;
        }

        // Trouver la plus grande composante
        $mainIdx = 0;
        $mainSize = \count($components[0]);
        for ($i = 1; $i < \count($components); ++$i) {
            if (\count($components[$i]) > $mainSize) {
                $mainSize = \count($components[$i]);
                $mainIdx = $i;
            }
        }
        // Mettre la plus grande en premier
        if ($mainIdx !== 0) {
            [$components[0], $components[$mainIdx]] = [$components[$mainIdx], $components[0]];
        }
        $mainComponent = $components[0];

        $passagesCreated = 0;

        // Pour chaque ilot isole, creuser un chemin vers la composante principale
        for ($i = 1; $i < \count($components); ++$i) {
            $island = $components[$i];
            $passagesCreated += $this->connectIsland($cells, $mainComponent, $island, $width, $height);
            // Ajouter les cellules de l'ile connectee a la composante principale
            $mainComponent = array_merge($mainComponent, $island);
        }

        if ($passagesCreated > 0) {
            $fullData['cells'] = $cells;
            $area->setFullData(json_encode($fullData));
        }

        return $passagesCreated;
    }

    /**
     * Place toutes les entites en une seule passe (mobs + harvest + zones).
     */
    public function placeAll(Map $map, BiomeDefinition $biome, int $difficulty, int $seed): array
    {
        $mobs = $this->placeMobSpawns($map, $biome, $difficulty, $seed);
        $harvests = $this->placeHarvestSpots($map, $biome, $seed);
        $zones = $this->placeZones($map, $biome);

        return [
            'mobs' => $mobs,
            'harvestSpots' => $harvests,
            'zones' => $zones,
        ];
    }

    /**
     * Retourne les cellules walkables de la carte sous forme de tableau [[x, y], ...].
     *
     * @return list<array{0: int, 1: int}>
     */
    private function getWalkableCells(Map $map): array
    {
        $fullData = $this->getFullDataCells($map);
        $walkable = [];

        foreach ($fullData as $x => $column) {
            foreach ($column as $y => $cell) {
                if (($cell['mouvement'] ?? CellHelper::MOVE_UNREACHABLE) !== CellHelper::MOVE_UNREACHABLE) {
                    $walkable[] = [(int) $x, (int) $y];
                }
            }
        }

        return $walkable;
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function getFullDataCells(Map $map): array
    {
        $area = $map->getAreas()->first();
        if (!$area) {
            return [];
        }

        $fullData = $area->getFullDataArray();

        return $fullData['cells'] ?? [];
    }

    /**
     * Melange deterministe via seed (Fisher-Yates avec LCG).
     *
     * @param list<array{0: int, 1: int}> $array
     *
     * @return list<array{0: int, 1: int}>
     */
    private function deterministicShuffle(array $array, int &$rng): array
    {
        $n = \count($array);
        for ($i = $n - 1; $i > 0; --$i) {
            $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;
            $j = $rng % ($i + 1);
            [$array[$i], $array[$j]] = [$array[$j], $array[$i]];
        }

        return $array;
    }

    /**
     * Verifie si la position (x, y) est trop proche d'une position deja placee.
     *
     * @param list<array{0: int, 1: int}> $placed
     */
    private function isTooCloseToExisting(int $x, int $y, array $placed, int $minDistance): bool
    {
        foreach ($placed as [$px, $py]) {
            if (abs($x - $px) + abs($y - $py) < $minDistance) {
                return true;
            }
        }

        return false;
    }

    /**
     * Trouve une cellule walkable sur un bord de carte.
     *
     * @return array{0: int, 1: int}|null
     */
    private function findWalkableBorderCell(array $cells, int $minX, int $maxX, int $minY, int $maxY, int &$rng): ?array
    {
        $candidates = [];

        for ($x = $minX; $x <= $maxX; ++$x) {
            for ($y = $minY; $y <= $maxY; ++$y) {
                if (isset($cells[$x][$y]) && ($cells[$x][$y]['mouvement'] ?? CellHelper::MOVE_UNREACHABLE) !== CellHelper::MOVE_UNREACHABLE) {
                    $candidates[] = [$x, $y];
                }
            }
        }

        if ($candidates === []) {
            return null;
        }

        $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;

        return $candidates[$rng % \count($candidates)];
    }

    /**
     * Creuse un passage en ligne droite entre l'ilot isole et la composante principale.
     *
     * @param array<int, array<int, mixed>> $cells     Cellules (modifiees par reference)
     * @param list<array{0: int, 1: int}>   $mainCells Composante principale
     * @param list<array{0: int, 1: int}>   $island    Ilot isole
     *
     * @return int Nombre de cellules creusees
     */
    private function connectIsland(array &$cells, array $mainCells, array $island, int $width, int $height): int
    {
        // Trouver la paire de cellules la plus proche entre l'ile et la composante principale
        $bestDist = \PHP_INT_MAX;
        $bestIsland = $island[0];
        $bestMain = $mainCells[0];

        // Echantillonner pour eviter O(n*m) complet sur de grandes cartes
        $sampleMain = \count($mainCells) > 100 ? array_slice($mainCells, 0, 100) : $mainCells;
        $sampleIsland = \count($island) > 50 ? array_slice($island, 0, 50) : $island;

        foreach ($sampleIsland as [$ix, $iy]) {
            foreach ($sampleMain as [$mx, $my]) {
                $dist = abs($ix - $mx) + abs($iy - $my);
                if ($dist < $bestDist) {
                    $bestDist = $dist;
                    $bestIsland = [$ix, $iy];
                    $bestMain = [$mx, $my];
                }
            }
        }

        // Creuser un chemin en L (horizontal puis vertical)
        [$ix, $iy] = $bestIsland;
        [$mx, $my] = $bestMain;

        $carved = 0;

        // Phase horizontale
        $stepX = $mx > $ix ? 1 : -1;
        $cx = $ix;
        while ($cx !== $mx) {
            $cx += $stepX;
            if ($cx >= 0 && $cx < $width && $iy >= 0 && $iy < $height) {
                if (isset($cells[$cx][$iy]) && ($cells[$cx][$iy]['mouvement'] ?? 0) === CellHelper::MOVE_UNREACHABLE) {
                    $cells[$cx][$iy]['mouvement'] = CellHelper::MOVE_DEFAULT;
                    $cells[$cx][$iy]['layers'][1] = null; // Retirer le ground layer (eau)
                    $cells[$cx][$iy]['layers'][2] = null; // Retirer le decoration layer (arbres)
                    $cells[$cx][$iy]['slug'] = $cx . '.' . $iy . '_0_0:0:0:0';
                    ++$carved;
                }
            }
        }

        // Phase verticale
        $stepY = $my > $iy ? 1 : -1;
        $cy = $iy;
        while ($cy !== $my) {
            $cy += $stepY;
            if ($mx >= 0 && $mx < $width && $cy >= 0 && $cy < $height) {
                if (isset($cells[$mx][$cy]) && ($cells[$mx][$cy]['mouvement'] ?? 0) === CellHelper::MOVE_UNREACHABLE) {
                    $cells[$mx][$cy]['mouvement'] = CellHelper::MOVE_DEFAULT;
                    $cells[$mx][$cy]['layers'][1] = null;
                    $cells[$mx][$cy]['layers'][2] = null;
                    $cells[$mx][$cy]['slug'] = $mx . '.' . $cy . '_0_0:0:0:0';
                    ++$carved;
                }
            }
        }

        return $carved;
    }
}
