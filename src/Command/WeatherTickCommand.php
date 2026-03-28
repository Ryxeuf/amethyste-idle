<?php

namespace App\Command;

use App\Entity\App\Map;
use App\GameEngine\World\MapWeatherMercurePublisher;
use App\GameEngine\World\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:weather:tick',
    description: 'Met à jour la météo de chaque carte',
)]
class WeatherTickCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WeatherService $weatherService,
        private readonly MapWeatherMercurePublisher $mapWeatherMercurePublisher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $maps = $this->entityManager->getRepository(Map::class)->findAll();
        $changed = 0;

        foreach ($maps as $map) {
            if ($this->weatherService->changeWeather($map)) {
                ++$changed;
                $this->mapWeatherMercurePublisher->publish($map);
            }
        }

        $this->entityManager->flush();

        if ($changed > 0) {
            $io->success(sprintf('%d carte(s) ont changé de météo.', $changed));
        }

        return Command::SUCCESS;
    }
}
