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
        return $this->startGroupFight($player, [$mob]);
    }

    /**
     * @param Mob[] $mobs
     */
    public function startGroupFight(Player $player, array $mobs): Fight
    {
        $mobIds = array_map(fn (Mob $m) => $m->getId(), $mobs);
        $this->logger->info('Starting fight between {player} and {mobs}', ['player' => $player->getId(), 'mobs' => implode(',', $mobIds)]);

        $fight = new Fight();
        $fight->addPlayer($player);
        $player->setFight($fight);

        foreach ($mobs as $mob) {
            $fight->addMob($mob);
            $mob->setFight($fight);
        }

        $player->setIsMoving(false);

        $this->entityManager->persist($fight);
        $this->entityManager->flush();

        $this->combatLogger->logFightStart($fight);
        $this->entityManager->flush();

        return $fight;
    }
}
