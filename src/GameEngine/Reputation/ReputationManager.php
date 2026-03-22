<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use Doctrine\ORM\EntityManagerInterface;

class ReputationManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function addReputation(Player $player, Faction $faction, int $amount): PlayerFaction
    {
        $playerFaction = $this->entityManager->getRepository(PlayerFaction::class)->findOneBy([
            'player' => $player,
            'faction' => $faction,
        ]);

        if (null === $playerFaction) {
            $playerFaction = new PlayerFaction();
            $playerFaction->setPlayer($player);
            $playerFaction->setFaction($faction);
            $playerFaction->setCreatedAt(new \DateTime());
            $playerFaction->setUpdatedAt(new \DateTime());
            $this->entityManager->persist($playerFaction);
        }

        $playerFaction->addReputation($amount);
        $playerFaction->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $playerFaction;
    }

    public function getPlayerFaction(Player $player, Faction $faction): ?PlayerFaction
    {
        return $this->entityManager->getRepository(PlayerFaction::class)->findOneBy([
            'player' => $player,
            'faction' => $faction,
        ]);
    }
}
