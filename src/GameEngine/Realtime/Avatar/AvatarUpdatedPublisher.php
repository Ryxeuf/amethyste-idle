<?php

declare(strict_types=1);

namespace App\GameEngine\Realtime\Avatar;

use App\Entity\App\Player;
use App\Service\Avatar\PlayerAvatarPayloadBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Publishes an update on the `map/avatar` topic when a player's avatar hash
 * changes. Subscribers (other players on the map) invalidate their texture
 * cache for that player and recompose the avatar from the new payload.
 */
final class AvatarUpdatedPublisher
{
    public const TOPIC = 'map/avatar';
    public const EVENT_TYPE = 'avatar_updated';

    public function __construct(
        private readonly HubInterface $hub,
        private readonly PlayerAvatarPayloadBuilder $payloadBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function publish(Player $player): void
    {
        $payload = $this->payloadBuilder->buildForMapEntity($player);
        if ($payload === null) {
            return;
        }

        $mapId = $player->getMap()?->getId();

        $update = new Update(
            self::TOPIC,
            json_encode([
                'topic' => self::TOPIC,
                'type' => self::EVENT_TYPE,
                'playerId' => $player->getId(),
                'mapId' => $mapId,
                'avatarHash' => $payload['avatarHash'],
                'renderMode' => $payload['renderMode'],
                'avatar' => $payload['avatar'],
                'avatarUpdatedAt' => $player->getAvatarUpdatedAt()?->format('c'),
            ], JSON_THROW_ON_ERROR),
        );

        $this->hub->publish($update);

        $this->logger->info('Mercure published map/avatar for player {playerId} (hash {hash})', [
            'playerId' => $player->getId(),
            'hash' => $payload['avatarHash'],
        ]);
    }
}
