<?php

namespace App\GameEngine\Realtime\Map;

use App\GameEngine\Zone\MapFreeze;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Contracts\Service\Attribute\Required;

abstract class MovedHandler
{
    private ?MapFreeze $mapFreeze = null;

    public function __construct(private readonly HubInterface $hub, private readonly LoggerInterface $logger)
    {
    }

    /**
     * Injection par setter pour ne pas casser les constructeurs des enfants.
     * Pivot PBBG (ZON-01) : publications map/move suspendues quand la carte est gelee.
     */
    #[Required]
    public function setMapFreeze(MapFreeze $mapFreeze): void
    {
        $this->mapFreeze = $mapFreeze;
    }

    protected function isMapFrozen(): bool
    {
        return $this->mapFreeze?->isGloballyFrozen() ?? false;
    }

    public function move(string $type, int $objectId, string $coordinates, array $data = []): void
    {
        if ($this->isMapFrozen()) {
            return;
        }

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
        if ($this->isMapFrozen()) {
            return;
        }

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
                'path' => array_map(fn (array $cell) => ['x' => $cell['x'], 'y' => $cell['y']], $path),
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
