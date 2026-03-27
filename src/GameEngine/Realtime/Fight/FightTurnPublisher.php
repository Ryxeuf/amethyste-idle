<?php

namespace App\GameEngine\Realtime\Fight;

use App\Entity\App\Fight;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Publie les changements de tour via Mercure pour les combats coopératifs.
 * Les joueurs écoutent le topic fight/<fightId>/turn pour savoir quand c'est leur tour.
 */
class FightTurnPublisher
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function publishTurnChange(Fight $fight): void
    {
        $currentTurnKey = $fight->getCurrentTurnKey();
        if ($currentTurnKey === null) {
            return;
        }

        $topic = 'fight/' . $fight->getId() . '/turn';

        $update = new Update(
            $topic,
            json_encode([
                'topic' => $topic,
                'type' => 'turn_change',
                'fightId' => $fight->getId(),
                'currentTurnKey' => $currentTurnKey,
                'step' => $fight->getStep(),
                'terminated' => $fight->isTerminated(),
                'victory' => $fight->isVictory(),
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);

        $this->logger->info('Mercure published turn change for fight {fightId}: {turnKey}', [
            'fightId' => $fight->getId(),
            'turnKey' => $currentTurnKey,
        ]);
    }

    public function publishFightEnd(Fight $fight): void
    {
        $topic = 'fight/' . $fight->getId() . '/turn';

        $update = new Update(
            $topic,
            json_encode([
                'topic' => $topic,
                'type' => 'fight_end',
                'fightId' => $fight->getId(),
                'terminated' => true,
                'victory' => $fight->isVictory(),
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }
}
