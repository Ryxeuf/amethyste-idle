<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:tmx:generate-css',
    description: 'Génère des classes CSS pour chaque cellule des cartes TMX',
)]
class TmxCssGeneratorCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output-dir', null, InputOption::VALUE_OPTIONAL, 'Répertoire de sortie des fichiers CSS', 'assets/styles/map')
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'Filtrer les fichiers TMX par nom (ex: world-1-map*)', null)
            ->addOption('by-layer', 'l', InputOption::VALUE_NONE, 'Créer des fichiers séparés pour chaque couche')
            ->addOption('with-sprites', 's', InputOption::VALUE_NONE, 'Inclure les informations de position de sprite dans les classes CSS')
            ->addOption('single-file', null, InputOption::VALUE_OPTIONAL, 'Générer un seul fichier CSS au lieu d\'un fichier par tileset', null);
    }

    /**
     * Génère les classes CSS à partir des fichiers TMX et les sauvegarde dans des fichiers CSS par tileset
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $terrainPath = $this->projectDir . '/terrain';
        $outputDir = $input->getOption('output-dir');
        $outputPath = $this->projectDir . '/' . $outputDir;
        $filter = $input->getOption('filter');
        $byLayer = $input->getOption('by-layer');
        $withSprites = $input->getOption('with-sprites');
        
        $io->title('Génération des classes CSS à partir des fichiers TMX');
        
        // Vérifier si le répertoire de sortie existe
        $filesystem = new Filesystem();
        if (!$filesystem->exists($outputPath)) {
            $filesystem->mkdir($outputPath);
            $io->success("Création du répertoire $outputPath");
        } else {
            $this->cleanCssDirectory($outputPath, $io);
        }
        
        // Trouver tous les fichiers TMX
        $finder = new Finder();
        $finder->files()->in($terrainPath)->name('*.tmx');
        
        // Appliquer le filtre si spécifié
        if ($filter) {
            $finder->name($filter);
            $io->info("Application du filtre: $filter");
        }
        
        if (!$finder->hasResults()) {
            $io->error('Aucun fichier TMX trouvé dans le répertoire terrain');
            return Command::FAILURE;
        }
        
        $io->info(sprintf('Traitement de %d fichiers TMX...', $finder->count()));
        
        // Stocker le contenu CSS par tileset
        $tilesetCssContents = [];
        
        foreach ($finder as $file) {
            $fileName = $file->getFilename();
            $mapName = pathinfo($fileName, PATHINFO_FILENAME);
            $io->section("Traitement de $fileName");
            
            // Parser le fichier TMX
            $fileCrawler = new Crawler($file->getContents());
            
            // Récupérer les dimensions de la carte
            $mapWidth = (int)$fileCrawler->attr('width');
            $mapHeight = (int)$fileCrawler->attr('height');
            $tileWidth = (int)$fileCrawler->attr('tilewidth');
            $tileHeight = (int)$fileCrawler->attr('tileheight');
            
            $io->text("Dimensions de la carte: $mapWidth x $mapHeight (tuiles: {$tileWidth}x{$tileHeight}px)");
            
            // Récupérer les tilesets
            $tilesets = [];
            $fileCrawler->filter('tileset')->each(function($tileset) use (&$tilesets, $terrainPath, $io) {
                $firstGid = (int)$tileset->attr('firstgid');
                $source = $tileset->attr('source');
                if ($source) {
                    $name = pathinfo($source, PATHINFO_FILENAME);
                    $tsxPath = $terrainPath . '/' . $source;
                    
                    // Charger le fichier TSX pour obtenir les informations sur l'image
                    if (file_exists($tsxPath)) {
                        $tsxContent = file_get_contents($tsxPath);
                        $tsxCrawler = new Crawler($tsxContent);
                        
                        $imageSrc = '';
                        $imageWidth = 0;
                        $imageHeight = 0;
                        $columns = 0;
                        
                        // Récupérer les attributs du tileset
                        if ($tsxCrawler->filter('tileset')->count() > 0) {
                            $columns = (int)$tsxCrawler->filter('tileset')->attr('columns');
                        }
                        
                        // Récupérer les informations de l'image
                        if ($tsxCrawler->filter('image')->count() > 0) {
                            $imageSrc = $tsxCrawler->filter('image')->attr('source');
                            $imageWidth = (int)$tsxCrawler->filter('image')->attr('width');
                            $imageHeight = (int)$tsxCrawler->filter('image')->attr('height');
                            
                            // Normaliser le chemin de l'image pour CSS
                            $imageSrc = str_replace('../../assets/styles/images/', '../images/', $imageSrc);
                        }
                        
                        $tilesets[$firstGid] = [
                            'name' => $name,
                            'firstGid' => $firstGid,
                            'source' => $source,
                            'imageSrc' => $imageSrc,
                            'imageWidth' => $imageWidth,
                            'imageHeight' => $imageHeight,
                            'columns' => $columns
                        ];
                    }
                }
            });
            
            if ($withSprites && count($tilesets) > 0) {
                $io->text("Tilesets trouvés: " . implode(', ', array_column($tilesets, 'name')));
            }
            
            // Analyser les couches et les données
            $layers = $fileCrawler->filter('layer');
            
            // Variable pour suivre les classes CSS déjà générées (pour éviter les doublons)
            $generatedClasses = [];
            
            foreach ($layers as $layer) {
                $layerCrawler = new Crawler($layer);
                $layerId = $layerCrawler->attr('id');
                $layerName = $layerCrawler->attr('name');
                
                // Ignorer la couche "collision"
                if ($layerName === 'collision') {
                    $io->text("Couche 'collision' ignorée");
                    continue;
                }
                
                $io->text("Couche trouvée: $layerName (ID: $layerId)");
                
                // Obtenir les données de la couche
                $dataCrawler = $layerCrawler->filter('data');
                if ($dataCrawler->count() === 0) {
                    continue;
                }
                
                $dataEncoding = $dataCrawler->attr('encoding');
                if ($dataEncoding !== 'csv') {
                    $io->warning("Encodage non supporté: $dataEncoding");
                    continue;
                }
                
                $data = trim($dataCrawler->text());
                $cells = array_map('trim', explode(',', $data));
                
                $io->text("Création des classes CSS pour " . count($cells) . " cellules");
                
                $processedValues = [];
                
                // Générer les classes CSS pour chaque cellule
                foreach ($cells as $index => $cellValue) {
                    if ($cellValue !== '0' && !in_array($cellValue, $processedValues)) {
                        $processedValues[] = $cellValue;
                        $x = $index % $mapWidth;
                        $y = floor($index / $mapWidth);
                        
                        // Trouver le tileset pour cette cellule
                        $tilesetInfo = $this->findTilesetForTile($tilesets, (int)$cellValue);
                        if (!$tilesetInfo) {
                            continue;
                        }
                        
                        $tilesetName = $tilesetInfo['name'];
                        $tileId = (int)$cellValue - $tilesetInfo['firstGid'];
                        
                        // Assurer que la valeur de cellule est propre et ne contient pas d'espaces
                        $cleanCellValue = (int)$cellValue;
                        
                        // Clé unique pour cette classe CSS
                        $classKey = "tileset-{$tilesetName}-cell-{$tileId}";
                        
                        // Si cette classe a déjà été générée pour ce tileset, on saute
                        if (isset($generatedClasses[$tilesetName][$classKey])) {
                            continue;
                        }
                        
                        // Marquer cette classe comme générée
                        $generatedClasses[$tilesetName][$classKey] = true;
                        
                        // Initialiser le contenu CSS pour ce tileset s'il n'existe pas
                        if (!isset($tilesetCssContents[$tilesetName])) {
                            $tilesetCssContents[$tilesetName] = '';
                        }
                        
                        // Ajouter la classe CSS pour ce tileset
                        $tilesetCssContents[$tilesetName] .= ".{$classKey} {\n";
                        $tilesetCssContents[$tilesetName] .= "  /* Map: $mapName, Layer: $layerName, Value: $cleanCellValue */\n";
                        
                        $columns = $tilesetInfo['columns'];
                        
                        // Calculer la position du sprite dans la feuille de sprite
                        $spriteX = ($tileId % $columns) * $tileWidth;
                        $spriteY = floor($tileId / $columns) * $tileHeight;
                        
                        $tilesetCssContents[$tilesetName] .= "  /* Tileset: {$tilesetName}, TileID: $tileId */\n";
                        $tilesetCssContents[$tilesetName] .= "  background-image: url('{$tilesetInfo['imageSrc']}');\n";
                        $tilesetCssContents[$tilesetName] .= "  background-position: -{$spriteX}px -{$spriteY}px;\n";
                        $tilesetCssContents[$tilesetName] .= "  width: {$tileWidth}px;\n";
                        $tilesetCssContents[$tilesetName] .= "  height: {$tileHeight}px;\n";
                        $tilesetCssContents[$tilesetName] .= "  display: inline-block;\n";
                        $tilesetCssContents[$tilesetName] .= "}\n\n";
                    }
                }
            }
        }
        
        // Écrire les fichiers CSS par tileset
        if ($input->getOption('single-file') !== null) {
            $singleFileName = $input->getOption('single-file') ?: 'world-1';
            $cssFilePath = "$outputPath/$singleFileName.css";
            $allContent = '';
            
            foreach ($tilesetCssContents as $tilesetName => $content) {
                if (!empty($content)) {
                    $allContent .= "/* Tileset: $tilesetName */\n";
                    $allContent .= $content;
                    $allContent .= "\n";
                }
            }
            
            file_put_contents($cssFilePath, $allContent);
            $io->success("Fichier CSS unique généré: $singleFileName.css");
        } else {
            foreach ($tilesetCssContents as $tilesetName => $content) {
                if (!empty($content)) {
                    $cssFileName = strtolower($tilesetName) . ".css";
                    $cssFilePath = "$outputPath/$cssFileName";
                    
                    file_put_contents($cssFilePath, $content);
                    $io->success("Fichier CSS généré pour le tileset '$tilesetName': $cssFileName");
                }
            }
        }
        
        $io->success('Génération des classes CSS terminée');
        
        return Command::SUCCESS;
    }
    
    /**
     * Trouve le tileset correspondant à un ID de tuile
     */
    private function findTilesetForTile(array $tilesets, int $gid): ?array
    {
        $selectedTileset = null;
        $maxFirstGid = 0;

        foreach ($tilesets as $firstGid => $tileset) {
            if ($firstGid <= $gid && $firstGid > $maxFirstGid) {
                $maxFirstGid = $firstGid;
                $selectedTileset = $tileset;
            }
        }

        return $selectedTileset;
    }
    
    private function cleanCssDirectory(string $outputPath, SymfonyStyle $io): void
    {
        // Nettoyer les anciens fichiers CSS
        $filesystem = new Filesystem();
        $finder = new Finder();
        $finder->files()->in($outputPath)->name('*.css');
        foreach ($finder as $file) {
            $filesystem->remove($file->getRealPath());
        }
        $io->info("Nettoyage des anciens fichiers CSS");
    }
} 