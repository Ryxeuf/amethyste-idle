<?php

namespace App\Service\Realtime;

use App\Entity\App\Player;
use App\GameEngine\Guild\GuildManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Construit la configuration temps reel pour /api/v1/realtime/config
 * (migration API-first, phase 0.4) : URL publique du hub Mercure, topics
 * pertinents pour le joueur, et JWT subscriber pour les clients natifs.
 *
 * Le hub autorise aujourd'hui les abonnes anonymes (directive `anonymous`
 * du Caddyfile) : le token est fourni des maintenant pour que les clients
 * l'envoient (query `authorization` ou header) et survivent au durcissement
 * futur du hub sans mise a jour.
 */
class RealtimeConfigBuilder
{
    public function __construct(
        private readonly MercureSubscriberTokenFactory $tokenFactory,
        private readonly GuildManager $guildManager,
        #[Autowire('%env(MERCURE_PUBLIC_URL)%')]
        private readonly string $hubPublicUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Player $player): array
    {
        $topics = [
            'map' => ['map/move', 'map/respawn', 'map/spot', 'map/weather'],
            'chat' => $this->buildChatTopics($player),
            'notifications' => [sprintf('player/%d/notifications', $player->getId())],
            'events' => ['event/announce', 'guild/city_control'],
            'fight' => $player->getFight() !== null
                ? [sprintf('fight/%d/turn', $player->getFight()->getId())]
                : [],
        ];

        $flatTopics = array_merge(...array_values($topics));

        return [
            'hubUrl' => $this->hubPublicUrl,
            'topics' => $topics,
            'subscriberToken' => $this->tokenFactory->create($flatTopics),
            'expiresIn' => MercureSubscriberTokenFactory::DEFAULT_TTL,
        ];
    }

    /**
     * @return list<string>
     */
    private function buildChatTopics(Player $player): array
    {
        $topics = ['chat/global', sprintf('chat/private/%d', $player->getId())];

        if ($player->getMap() !== null) {
            $topics[] = sprintf('chat/map/%d', $player->getMap()->getId());
        }

        $guild = $this->guildManager->getPlayerGuild($player);
        if ($guild !== null) {
            $topics[] = sprintf('chat/guild/%d', $guild->getId());
        }

        return $topics;
    }
}
