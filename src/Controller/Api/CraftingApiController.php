<?php

namespace App\Controller\Api;

use App\Entity\Game\Recipe;
use App\GameEngine\Crafting\CraftingManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/craft')]
class CraftingApiController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly CraftingManager $craftingManager,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/batch/{slug}', name: 'api_craft_batch', methods: ['POST'])]
    public function batch(string $slug, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->json(['success' => false, 'message' => 'Joueur introuvable.'], 403);
        }

        $recipe = $this->em->getRepository(Recipe::class)->findOneBy(['slug' => $slug]);
        if (!$recipe) {
            return $this->json(['success' => false, 'message' => 'Recette introuvable.'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $quantity = max(1, min((int) ($data['quantity'] ?? 1), 99));

        $result = $this->craftingManager->craftMultiple($player, $recipe, $quantity);

        return $this->json([
            'success' => $result['success'],
            'crafted' => $result['crafted'],
            'totalXp' => $result['totalXp'],
            'message' => $result['message'],
        ]);
    }
}
