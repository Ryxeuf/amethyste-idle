<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:avatar:inventory',
    description: 'Inventory character sprite assets and verify size coherence for the avatar system',
)]
class AvatarInventoryCommand extends Command
{
    /** Directories to scan (relative to assets/styles/images/). */
    private const SCAN_DIRS = [
        'character/Male' => 'Personnages masculins',
        'character/Female' => 'Personnages feminins',
        'character/Soldier' => 'Soldats',
        'monster' => 'Monstres',
        'Boss' => 'Boss',
        'Animal' => 'Animaux',
        'avatar/body' => 'Avatar : corps',
        'avatar/hair' => 'Avatar : cheveux',
        'avatar/outfit' => 'Avatar : tenues',
        'avatar/head' => 'Avatar : coiffes',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('export', null, InputOption::VALUE_REQUIRED, 'Export inventory to a markdown file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Avatar Asset Inventory');

        $basePath = $this->projectDir . '/assets/styles/images';
        /** @var array<string, array{label: string, files: list<array{name: string, width: int, height: int, format: string, filesize: int}>, exists: bool}> $categories */
        $categories = [];
        /** @var list<string> $alerts */
        $alerts = [];
        $totalFiles = 0;

        foreach (self::SCAN_DIRS as $relDir => $label) {
            $dirPath = $basePath . '/' . $relDir;

            if (!is_dir($dirPath)) {
                $categories[$relDir] = ['label' => $label, 'files' => [], 'exists' => false];
                if (str_starts_with($relDir, 'avatar/')) {
                    $alerts[] = sprintf('Repertoire manquant : %s/ (necessaire pour le systeme avatar)', $relDir);
                }
                continue;
            }

            $finder = new Finder();
            $finder->files()->in($dirPath)->name('*.png')->sortByName();

            /** @var list<array{name: string, width: int, height: int, format: string, filesize: int}> $files */
            $files = [];
            foreach ($finder as $file) {
                $realPath = $file->getRealPath();
                if (false === $realPath) {
                    continue;
                }

                $size = @getimagesize($realPath);
                if (false === $size) {
                    $alerts[] = sprintf('Impossible de lire : %s/%s', $relDir, $file->getRelativePathname());
                    continue;
                }

                $files[] = [
                    'name' => $file->getRelativePathname(),
                    'width' => $size[0],
                    'height' => $size[1],
                    'format' => $this->classifyFormat($size[0], $size[1]),
                    'filesize' => (int) $file->getSize(),
                ];
                ++$totalFiles;
            }

            $categories[$relDir] = ['label' => $label, 'files' => $files, 'exists' => true];
        }

        // Root-level multi-character sheets (character/ root + demons.png)
        $rootMulti = $this->scanRootSheets($basePath);
        if (!empty($rootMulti)) {
            $categories['_root'] = ['label' => 'Sheets multi-personnages (racine)', 'files' => $rootMulti, 'exists' => true];
            $totalFiles += \count($rootMulti);
        }

        // --- Display per category ---
        $io->section('Inventaire par categorie');

        foreach ($categories as $dir => $cat) {
            if (!$cat['exists']) {
                $io->text(sprintf('  <comment>%s</comment> — repertoire inexistant (%s/)', $cat['label'], $dir));
                continue;
            }

            if (empty($cat['files'])) {
                $io->text(sprintf('  <comment>%s</comment> — vide', $cat['label']));
                continue;
            }

            $io->text(sprintf('  <info>%s</info> — %d fichier(s)', $cat['label'], \count($cat['files'])));
            $rows = array_map(fn (array $f): array => [
                $f['name'],
                sprintf('%dx%d', $f['width'], $f['height']),
                $f['format'],
                $this->formatFilesize($f['filesize']),
            ], $cat['files']);

            $io->table(['Fichier', 'Dimensions', 'Format', 'Taille'], $rows);
        }

        // --- Size coherence ---
        $io->section('Coherence des tailles');
        $coherenceOk = true;

        foreach ($categories as $cat) {
            if (!$cat['exists'] || empty($cat['files'])) {
                continue;
            }

            $dimensions = array_values(array_unique(array_map(
                fn (array $f): string => sprintf('%dx%d', $f['width'], $f['height']),
                $cat['files']
            )));

            if (\count($dimensions) === 1) {
                $io->text(sprintf('  <info>OK</info>  %s — toutes en %s', $cat['label'], $dimensions[0]));
            } else {
                $io->text(sprintf('  <error>%d tailles</error>  %s — %s', \count($dimensions), $cat['label'], implode(', ', $dimensions)));
                $coherenceOk = false;
                $alerts[] = sprintf('Incoherence de taille dans %s : %s', $cat['label'], implode(', ', $dimensions));
            }
        }

        if ($coherenceOk) {
            $io->success('Toutes les categories ont des tailles coherentes.');
        }

        // --- Avatar 8x8 gap analysis ---
        $io->section('Analyse systeme avatar 8x8');
        $avatarDirs = ['avatar/body', 'avatar/hair', 'avatar/outfit', 'avatar/head'];
        $avatarReady = 0;

        foreach ($avatarDirs as $dir) {
            $cat = $categories[$dir] ?? null;
            $count = null !== $cat ? \count($cat['files']) : 0;

            if (null !== $cat && $cat['exists'] && $count > 0) {
                $io->text(sprintf('  <info>OK</info>  %s/ — %d asset(s)', $dir, $count));
                ++$avatarReady;
            } else {
                $io->text(sprintf('  <comment>MANQUANT</comment>  %s/ — aucun asset', $dir));
            }
        }

        if ($avatarReady === 0) {
            $io->warning([
                'Aucun asset au format avatar 8x8 disponible.',
                'Les assets doivent etre places dans assets/styles/images/avatar/{body,hair,outfit,head}/.',
            ]);
        }

        // --- Summary ---
        $io->section('Resume');
        $io->text(sprintf('  Total fichiers scannes : %d', $totalFiles));
        $activeCategories = \count(array_filter($categories, fn (array $c): bool => $c['exists'] && !empty($c['files'])));
        $io->text(sprintf('  Categories actives : %d', $activeCategories));
        $io->text(sprintf('  Avatar 8x8 prets : %d/4 repertoires', $avatarReady));

        if (!empty($alerts)) {
            $io->newLine();
            $io->section('Alertes');
            foreach ($alerts as $alert) {
                $io->text(sprintf('  ! %s', $alert));
            }
        }

        // --- Export ---
        $exportPath = $input->getOption('export');
        if (\is_string($exportPath)) {
            $fullPath = str_starts_with($exportPath, '/') ? $exportPath : $this->projectDir . '/' . $exportPath;
            $dir = \dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($fullPath, $this->generateMarkdown($categories, $alerts, $totalFiles, $avatarReady));
            $io->success(sprintf('Inventaire exporte vers %s', $exportPath));
        }

        return Command::SUCCESS;
    }

    private function classifyFormat(int $width, int $height): string
    {
        // Avatar 8×8 (square, divisible by 8)
        if ($width === $height && $width % 8 === 0 && $width >= 128) {
            $fw = (int) ($width / 8);

            return sprintf('avatar 8x8 (%dx%d/frame)', $fw, $fw);
        }

        // Single 3×4 (one character)
        if ($width % 3 === 0 && $height % 4 === 0) {
            $fw = (int) ($width / 3);
            $fh = (int) ($height / 4);
            if ($fw >= 16 && $fw <= 64 && $fh >= 16 && $fh <= 64) {
                return sprintf('single 3x4 (%dx%d/frame)', $fw, $fh);
            }
        }

        // Multi 12×8 (8 characters in a 4×2 grid of 3×4 blocks)
        if ($width % 12 === 0 && $height % 8 === 0) {
            $fw = (int) ($width / 12);
            $fh = (int) ($height / 8);
            if ($fw >= 16 && $fw <= 64 && $fh >= 16 && $fh <= 64) {
                return sprintf('multi 12x8 (%dx%d/frame)', $fw, $fh);
            }
        }

        return 'autre';
    }

    /** @return list<array{name: string, width: int, height: int, format: string, filesize: int}> */
    private function scanRootSheets(string $basePath): array
    {
        $results = [];

        // Character root (multi-char sheets like Midona.png, monk.png)
        $charPath = $basePath . '/character';
        if (is_dir($charPath)) {
            $finder = new Finder();
            $finder->files()->in($charPath)->depth(0)->name('*.png')->sortByName();

            foreach ($finder as $file) {
                $realPath = $file->getRealPath();
                if (false === $realPath) {
                    continue;
                }

                $size = @getimagesize($realPath);
                if (false === $size) {
                    continue;
                }
                $results[] = [
                    'name' => 'character/' . $file->getFilename(),
                    'width' => $size[0],
                    'height' => $size[1],
                    'format' => $this->classifyFormat($size[0], $size[1]),
                    'filesize' => (int) $file->getSize(),
                ];
            }
        }

        // Root demons.png
        $demonsPath = $basePath . '/demons.png';
        if (file_exists($demonsPath)) {
            $size = @getimagesize($demonsPath);
            if (false !== $size) {
                $results[] = [
                    'name' => 'demons.png',
                    'width' => $size[0],
                    'height' => $size[1],
                    'format' => $this->classifyFormat($size[0], $size[1]),
                    'filesize' => (int) filesize($demonsPath),
                ];
            }
        }

        return $results;
    }

    private function formatFilesize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1_048_576) {
            return sprintf('%.1f KB', $bytes / 1024);
        }

        return sprintf('%.1f MB', $bytes / 1_048_576);
    }

    /**
     * @param array<string, array{label: string, files: list<array{name: string, width: int, height: int, format: string, filesize: int}>, exists: bool}> $categories
     * @param list<string> $alerts
     */
    private function generateMarkdown(array $categories, array $alerts, int $totalFiles, int $avatarReady): string
    {
        $lines = [
            '# Inventaire des assets avatar',
            '',
            sprintf('> Genere le %s par `php bin/console app:avatar:inventory --export`', date('Y-m-d')),
            '> Tache roadmap : AVT-01 (Sprint 7)',
            '',
            '---',
            '',
            '## Resume',
            '',
            sprintf('- **Total fichiers scannes** : %d', $totalFiles),
            sprintf('- **Categories actives** : %d', \count(array_filter($categories, fn (array $c): bool => $c['exists'] && !empty($c['files'])))),
            sprintf('- **Avatar 8x8 prets** : %d/4 repertoires', $avatarReady),
            '',
        ];

        foreach ($categories as $dir => $cat) {
            $lines[] = sprintf('## %s', $cat['label']);
            $lines[] = '';

            if (!$cat['exists']) {
                $lines[] = sprintf('> Repertoire `%s/` inexistant.', $dir);
                $lines[] = '';
                continue;
            }

            if (empty($cat['files'])) {
                $lines[] = '> Aucun fichier PNG.';
                $lines[] = '';
                continue;
            }

            $lines[] = sprintf('%d fichier(s)', \count($cat['files']));
            $lines[] = '';
            $lines[] = '| Fichier | Dimensions | Format | Taille |';
            $lines[] = '|---------|-----------|--------|--------|';

            foreach ($cat['files'] as $f) {
                $lines[] = sprintf(
                    '| `%s` | %dx%d | %s | %s |',
                    $f['name'],
                    $f['width'],
                    $f['height'],
                    $f['format'],
                    $this->formatFilesize($f['filesize']),
                );
            }

            $lines[] = '';

            $dims = array_values(array_unique(array_map(
                fn (array $f): string => sprintf('%dx%d', $f['width'], $f['height']),
                $cat['files']
            )));
            if (\count($dims) === 1) {
                $lines[] = sprintf('**Coherence** : OK — toutes en %s', $dims[0]);
            } else {
                $lines[] = sprintf('**Coherence** : ATTENTION — %d tailles differentes (%s)', \count($dims), implode(', ', $dims));
            }
            $lines[] = '';
        }

        if (!empty($alerts)) {
            $lines[] = '## Alertes';
            $lines[] = '';
            foreach ($alerts as $alert) {
                $lines[] = sprintf('- %s', $alert);
            }
            $lines[] = '';
        }

        $lines[] = '## Analyse pour le systeme avatar';
        $lines[] = '';
        $lines[] = 'Le systeme avatar modulaire (voir `docs/roadmap/PLAN_AVATAR_SYSTEM.md`) necessite des assets';
        $lines[] = 'au format **8 colonnes x 8 lignes** avec layers separees (body, hair, outfit, head).';
        $lines[] = '';
        $lines[] = '### Assets existants (format RPG Maker VX)';
        $lines[] = '';
        $lines[] = '- Sprites personnages single 3x4 pour joueurs, PNJ, mobs';
        $lines[] = '- Sprites multi-personnages 12x8 pour certains mobs';
        $lines[] = '- Pas de layers separees : body + outfit + hair sont integres dans chaque sprite';
        $lines[] = '';
        $lines[] = '### Manquant pour le systeme avatar 8x8';
        $lines[] = '';
        $lines[] = '- [ ] Assets body de base (skin tones, genres) dans `avatar/body/`';
        $lines[] = '- [ ] Assets coiffures dans `avatar/hair/`';
        $lines[] = '- [ ] Assets tenues/armures dans `avatar/outfit/`';
        $lines[] = '- [ ] Assets coiffes/casques dans `avatar/head/`';
        $lines[] = '- [ ] Verification alignement pixel-perfect entre layers (AVT-04)';
        $lines[] = '';

        return implode("\n", $lines) . "\n";
    }
}
