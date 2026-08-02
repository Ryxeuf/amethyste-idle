<?php

namespace App\Controller\Game;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\EnchantmentDefinition;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Enum\CraftSpecialization;
use App\GameEngine\Crafting\CraftBranchCatalog;
use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Crafting\CraftSpecializationService;
use App\GameEngine\Crafting\ExperimentationManager;
use App\GameEngine\Economy\PurityChain;
use App\GameEngine\Enchantment\EnchantmentManager;
use App\GameEngine\Settlement\SettlementWorkshopBonus;
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
        private readonly CraftSpecializationService $craftSpecializationService,
        private readonly CraftBranchCatalog $craftBranchCatalog,
        private readonly SettlementWorkshopBonus $workshopBonus,
        private readonly PurityChain $purityChain,
    ) {
    }

    #[Route('', name: 'app_game_craft')]
    public function index(): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        // ECO-20 : un seul travail a la fois — un etabli est un etabli.
        $activeJob = $this->craftingManager->getActiveJob($player);

        // OBJ-06 : les sept metiers exigent leur outil — la marmite, la varlope
        // et l'aiguille existent desormais (`Item::CRAFT_TOOL_TYPES` les cite),
        // et le palier d'entree est livre par le nœud gratuit de chaque arbre.
        $crafts = array_keys(Item::CRAFT_TOOL_TYPES);
        // FOY-07 : le bonus que le lieu accorde, metier par metier. Le joueur
        // doit pouvoir arbitrer *ou* crafter — un total sans sa composition ne
        // lui dirait pas quoi faire de l'information.
        $workshopBonusByCraft = [];
        $recipesByCraft = [];
        $purityPreviewByCraft = [];
        $canCraftByCraft = [];
        $craftLevels = [];
        $craftToolStatus = [];
        $maxCraftByCraft = [];
        $lockedRecipesByCraft = [];
        $nextUnlockByCraft = [];

        foreach ($crafts as $craft) {
            $workshopBonusByCraft[$craft] = $this->workshopBonus->describe($player->getCurrentZone(), $craft);
            $recipes = $this->craftingManager->getAvailableRecipes($player, $craft);
            $recipesByCraft[$craft] = $recipes;
            $canCraftByCraft[$craft] = [];
            $maxCraftByCraft[$craft] = [];
            $craftLevels[$craft] = $this->craftingManager->getCraftingLevel($player, $craft);
            $craftToolStatus[$craft] = $this->craftingManager->checkCraftTool($player, $craft);
            $lockedRecipesByCraft[$craft] = $this->craftingManager->getLockedRecipes($player, $craft);
            $nextUnlockByCraft[$craft] = $this->craftingManager->getNextUnlockInfo($player, $craft);

            foreach ($recipes as $recipe) {
                $canCraftByCraft[$craft][$recipe->getId()] = $this->craftingManager->canCraft($player, $recipe);
                $maxCraftByCraft[$craft][$recipe->getId()] = $this->craftingManager->maxCraftable($player, $recipe);
                // ECO-26 — la regle du maillon faible doit se voir **avant** le
                // craft. Un joueur qui decouvre a la sortie que son lingot est
                // trouble, sans savoir lequel de ses six intrants l'a decide,
                // n'apprend rien et vit la regle comme une punition.
                $purityPreviewByCraft[$craft][$recipe->getId()] = $this->purityChain->preview($player, $recipe);
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
            'activeJob' => $activeJob,
            'crafts' => $crafts,
            'recipesByCraft' => $recipesByCraft,
            'canCraftByCraft' => $canCraftByCraft,
            'maxCraftByCraft' => $maxCraftByCraft,
            'purityPreviewByCraft' => $purityPreviewByCraft,
            'craftLevels' => $craftLevels,
            'craftToolStatus' => $craftToolStatus,
            'workshopBonusByCraft' => $workshopBonusByCraft,
            'currentZone' => $player->getCurrentZone(),
            'lockedRecipesByCraft' => $lockedRecipesByCraft,
            'nextUnlockByCraft' => $nextUnlockByCraft,
            'player' => $player,
            'playerItems' => array_values($playerItems),
            'enchantmentDefinitions' => $enchantmentDefinitions,
            'equippedItems' => $equippedItems,
            'activeEnchantments' => $activeEnchantments,
            // DOM-04 : une ligne par arbre, et non plus un choix unique pour
            // tout le personnage. L'ecran doit montrer les sept arbres, ce qui
            // est pris dans chacun, et ce qui reste ouvert.
            'specializationTrees' => $this->buildSpecializationTrees($player),
            'specializationBonus' => CraftSpecializationService::QUALITY_BONUS_CHANCE,
            'specializationRequiredXp' => CraftSpecializationService::REQUIRED_DOMAIN_XP,
            'specializationRespecCost' => $this->craftSpecializationService->getRespecCost(),
        ]);
    }

    #[Route('/specialization', name: 'app_game_craft_specialization', methods: ['POST'])]
    public function chooseSpecialization(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('craft_specialization', (string) $request->request->get('_token'))) {
            $this->addFlash('warning', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_game_craft');
        }

        $craft = CraftSpecialization::tryFrom((string) $request->request->get('craft'));
        $branch = (string) $request->request->get('branch');
        if ($craft === null || $branch === '') {
            $this->addFlash('warning', 'Specialisation inconnue.');

            return $this->redirectToRoute('app_game_craft');
        }

        // Le respec est le meme geste que le choix, a ceci pres qu'il se paie.
        // Les separer en deux routes aurait duplique la validation, et l'une des
        // deux aurait fini par diverger.
        $result = $player->getCraftSpecializationFor($craft->value) === null
            ? $this->craftSpecializationService->choose($player, $craft, $branch)
            : $this->craftSpecializationService->respec($player, $craft, $branch);

        $this->addFlash($result['success'] ? 'success' : 'warning', $result['message']);

        return $this->redirectToRoute('app_game_craft');
    }

    /**
     * Ce que chaque arbre d'artisanat propose, et ou en est le joueur.
     *
     * @return list<array{craft: CraftSpecialization, branches: array<string, array{label: string, description: string}>, chosen: ?string, check: array{ok: bool, reason: string}}>
     */
    private function buildSpecializationTrees(Player $player): array
    {
        $trees = [];
        foreach ($this->craftSpecializationService->getAvailableSpecializations() as $craft) {
            $current = $player->getCraftSpecializationFor($craft->value);

            $trees[] = [
                'craft' => $craft,
                'branches' => $this->craftBranchCatalog->branchesOf($craft),
                'chosen' => $current?->getBranch(),
                'check' => $this->craftSpecializationService->canChoose($player, $craft),
            ];
        }

        return $trees;
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

        // ECO-20 : l'etabli est desormais temporise. Cette route sans JS met en
        // chantier ; la recuperation se fait par `app_game_craft_collect`.
        $result = $this->craftingManager->startCraft($player, $recipe);
        $this->addFlash($result['success'] ? 'success' : 'warning', $result['message']);

        return $this->redirectToRoute('app_game_craft');
    }

    #[Route('/collect', name: 'app_game_craft_collect', methods: ['POST'])]
    public function collect(): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        $result = $this->craftingManager->collectCraft($player);
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
