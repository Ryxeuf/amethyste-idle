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
     * @return array<array{entity: CharacterInterface, type: string, key: string, speed: int}>
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
     * @return array<array{entity: CharacterInterface, type: string, key: string, speed: int, round: int}>
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
     * Utilise uniquement pour les combats solo (non-coop).
     */
    public function isMobFirst(Fight $fight): bool
    {
        $turnOrder = $this->getTurnOrder($fight);
        if (empty($turnOrder)) {
            return false;
        }

        return $turnOrder[0]['type'] === 'mob';
    }

    /**
     * Retourne le participant dont c'est le tour actuellement dans un combat coop.
     *
     * @return array{entity: CharacterInterface, type: string, key: string, speed: int}|null
     */
    public function getCurrentTurnParticipant(Fight $fight): ?array
    {
        $currentKey = $fight->getCurrentTurnKey();
        if ($currentKey === null) {
            return null;
        }

        $turnOrder = $this->getTurnOrder($fight);
        foreach ($turnOrder as $participant) {
            if ($participant['key'] === $currentKey) {
                return $participant;
            }
        }

        // Si le participant courant est mort, avancer au suivant
        return $turnOrder[0] ?? null;
    }

    /**
     * Initialise le tour pour un combat coop : place le currentTurnKey sur le premier participant.
     * Retourne les messages des mobs qui agissent avant le premier joueur.
     *
     * @return array{messages: array<string>, nextKey: string|null}
     */
    public function initializeCoopTurns(Fight $fight, MobActionHandler $mobActionHandler): array
    {
        $turnOrder = $this->getTurnOrder($fight);
        if (empty($turnOrder)) {
            return ['messages' => [], 'nextKey' => null];
        }

        $messages = [];
        // Auto-resolve mob turns at the start until we reach a player
        foreach ($turnOrder as $participant) {
            if ($participant['type'] === 'player') {
                $fight->setCurrentTurnKey($participant['key']);

                return ['messages' => $messages, 'nextKey' => $participant['key']];
            }
            // Mob acts automatically
            $mobResult = $mobActionHandler->doAction($fight);
            $messages = array_merge($messages, $mobResult['messages']);
            $fight->setStep($fight->getStep() + 1);

            if ($fight->isTerminated()) {
                return ['messages' => $messages, 'nextKey' => null];
            }
        }

        // Shouldn't happen (all mobs, no players)
        $fight->setCurrentTurnKey($turnOrder[0]['key']);

        return ['messages' => $messages, 'nextKey' => $turnOrder[0]['key']];
    }

    /**
     * Avance au prochain tour apres qu'un participant a agi.
     * Auto-resolve les tours des mobs. Retourne le prochain joueur qui doit agir.
     *
     * @return array{messages: array<string>, dangerAlert: string|null, nextKey: string|null}
     */
    public function advanceCoopTurn(Fight $fight, MobActionHandler $mobActionHandler): array
    {
        $turnOrder = $this->getTurnOrder($fight);
        $currentKey = $fight->getCurrentTurnKey();
        $messages = [];
        $dangerAlert = null;

        if (empty($turnOrder) || $fight->isTerminated()) {
            return ['messages' => $messages, 'dangerAlert' => null, 'nextKey' => null];
        }

        // Find current position in turn order
        $currentIndex = $this->findIndexByKey($turnOrder, $currentKey);

        // Walk through the turn order from current position
        $total = count($turnOrder);
        $index = ($currentIndex + 1) % $total;
        $visited = 0;

        while ($visited < $total) {
            $participant = $turnOrder[$index] ?? null;

            // Recalculate turn order if participants died
            if ($participant === null) {
                $turnOrder = $this->getTurnOrder($fight);
                $total = count($turnOrder);
                if ($total === 0) {
                    return ['messages' => $messages, 'dangerAlert' => $dangerAlert, 'nextKey' => null];
                }
                $index = 0;
                $participant = $turnOrder[0];
            }

            if ($participant['type'] === 'player') {
                $fight->setCurrentTurnKey($participant['key']);

                return ['messages' => $messages, 'dangerAlert' => $dangerAlert, 'nextKey' => $participant['key']];
            }

            // Mob turn: auto-resolve
            $mobResult = $mobActionHandler->doAction($fight);
            $messages = array_merge($messages, $mobResult['messages']);
            if ($mobResult['dangerAlert'] !== null) {
                $dangerAlert = $mobResult['dangerAlert'];
            }
            $fight->setStep($fight->getStep() + 1);

            if ($fight->isTerminated()) {
                return ['messages' => $messages, 'dangerAlert' => $dangerAlert, 'nextKey' => null];
            }

            // Recalculate after mob action (mobs may have died)
            $turnOrder = $this->getTurnOrder($fight);
            $total = count($turnOrder);
            $index = ($index + 1) % $total;
            ++$visited;
        }

        // Full cycle without finding a player - shouldn't happen
        return ['messages' => $messages, 'dangerAlert' => $dangerAlert, 'nextKey' => null];
    }

    /**
     * Vérifie si c'est le tour du joueur donné dans un combat coop.
     */
    public function isPlayerTurn(Fight $fight, int $playerId): bool
    {
        return $fight->getCurrentTurnKey() === 'player_' . $playerId;
    }

    private function findIndexByKey(array $turnOrder, ?string $key): int
    {
        foreach ($turnOrder as $i => $participant) {
            if ($participant['key'] === $key) {
                return $i;
            }
        }

        return 0;
    }
}
