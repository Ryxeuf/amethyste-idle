<?php

namespace App\GameEngine\Realtime\Map;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

abstract class RespawnedHandler
{
    public function __construct(private readonly HubInterface $hub)
    {
    }

    public function respawn(string $type, array $object, string $coordinates, array $data = []): void
    {
        $update = new Update(
            'map/respawn',
            json_encode(['topic' => 'map/respawn', 'type' => $type, 'object' => $object, 'coordinates' => $coordinates, 'data' => $data], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }
}
