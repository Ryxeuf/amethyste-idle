<?php

namespace App\Controller\Game\Fight;

use App\Entity\App\Mob;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight/loot', name: 'app_game_fight_loot', methods: ['GET'])]
class FightLootIndexController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return new JsonResponse(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }

        $fight = $player->getFight();
        if (!$fight) {
            return new JsonResponse(['error' => 'Fight not found'], Response::HTTP_NOT_FOUND);
        }

        // Calculer les récompenses
        $gold = 0;
        $experience = 0;
        $items = [];
        $isWorldBoss = $fight->isWorldBossFight();
        $isCoop = $fight->isCoopFight();
        $contributions = $isWorldBoss ? $fight->getRankedContributors() : [];

        /** @var Mob $mob */
        foreach ($fight->getMobs() as $mob) {
            foreach ($mob->getItems() as $item) {
                // World boss / coop : ne montrer que les items liés à ce joueur
                if (($isWorldBoss || $isCoop) && $item->getBoundToPlayerId() !== $player->getId()) {
                    continue;
                }

                $items[] = [
                    'id' => $item->getId(),
                    'name' => $item->getGenericItem()->getName(),
                    'description' => $item->getGenericItem()->getDescription(),
                    'value' => $item->getGenericItem()->getValue(),
                    'rarity' => $item->getGenericItem()->getRarity(),
                ];
            }
        }

        // Retourner les données mises à jour
        return $this->render('game/fight/loot.html.twig', [
            'fight' => $fight,
            'gold' => $gold,
            'experience' => $experience,
            'items' => $items,
            'isWorldBoss' => $isWorldBoss,
            'contributions' => $contributions,
        ]);
    }
}
