<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Api\LegacyResponseEnveloper;
use App\Controller\Game\Fight\FightLootProceedController;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Butin de fin de combat sous /api/v1 (migration API-first, phase 1.4).
 * GET : payload JSON du butin (equivalent de l'ecran Twig game/fight/loot).
 * POST proceed : delegue au controleur legacy (deja JSON), enveloppe v1.
 */
#[Route('/api/v1/fight/loot')]
class FightLootController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly FightLootProceedController $lootProceedController,
        private readonly LegacyResponseEnveloper $enveloper,
    ) {
    }

    #[Route('', name: 'api_v1_fight_loot', methods: ['GET'])]
    public function loot(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        $fight = $player->getFight();
        if (!$fight) {
            return ApiResponse::error('not_found', 'Fight not found.', 404);
        }

        $isWorldBoss = $fight->isWorldBossFight();
        $isCoop = $fight->isCoopFight();
        $locale = $request->getLocale();

        $items = [];
        foreach ($fight->getMobs() as $mob) {
            foreach ($mob->getItems() as $item) {
                // World boss / coop : ne montrer que les items lies a ce joueur
                if (($isWorldBoss || $isCoop) && $item->getBoundToPlayerId() !== $player->getId()) {
                    continue;
                }

                $items[] = [
                    'id' => $item->getId(),
                    'name' => $item->getGenericItem()->getLocalizedName($locale),
                    'description' => $item->getGenericItem()->getLocalizedDescription($locale),
                    'value' => $item->getGenericItem()->getValue(),
                    'rarity' => $item->getGenericItem()->getRarity(),
                ];
            }
        }

        return ApiResponse::success([
            'fightId' => $fight->getId(),
            'victory' => $fight->isVictory(),
            'isWorldBoss' => $isWorldBoss,
            'isCoop' => $isCoop,
            'items' => $items,
            'contributions' => $isWorldBoss ? $fight->getRankedContributors() : [],
        ]);
    }

    #[Route('/proceed', name: 'api_v1_fight_loot_proceed', methods: ['POST'])]
    public function proceed(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->enveloper->envelope(($this->lootProceedController)($request));
    }
}
