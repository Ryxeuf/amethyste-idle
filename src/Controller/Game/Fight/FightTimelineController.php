<?php

namespace App\Controller\Game\Fight;

use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\StatusEffectManager;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboStreamResponse;

#[Route('/game/fight/timeline', name: 'app_game_fight_timeline')]
class FightTimelineController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly FightTurnResolver $turnResolver,
        private readonly StatusEffectManager $statusEffectManager,
    ) {
    }

    public function __invoke(): Response
    {
        $player = $this->playerHelper->getPlayer();
        $fight = $player?->getFight();

        $timeline = [];
        $currentRound = 1;
        $statusEffects = [];

        if ($fight && !$fight->isVictory() && !$fight->isDefeat()) {
            $timeline = $this->turnResolver->getTimeline($fight, 3);
            $turnOrder = $this->turnResolver->getTurnOrder($fight);
            $currentRound = (int) floor($fight->getStep() / max(1, count($turnOrder))) + 1;

            foreach ($fight->getPlayers() as $fightPlayer) {
                $statusEffects['player_' . $fightPlayer->getId()] = $this->statusEffectManager->getActiveEffects($fight, $fightPlayer);
            }
            foreach ($fight->getMobs() as $mob) {
                $statusEffects['mob_' . $mob->getId()] = $this->statusEffectManager->getActiveEffects($fight, $mob);
            }
        }

        $response = $this->render('game/fight/timeline.html.twig', [
            'timeline' => $timeline,
            'currentRound' => $currentRound,
            'statusEffects' => $statusEffects,
        ], new TurboStreamResponse());
        $response->headers->set('Content-Type', 'text/vnd.turbo-stream.html');

        return $response;
    }
}
