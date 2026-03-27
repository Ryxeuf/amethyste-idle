<?php

namespace App\GameEngine\Fight\Handler;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\GameEngine\Enchantment\EnchantmentManager;
use App\GameEngine\Fight\CombatLogger;
use App\Repository\DungeonRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class FightHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly CombatLogger $combatLogger,
        private readonly EnchantmentManager $enchantmentManager,
        private readonly DungeonRunRepository $dungeonRunRepository,
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

        // Scale mob stats for dungeon difficulty
        $activeRun = $this->dungeonRunRepository->findActiveRun($player);
        $difficultyMultiplier = $activeRun?->getDifficulty()->statMultiplier() ?? 1.0;

        foreach ($mobs as $mob) {
            if ($difficultyMultiplier > 1.0) {
                $scaledLife = (int) round($mob->getLife() * $difficultyMultiplier);
                $mob->setLife($scaledLife);
            }
            $fight->addMob($mob);
            $mob->setFight($fight);
        }

        $player->setIsMoving(false);

        // Nettoyage des enchantements expires au debut du combat
        $this->enchantmentManager->cleanExpiredForPlayer($player);

        $this->entityManager->persist($fight);
        $this->entityManager->flush();

        $this->combatLogger->logFightStart($fight);
        $this->entityManager->flush();

        return $fight;
    }

    /**
     * Ajoute un joueur à un combat world boss existant.
     */
    public function joinWorldBossFight(Player $player, Fight $fight): void
    {
        $this->logger->info('Player {player} joining world boss fight {fight}', [
            'player' => $player->getId(),
            'fight' => $fight->getId(),
        ]);

        $fight->addPlayer($player);
        $player->setFight($fight);
        $player->setIsMoving(false);

        $this->entityManager->flush();

        $this->combatLogger->logPlayerJoined($fight, $player);
        $this->entityManager->flush();
    }
}
