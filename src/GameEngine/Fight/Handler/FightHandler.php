<?php

namespace App\GameEngine\Fight\Handler;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\GameEngine\Fight\CombatLogger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class FightHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly CombatLogger $combatLogger,
    ) {
    }

    public function startFight(Player $player, Mob $mob): Fight
    {
        $this->logger->info('Starting fight between {player} and {mob}', ['player' => $player->getId(), 'mob' => $mob->getId()]);
        $fight = new Fight();
        $fight->addPlayer($player);
        $fight->addMob($mob);

        $player->setFight($fight);
        $mob->setFight($fight);

        $player->setIsMoving(false);

        $this->entityManager->persist($fight);
        $this->entityManager->flush();

        $this->combatLogger->logFightStart($fight);
        $this->entityManager->flush();

        return $fight;
    }
}
