<?php

namespace App\Command;

use App\Entity\App\Map;
use App\Entity\App\World;
use App\GameEngine\Terrain\Generator\BiomeRegistry;
use App\GameEngine\Terrain\Generator\MapGenerator;
use App\GameEngine\Terrain\Generator\ObjectPlacer;
use App\GameEngine\Terrain\MapFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:terrain:generate',
    description: 'Génère une carte procédurale via Perlin Noise et l\'écrit en base de données',
)]
class TerrainGenerateCommand extends Command
{
    public function __construct(
        private readonly MapGenerator $mapGenerator,
        private readonly MapFactory $mapFactory,
        private readonly ObjectPlacer $objectPlacer,
        private readonly BiomeRegistry $biomeRegistry,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('biome', null, InputOption::VALUE_REQUIRED, 'Biome à générer (plains, forest, swamp)', 'plains')
            ->addOption('difficulty', null, InputOption::VALUE_REQUIRED, 'Difficulté des mobs placés (1-10)', '1')
            ->addOption('seed', null, InputOption::VALUE_REQUIRED, 'Seed pour la génération déterministe (int)', null)
            ->addOption('width', null, InputOption::VALUE_REQUIRED, 'Largeur en tiles', '60')
            ->addOption('height', null, InputOption::VALUE_REQUIRED, 'Hauteur en tiles', '40')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Nom de la carte (auto-généré si absent)', null)
            ->addOption('map-id', null, InputOption::VALUE_REQUIRED, 'ID d\'une carte existante à régénérer (écrase le terrain)', null)
            ->addOption('world-id', null, InputOption::VALUE_REQUIRED, 'ID du monde parent (défaut : premier monde)', null)
            ->addOption('place-entities', null, InputOption::VALUE_NONE, 'Placer mobs et spots de récolte après génération')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simuler sans écrire en base de données');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $biomeSlug = $input->getOption('biome');
        $difficulty = (int) $input->getOption('difficulty');
        $seed = $input->getOption('seed') !== null ? (int) $input->getOption('seed') : null;
        $width = (int) $input->getOption('width');
        $height = (int) $input->getOption('height');
        $mapId = $input->getOption('map-id') !== null ? (int) $input->getOption('map-id') : null;
        $worldId = $input->getOption('world-id') !== null ? (int) $input->getOption('world-id') : null;
        $placeEntities = (bool) $input->getOption('place-entities');
        $dryRun = (bool) $input->getOption('dry-run');

        // Validation biome
        $biome = $this->biomeRegistry->get($biomeSlug);
        if ($biome === null) {
            $available = implode(', ', array_keys($this->biomeRegistry->getChoices()));
            $io->error(sprintf('Biome "%s" inconnu. Biomes disponibles : %s', $biomeSlug, $available));

            return Command::FAILURE;
        }

        // Validation difficulty
        if ($difficulty < 1 || $difficulty > 10) {
            $io->error('La difficulté doit être entre 1 et 10.');

            return Command::FAILURE;
        }

        // Validation dimensions
        if ($width < 10 || $width > 200 || $height < 10 || $height > 200) {
            $io->error('Les dimensions doivent être entre 10 et 200 tiles.');

            return Command::FAILURE;
        }

        $effectiveSeed = $seed ?? random_int(0, 2147483647);

        $io->title('Génération procédurale de carte');
        $io->table(
            ['Paramètre', 'Valeur'],
            [
                ['Biome', $biome->getLabel() . ' (' . $biomeSlug . ')'],
                ['Difficulté', (string) $difficulty],
                ['Seed', (string) $effectiveSeed],
                ['Dimensions', $width . ' × ' . $height . ' tiles'],
                ['Placement entités', $placeEntities ? 'oui' : 'non'],
                ['Dry-run', $dryRun ? 'oui' : 'non'],
            ]
        );

        if ($dryRun) {
            $io->note('Mode dry-run : aucune écriture en base de données.');

            return Command::SUCCESS;
        }

        // Récupérer ou créer la carte
        if ($mapId !== null) {
            $map = $this->em->getRepository(Map::class)->find($mapId);
            if ($map === null) {
                $io->error(sprintf('Carte ID %d introuvable.', $mapId));

                return Command::FAILURE;
            }
            $io->info(sprintf('Régénération de la carte existante : %s (ID %d)', $map->getName(), $mapId));
        } else {
            $mapName = $input->getOption('name') ?? sprintf('generated-%s-%d', $biomeSlug, $effectiveSeed);

            $world = $this->resolveWorld($worldId);
            if ($world === null) {
                $io->error('Aucun monde trouvé en base de données. Créez d\'abord un monde (via les fixtures).');

                return Command::FAILURE;
            }

            $io->info(sprintf('Création d\'une nouvelle carte "%s" dans le monde "%s"', $mapName, $world->getName()));
            $map = $this->mapFactory->createBlankMap($mapName, $width, $height, $world);
        }

        // Génération du terrain
        $io->info('Génération du terrain (Perlin Noise + auto-tiling)...');
        $this->mapGenerator->generate($map, $biome, $difficulty, $effectiveSeed);
        $io->success('Terrain généré.');

        // Placement des entités
        if ($placeEntities) {
            $io->info('Placement des mobs...');
            $mobCount = $this->objectPlacer->placeMobSpawns($map, $biome, $difficulty, $effectiveSeed);
            $io->info(sprintf('  → %d spawn(s) de mobs placés.', $mobCount));

            $io->info('Placement des spots de récolte...');
            $spotCount = $this->objectPlacer->placeHarvestSpots($map, $biome, $effectiveSeed);
            $io->info(sprintf('  → %d spot(s) de récolte placés.', $spotCount));
        }

        $io->success(sprintf(
            'Carte "%s" (ID %d) générée avec succès. Seed : %d',
            $map->getName(),
            $map->getId(),
            $effectiveSeed
        ));
        $io->note('Pour visualiser : /game/map?mapId=' . $map->getId());

        return Command::SUCCESS;
    }

    private function resolveWorld(?int $worldId): ?World
    {
        if ($worldId !== null) {
            return $this->em->getRepository(World::class)->find($worldId);
        }

        return $this->em->getRepository(World::class)->findOneBy([], ['id' => 'ASC']);
    }
}
