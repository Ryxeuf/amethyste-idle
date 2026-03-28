<?php

namespace App\GameEngine\World;

use App\Entity\App\Map;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Publie la météo d'une carte sur le topic Mercure (clients en jeu).
 */
final class MapWeatherMercurePublisher
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function publish(Map $map): void
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
