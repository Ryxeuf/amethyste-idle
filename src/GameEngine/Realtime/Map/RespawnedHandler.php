<?php

namespace App\GameEngine\Realtime\Map;

use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;

abstract class RespawnedHandler
{
    public function __construct(private readonly PublisherInterface $publisher)
    {
    }

    public function respawn(string $type, array $object, int $cellId, array $data = []): void
    {
        $update = new Update(
            'map/respawn',
            json_encode(['topic' => 'map/respawn', 'type' => $type, 'object' => $object, 'cell' => $cellId, 'data' => $data], JSON_THROW_ON_ERROR)
        );

        // The Publisher service is an invokable object
        $this->publisher->__invoke($update);
    }
}