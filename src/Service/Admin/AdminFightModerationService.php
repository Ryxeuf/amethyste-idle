<?php

namespace App\Service\Admin;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\GameEngine\Fight\CombatLogArchiver;
use App\GameEngine\Fight\StatusEffectManager;
use App\GameEngine\Realtime\Map\MovedPlayerHandler;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Operations de moderation sur les combats et l'etat « session » des joueurs.
 */
class AdminFightModerationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MovedPlayerHandler $movedPlayerHandler,
        private readonly CombatLogArchiver $combatLogArchiver,
        private readonly StatusEffectManager $statusEffectManager,
    ) {
    }

    public function setPlayerMoving(Player $player, bool $isMoving): void
    {
        $player->setIsMoving($isMoving);
        $this->em->flush();
        $this->movedPlayerHandler->movePlayer($player);
    }

    /**
     * Retire un joueur de son combat (world boss : les autres continuent ;
     * combat classique sans autre joueur : libere les mobs comme une fuite).
     */
    public function detachPlayerFromFight(Player $player): void
    {
        $fight = $player->getFight();
        if ($fight === null) {
            return;
        }

        $player->setFight(null);
        $fight->removePlayer($player);
        $player->setIsMoving(false);

        if ($fight->isWorldBossFight()) {
            $this->em->flush();
            $this->movedPlayerHandler->movePlayer($player);

            return;
        }

        if ($fight->getPlayers()->isEmpty()) {
            $fight->setInProgress(false);
            foreach ($fight->getMobs() as $mob) {
                $mob->setFight(null);
            }
        }

        $this->em->flush();
        $this->movedPlayerHandler->movePlayer($player);
    }

    public function applyFightParameters(Fight $fight, int $step, bool $inProgress): void
    {
        $fight->setStep(max(0, $step));
        $fight->setInProgress($inProgress);
        $this->em->flush();
    }

    /**
     * Termine le combat : archive, effets, detache tout le monde, mobs reassocies a la carte (fight_id null).
     * Conserve la ligne fight en base (in_progress = false).
     */
    public function forceReleaseFightKeepingMobs(Fight $fight): void
    {
        $this->archiveAndClearEffects($fight);

        $players = $fight->getPlayers()->toArray();
        foreach ($players as $player) {
            $player->setFight(null);
            $fight->removePlayer($player);
            $player->setIsMoving(false);
        }

        foreach ($fight->getMobs() as $mob) {
            $mob->setFight(null);
        }

        $fight->setInProgress(false);
        $this->em->flush();

        foreach ($players as $player) {
            $this->movedPlayerHandler->movePlayer($player);
        }
    }

    /**
     * Supprime le combat et les mobs (comme une fin de combat cote serveur). Destructif.
     *
     * @return Player[] joueurs qui etaient dans le combat (pour Mercure)
     */
    public function forceDeleteFightAndMobs(Fight $fight): array
    {
        $this->archiveAndClearEffects($fight);

        $players = $fight->getPlayers()->toArray();
        foreach ($players as $player) {
            $player->setFight(null);
            $fight->removePlayer($player);
            $player->setIsMoving(false);
            if ($player->isDead()) {
                $player->setLife(max(1, (int) round($player->getMaxLife() / 2)));
                $player->setDiedAt(null);
            }
        }

        foreach ($fight->getMobs() as $mob) {
            foreach ($mob->getItems() as $item) {
                $this->em->remove($item);
            }
            $this->em->remove($mob);
        }

        $this->em->remove($fight);
        $this->em->flush();

        foreach ($players as $player) {
            $this->movedPlayerHandler->movePlayer($player);
        }

        return $players;
    }

    private function archiveAndClearEffects(Fight $fight): void
    {
        try {
            $this->combatLogArchiver->archive($fight);
        } catch (\Throwable) {
        }
        $this->statusEffectManager->clearAllEffects($fight);
    }
}
