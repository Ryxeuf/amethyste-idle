<?php

namespace App\Command;

use App\Entity\App\Map;
use App\GameEngine\World\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsCommand(
    name: 'app:weather:tick',
    description: 'Met à jour la météo de chaque carte',
)]
class WeatherTickCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WeatherService $weatherService,
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
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
                $this->publishWeatherChange($map);
            }
        }

        $this->entityManager->flush();

        if ($changed > 0) {
            $io->success(sprintf('%d carte(s) ont changé de météo.', $changed));
        }

        return Command::SUCCESS;
    }

    private function publishWeatherChange(Map $map): void
    {
        $weather = $map->getCurrentWeather();

        $update = new Update(
            'map/weather',
            json_encode([
                'topic' => 'map/weather',
                'mapId' => $map->getId(),
                'weather' => $weather->value,
                'label' => $weather->label(),
                'icon' => $weather->icon(),
                'changedAt' => $map->getWeatherChangedAt()?->format('c'),
            ], JSON_THROW_ON_ERROR),
        );

        $this->hub->publish($update);

        $this->logger->info('Météo changée sur {map} : {weather}', [
            'map' => $map->getName(),
            'weather' => $weather->value,
        ]);
    }
}
