<?php

namespace App\Command;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\Game\Monster;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:terrain:import',
    description: 'Import Tiled Map Editor (.tmx) terrain files into game data and optionally sync entities to database',
)]
class TerrainImportCommand extends Command
{
    public const ABILITY_CLIMB = 0b10;
    public const ABILITY_SWIM = 0b100;

    private array $collisions = [
        0 => null,
        1 => null,
        2 => 'W',
        3 => 'E',
        4 => 'S',
        5 => 'N',
        6 => 'NS',
        7 => 'EW',
        8 => 'NE',
        9 => 'ES',
        10 => 'SW',
        11 => 'WN',
        12 => 'ESW',
        13 => 'NEW',
        14 => 'NES',
        15 => 'NSW',
        16 => self::ABILITY_CLIMB,
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Terrain file name (e.g. world-1-map-0-1.tmx)')
            ->addOption('validate', null, InputOption::VALUE_NONE, 'Validate maps without importing')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Import all TMX files in terrain/')
            ->addOption('sync-entities', null, InputOption::VALUE_NONE, 'Create/update database entities from object layers (mobs, portals, spots)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Parse and report statistics without writing any files or database changes')
            ->addOption('stats', null, InputOption::VALUE_NONE, 'Show detailed statistics after import (cell counts, layer info, tileset usage)')
            ->addOption('map-id', null, InputOption::VALUE_REQUIRED, 'Map ID to use for entity sync (default: auto-detect from Map table)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $validateOnly = $input->getOption('validate');
        $syncEntities = $input->getOption('sync-entities');
        $dryRun = $input->getOption('dry-run');
        $showStats = $input->getOption('stats') || $dryRun;
        $mapId = $input->getOption('map-id') ? (int) $input->getOption('map-id') : null;

        if ($input->getOption('all')) {
            $names = ['*.tmx'];
        } elseif ($input->getArgument('name')) {
            $names = [$input->getArgument('name')];
        } else {
            $names = [
                // 'world-1-map-0-0.tmx',
                'world-1-map-0-1.tmx',
                // 'world-1-map-1-0.tmx',
                // 'world-1-map-1-1.tmx',
                // 'world-1-map-1-2.tmx',
                // 'world-1-map-2-0.tmx',
                // 'world-1-map-2-1.tmx',
                // 'world-1-map-2-2.tmx',
                // 'world-1-cave-1.tmx',
                // 'world-1-house-1.tmx',
            ];
        }
        $filePath = $this->projectDir . '/terrain';

        $finder = new Finder();
        $finder->files()->in($filePath)->name($names);
        foreach ($finder as $file) {
            $fileNameA = explode('.', $file->getFilename());
            $fileName = array_shift($fileNameA);
            $io->info("Importing $fileName");
            $fileCrawler = new Crawler($file->getContents());

            $pattern = '/([a-z]*)-([0-9]*)-([a-z]*)-([0-9]*)-([0-9]*)/';
            preg_match($pattern, $fileName, $res);
            $offsetX = $res[4] ?? 0;
            $offsetY = $res[5] ?? 0;

            $mapWidth = (int) $fileCrawler->attr('width');
            $mapHeight = (int) $fileCrawler->attr('height');
            $map['width'] = (int) $fileCrawler->attr('width');
            $map['height'] = (int) $fileCrawler->attr('height');
            $map['tileHeight'] = (int) $fileCrawler->attr('tileheight');
            $map['tileWidth'] = (int) $fileCrawler->attr('tilewidth');
            $map['cells'] = [];
            $map['terrains'] = [];
            $slugs = [];

            //            foreach ($layers as $layer) {
            //                $layerCrawler = new Crawler($layer);
            //                dump($layerCrawler->attr('name'));
            //                die;
            //                dump($layerCrawler->filterXPath('//data')->html());
            //                die;
            //            }
            //            dump($layers);
            //            die;

            $tilesets = $fileCrawler->filterXPath('//map/tileset');
            foreach ($tilesets as $tileset) {
                $tilesetCrawler = new Crawler($tileset);
                $sourcePath = explode('/', $tilesetCrawler->attr('source'));
                $tilesetName = array_pop($sourcePath);
                $sourcePath = $filePath . '/' . $tilesetCrawler->attr('source');
                $sourceContentPathA = explode('/', $sourcePath);
                array_pop($sourceContentPathA);
                /**
                 * Path of the folder containing the source tileset
                 * This is useful to get the image file from this path.
                 */
                $sourceContentPath = implode('/', $sourceContentPathA) . '/';
                $sourceContent = file_get_contents($sourcePath);

                $crawler = new Crawler($sourceContent);
                $tilewidth = $crawler->filterXPath('//tileset')->attr('tilewidth');
                $tileheight = $crawler->filterXPath('//tileset')->attr('tileheight');
                $tilecount = $crawler->filterXPath('//tileset')->attr('tilecount');
                $columns = $crawler->filterXPath('//tileset')->attr('columns');
                $sourceImage = $crawler->filterXPath('//tileset/image')->attr('source');
                $imageWidth = $crawler->filterXPath('//tileset/image')->attr('width');
                $imageHeight = $crawler->filterXPath('//tileset/image')->attr('height');
                $terrainSlug = '';
                $sanitizePath = str_replace('../', '', $sourceContentPath);
                $sanitizePath = str_replace($filePath . '/', '', $sanitizePath);

                $map['terrains'][$tilesetCrawler->attr('firstgid')] = [
                    'fullPath' => $sourceContentPath,
                    'sanitizePath' => $sanitizePath,
                    'image' => $sourceImage,
                    'tilesetName' => $tilesetName,
                    'tilewidth' => $tilewidth,
                    'tileheight' => $tileheight,
                    'tilecount' => $tilecount,
                    'columns' => $columns,
                    'imageWidth' => $imageWidth,
                    'imageHeight' => $imageHeight,
                    'firstgid' => (int) $tilesetCrawler->attr('firstgid'),
                ];
            }

            $layers = $fileCrawler->filterXPath('//map/layer');

            $layerIdx = 0;
            foreach ($layers as $layer) {
                $layerCrawler = new Crawler($layer);
                $layerData = $layerCrawler->filterXPath('//data')->html();
                $data = explode("\n", $layerData);
                $idx = 0;
                $y = (int) $offsetY * $mapHeight;
                foreach ($data as $values) {
                    $x = (int) $offsetX * $mapWidth;
                    if ($values === '') {
                        continue;
                    }
                    //                    $x = intdiv($idx, $mapWidth);
                    //                    $y = $idx % $mapWidth;
                    $lineValues = explode(',', $values);
                    foreach ($lineValues as $lineValue) {
                        if ($lineValue === '') {
                            continue;
                        }
                        $value = (int) $lineValue;
                        if (!isset($map['cells'][$x][$y])) {
                            $map['cells'][$x][$y] = [
                                'x' => $x,
                                'y' => $y,
                                'mouvement' => 0,
                                'cellIdx' => $value,
                                'layers' => [],
                            ];
                        }

                        if ($layerCrawler->attr('name') !== 'collision') {
                            $map['cells'][$x][$y]['layers'][$layerIdx] = null;
                            if ($value !== 0) {
                                // Find the map index to use
                                $terrains = $map['terrains'];
                                $cellMapIdx = key($terrains);
                                $tileset = '';
                                $source = '';
                                foreach ($terrains as $terrainIdx => $terrainValue) {
                                    if ($value - (int) $terrainIdx >= 0) {
                                        $cellMapIdx = $terrainIdx;
                                        $tileset = $terrainValue['tilesetName'];
                                        $source = $terrainValue['sanitizePath'] . $terrainValue['tilesetName'];
                                    } else {
                                        break;
                                    }
                                    // $cellMapIdx = $value - $terrainIdx >= 0 ? $terrainIdx : $cellMapIdx;
                                }
                                $tilesetName = str_replace('.tsx', '', $tileset);
                                $map['cells'][$x][$y]['layers'][$layerIdx] = [
                                    'mapIdx' => $cellMapIdx,
                                    'idxInMap' => $value - $cellMapIdx,
                                    'tilesetName' => $tilesetName,
                                    'source' => $source,
                                ];
                            }
                        } else {
                            // Find the map index to use
                            $terrains = $map['terrains'];
                            $cellMapIdx = key($terrains);
                            foreach ($terrains as $terrainIdx => $terrainValue) {
                                $cellMapIdx = $value - (int) $terrainIdx > 0 ? $terrainIdx : $cellMapIdx;
                            }
                            //                            $map['cells'][$x][$y]['layers']['collision'] = [
                            //                                'mapIdx'       => $cellMapIdx,
                            //                                'idxInMap' => $value - $cellMapIdx,
                            //                            ];

                            $idxInMap = $value - $cellMapIdx;
                            $slug = match ($idxInMap) {
                                1 => $x . '.' . $y . '_-1_-1:-1:-1:-1',
                                2 => $x . '.' . $y . '_0_0:0:0:-1',
                                3 => $x . '.' . $y . '_0_0:-1:0:0',
                                4 => $x . '.' . $y . '_0_0:0:-1:0',
                                5 => $x . '.' . $y . '_0_-1:0:0:0',
                                6 => $x . '.' . $y . '_0_-1:0:-1:0',
                                7 => $x . '.' . $y . '_0_0:-1:0:-1',
                                8 => $x . '.' . $y . '_0_-1:-1:0:0',
                                9 => $x . '.' . $y . '_0_0:-1:-1:0',
                                10 => $x . '.' . $y . '_0_0:0:-1:-1',
                                11 => $x . '.' . $y . '_0_-1:0:0:-1',
                                12 => $x . '.' . $y . '_0_0:-1:-1:-1',
                                13 => $x . '.' . $y . '_0_-1:-1:0:-1',
                                14 => $x . '.' . $y . '_0_-1:-1:-1:0',
                                15 => $x . '.' . $y . '_0_-1:0:-1:-1',
                                16 => $x . '.' . $y . '_2_0:0:0:0',
                                default => $x . '.' . $y . '_0_0:0:0:0',
                            };
                            $map['cells'][$x][$y]['slug'] = $slug;
                            $slugs[] = $slug;

                            $map['cells'][$x][$y]['mouvement'] = match ($idxInMap) {
                                1 => -1,
                                16 => 2,
                                default => 1,
                            };
                        }

                        ++$idx;
                        ++$x;
                    }
                    ++$y;
                }
                ++$layerIdx;
            }

            // --- Parse object groups (NPC spawns, mob spawns, portals, chests) ---
            $map['objects'] = [];
            $objectGroups = $fileCrawler->filterXPath('//map/objectgroup');
            foreach ($objectGroups as $objectGroup) {
                $groupCrawler = new Crawler($objectGroup);
                $groupName = $groupCrawler->attr('name') ?? 'objects';

                $objects = $groupCrawler->filterXPath('//object');
                foreach ($objects as $object) {
                    $objCrawler = new Crawler($object);
                    $objType = $objCrawler->attr('type') ?? $objCrawler->attr('class') ?? '';
                    $objName = $objCrawler->attr('name') ?? '';
                    $objX = (int) floor((float) $objCrawler->attr('x') / $map['tileWidth']) + (int) $offsetX * $mapWidth;
                    $objY = (int) floor((float) $objCrawler->attr('y') / $map['tileHeight']) + (int) $offsetY * $mapHeight;
                    $objWidth = (int) ceil((float) ($objCrawler->attr('width') ?? $map['tileWidth']) / $map['tileWidth']);
                    $objHeight = (int) ceil((float) ($objCrawler->attr('height') ?? $map['tileHeight']) / $map['tileHeight']);

                    // Parse custom properties
                    $properties = [];
                    $propNodes = $objCrawler->filterXPath('//properties/property');
                    foreach ($propNodes as $propNode) {
                        $propCrawler = new Crawler($propNode);
                        $properties[$propCrawler->attr('name')] = $propCrawler->attr('value') ?? $propCrawler->text('');
                    }

                    $map['objects'][] = [
                        'group' => $groupName,
                        'type' => $objType,
                        'name' => $objName,
                        'x' => $objX,
                        'y' => $objY,
                        'width' => $objWidth,
                        'height' => $objHeight,
                        'properties' => $properties,
                    ];
                }
            }

            if (!empty($map['objects'])) {
                $io->info(sprintf('  Found %d objects in object layers', count($map['objects'])));
                $typeCounts = [];
                foreach ($map['objects'] as $obj) {
                    $type = $obj['type'] ?: 'untyped';
                    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                }
                foreach ($typeCounts as $type => $count) {
                    $io->info(sprintf('    - %s: %d', $type, $count));
                }
            }

            // --- Statistics ---
            if ($showStats) {
                $totalCells = 0;
                $walkableCells = 0;
                $wallCells = 0;
                $layerCount = $layerIdx;
                $tilesetCount = count($map['terrains']);
                $objectCount = count($map['objects'] ?? []);

                foreach ($map['cells'] as $col) {
                    foreach ($col as $cell) {
                        ++$totalCells;
                        $movement = $cell['mouvement'] ?? 0;
                        if ($movement === -1) {
                            ++$wallCells;
                        } else {
                            ++$walkableCells;
                        }
                    }
                }

                $io->section('Statistiques — ' . $fileName);
                $io->table(
                    ['Propriété', 'Valeur'],
                    [
                        ['Dimensions', $mapWidth . ' × ' . $mapHeight . ' tiles'],
                        ['Tile size', $map['tileWidth'] . ' × ' . $map['tileHeight'] . ' px'],
                        ['Total cells', number_format($totalCells)],
                        ['Walkable cells', number_format($walkableCells) . ' (' . ($totalCells > 0 ? round($walkableCells / $totalCells * 100, 1) : 0) . '%)'],
                        ['Wall cells', number_format($wallCells) . ' (' . ($totalCells > 0 ? round($wallCells / $totalCells * 100, 1) : 0) . '%)'],
                        ['Layers', (string) $layerCount],
                        ['Tilesets', (string) $tilesetCount],
                        ['Objects', (string) $objectCount],
                    ]
                );

                if (!empty($map['terrains'])) {
                    $tilesetRows = [];
                    foreach ($map['terrains'] as $firstGid => $terrain) {
                        $tilesetRows[] = [
                            $terrain['tilesetName'],
                            (string) $firstGid,
                            $terrain['columns'] . ' col × ' . $terrain['tilecount'] . ' tiles',
                            $terrain['tilewidth'] . '×' . $terrain['tileheight'] . ' px',
                        ];
                    }
                    $io->table(['Tileset', 'First GID', 'Grid', 'Tile size'], $tilesetRows);
                }
            }

            // Dry-run: skip file writing
            if ($dryRun) {
                $io->note($fileName . ' dry-run complete (no files written)');
                continue;
            }

            // --- Validation ---
            if ($validateOnly) {
                $errors = $this->validateMap($map, $filePath);
                if (empty($errors)) {
                    $io->success($fileName . ' validation passed');
                } else {
                    $io->error($fileName . ' validation failed:');
                    foreach ($errors as $error) {
                        $io->warning('  ' . $error);
                    }
                }
                continue;
            }

            $fileSystem = new Filesystem();
            $fileSystem->mkdir($this->projectDir . '/data/export/map');
            $fileSystem->mkdir($this->projectDir . '/data/map');

            // Export main map data
            $exportFile = $this->projectDir . '/data/export/map/' . $fileName . '.json';
            file_put_contents($exportFile, json_encode($map));

            $mapFile = $this->projectDir . '/data/map/' . $fileName . '.json';
            file_put_contents($mapFile, json_encode($map));

            // Export slugs
            $exportFileSlugs = $this->projectDir . '/data/export/map/' . $fileName . '-slugs.json';
            file_put_contents($exportFileSlugs, json_encode($slugs));

            // Export objects separately for easy access
            if (!empty($map['objects'])) {
                $objectsFile = $this->projectDir . '/data/export/map/' . $fileName . '-objects.json';
                file_put_contents($objectsFile, json_encode($map['objects'], JSON_PRETTY_PRINT));
                $io->info(sprintf('  Exported %d objects to %s', count($map['objects']), $fileName . '-objects.json'));
            }

            // Sync entities from object layers
            if ($syncEntities && !empty($map['objects'])) {
                $synced = $this->syncEntitiesFromObjects($map['objects'], $io, $mapId);
                $io->info(sprintf('  Synced %d entities to database', $synced));
            }

            $io->success($fileName . ' imported');
        }

        return Command::SUCCESS;
    }

    private function validateMap(array $map, string $filePath): array
    {
        $errors = [];

        // Check tilesets exist
        foreach ($map['terrains'] as $terrain) {
            $imagePath = ($terrain['fullPath'] ?? $filePath . '/') . ($terrain['image'] ?? '');
            if (!file_exists($imagePath)) {
                $errors[] = sprintf('Tileset image not found: %s', $imagePath);
            }
        }

        // Check collision layer is present
        $hasCollision = false;
        foreach ($map['cells'] as $col) {
            foreach ($col as $cell) {
                if (isset($cell['mouvement']) && $cell['mouvement'] === -1) {
                    $hasCollision = true;
                    break 2;
                }
            }
        }
        if (!$hasCollision) {
            $errors[] = 'No collision data found — did you add a "collision" layer in Tiled?';
        }

        // Check dimensions
        $expectedWidth = $map['width'] ?? 0;
        $expectedHeight = $map['height'] ?? 0;
        $actualCols = count($map['cells']);
        if ($actualCols > 0) {
            $firstCol = reset($map['cells']);
            $actualRows = is_array($firstCol) ? count($firstCol) : 0;
            if ($actualCols !== $expectedWidth || $actualRows !== $expectedHeight) {
                $errors[] = sprintf(
                    'Dimension mismatch: expected %dx%d, got %dx%d cells',
                    $expectedWidth, $expectedHeight, $actualCols, $actualRows
                );
            }
        }

        // Validate object layers
        foreach ($map['objects'] ?? [] as $obj) {
            $x = $obj['x'];
            $y = $obj['y'];
            $type = $obj['type'] ?? '';

            // Check spawns are on walkable cells
            if (in_array($type, ['mob_spawn', 'npc_spawn', 'harvest_spot'])) {
                $cell = $map['cells'][$x][$y] ?? null;
                if ($cell !== null && ($cell['mouvement'] ?? 0) === -1) {
                    $errors[] = sprintf(
                        '%s "%s" at (%d,%d) is on a non-walkable cell',
                        $type, $obj['name'] ?? '', $x, $y
                    );
                }
            }

            // Check portals have required properties
            if ($type === 'portal') {
                $props = $obj['properties'] ?? [];
                if (empty($props['target_map'])) {
                    $errors[] = sprintf(
                        'Portal "%s" at (%d,%d) missing "target_map" property',
                        $obj['name'] ?? '', $x, $y
                    );
                }
                if (empty($props['target_x']) || empty($props['target_y'])) {
                    $errors[] = sprintf(
                        'Portal "%s" at (%d,%d) missing "target_x" or "target_y" property',
                        $obj['name'] ?? '', $x, $y
                    );
                }
            }

            // Check mob_spawn has monster reference
            if ($type === 'mob_spawn') {
                $props = $obj['properties'] ?? [];
                if (empty($props['monster_slug'])) {
                    $errors[] = sprintf(
                        'mob_spawn "%s" at (%d,%d) missing "monster_slug" property',
                        $obj['name'] ?? '', $x, $y
                    );
                }
            }
        }

        return $errors;
    }

    private function syncEntitiesFromObjects(array $objects, SymfonyStyle $io, ?int $mapId = null): int
    {
        $synced = 0;

        $mapRepository = $this->entityManager->getRepository(Map::class);

        if ($mapId !== null) {
            $map = $mapRepository->find($mapId);
        } else {
            // Auto-detect: use the first available map
            $map = $mapRepository->findOneBy([], ['id' => 'ASC']);
        }

        if (!$map) {
            $io->warning('No map found — use --map-id to specify the target map. Skipping entity sync.');

            return 0;
        }

        $io->info(sprintf('Using map #%d "%s" for entity sync', $map->getId(), $map->getName()));

        foreach ($objects as $obj) {
            $type = $obj['type'] ?? '';
            $x = $obj['x'];
            $y = $obj['y'];
            $coords = $x . '.' . $y;
            $props = $obj['properties'] ?? [];

            switch ($type) {
                case 'portal':
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
                    ++$synced;
                    $io->info(sprintf('  + Portal "%s" at %s → map %s at %s',
                        $objectLayer->getName(),
                        $coords,
                        $props['target_map_id'] ?? '?',
                        $objectLayer->getDestinationCoordinates() ?? '?'
                    ));
                    break;

                case 'mob_spawn':
                    $monsterSlug = $props['monster_slug'] ?? '';
                    if (empty($monsterSlug)) {
                        $io->warning(sprintf('  mob_spawn at %s has no monster_slug — skipped', $coords));
                        break;
                    }
                    $monster = $this->entityManager->getRepository(Monster::class)->findOneBy(['slug' => $monsterSlug]);
                    if (!$monster) {
                        $io->warning(sprintf('  Monster "%s" not found — skipped mob_spawn at %s', $monsterSlug, $coords));
                        break;
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
                    ++$synced;
                    $io->info(sprintf('  + Mob "%s" (level %d) at %s', $monster->getName(), $monster->getLevel(), $coords));
                    break;

                case 'harvest_spot':
                case 'spot':
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
                    ++$synced;
                    $io->info(sprintf('  + Harvest spot "%s" at %s', $objectLayer->getName(), $coords));
                    break;

                case 'chest':
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
                    ++$synced;
                    $io->info(sprintf('  + Chest "%s" at %s', $objectLayer->getName(), $coords));
                    break;
            }
        }

        $this->entityManager->flush();

        return $synced;
    }
}
