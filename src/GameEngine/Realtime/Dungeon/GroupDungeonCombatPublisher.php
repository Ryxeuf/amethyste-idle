<?php

namespace App\GameEngine\Realtime\Dungeon;

use App\Entity\App\GroupDungeonRun;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Publie l'etat de combat d'un donjon de groupe via Mercure (ZON-19,
 * sous-jalon 3).
 *
 * Les membres connectes simultanement ecoutent le topic `dungeon/run/<id>`
 * pour rafraichir la banniere (PV de rencontre, tour actif, bouton Attaquer)
 * sans recharger la page. Le modele reste semi-synchrone : Mercure n'est qu'un
 * confort quand le groupe est en ligne, l'etat autoritatif est resolu
 * paresseusement cote serveur.
 */
class GroupDungeonCombatPublisher
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $snapshot etat de combat renvoye par le service
     */
    public function publishState(GroupDungeonRun $run, array $snapshot): void
    {
        $topic = 'dungeon/run/' . $run->getId();

        try {
            $update = new Update(
                $topic,
                json_encode(array_merge(
                    ['topic' => $topic, 'type' => 'group_dungeon_state', 'runId' => $run->getId()],
                    $snapshot,
                ), JSON_THROW_ON_ERROR)
            );
            $this->hub->publish($update);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to publish group dungeon combat state via Mercure: {error}', ['error' => $e->getMessage()]);
        }
    }
}
