<?php

namespace App\Controller\Game\Fight;

use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Fight\StatusEffectManager;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight', name: 'app_game_fight')]
class FightIndexController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly StatusEffectManager $statusEffectManager,
        private readonly CombatSkillResolver $combatSkillResolver,
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
            throw $this->createNotFoundException('Fight not found');
        }
        if ($player->getFight()->isVictory()) {
            return $this->redirectToRoute('app_game_fight_loot');
        }
        if ($player->getFight()->isDefeat()) {
            return $this->redirectToRoute('app_game_map');
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

        // Get unlocked combat spells for the player
        $unlockedSpells = $this->combatSkillResolver->getUnlockedSpells($player);

        // Get cooldowns for the player
        $playerCooldowns = [];
        $entityKey = 'player_' . $player->getId();
        foreach ($unlockedSpells as $spell) {
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

        return $this->render('game/fight/index.html.twig', [
            'player' => $player,
            'fight' => $fight,
            'mob' => $mob,
            'statusEffects' => $statusEffects,
            'unlockedSpells' => $unlockedSpells,
            'playerCooldowns' => $playerCooldowns,
            'dangerAlert' => $dangerAlert,
        ]);
    }
}
