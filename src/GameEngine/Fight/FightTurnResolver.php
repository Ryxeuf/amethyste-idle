<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\CharacterInterface;

/**
 * Calcule l'ordre des tours de combat base sur la vitesse des participants.
 * Plus la vitesse est haute, plus le participant agit tot dans le round.
 */
class FightTurnResolver
{
    /**
     * Retourne les participants tries par vitesse decroissante (le plus rapide agit en premier).
     * Exclut les morts.
     *
     * @return array<array{entity: CharacterInterface, type: string, key: string}>
     */
    public function getTurnOrder(Fight $fight): array
    {
        $participants = [];

        foreach ($fight->getPlayers() as $player) {
            if (!$player->isDead()) {
                $participants[] = [
                    'entity' => $player,
                    'type' => 'player',
                    'key' => 'player_' . $player->getId(),
                    'speed' => $player->getSpeed(),
                ];
            }
        }

        foreach ($fight->getMobs() as $mob) {
            if (!$mob->isDead()) {
                $participants[] = [
                    'entity' => $mob,
                    'type' => 'mob',
                    'key' => 'mob_' . $mob->getId(),
                    'speed' => $mob->getSpeed(),
                ];
            }
        }

        // Tri par vitesse decroissante ; a egalite, le joueur a la priorite
        usort($participants, function (array $a, array $b) {
            if ($b['speed'] === $a['speed']) {
                return $a['type'] === 'player' ? -1 : 1;
            }

            return $b['speed'] <=> $a['speed'];
        });

        return $participants;
    }

    /**
     * Genere la timeline pour les N prochains rounds.
     * Chaque element contient le participant, son type, et le numero de round.
     *
     * @return array<array{entity: CharacterInterface, type: string, key: string, round: int}>
     */
    public function getTimeline(Fight $fight, int $rounds = 3): array
    {
        $turnOrder = $this->getTurnOrder($fight);
        $currentRound = (int) floor($fight->getStep() / max(1, count($turnOrder))) + 1;

        $timeline = [];
        for ($r = 0; $r < $rounds; ++$r) {
            foreach ($turnOrder as $participant) {
                $timeline[] = array_merge($participant, [
                    'round' => $currentRound + $r,
                ]);
            }
        }

        return $timeline;
    }

    /**
     * Determine si le mob agit avant le joueur dans le round courant.
     */
    public function isMobFirst(Fight $fight): bool
    {
        $turnOrder = $this->getTurnOrder($fight);
        if (empty($turnOrder)) {
            return false;
        }

        return $turnOrder[0]['type'] === 'mob';
    }
}
