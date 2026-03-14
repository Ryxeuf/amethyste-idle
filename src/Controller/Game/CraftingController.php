<?php

namespace App\Controller\Game;

use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Crafting\ExperimentationManager;
use App\Entity\Game\Recipe;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/game/crafting')]
#[IsGranted('ROLE_USER')]
class CraftingController extends AbstractController
{
    public function __construct(
        private readonly CraftingManager $craftingManager,
        private readonly ExperimentationManager $experimentationManager,
        private readonly EntityManagerInterface $em,
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    #[Route('', name: 'game_crafting')]
    public function index(): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        $crafts = ['alchemy', 'blacksmithing', 'cooking', 'enchanting'];
        $recipesByCraft = [];
        $canCraftByCraft = [];
        $craftLevels = [];

        foreach ($crafts as $craft) {
            $recipes = $this->craftingManager->getAvailableRecipes($player, $craft);
            $recipesByCraft[$craft] = $recipes;
            $canCraftByCraft[$craft] = [];
            $craftLevels[$craft] = $this->craftingManager->getCraftingLevel($player, $craft);

            foreach ($recipes as $recipe) {
                $canCraftByCraft[$craft][$recipe->getId()] = $this->craftingManager->canCraft($player, $recipe);
            }
        }

        // Recuperer les items du joueur pour l'experimentation
        $playerItems = [];
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->isBag()) {
                foreach ($inventory->getItems() as $playerItem) {
                    $slug = $playerItem->getGenericItem()->getSlug();
                    if (!isset($playerItems[$slug])) {
                        $playerItems[$slug] = [
                            'name' => $playerItem->getGenericItem()->getName(),
                            'slug' => $slug,
                            'count' => 0,
                        ];
                    }
                    $playerItems[$slug]['count']++;
                }
            }
        }

        return $this->render('game/crafting/index.html.twig', [
            'crafts' => $crafts,
            'recipesByCraft' => $recipesByCraft,
            'canCraftByCraft' => $canCraftByCraft,
            'craftLevels' => $craftLevels,
            'player' => $player,
            'playerItems' => array_values($playerItems),
        ]);
    }

    #[Route('/craft/{slug}', name: 'game_crafting_craft', methods: ['POST'])]
    public function craft(string $slug): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        $recipe = $this->em->getRepository(Recipe::class)->findOneBy(['slug' => $slug]);
        if (!$recipe) {
            throw $this->createNotFoundException();
        }

        $result = $this->craftingManager->craft($player, $recipe);
        $this->addFlash($result['success'] ? 'success' : 'warning', $result['message']);

        return $this->redirectToRoute('game_crafting');
    }

    #[Route('/experiment', name: 'game_crafting_experiment', methods: ['POST'])]
    public function experiment(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        $itemSlugs = $request->request->all('items');

        $result = $this->experimentationManager->experiment($player, $itemSlugs);
        $this->addFlash($result['success'] ? 'success' : 'info', $result['message']);

        return $this->redirectToRoute('game_crafting');
    }
}
