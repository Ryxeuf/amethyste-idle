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
            json_encode([
                'topic' => 'map/move',
                'type' => $type,
                'object' => $objectId,
                'x' => $x,
                'y' => $y,
                'coordinates' => $coordinates,
                'data' => $data,
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);

        $this->logger->info('Mercure published moved {type} {objectId} to {coordinates}', [
            'type' => $type,
            'objectId' => $objectId,
            'coordinates' => $coordinates,
        ]);
    }

    /**
     * Publishes a complete path so the client can animate movement cell by cell.
     */
    public function movePath(string $type, int $objectId, string $finalCoordinates, array $path, array $data = []): void
    {
        [$x, $y] = explode('.', $finalCoordinates);
        $update = new Update(
            'map/move',
            json_encode([
                'topic' => 'map/move',
                'type' => $type,
                'object' => $objectId,
                'x' => $x,
                'y' => $y,
                'coordinates' => $finalCoordinates,
                'path' => array_map(fn(array $cell) => ['x' => $cell['x'], 'y' => $cell['y']], $path),
                'data' => $data,
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);

        $this->logger->info('Mercure published path for {type} {objectId}: {count} cells to {coordinates}', [
            'type' => $type,
            'objectId' => $objectId,
            'count' => count($path),
            'coordinates' => $finalCoordinates,
        ]);
    }
}
