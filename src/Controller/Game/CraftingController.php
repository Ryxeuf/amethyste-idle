<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerItem;
use App\Entity\Game\EnchantmentDefinition;
use App\Entity\Game\Recipe;
use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Crafting\ExperimentationManager;
use App\GameEngine\Enchantment\EnchantmentManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/game/craft')]
#[IsGranted('ROLE_USER')]
class CraftingController extends AbstractController
{
    public function __construct(
        private readonly CraftingManager $craftingManager,
        private readonly ExperimentationManager $experimentationManager,
        private readonly EnchantmentManager $enchantmentManager,
        private readonly EntityManagerInterface $em,
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    #[Route('', name: 'app_game_craft')]
    public function index(): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        $crafts = ['forgeron', 'tanneur', 'alchimiste', 'joaillier'];
        $recipesByCraft = [];
        $canCraftByCraft = [];
        $craftLevels = [];
        $craftToolStatus = [];

        $maxCraftByCraft = [];

        foreach ($crafts as $craft) {
            $recipes = $this->craftingManager->getAvailableRecipes($player, $craft);
            $recipesByCraft[$craft] = $recipes;
            $canCraftByCraft[$craft] = [];
            $maxCraftByCraft[$craft] = [];
            $craftLevels[$craft] = $this->craftingManager->getCraftingLevel($player, $craft);
            $craftToolStatus[$craft] = $this->craftingManager->checkCraftTool($player, $craft);

            foreach ($recipes as $recipe) {
                $canCraftByCraft[$craft][$recipe->getId()] = $this->craftingManager->canCraft($player, $recipe);
                $maxCraftByCraft[$craft][$recipe->getId()] = $this->craftingManager->maxCraftable($player, $recipe);
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
                    ++$playerItems[$slug]['count'];
                }
            }
        }

        // Enchantements
        $enchantmentDefinitions = $this->enchantmentManager->getAvailableDefinitions($player);
        $equippedItems = [];
        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem->getGear() !== 0 && $playerItem->isGear()) {
                    $equippedItems[] = $playerItem;
                }
            }
        }
        $activeEnchantments = $this->enchantmentManager->getActiveEnchantmentsForPlayer($player);

        return $this->render('game/crafting/index.html.twig', [
            'crafts' => $crafts,
            'recipesByCraft' => $recipesByCraft,
            'canCraftByCraft' => $canCraftByCraft,
            'maxCraftByCraft' => $maxCraftByCraft,
            'craftLevels' => $craftLevels,
            'craftToolStatus' => $craftToolStatus,
            'player' => $player,
            'playerItems' => array_values($playerItems),
            'enchantmentDefinitions' => $enchantmentDefinitions,
            'equippedItems' => $equippedItems,
            'activeEnchantments' => $activeEnchantments,
        ]);
    }

    #[Route('/craft/{slug}', name: 'app_game_craft_execute', methods: ['POST'])]
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

        return $this->redirectToRoute('app_game_craft');
    }

    #[Route('/enchant', name: 'app_game_craft_enchant', methods: ['POST'])]
    public function enchant(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        $playerItemId = (int) $request->request->get('player_item_id');
        $definitionSlug = $request->request->get('enchantment_slug');

        $playerItem = $this->em->getRepository(PlayerItem::class)->find($playerItemId);
        if (!$playerItem) {
            $this->addFlash('warning', 'Objet introuvable.');

            return $this->redirectToRoute('app_game_craft');
        }

        $definition = $this->em->getRepository(EnchantmentDefinition::class)->findOneBy(['slug' => $definitionSlug]);
        if (!$definition) {
            $this->addFlash('warning', 'Enchantement introuvable.');

            return $this->redirectToRoute('app_game_craft');
        }

        $result = $this->enchantmentManager->apply($player, $playerItem, $definition);
        $this->addFlash($result['success'] ? 'success' : 'warning', $result['message']);

        return $this->redirectToRoute('app_game_craft');
    }

    #[Route('/experiment', name: 'app_game_craft_experiment', methods: ['POST'])]
    public function experiment(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        $itemSlugs = $request->request->all('items');

        $result = $this->experimentationManager->experiment($player, $itemSlugs);
        $this->addFlash($result['success'] ? 'success' : 'info', $result['message']);

        return $this->redirectToRoute('app_game_craft');
    }
}
