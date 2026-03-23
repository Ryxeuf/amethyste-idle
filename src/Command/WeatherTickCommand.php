<?php

namespace App\Command;

use App\Entity\App\Map;
use App\GameEngine\World\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsCommand(
    name: 'app:weather:tick',
    description: 'Change la météo sur chaque carte',
)]
class WeatherTickCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WeatherService $weatherService,
        private readonly HubInterface $hub,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $maps = $this->entityManager->getRepository(Map::class)->findAll();
        $changed = 0;

        foreach ($maps as $map) {
            $oldWeather = $this->weatherService->getCurrentWeather($map);
            $newWeather = $this->weatherService->changeWeather($map);

            if ($oldWeather !== $newWeather) {
                $this->hub->publish(new Update(
                    'map/weather',
                    json_encode([
                        'topic' => 'map/weather',
                        'mapId' => $map->getId(),
                        'weather' => $newWeather->value,
                        'label' => $newWeather->label(),
                    ], JSON_THROW_ON_ERROR),
                ));
                ++$changed;
            }
        }

        $this->entityManager->flush();

        if ($changed > 0) {
            $io->success(sprintf('Météo changée sur %d carte(s).', $changed));
        }

        return Command::SUCCESS;
    }
}
