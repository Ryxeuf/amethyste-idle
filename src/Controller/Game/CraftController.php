<?php

namespace App\Controller\Game;

use App\Entity\Game\CraftRecipe;
use App\GameEngine\Craft\CraftManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CraftController extends AbstractController
{
    private const PROFESSIONS = [
        'blacksmith' => 'Forgeron',
        'tanner' => 'Tanneur',
        'alchemist' => 'Alchimiste',
        'jeweler' => 'Joaillier',
    ];

    public function __construct(
        private readonly CraftManager $craftManager,
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/game/craft', name: 'app_game_craft')]
    public function index(): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        $recipesByProfession = [];
        foreach (self::PROFESSIONS as $key => $label) {
            $recipes = $this->craftManager->getAvailableRecipes($player, $key);
            $recipesByProfession[$key] = [
                'label' => $label,
                'recipes' => $recipes,
                'canCraft' => array_map(
                    fn (CraftRecipe $r) => $this->craftManager->canCraft($player, $r),
                    $recipes
                ),
            ];
        }

        return $this->render('game/craft/index.html.twig', [
            'professions' => self::PROFESSIONS,
            'recipesByProfession' => $recipesByProfession,
        ]);
    }

    #[Route('/game/craft/{profession}', name: 'app_game_craft_profession', requirements: ['profession' => 'blacksmith|tanner|alchemist|jeweler'])]
    public function profession(string $profession): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        $recipes = $this->craftManager->getAvailableRecipes($player, $profession);
        $canCraft = [];
        $hasIngredients = [];
        foreach ($recipes as $recipe) {
            $canCraft[$recipe->getId()] = $this->craftManager->canCraft($player, $recipe);
            $hasIngredients[$recipe->getId()] = $this->craftManager->hasIngredients($player, $recipe);
        }

        return $this->render('game/craft/index.html.twig', [
            'professions' => self::PROFESSIONS,
            'activeProfession' => $profession,
            'recipes' => $recipes,
            'canCraft' => $canCraft,
            'hasIngredients' => $hasIngredients,
            'recipesByProfession' => $this->getAllRecipesByProfession($player),
        ]);
    }

    #[Route('/game/craft/execute/{recipeId}', name: 'app_game_craft_execute', methods: ['POST'])]
    public function execute(int $recipeId): JsonResponse
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->json(['error' => 'Joueur non trouvé.'], Response::HTTP_UNAUTHORIZED);
        }

        $recipe = $this->entityManager->getRepository(CraftRecipe::class)->find($recipeId);
        if ($recipe === null) {
            return $this->json(['error' => 'Recette introuvable.'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->craftManager->canCraft($player, $recipe)) {
            return $this->json(
                ['error' => 'Vous ne pouvez pas fabriquer cet objet.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $result = $this->craftManager->craft($player, $recipe);

            return $this->json([
                'success' => true,
                'item' => [
                    'name' => $result->item->getGenericItem()->getName(),
                    'slug' => $result->item->getGenericItem()->getSlug(),
                ],
                'quality' => $result->quality,
                'experience' => $result->experienceGained,
                'message' => sprintf(
                    'Vous avez fabriqué : %s (Qualité : %s, +%d XP)',
                    $result->item->getGenericItem()->getName(),
                    ucfirst($result->quality),
                    $result->experienceGained
                ),
            ]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/game/craft/experiment', name: 'app_game_craft_experiment', methods: ['POST'])]
    public function experiment(Request $request): JsonResponse
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->json(['error' => 'Joueur non trouvé.'], Response::HTTP_UNAUTHORIZED);
        }

        $itemSlugs = $request->request->all('item_slugs');
        if (empty($itemSlugs) || !is_array($itemSlugs)) {
            return $this->json(
                ['error' => 'Vous devez sélectionner des ingrédients.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $discovered = $this->craftManager->experiment($player, $itemSlugs);

        if ($discovered !== null) {
            return $this->json([
                'success' => true,
                'recipe' => [
                    'name' => $discovered->getName(),
                    'slug' => $discovered->getSlug(),
                    'description' => $discovered->getDescription(),
                ],
                'message' => 'Vous avez découvert une nouvelle recette : ' . $discovered->getName(),
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Cette combinaison ne donne rien... Essayez autre chose.',
        ]);
    }

    /**
     * @return array<string, array{label: string, recipes: CraftRecipe[]}>
     */
    private function getAllRecipesByProfession(mixed $player): array
    {
        $recipesByProfession = [];
        foreach (self::PROFESSIONS as $key => $label) {
            $recipes = $this->craftManager->getAvailableRecipes($player, $key);
            $canCraft = [];
            foreach ($recipes as $recipe) {
                $canCraft[$recipe->getId()] = $this->craftManager->canCraft($player, $recipe);
            }
            $recipesByProfession[$key] = [
                'label' => $label,
                'recipes' => $recipes,
                'canCraft' => $canCraft,
            ];
        }

        return $recipesByProfession;
    }
}
