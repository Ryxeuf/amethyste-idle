<?php

namespace App\Command;

use App\DataStorage\MapStorage;
use App\Entity\App\Map;
use App\Transformer\MapModelTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:map:dump',
    description: 'Dump json of a map',
)]
class ApiMapDumpCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MapModelTransformer $mapModelTransformer,
        private readonly MapStorage $mapStorage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::OPTIONAL, 'Map id to generate')
            ->addOption('model', 'm', InputOption::VALUE_NONE, 'Dump map infos instead of tag map for mouvement');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mapId = $input->getArgument('id');

        if ($mapId) {
            $io->note(sprintf('Dump map #%s', $mapId));
            $maps = [$this->entityManager->getRepository(Map::class)->find($mapId)];
        } else {
            $io->note('Dump all maps');
            $maps = $this->entityManager->getRepository(Map::class)->findAll();
        }

        foreach ($maps as $map) {
            if ($input->getOption('model')) {
                $jsonContent = json_encode($this->mapModelTransformer->transformStaticMapModel($map));
                $filePath = $this->mapStorage->storeMapInfos($map, $jsonContent);
                $io->info('Written ' . $filePath . ' static map file');
            } else {
                $jsonContent = json_encode($this->mapModelTransformer->generateDijkstraTagMap($map));
                $filePath = $this->mapStorage->storeMapTag($map, $jsonContent);
                $io->info('Written ' . $filePath . ' tag map file');
            }
        }

        return Command::SUCCESS;
    }
}
