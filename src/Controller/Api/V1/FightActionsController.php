<?php

namespace App\Controller\Api\V1;

use App\Api\LegacyResponseEnveloper;
use App\Controller\Game\Fight\FightAttackController;
use App\Controller\Game\Fight\FightFleeController;
use App\Controller\Game\Fight\FightItemController;
use App\Controller\Game\Fight\FightSpellController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Actions de combat sous /api/v1 (migration API-first, phase 1.2).
 *
 * Delegue aux controleurs legacy /game/fight/* (qui repondent deja en JSON)
 * et re-enveloppe leurs reponses dans la convention ApiResponse :
 * - 4xx legacy                      -> error {code du statut}
 * - 200 legacy avec success: false  -> error action_rejected (409)
 * - 200 legacy succes               -> success {payload}
 *
 * La logique metier reste dans les controleurs legacy jusqu'a son extraction
 * en services GameEngine (phases suivantes) ; le contrat /api/v1, lui, est stable.
 */
#[Route('/api/v1/fight')]
class FightActionsController extends AbstractController
{
    public function __construct(
        private readonly FightAttackController $attackController,
        private readonly FightSpellController $spellController,
        private readonly FightItemController $itemController,
        private readonly FightFleeController $fleeController,
        private readonly LegacyResponseEnveloper $enveloper,
    ) {
    }

    #[Route('/attack', name: 'api_v1_fight_attack', methods: ['POST'])]
    public function attack(Request $request): JsonResponse
    {
        return $this->envelope(($this->attackController)($request));
    }

    #[Route('/spell', name: 'api_v1_fight_spell', methods: ['POST'])]
    public function spell(Request $request): JsonResponse
    {
        return $this->envelope(($this->spellController)($request));
    }

    #[Route('/item', name: 'api_v1_fight_item', methods: ['POST'])]
    public function item(Request $request): JsonResponse
    {
        return $this->envelope(($this->itemController)($request));
    }

    #[Route('/flee', name: 'api_v1_fight_flee', methods: ['POST'])]
    public function flee(): JsonResponse
    {
        return $this->envelope(($this->fleeController)());
    }

    private function envelope(Response $legacyResponse): JsonResponse
    {
        return $this->enveloper->envelope($legacyResponse);
    }
}
