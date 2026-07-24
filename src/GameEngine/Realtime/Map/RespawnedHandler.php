<?php

namespace App\GameEngine\Realtime\Map;

use App\GameEngine\Zone\MapFreeze;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Contracts\Service\Attribute\Required;

abstract class RespawnedHandler
{
    private ?MapFreeze $mapFreeze = null;

    public function __construct(private readonly HubInterface $hub)
    {
    }

    /**
     * Injection par setter pour ne pas casser les constructeurs des enfants.
     * Pivot PBBG (ZON-01) : publications map/respawn suspendues quand la carte est gelee.
     */
    #[Required]
    public function setMapFreeze(MapFreeze $mapFreeze): void
    {
        $this->mapFreeze = $mapFreeze;
    }

    public function respawn(string $type, array $object, string $coordinates, array $data = []): void
    {
        if ($this->mapFreeze?->isGloballyFrozen() ?? false) {
            return;
        }

        $update = new Update(
            'map/respawn',
            json_encode(['topic' => 'map/respawn', 'type' => $type, 'object' => $object, 'coordinates' => $coordinates, 'data' => $data], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }
}
