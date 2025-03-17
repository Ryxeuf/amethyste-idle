<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\SearchEngine\CellSearchEngine;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\App\World;
use App\Entity\App\Map;
use App\Entity\App\Area;
use App\SearchEngine\Transformer\CellTransformer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:index:cell')]
class IndexCellCommand extends Command
{
    private string $dataPath;

    public function __construct(
        private readonly CellSearchEngine $cellEngine,
        private readonly EntityManagerInterface $entityManager,
        private readonly CellTransformer $cellTransformer,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
        $this->dataPath = $this->projectDir . '/data/map/';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $terrains = [];

        /** @var World[] $worlds */
        $worlds = $this->entityManager->getRepository(World::class)->findAll();
        foreach ($worlds as $world) {
            /** @var Map[] $maps */
            $maps = $world->getMaps();
            foreach ($maps as $map) {
                /** @var Area[] $areas */
                $areas = $map->getAreas();
                foreach ($areas as $area) {
                    $filePath = $this->dataPath . $area->getSlug() . '.json';
                    $data = json_decode(file_get_contents($filePath), true);
                    $this->cellTransformer->addTerrains($data['terrains']);

                    $cells = [];
                    foreach ($data['cells'] as $subSet) {
                        foreach ($subSet as $cell) {
                            $searchCell = $this->cellTransformer->transform($cell, $area, $map, $world);
                            $cells[] = $searchCell->toArray();
                        }
                    }
                    $this->cellEngine->upsert($cells);
                    $output->writeln(sprintf('Indexed %d cells for area %s', count($cells), $area->getSlug()));
                }
            }
        }

        return Command::SUCCESS;
    }
}
