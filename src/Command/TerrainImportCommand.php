<?php

namespace App\Command;

use App\GameEngine\Terrain\AreaSynchronizer;
use App\GameEngine\Terrain\EntitySynchronizer;
use App\GameEngine\Terrain\TmxParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:terrain:import',
    description: 'Import Tiled Map Editor (.tmx) terrain files into game data and optionally sync entities to database',
)]
class TerrainImportCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly TmxParser $tmxParser,
        private readonly EntitySynchronizer $entitySynchronizer,
        private readonly AreaSynchronizer $areaSynchronizer,
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
            ->addOption('map-id', null, InputOption::VALUE_REQUIRED, 'Map ID to use for entity sync (default: auto-detect from Map table)')
            ->addOption('sync-zones', null, InputOption::VALUE_NONE, 'Sync zone/biome objects to Area entities (biome, weather, music, light_level)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $validateOnly = $input->getOption('validate');
        $syncEntities = $input->getOption('sync-entities');
        $syncZones = $input->getOption('sync-zones');
        $dryRun = $input->getOption('dry-run');
        $showStats = $input->getOption('stats') || $dryRun;
        $mapId = $input->getOption('map-id') ? (int) $input->getOption('map-id') : null;

        if ($input->getOption('all')) {
            $names = ['*.tmx'];
        } elseif ($input->getArgument('name')) {
            $names = [$input->getArgument('name')];
        } else {
            $names = [
                'world-1-map-0-1.tmx',
            ];
        }

        $terrainPath = $this->projectDir . '/terrain';

        $finder = new Finder();
        $finder->files()->in($terrainPath)->name($names);

        foreach ($finder as $file) {
            $fileNameA = explode('.', $file->getFilename());
            $fileName = array_shift($fileNameA);
            $io->info("Importing $fileName");

            $pattern = '/([a-z]*)-([0-9]*)-([a-z]*)-([0-9]*)-([0-9]*)/';
            preg_match($pattern, $fileName, $res);
            $offsetX = (int) ($res[4] ?? 0);
            $offsetY = (int) ($res[5] ?? 0);

            // Parse TMX file
            $result = $this->tmxParser->parse($file->getContents(), $terrainPath, $offsetX, $offsetY);
            $map = $result['map'];
            $slugs = $result['slugs'];

            // Report objects
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

            // Statistics
            if ($showStats) {
                $this->showStatistics($io, $map, $fileName);
            }

            // Dry-run: skip file writing
            if ($dryRun) {
                $io->note($fileName . ' dry-run complete (no files written)');
                continue;
            }

            // Validation only
            if ($validateOnly) {
                $errors = $this->tmxParser->validateMap($map, $terrainPath);
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

            // Export files
            $this->exportFiles($map, $slugs, $fileName);

            // Sync entities from object layers
            if ($syncEntities && !empty($map['objects'])) {
                $syncResult = $this->entitySynchronizer->syncEntitiesFromObjects($map['objects'], $mapId);
                foreach ($syncResult['messages'] as $message) {
                    $io->info($message);
                }
                $io->info(sprintf('  Synced %d entities to database', $syncResult['synced']));
            }

            // Sync zone/biome objects to Area entities
            if ($syncZones && !empty($map['objects'])) {
                $zoneResult = $this->areaSynchronizer->syncZonesFromObjects($map['objects'], $mapId);
                foreach ($zoneResult['messages'] as $message) {
                    $io->info($message);
                }
                if ($zoneResult['synced'] > 0) {
                    $io->info(sprintf('  Synced %d zone(s) to Area entities', $zoneResult['synced']));
                }
            }

            $io->success($fileName . ' imported');
        }

        return Command::SUCCESS;
    }

    private function showStatistics(SymfonyStyle $io, array $map, string $fileName): void
    {
        $totalCells = 0;
        $walkableCells = 0;
        $wallCells = 0;
        $tilesetCount = count($map['terrains']);
        $objectCount = count($map['objects']);

        // Count layers from a sample cell
        $layerCount = 0;
        foreach ($map['cells'] as $col) {
            foreach ($col as $cell) {
                $layerCount = max($layerCount, count($cell['layers'] ?? []));
                ++$totalCells;
                $movement = $cell['mouvement'];
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
                ['Dimensions', $map['width'] . ' × ' . $map['height'] . ' tiles'],
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

    private function exportFiles(array $map, array $slugs, string $fileName): void
    {
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($this->projectDir . '/data/export/map');
        $fileSystem->mkdir($this->projectDir . '/data/map');

        $exportFile = $this->projectDir . '/data/export/map/' . $fileName . '.json';
        file_put_contents($exportFile, json_encode($map));

        $mapFile = $this->projectDir . '/data/map/' . $fileName . '.json';
        file_put_contents($mapFile, json_encode($map));

        $exportFileSlugs = $this->projectDir . '/data/export/map/' . $fileName . '-slugs.json';
        file_put_contents($exportFileSlugs, json_encode($slugs));

        if (!empty($map['objects'])) {
            $objectsFile = $this->projectDir . '/data/export/map/' . $fileName . '-objects.json';
            file_put_contents($objectsFile, json_encode($map['objects'], JSON_PRETTY_PRINT));
        }
    }
}
