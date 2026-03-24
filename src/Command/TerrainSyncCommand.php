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
    description: 'Unified terrain pipeline: import TMX, sync entities & zones, rebuild Dijkstra cache, and report diff',
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
            ->addArgument('name', InputArgument::OPTIONAL, 'TMX file name (e.g. world-1-map-0-1.tmx)')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Process all TMX files in terrain/')
            ->addOption('map-id', null, InputOption::VALUE_REQUIRED, 'Map ID for entity/zone sync (default: auto-detect)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Parse and report without writing files or database changes')
            ->addOption('skip-dijkstra', null, InputOption::VALUE_NONE, 'Skip Dijkstra cache rebuild')
            ->addOption('skip-entities', null, InputOption::VALUE_NONE, 'Skip entity sync (mobs, portals, spots, chests)')
            ->addOption('skip-zones', null, InputOption::VALUE_NONE, 'Skip zone/biome sync');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $mapIdOption = $input->getOption('map-id') ? (int) $input->getOption('map-id') : null;
        $skipDijkstra = $input->getOption('skip-dijkstra');
        $skipEntities = $input->getOption('skip-entities');
        $skipZones = $input->getOption('skip-zones');

        $io->title('Terrain Sync Pipeline');

        if ($dryRun) {
            $io->note('Mode dry-run : aucune ecriture fichier ou base de donnees.');
        }

        // Resolve TMX file list
        if ($input->getOption('all')) {
            $names = ['*.tmx'];
        } elseif ($input->getArgument('name')) {
            $names = [$input->getArgument('name')];
        } else {
            $names = ['*.tmx'];
            $io->note('Aucun fichier specifie, import de tous les TMX (equivalent a --all).');
        }

        $terrainPath = $this->projectDir . '/terrain';
        $finder = new Finder();
        $finder->files()->in($terrainPath)->name($names)->sortByName();

        $totalFiles = 0;
        $totalEntities = 0;
        $totalZones = 0;
        $diffReport = [];

        // --- Step 1: Import TMX files + sync entities & zones ---
        $io->section('1/3 — Import TMX et synchronisation');

        foreach ($finder as $file) {
            $fileNameParts = explode('.', $file->getFilename());
            $fileName = array_shift($fileNameParts);

            $pattern = '/([a-z]*)-([0-9]*)-([a-z]*)-([0-9]*)-([0-9]*)/';
            preg_match($pattern, $fileName, $res);
            $offsetX = (int) ($res[4] ?? 0);
            $offsetY = (int) ($res[5] ?? 0);

            $io->text(sprintf('<info>%s</info> (offset %d,%d)', $fileName, $offsetX, $offsetY));

            // Parse TMX
            $result = $this->tmxParser->parse($file->getContents(), $terrainPath, $offsetX, $offsetY);
            $map = $result['map'];
            $slugs = $result['slugs'];

            // Collect object counts for diff report
            $objectCounts = [];
            foreach ($map['objects'] ?? [] as $obj) {
                $type = $obj['type'] ?: 'untyped';
                $objectCounts[$type] = ($objectCounts[$type] ?? 0) + 1;
            }

            $cellCount = 0;
            foreach ($map['cells'] as $col) {
                $cellCount += count($col);
            }

            $fileDiff = [
                'file' => $fileName,
                'cells' => $cellCount,
                'objects' => $objectCounts,
                'entities_synced' => 0,
                'zones_synced' => 0,
                'entity_messages' => [],
                'zone_messages' => [],
            ];

            if (!$dryRun) {
                // Export JSON files
                $this->exportFiles($map, $slugs, $fileName);

                // Sync entities
                if (!$skipEntities && !empty($map['objects'])) {
                    $syncResult = $this->entitySynchronizer->syncEntitiesFromObjects($map['objects'], $mapIdOption);
                    $fileDiff['entities_synced'] = $syncResult['synced'];
                    $fileDiff['entity_messages'] = $syncResult['messages'];
                    $totalEntities += $syncResult['synced'];
                }

                // Sync zones
                if (!$skipZones && !empty($map['objects'])) {
                    $zoneResult = $this->areaSynchronizer->syncZonesFromObjects($map['objects'], $mapIdOption);
                    $fileDiff['zones_synced'] = $zoneResult['synced'];
                    $fileDiff['zone_messages'] = $zoneResult['messages'];
                    $totalZones += $zoneResult['synced'];
                }
            }

            $diffReport[] = $fileDiff;
            ++$totalFiles;

        }

        // --- Step 2: Dijkstra cache rebuild ---
        $dijkstraRebuilt = 0;
        if (!$dryRun && !$skipDijkstra) {
            $io->section('2/3 — Reconstruction cache Dijkstra');

            $maps = $this->entityManager->getRepository(Map::class)->findAll();
            foreach ($maps as $mapEntity) {
                $io->text(sprintf('  Map #%d "%s"...', $mapEntity->getId(), $mapEntity->getName()));
                $jsonContent = json_encode($this->mapModelTransformer->generateDijkstraTagMap($mapEntity));
                $filePath = $this->mapStorage->storeMapTag($mapEntity, $jsonContent);
                $io->text(sprintf('    -> %s', $filePath));
                ++$dijkstraRebuilt;
            }
        } else {
            $io->section('2/3 — Dijkstra');
            $io->text($dryRun ? 'Skipped (dry-run)' : 'Skipped (--skip-dijkstra)');
        }

        // --- Step 3: Diff report ---
        $io->section('3/3 — Rapport diff');

        $reportRows = [];
        foreach ($diffReport as $diff) {
            $objectsSummary = [];
            foreach ($diff['objects'] as $type => $count) {
                $objectsSummary[] = sprintf('%s:%d', $type, $count);
            }

            $reportRows[] = [
                $diff['file'],
                number_format($diff['cells']),
                implode(', ', $objectsSummary) ?: '-',
                (string) $diff['entities_synced'],
                (string) $diff['zones_synced'],
            ];
        }

        $io->table(
            ['Fichier', 'Cells', 'Objets TMX', 'Entites sync', 'Zones sync'],
            $reportRows,
        );

        // Detail messages
        foreach ($diffReport as $diff) {
            $messages = array_merge($diff['entity_messages'], $diff['zone_messages']);
            if (!empty($messages)) {
                $io->text(sprintf('<comment>%s</comment>:', $diff['file']));
                foreach ($messages as $msg) {
                    $io->text('  ' . $msg);
                }
            }
        }

        // Summary
        $io->newLine();
        $io->success(sprintf(
            'Sync termine : %d fichier(s) TMX, %d entite(s), %d zone(s), %d carte(s) Dijkstra.',
            $totalFiles,
            $totalEntities,
            $totalZones,
            $dijkstraRebuilt,
        ));

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
            file_put_contents($objectsFile, json_encode($map['objects'], \JSON_PRETTY_PRINT));
        }
    }
}
