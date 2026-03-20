<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:terrain:preview',
    description: 'Generate a PNG preview image from a Tiled map (.tmx)',
)]
class TerrainPreviewCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('map', InputArgument::REQUIRED, 'TMX filename (e.g. world-1-map-0-1.tmx) or "all"')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output directory', 'data/preview')
            ->addOption('scale', 's', InputOption::VALUE_REQUIRED, 'Scale factor (0.25, 0.5, 1)', '1')
            ->addOption('show-collisions', null, InputOption::VALUE_NONE, 'Overlay collision data (red = wall, yellow = partial)')
            ->addOption('show-objects', null, InputOption::VALUE_NONE, 'Overlay object markers (portals, spawns, spots)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mapArg = $input->getArgument('map');
        $outputDir = $this->projectDir . '/' . ltrim($input->getOption('output'), '/');
        $scale = (float) $input->getOption('scale');
        $showCollisions = $input->getOption('show-collisions');
        $showObjects = $input->getOption('show-objects');

        if ($scale <= 0 || $scale > 2) {
            $io->error('Scale must be between 0.01 and 2.');

            return Command::FAILURE;
        }

        if (!extension_loaded('gd')) {
            $io->error('PHP GD extension is required. Install it with: apt-get install php-gd');

            return Command::FAILURE;
        }

        $terrainDir = $this->projectDir . '/terrain';
        $finder = new Finder();
        $finder->files()->in($terrainDir)->depth(0)->name($mapArg === 'all' ? '*.tmx' : $mapArg);

        if (!$finder->hasResults()) {
            $io->error(sprintf('No TMX file found matching "%s" in terrain/', $mapArg));

            return Command::FAILURE;
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        foreach ($finder as $file) {
            $fileName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $io->section('Generating preview: ' . $fileName);

            $result = $this->generatePreview(
                $file->getPathname(),
                $terrainDir,
                $outputDir,
                $fileName,
                $scale,
                $showCollisions,
                $showObjects,
                $io,
            );

            if ($result) {
                $io->success(sprintf('Preview saved: %s', $result));
            }
        }

        return Command::SUCCESS;
    }

    private function generatePreview(
        string $tmxPath,
        string $terrainDir,
        string $outputDir,
        string $fileName,
        float $scale,
        bool $showCollisions,
        bool $showObjects,
        SymfonyStyle $io,
    ): ?string {
        $crawler = new Crawler(file_get_contents($tmxPath));

        $mapWidth = (int) $crawler->attr('width');
        $mapHeight = (int) $crawler->attr('height');
        $tileWidth = (int) $crawler->attr('tilewidth');
        $tileHeight = (int) $crawler->attr('tileheight');

        $imgWidth = (int) ($mapWidth * $tileWidth * $scale);
        $imgHeight = (int) ($mapHeight * $tileHeight * $scale);

        $io->text(sprintf('Map: %dx%d tiles, %dx%d px (scaled: %dx%d px)',
            $mapWidth, $mapHeight,
            $mapWidth * $tileWidth, $mapHeight * $tileHeight,
            $imgWidth, $imgHeight
        ));

        // Parse tilesets
        $tilesets = $this->parseTilesets($crawler, $terrainDir);

        // Load tileset images
        $tilesetImages = [];
        foreach ($tilesets as $firstGid => $ts) {
            $imagePath = $ts['imagePath'];
            if (!file_exists($imagePath)) {
                $io->warning(sprintf('Tileset image not found: %s', $imagePath));
                continue;
            }
            $img = imagecreatefrompng($imagePath);
            if ($img === false) {
                $io->warning(sprintf('Failed to load tileset image: %s', $imagePath));
                continue;
            }
            $tilesetImages[$firstGid] = $img;
        }

        if (empty($tilesetImages)) {
            $io->error('No tileset images could be loaded.');

            return null;
        }

        // Create output image
        $image = imagecreatetruecolor($imgWidth, $imgHeight);
        $bgColor = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $bgColor);
        imagealphablending($image, true);

        // Parse and render layers (skip collision layer)
        $layers = $crawler->filterXPath('//map/layer');
        $collisionData = [];

        foreach ($layers as $layer) {
            $layerCrawler = new Crawler($layer);
            $layerName = $layerCrawler->attr('name');
            $layerData = $layerCrawler->filterXPath('//data')->html();
            $rows = explode("\n", $layerData);

            $y = 0;
            foreach ($rows as $row) {
                if (trim($row) === '') {
                    continue;
                }
                $values = explode(',', $row);
                $x = 0;
                foreach ($values as $val) {
                    $val = trim($val);
                    if ($val === '') {
                        continue;
                    }
                    $gid = (int) $val;

                    if ($layerName === 'collision') {
                        $collisionData[$x][$y] = $gid;
                    } elseif ($gid > 0) {
                        $this->drawTile($image, $gid, $x, $y, $tileWidth, $tileHeight, $scale, $tilesets, $tilesetImages);
                    }

                    ++$x;
                }
                ++$y;
            }
        }

        // Collision overlay
        if ($showCollisions && !empty($collisionData)) {
            $this->drawCollisionOverlay($image, $collisionData, $tileWidth, $tileHeight, $scale, $tilesets);
        }

        // Object overlay
        if ($showObjects) {
            $this->drawObjectOverlay($image, $crawler, $tileWidth, $tileHeight, $scale);
        }

        // Save PNG
        $outputPath = $outputDir . '/' . $fileName . '.png';
        imagepng($image, $outputPath, 6);
        imagedestroy($image);

        foreach ($tilesetImages as $img) {
            imagedestroy($img);
        }

        $fileSize = filesize($outputPath);
        $io->text(sprintf('File size: %s', $this->formatBytes($fileSize)));

        return $outputPath;
    }

    private function parseTilesets(Crawler $crawler, string $terrainDir): array
    {
        $tilesets = [];
        $tsNodes = $crawler->filterXPath('//map/tileset');

        foreach ($tsNodes as $tsNode) {
            $tsCrawler = new Crawler($tsNode);
            $firstGid = (int) $tsCrawler->attr('firstgid');
            $sourcePath = $terrainDir . '/' . $tsCrawler->attr('source');

            if (!file_exists($sourcePath)) {
                continue;
            }

            $tsxContent = new Crawler(file_get_contents($sourcePath));
            $tileWidth = (int) $tsxContent->filterXPath('//tileset')->attr('tilewidth');
            $tileHeight = (int) $tsxContent->filterXPath('//tileset')->attr('tileheight');
            $columns = (int) $tsxContent->filterXPath('//tileset')->attr('columns');
            $tileCount = (int) $tsxContent->filterXPath('//tileset')->attr('tilecount');
            $imageSource = $tsxContent->filterXPath('//tileset/image')->attr('source');

            // Resolve image path relative to TSX file
            $tsxDir = dirname($sourcePath);
            $imagePath = realpath($tsxDir . '/' . $imageSource) ?: $tsxDir . '/' . $imageSource;

            $tilesets[$firstGid] = [
                'firstGid' => $firstGid,
                'tileWidth' => $tileWidth,
                'tileHeight' => $tileHeight,
                'columns' => $columns,
                'tileCount' => $tileCount,
                'imagePath' => $imagePath,
            ];
        }

        // Sort by firstGid ascending
        ksort($tilesets);

        return $tilesets;
    }

    private function drawTile(
        \GdImage $image,
        int $gid,
        int $cellX,
        int $cellY,
        int $tileWidth,
        int $tileHeight,
        float $scale,
        array $tilesets,
        array $tilesetImages,
    ): void {
        // Find which tileset this GID belongs to
        $matchedFirstGid = null;
        foreach ($tilesets as $firstGid => $ts) {
            if ($gid >= $firstGid) {
                $matchedFirstGid = $firstGid;
            } else {
                break;
            }
        }

        if ($matchedFirstGid === null || !isset($tilesetImages[$matchedFirstGid])) {
            return;
        }

        $ts = $tilesets[$matchedFirstGid];
        $localId = $gid - $matchedFirstGid;

        if ($localId < 0 || $localId >= $ts['tileCount']) {
            return;
        }

        $srcX = ($localId % $ts['columns']) * $ts['tileWidth'];
        $srcY = intdiv($localId, $ts['columns']) * $ts['tileHeight'];

        $dstX = (int) ($cellX * $tileWidth * $scale);
        $dstY = (int) ($cellY * $tileHeight * $scale);
        $dstW = (int) ($tileWidth * $scale);
        $dstH = (int) ($tileHeight * $scale);

        imagecopyresampled(
            $image,
            $tilesetImages[$matchedFirstGid],
            $dstX, $dstY,
            $srcX, $srcY,
            $dstW, $dstH,
            $ts['tileWidth'], $ts['tileHeight']
        );
    }

    private function drawCollisionOverlay(
        \GdImage $image,
        array $collisionData,
        int $tileWidth,
        int $tileHeight,
        float $scale,
        array $tilesets,
    ): void {
        // Find collision tileset firstGid (Collisions.tsx)
        $collisionFirstGid = null;
        foreach ($tilesets as $firstGid => $ts) {
            if (str_contains($ts['imagePath'], 'collisions') || str_contains($ts['imagePath'], 'Collisions')) {
                $collisionFirstGid = $firstGid;
                break;
            }
        }

        $red = imagecolorallocatealpha($image, 255, 0, 0, 80);
        $yellow = imagecolorallocatealpha($image, 255, 255, 0, 90);

        foreach ($collisionData as $x => $col) {
            foreach ($col as $y => $gid) {
                if ($gid === 0) {
                    continue;
                }

                $localId = $collisionFirstGid !== null ? $gid - $collisionFirstGid : $gid;
                $dstX = (int) ($x * $tileWidth * $scale);
                $dstY = (int) ($y * $tileHeight * $scale);
                $dstW = (int) ($tileWidth * $scale);
                $dstH = (int) ($tileHeight * $scale);

                // localId 1 = full wall, others = partial collision
                $color = ($localId === 1) ? $red : $yellow;
                imagefilledrectangle($image, $dstX, $dstY, $dstX + $dstW - 1, $dstY + $dstH - 1, $color);
            }
        }
    }

    private function drawObjectOverlay(
        \GdImage $image,
        Crawler $crawler,
        int $tileWidth,
        int $tileHeight,
        float $scale,
    ): void {
        $colors = [
            'portal' => [0, 150, 255],      // Blue
            'mob_spawn' => [255, 50, 50],    // Red
            'npc_spawn' => [50, 200, 50],    // Green
            'harvest_spot' => [255, 200, 0], // Gold
            'spot' => [255, 200, 0],         // Gold
            'chest' => [200, 100, 255],      // Purple
        ];

        $objectGroups = $crawler->filterXPath('//map/objectgroup');
        foreach ($objectGroups as $objectGroup) {
            $groupCrawler = new Crawler($objectGroup);
            $objects = $groupCrawler->filterXPath('//object');

            foreach ($objects as $object) {
                $objCrawler = new Crawler($object);
                $type = $objCrawler->attr('type') ?? $objCrawler->attr('class') ?? '';
                $objPixelX = (float) $objCrawler->attr('x');
                $objPixelY = (float) $objCrawler->attr('y');

                $rgb = $colors[$type] ?? [180, 180, 180];
                $color = imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], 40);
                $border = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);

                $dstX = (int) ($objPixelX * $scale);
                $dstY = (int) ($objPixelY * $scale);
                $dstW = (int) ($tileWidth * $scale);
                $dstH = (int) ($tileHeight * $scale);

                imagefilledrectangle($image, $dstX, $dstY, $dstX + $dstW - 1, $dstY + $dstH - 1, $color);
                imagerectangle($image, $dstX, $dstY, $dstX + $dstW - 1, $dstY + $dstH - 1, $border);
            }
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}
