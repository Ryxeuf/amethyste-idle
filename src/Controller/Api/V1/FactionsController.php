<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use App\Entity\Game\FactionReward;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Factions et reputation en JSON (migration API-first, ecrans meta).
 * Lecture seule.
 */
#[Route('/api/v1')]
class FactionsController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/factions', name: 'api_v1_factions', methods: ['GET'])]
    public function factions(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        $locale = $request->getLocale();

        $playerFactionMap = [];
        foreach ($this->entityManager->getRepository(PlayerFaction::class)->findBy(['player' => $player]) as $playerFaction) {
            $playerFactionMap[$playerFaction->getFaction()->getId()] = $playerFaction;
        }

        $rewardsByFaction = [];
        foreach ($this->entityManager->getRepository(FactionReward::class)->findBy([], ['requiredTier' => 'ASC']) as $reward) {
            $rewardsByFaction[$reward->getFaction()->getId()][] = [
                'requiredTier' => $reward->getRequiredTier()->value,
                'type' => $reward->getRewardType(),
                'data' => $reward->getRewardData(),
                'label' => $reward->getLocalizedLabel($locale),
            ];
        }

        $factions = [];
        foreach ($this->entityManager->getRepository(Faction::class)->findAll() as $faction) {
            $playerFaction = $playerFactionMap[$faction->getId()] ?? null;

            $factions[] = [
                'id' => $faction->getId(),
                'slug' => $faction->getSlug(),
                'name' => $faction->getLocalizedName($locale),
                'description' => $faction->getLocalizedDescription($locale),
                'reputation' => $playerFaction?->getReputation() ?? 0,
                'tier' => $playerFaction?->getTier()->value,
                'progressPercent' => $playerFaction?->getProgressPercent() ?? 0,
                'rewards' => $rewardsByFaction[$faction->getId()] ?? [],
            ];
        }

        return ApiResponse::success(['factions' => $factions]);
    }
}
