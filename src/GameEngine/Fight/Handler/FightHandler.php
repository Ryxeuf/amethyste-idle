<?php

namespace App\GameEngine\Fight\Handler;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\GameEngine\Enchantment\EnchantmentManager;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Party\PartyManager;
use App\Repository\DungeonRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class FightHandler
{
    /**
     * Multiplicateur de HP du world boss par joueur additionnel (35% par joueur).
     */
    private const WORLD_BOSS_HP_SCALE_PER_PLAYER = 0.35;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly CombatLogger $combatLogger,
        private readonly EnchantmentManager $enchantmentManager,
        private readonly DungeonRunRepository $dungeonRunRepository,
        private readonly PartyManager $partyManager,
        private readonly FightTurnResolver $turnResolver,
        private readonly MobActionHandler $mobActionHandler,
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

        // Add all party members to the fight (coop combat)
        $partyPlayers = $this->resolvePartyPlayers($player);
        foreach ($partyPlayers as $partyPlayer) {
            $fight->addPlayer($partyPlayer);
            $partyPlayer->setFight($fight);
            $partyPlayer->setIsMoving(false);
            $this->enchantmentManager->cleanExpiredForPlayer($partyPlayer);
        }

        // Scale mob stats for dungeon difficulty
        $activeRun = $this->dungeonRunRepository->findActiveRun($player);
        $difficulty = $activeRun?->getDifficulty();
        $statMultiplier = $difficulty?->statMultiplier() ?? 1.0;

        if ($difficulty !== null && $statMultiplier > 1.0) {
            $fight->setMetadataValue('difficulty_multiplier', $statMultiplier);
            $fight->setMetadataValue('difficulty_damage_multiplier', $difficulty->damageMultiplier());
            $fight->setMetadataValue('difficulty_drop_multiplier', $difficulty->dropMultiplier());
            $fight->setMetadataValue('difficulty_xp_multiplier', $difficulty->xpMultiplier());
        }

        foreach ($mobs as $mob) {
            if ($statMultiplier > 1.0) {
                $scaledLife = (int) round($mob->getLife() * $statMultiplier);
                $mob->setLife($scaledLife);
            }
            $fight->addMob($mob);
            $mob->setFight($fight);

            // Initialize boss phase tracking
            $monster = $mob->getMonster();
            if ($monster->isBoss() && $monster->getBossPhases()) {
                $phase = $monster->getCurrentBossPhase(100);
                if ($phase !== null && isset($phase['name'])) {
                    $fight->setMetadataValue('boss_phase_' . $mob->getId(), $phase['name']);
                }
            }
        }

        // Scale world boss HP based on initial party size
        if ($fight->isWorldBossFight() && \count($partyPlayers) > 1) {
            $multiplier = 1.0 + self::WORLD_BOSS_HP_SCALE_PER_PLAYER * (\count($partyPlayers) - 1);
            $fight->setMetadataValue('world_boss_player_multiplier', $multiplier);
            foreach ($mobs as $mob) {
                if ($mob->isWorldBoss()) {
                    $mob->setLife((int) round($mob->getLife() * $multiplier));
                }
            }
        }

        $this->entityManager->persist($fight);
        $this->entityManager->flush();

        $this->combatLogger->logFightStart($fight);

        // Initialize coop turn system if multiple players
        if (count($partyPlayers) > 1) {
            $this->turnResolver->initializeCoopTurns($fight, $this->mobActionHandler);
        }

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

        $this->scaleWorldBossHpForNewPlayer($fight);

        $this->entityManager->flush();

        $this->combatLogger->logPlayerJoined($fight, $player);
        $this->entityManager->flush();
    }

    /**
     * Recalcule les HP du world boss proportionnellement au nouveau nombre de joueurs.
     * Maintient le pourcentage de vie actuel du boss.
     */
    private function scaleWorldBossHpForNewPlayer(Fight $fight): void
    {
        $playerCount = $fight->getPlayers()->count();
        $previousMultiplier = (float) $fight->getMetadataValue('world_boss_player_multiplier', 1.0);
        $newMultiplier = 1.0 + self::WORLD_BOSS_HP_SCALE_PER_PLAYER * ($playerCount - 1);

        $fight->setMetadataValue('world_boss_player_multiplier', $newMultiplier);

        foreach ($fight->getMobs() as $mob) {
            if (!$mob->isWorldBoss() || $mob->isDead()) {
                continue;
            }

            $baseLife = $mob->getMonster()->getLife();
            $oldMaxLife = (int) round($baseLife * $previousMultiplier);
            $newMaxLife = (int) round($baseLife * $newMultiplier);

            $ratio = $oldMaxLife > 0 ? ($mob->getLife() / $oldMaxLife) : 1.0;
            $mob->setLife((int) round($newMaxLife * $ratio));
        }
    }

    /**
     * Résout les joueurs qui doivent entrer en combat : le joueur + ses coéquipiers du groupe.
     * Filtre les membres indisponibles (déjà en combat, morts, trop éloignés).
     *
     * @return Player[]
     */
    private function resolvePartyPlayers(Player $player): array
    {
        $party = $this->partyManager->getPlayerParty($player);
        if ($party === null) {
            return [$player];
        }

        $players = [$player];
        foreach ($party->getMembers() as $member) {
            $memberPlayer = $member->getPlayer();
            if ($memberPlayer->getId() === $player->getId()) {
                continue;
            }

            // Skip if already in a fight
            if ($memberPlayer->getFight() !== null) {
                continue;
            }

            // Skip if dead
            if ($memberPlayer->isDead()) {
                continue;
            }

            // Must be on the same map
            if ($memberPlayer->getMap()?->getId() !== $player->getMap()?->getId()) {
                continue;
            }

            $players[] = $memberPlayer;
        }

        return $players;
    }
}
