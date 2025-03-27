<?php

namespace App\GameEngine\Realtime\Map;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Psr\Log\LoggerInterface;

abstract class MovedHandler
{
    public function __construct(private readonly HubInterface $hub, private readonly LoggerInterface $logger)
    {
    }

    public function move(string $type, int $objectId, string $coordinates, array $data = []): void
    {
        [$x, $y] = explode('.', $coordinates);
        $update = new Update(
            'map/move',
            json_encode(['topic' => 'map/move', 'type' => $type, 'object' => $objectId, 'x' => $x, 'y' => $y, 'coordinates' => $coordinates, 'data' => $data], JSON_THROW_ON_ERROR)
        );

        // The Publisher service is an invokable object
        $this->hub->publish($update);

        $this->logger->info('Mercure published moved {type} {objectId} to {coordinates}', ['type' => $type, 'objectId' => $objectId, 'coordinates' => $coordinates]);
    }
}