<?php

namespace App\Command;

use App\DataStorage\MapStorage;
use App\Entity\App\Map;
use App\GameEngine\Terrain\AreaSynchronizer;
use App\GameEngine\Terrain\EntitySynchronizer;
use App\GameEngine\Terrain\TmxParser;
use App\Transformer\MapModelTransformer;
use Doctrine\ORM\EntityManagerInterface;
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
    name: 'app:terrain:sync',
    description: 'Unified terrain pipeline: import TMX, sync entities/zones, rebuild Dijkstra cache, report diff',
)]
class TerrainSyncCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly TmxParser $tmxParser,
        private readonly EntitySynchronizer $entitySynchronizer,
        private readonly AreaSynchronizer $areaSynchronizer,
        private readonly EntityManagerInterface $entityManager,
        private readonly MapModelTransformer $mapModelTransformer,
        private readonly MapStorage $mapStorage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Terrain file name (e.g. world-1-map-0-1.tmx)')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Import all TMX files in terrain/')
            ->addOption('map-id', null, InputOption::VALUE_REQUIRED, 'Map ID for entity/zone sync (default: auto-detect)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Parse and report without writing files or database changes')
            ->addOption('skip-dijkstra', null, InputOption::VALUE_NONE, 'Skip Dijkstra tag map regeneration')
            ->addOption('skip-entities', null, InputOption::VALUE_NONE, 'Skip entity sync (mobs, portals, spots, chests)')
            ->addOption('skip-zones', null, InputOption::VALUE_NONE, 'Skip zone/biome Area sync');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $skipDijkstra = $input->getOption('skip-dijkstra');
        $skipEntities = $input->getOption('skip-entities');
        $skipZones = $input->getOption('skip-zones');
        $mapId = $input->getOption('map-id') ? (int) $input->getOption('map-id') : null;

        $io->title('Terrain Sync Pipeline');

        if ($dryRun) {
            $io->note('Mode dry-run : aucune ecriture fichier ni base de donnees.');
        }

        // Resolve TMX files
        if ($input->getOption('all')) {
            $names = ['*.tmx'];
        } elseif ($input->getArgument('name')) {
            $names = [$input->getArgument('name')];
        } else {
            $names = ['*.tmx'];
        }

        $terrainPath = $this->projectDir . '/terrain';
        $finder = new Finder();
        $finder->files()->in($terrainPath)->name($names)->sortByName();

        $fileCount = $finder->count();
        if ($fileCount === 0) {
            $io->warning('Aucun fichier TMX trouve.');

            return Command::SUCCESS;
        }

        $io->info(sprintf('%d fichier(s) TMX a traiter.', $fileCount));

        // Diff counters
        $totalEntitiesSynced = 0;
        $totalZonesSynced = 0;
        $totalFilesExported = 0;
        $allEntityMessages = [];
        $allZoneMessages = [];

        // Step 1 — Parse & export TMX files
        $io->section('Etape 1/4 — Import TMX');

        foreach ($finder as $file) {
            $fileNameA = explode('.', $file->getFilename());
            $fileName = array_shift($fileNameA);

            $pattern = '/([a-z]*)-([0-9]*)-([a-z]*)-([0-9]*)-([0-9]*)/';
            preg_match($pattern, $fileName, $res);
            $offsetX = (int) ($res[4] ?? 0);
            $offsetY = (int) ($res[5] ?? 0);

            $result = $this->tmxParser->parse($file->getContents(), $terrainPath, $offsetX, $offsetY);
            $map = $result['map'];
            $slugs = $result['slugs'];

            // Statistics
            $totalCells = 0;
            $walkableCells = 0;
            foreach ($map['cells'] as $col) {
                foreach ($col as $cell) {
                    ++$totalCells;
                    if (($cell['mouvement'] ?? 0) !== -1) {
                        ++$walkableCells;
                    }
                }
            }
            $objectCount = count($map['objects'] ?? []);

            $io->text(sprintf(
                '  <info>%s</info> — %dx%d, %d cells (%d walkable), %d objects',
                $fileName,
                $map['width'],
                $map['height'],
                $totalCells,
                $walkableCells,
                $objectCount,
            ));

            if (!$dryRun) {
                $this->exportFiles($map, $slugs, $fileName);
                ++$totalFilesExported;
            }

            // Step 2 — Sync entities
            if (!$skipEntities && !empty($map['objects'])) {
                $syncResult = $this->entitySynchronizer->syncEntitiesFromObjects(
                    $dryRun ? [] : $map['objects'],
                    $mapId,
                );
                if (!$dryRun) {
                    $totalEntitiesSynced += $syncResult['synced'];
                    array_push($allEntityMessages, ...$syncResult['messages']);
                } else {
                    // In dry-run, count what would be synced
                    $entityTypes = [];
                    foreach ($map['objects'] as $obj) {
                        $type = $obj['type'] ?? '';
                        if (in_array($type, ['portal', 'mob_spawn', 'harvest_spot', 'spot', 'chest'], true)) {
                            $entityTypes[$type] = ($entityTypes[$type] ?? 0) + 1;
                        }
                    }
                    foreach ($entityTypes as $type => $count) {
                        $allEntityMessages[] = sprintf('  [dry-run] %d %s(s) a synchroniser', $count, $type);
                    }
                }
            }

            // Step 3 — Sync zones
            if (!$skipZones && !empty($map['objects'])) {
                if (!$dryRun) {
                    $zoneResult = $this->areaSynchronizer->syncZonesFromObjects($map['objects'], $mapId);
                    $totalZonesSynced += $zoneResult['synced'];
                    array_push($allZoneMessages, ...$zoneResult['messages']);
                } else {
                    $zoneObjects = $this->areaSynchronizer->filterZoneObjects($map['objects']);
                    if (!empty($zoneObjects)) {
                        $allZoneMessages[] = sprintf('  [dry-run] %d zone(s) a synchroniser', count($zoneObjects));
                    }
                }
            }
        }

        // Show entity sync details
        if (!$skipEntities && !empty($allEntityMessages)) {
            $io->section('Etape 2/4 — Sync entites');
            foreach ($allEntityMessages as $msg) {
                $io->text($msg);
            }
        }

        // Show zone sync details
        if (!$skipZones && !empty($allZoneMessages)) {
            $io->section('Etape 3/4 — Sync zones/biomes');
            foreach ($allZoneMessages as $msg) {
                $io->text($msg);
            }
        }

        // Step 4 — Rebuild Dijkstra
        $dijkstraMaps = 0;
        if (!$skipDijkstra && !$dryRun) {
            $io->section('Etape 4/4 — Rebuild Dijkstra');

            $maps = $mapId !== null
                ? [$this->entityManager->getRepository(Map::class)->find($mapId)]
                : $this->entityManager->getRepository(Map::class)->findAll();

            foreach ($maps as $mapEntity) {
                if ($mapEntity === null) {
                    continue;
                }

                $tagMap = $this->mapModelTransformer->generateDijkstraTagMap($mapEntity);
                $jsonContent = json_encode($tagMap);
                $filePath = $this->mapStorage->storeMapTag($mapEntity, $jsonContent);
                $io->text(sprintf(
                    '  Map #%d "%s" — %d nodes, ecrit dans %s',
                    $mapEntity->getId(),
                    $mapEntity->getName(),
                    count($tagMap),
                    basename($filePath),
                ));
                ++$dijkstraMaps;
            }
        } elseif ($skipDijkstra) {
            $io->section('Etape 4/4 — Rebuild Dijkstra (ignore)');
        } elseif ($dryRun) {
            $io->section('Etape 4/4 — Rebuild Dijkstra (dry-run, ignore)');
        }

        // Summary report
        $io->section('Rapport');
        $io->table(
            ['Metrique', 'Valeur'],
            [
                ['Fichiers TMX traites', (string) $fileCount],
                ['Fichiers JSON exportes', $dryRun ? '0 (dry-run)' : (string) $totalFilesExported],
                ['Entites synchronisees', $dryRun ? '— (dry-run)' : (string) $totalEntitiesSynced],
                ['Zones synchronisees', $dryRun ? '— (dry-run)' : (string) $totalZonesSynced],
                ['Dijkstra maps regenerees', $dryRun ? '— (dry-run)' : (string) $dijkstraMaps],
            ],
        );

        if ($dryRun) {
            $io->note('Dry-run termine. Aucune modification effectuee.');
        } else {
            $io->success('Terrain sync termine.');
        }

        return Command::SUCCESS;
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
