<?php

namespace App\Controller\Game\Fight;

use App\Entity\App\Player;
use App\GameEngine\Fight\CombatCapacityResolver;
use App\GameEngine\Fight\CombatLogArchiver;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\StatusEffectManager;
use App\Helper\PlayerHelper;
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
        if ($player->getFight()->isVictory()) {
            return $this->redirectToRoute('app_game_fight_loot');
        }
        if ($player->getFight()->isDefeat()) {
            return $this->handleDefeat($player);
        }

        $fight = $player->getFight();

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

        // Danger alert check from mob AI
        $dangerAlert = null;
        $mob = $fight->getMobs()->first();
        if ($mob && !$mob->isDead()) {
            $monster = $mob->getMonster();
            $aiPattern = $monster->getAiPattern();
            $hpPercent = ($mob->getLife() / $mob->getMaxLife()) * 100;

            if ($monster->isBoss() && $monster->getBossPhases()) {
                $phase = $monster->getCurrentBossPhase((int) $hpPercent);
                if ($phase && isset($phase['danger_message'])) {
                    $dangerAlert = $phase['danger_message'];
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

        return $this->render('game/fight/index.html.twig', [
            'player' => $player,
            'fight' => $fight,
            'mob' => $mob,
            'statusEffects' => $statusEffects,
            'materiaSpells' => $materiaSpells,
            'playerCooldowns' => $playerCooldowns,
            'dangerAlert' => $dangerAlert,
            'fightLogs' => $fightLogs,
            'timeline' => $timeline,
            'currentRound' => $currentRound,
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

        // Dissocier tous les joueurs du combat
        foreach ($fight->getPlayers() as $fightPlayer) {
            $fightPlayer->setFight(null);
            $this->entityManager->persist($fightPlayer);
        }

        // Supprimer les mobs du combat
        foreach ($fight->getMobs() as $mob) {
            $this->entityManager->remove($mob);
        }

        // Supprimer le combat
        $this->entityManager->remove($fight);

        // Respawn du joueur (restaurer HP si pas deja fait par le PlayerRespawnHandler)
        if ($player->isDead()) {
            $player->setLife((int) round($player->getMaxLife() / 2));
            $player->setDiedAt(null);
        }

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return $this->render('game/fight/defeat.html.twig', [
            'player' => $player,
        ]);
    }
}
