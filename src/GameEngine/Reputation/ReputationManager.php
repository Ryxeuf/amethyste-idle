<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use App\Enum\ReputationTier;
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
            $this->entityManager->persist($playerFaction);
        }

        $playerFaction->addReputation($amount);

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

    public function getReputationAmount(int $monsterLevel): int
    {
        return match (true) {
            $monsterLevel >= 20 => 50,
            $monsterLevel >= 10 => 25,
            $monsterLevel >= 5 => 15,
            default => 10,
        };
    }

    /**
     * @return array<string, ReputationTier>
     */
    public function getUnlockedTiers(Player $player, Faction $faction): array
    {
        $playerFaction = $this->getPlayerFaction($player, $faction);
        if (null === $playerFaction) {
            return [];
        }

        $currentTier = $playerFaction->getTier();
        $unlocked = [];

        foreach (ReputationTier::cases() as $tier) {
            if ($tier === ReputationTier::Hostile || $tier === ReputationTier::Inconnu) {
                continue;
            }
            if ($playerFaction->getReputation() >= $tier->threshold()) {
                $unlocked[$tier->value] = $tier;
            }
        }

        return $unlocked;
    }
}
