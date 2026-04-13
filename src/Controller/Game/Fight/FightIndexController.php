<?php

namespace App\Controller\Game\Fight;

use App\Entity\App\Player;
use App\GameEngine\Dungeon\DungeonManager;
use App\GameEngine\Fight\CombatCapacityResolver;
use App\GameEngine\Fight\CombatLogArchiver;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\StatusEffectManager;
use App\GameEngine\GoldSink\GoldSinkManager;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;
use App\Helper\PlayerHelper;
use App\Repository\DungeonRunRepository;
use App\Repository\FightRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight', name: 'app_game_fight')]
class FightIndexController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly StatusEffectManager $statusEffectManager,
        private readonly CombatCapacityResolver $combatCapacityResolver,
        private readonly CombatLogger $combatLogger,
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatLogArchiver $combatLogArchiver,
        private readonly FightTurnResolver $turnResolver,
        private readonly PlayerEffectiveStatsCalculator $playerEffectiveStatsCalculator,
        private readonly DungeonRunRepository $dungeonRunRepository,
        private readonly DungeonManager $dungeonManager,
        private readonly FightRepository $fightRepository,
        private readonly GoldSinkManager $goldSinkManager,
    ) {
    }

    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        if (!$player) {
            throw $this->createNotFoundException('Player not found');
        }
        if (!$player->getFight()) {
            return $this->redirectToRoute('app_game_map');
        }

        // Charger le combat avec toutes ses relations via JOIN FETCH (élimine N+1)
        $fight = $this->fightRepository->findWithRelations($player->getFight()->getId());
        if (!$fight) {
            return $this->redirectToRoute('app_game_map');
        }

        if ($fight->isVictory()) {
            return $this->redirectToRoute('app_game_fight_loot');
        }
        // World boss / coop : si ce joueur est mort mais d'autres joueurs sont vivants,
        // le retirer du combat et le respawn
        if ($player->isDead() && ($fight->isWorldBossFight() || $fight->isCoopFight())) {
            return $this->handleMultiPlayerDeath($player);
        }

        if ($fight->isDefeat()) {
            return $this->handleDefeat($player);
        }

        // Gather status effects for all participants
        $statusEffects = [];
        foreach ($fight->getPlayers() as $fightPlayer) {
            $statusEffects['player_' . $fightPlayer->getId()] = $this->statusEffectManager->getActiveEffects($fight, $fightPlayer);
        }
        foreach ($fight->getMobs() as $mob) {
            $statusEffects['mob_' . $mob->getId()] = $this->statusEffectManager->getActiveEffects($fight, $mob);
        }

        // Get combat spells from equipped materia
        $materiaSpells = $this->combatCapacityResolver->getEquippedMateriaSpells($player);

        // Get cooldowns for the player
        $playerCooldowns = [];
        $entityKey = 'player_' . $player->getId();
        foreach ($materiaSpells as $entry) {
            $spell = $entry['spell'];
            $playerCooldowns[$spell->getSlug()] = $fight->getSpellCooldown($entityKey, $spell->getSlug());
        }

        // Danger alert + current boss phase for UI display
        $dangerAlert = null;
        $bossPhases = [];
        foreach ($fight->getMobs() as $fightMob) {
            if ($fightMob->isDead()) {
                continue;
            }
            $monster = $fightMob->getMonster();
            $aiPattern = $monster->getAiPattern();
            $hpPercent = ($fightMob->getLife() / $fightMob->getMaxLife()) * 100;

            if ($monster->isBoss() && $monster->getBossPhases()) {
                $phase = $monster->getCurrentBossPhase((int) $hpPercent);
                if ($phase !== null) {
                    $bossPhases[$fightMob->getId()] = $phase;
                    if ($dangerAlert === null && isset($phase['danger_message'])) {
                        $dangerAlert = $phase['danger_message'];
                    }
                }
            }

            if ($dangerAlert === null && $aiPattern !== null && isset($aiPattern['danger_alert'])) {
                $alertThreshold = $aiPattern['danger_alert']['threshold'] ?? 30;
                if ($hpPercent <= $alertThreshold) {
                    $dangerAlert = $aiPattern['danger_alert']['message'] ?? null;
                }
            }
        }

        // Get combat logs
        $fightLogs = $this->combatLogger->getLogsForFight($fight);

        // Timeline : ordre des tours base sur la vitesse
        $timeline = $this->turnResolver->getTimeline($fight, 3);
        $currentRound = (int) floor($fight->getStep() / max(1, count($this->turnResolver->getTurnOrder($fight)))) + 1;

        $effectiveMaxLifeByPlayer = [];
        foreach ($fight->getPlayers() as $fightPlayerEntity) {
            $effectiveMaxLifeByPlayer[$fightPlayerEntity->getId()] = $this->playerEffectiveStatsCalculator->getEffectiveMaxLife($fightPlayerEntity);
        }

        // Coop fight data
        $isCoop = $fight->isCoopFight();
        $isMyTurn = !$isCoop || $this->turnResolver->isPlayerTurn($fight, $player->getId());

        return $this->render('game/fight/index.html.twig', [
            'player' => $player,
            'fight' => $fight,
            'statusEffects' => $statusEffects,
            'materiaSpells' => $materiaSpells,
            'playerCooldowns' => $playerCooldowns,
            'dangerAlert' => $dangerAlert,
            'bossPhases' => $bossPhases,
            'fightLogs' => $fightLogs,
            'timeline' => $timeline,
            'currentRound' => $currentRound,
            'effectiveMaxLifeByPlayer' => $effectiveMaxLifeByPlayer,
            'isCoop' => $isCoop,
            'isMyTurn' => $isMyTurn,
            'currentTurnKey' => $fight->getCurrentTurnKey(),
        ]);
    }

    /**
     * Gère la mort d'un joueur dans un combat multi-joueurs (world boss ou coop) :
     * le retire du combat et le respawn, tandis que le combat continue pour les autres.
     */
    private function handleMultiPlayerDeath(Player $player): Response
    {
        $fight = $player->getFight();

        // Dissocier le joueur du combat
        $player->setFight(null);
        $fight->removePlayer($player);

        // Respawn du joueur
        $player->setLife((int) round($player->getMaxLife() / 2));
        $player->setDiedAt(null);

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return $this->render('game/fight/defeat.html.twig', [
            'player' => $player,
            'effectiveMaxLife' => $this->playerEffectiveStatsCalculator->getEffectiveMaxLife($player),
        ]);
    }

    /**
     * Gere la defaite : nettoie le combat, respawn le joueur, et redirige vers la carte.
     * Casse la boucle de redirection /game/fight <-> /game/map.
     */
    private function handleDefeat(Player $player): Response
    {
        $fight = $player->getFight();

        // Archiver les logs du combat avant nettoyage
        $this->combatLogArchiver->archive($fight);

        // Nettoyer les effets de statut
        $this->statusEffectManager->clearAllEffects($fight);

        // Degrader la durabilite des items equipes (gold sink)
        foreach ($fight->getPlayers() as $fightPlayer) {
            $this->goldSinkManager->degradeEquippedItems($fightPlayer);
        }

        // Dissocier tous les joueurs du combat et les respawn
        foreach ($fight->getPlayers() as $fightPlayer) {
            $fightPlayer->setFight(null);
            if ($fightPlayer->isDead()) {
                $fightPlayer->setLife((int) round($fightPlayer->getMaxLife() / 2));
                $fightPlayer->setDiedAt(null);
            }
            $this->entityManager->persist($fightPlayer);
        }

        // Supprimer les mobs du combat
        foreach ($fight->getMobs() as $mob) {
            $this->entityManager->remove($mob);
        }

        // Supprimer le combat
        $this->entityManager->remove($fight);

        $this->entityManager->flush();

        // Abandon du donjon en cas de defaite
        $activeRun = $this->dungeonRunRepository->findActiveRun($player);
        if ($activeRun !== null) {
            $this->dungeonManager->abandonRun($activeRun);
        }

        // Respawn du joueur si pas fait dans la boucle ci-dessus
        if ($player->isDead()) {
            $player->setLife((int) round($player->getMaxLife() / 2));
            $player->setDiedAt(null);
            $this->entityManager->persist($player);
            $this->entityManager->flush();
        }

        return $this->render('game/fight/defeat.html.twig', [
            'player' => $player,
            'effectiveMaxLife' => $this->playerEffectiveStatsCalculator->getEffectiveMaxLife($player),
        ]);
    }
}
