<?php

namespace App\GameEngine\Crafting;

use App\Entity\App\CraftJob;
use App\Entity\App\Player;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Event\CraftEvent;
use App\Event\Game\DomainLevelUpEvent;
use App\GameEngine\Event\GameEventBonusProvider;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Player\PlayerActionHelper;
use App\GameEngine\Settlement\SettlementWorkshopBonus;
use App\Helper\GearHelper;
use App\Helper\InventoryHelper;
use App\Repository\CraftJobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CraftingManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
        private readonly QualityCalculator $qualityCalculator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly GameEventBonusProvider $gameEventBonusProvider,
        private readonly GearHelper $gearHelper,
        private readonly PlayerActionHelper $playerActionHelper,
        private readonly CraftSpecializationService $craftSpecializationService,
        private readonly RecipeUnlockCatalog $recipeUnlockCatalog,
        private readonly CraftJobRepository $craftJobRepository,
        private readonly SettlementWorkshopBonus $workshopBonus,
    ) {
    }

    /**
     * Retourne les recettes disponibles pour un joueur et un type d'artisanat.
     *
     * Les recettes exclusives a une specialisation ne sont retournees que si le joueur
     * possede la specialisation correspondante.
     *
     * @return Recipe[]
     */
    public function getAvailableRecipes(Player $player, string $craft): array
    {
        $recipes = $this->entityManager->getRepository(Recipe::class)->findBy([
            'craft' => $craft,
        ]);

        return array_filter($recipes, fn (Recipe $recipe) => $this->isRecipeUnlocked($player, $recipe));
    }

    /**
     * Ce joueur peut-il fabriquer cette recette ?
     *
     * Trois gardiens, dans l'ordre du moins au plus specifique :
     *
     * 1. le **niveau de metier**, tire de l'XP du domaine ;
     * 2. la **specialisation**, quand la recette en exige une ;
     * 3. le **plan appris** dans l'arbre de talent (ECO-20).
     *
     * Le troisieme etait ecrit dans les donnees depuis toujours — 51 nœuds
     * d'arbre citant 82 recettes — et **n'etait lu nulle part** : un forgeron
     * niveau 10 fabriquait les 29 recettes de forge sans avoir achete un seul
     * nœud. Le brancher est ce qui rend la specialisation reelle : acheter tous
     * les nœuds d'artisanat couterait 2090 points pour un plafond de 500.
     *
     * Une recette qu'aucun skill ne cite reste gatee par les deux premiers
     * gardiens seulement : le troisieme ne doit jamais rendre une recette
     * inatteignable faute d'avoir ete citee.
     */
    public function isRecipeUnlocked(Player $player, Recipe $recipe): bool
    {
        if ($this->getCraftingLevel($player, $recipe->getCraft()) < $recipe->getRequiredLevel()) {
            return false;
        }

        $required = $recipe->getRequiredSpecialization();
        if ($required !== null && $required !== $player->getCraftSpecialization()) {
            return false;
        }

        // Aucun arbre ne revendique cette recette : le troisieme gardien ne
        // s'applique pas, sinon brancher le gardien la rendrait inatteignable.
        if (!$this->recipeUnlockCatalog->isGatedBySkill($recipe->getSlug())) {
            return true;
        }

        return $this->playerActionHelper->hasUnlockedRecipe($recipe->getSlug(), $player);
    }

    /**
     * Retourne les recettes verrouilees (niveau insuffisant ou specialisation manquante),
     * triees par niveau requis.
     *
     * @return Recipe[]
     */
    public function getLockedRecipes(Player $player, string $craft): array
    {
        $recipes = $this->entityManager->getRepository(Recipe::class)->findBy(
            ['craft' => $craft],
            ['requiredLevel' => 'ASC']
        );

        return array_values(array_filter($recipes, fn (Recipe $recipe) => !$this->isRecipeUnlocked($player, $recipe)));
    }

    /**
     * Retourne les infos de progression : prochain niveau de deblocage et nombre de recettes.
     *
     * @return array{nextLevel: int|null, count: int, totalLocked: int}
     */
    public function getNextUnlockInfo(Player $player, string $craft): array
    {
        $lockedRecipes = $this->getLockedRecipes($player, $craft);

        if (empty($lockedRecipes)) {
            return ['nextLevel' => null, 'count' => 0, 'totalLocked' => 0];
        }

        $nextLevel = $lockedRecipes[0]->getRequiredLevel();
        $countAtNextLevel = 0;
        foreach ($lockedRecipes as $recipe) {
            if ($recipe->getRequiredLevel() === $nextLevel) {
                ++$countAtNextLevel;
            }
        }

        return [
            'nextLevel' => $nextLevel,
            'count' => $countAtNextLevel,
            'totalLocked' => count($lockedRecipes),
        ];
    }

    /**
     * Verifie si le joueur possede tous les ingredients necessaires.
     *
     * @return array{possible: bool, missing: array}
     */
    public function canCraft(Player $player, Recipe $recipe): array
    {
        $bagItems = $this->getBagItemsBySlug($player);
        $missing = [];

        foreach ($recipe->getIngredients() as $ingredient) {
            $slug = $ingredient['slug'];
            $requiredQty = $ingredient['quantity'] ?? 1;
            $available = $bagItems[$slug] ?? 0;

            if ($available < $requiredQty) {
                $missing[] = [
                    'slug' => $slug,
                    'need' => $requiredQty,
                    'have' => $available,
                ];
            }
        }

        return [
            'possible' => empty($missing),
            'missing' => $missing,
        ];
    }

    /**
     * Vérifie que le joueur a l'outil de craft requis équipé.
     *
     * @return array{ok: bool, message: string}
     */
    public function checkCraftTool(Player $player, string $craft): array
    {
        $requiredToolType = Item::CRAFT_TOOL_TYPES[$craft] ?? null;
        if ($requiredToolType === null) {
            return ['ok' => true, 'message' => ''];
        }

        $toolLabel = Item::TOOL_TYPE_LABELS[$requiredToolType];

        $hasSlot = $player->hasToolSlot($requiredToolType)
            || \in_array($requiredToolType, $this->playerActionHelper->getUnlockedToolSlots(), true);

        if ($hasSlot && !$player->hasToolSlot($requiredToolType)) {
            $player->unlockToolSlot($requiredToolType);
            $this->entityManager->flush();
        }

        if (!$hasSlot) {
            return ['ok' => false, 'message' => "Vous devez débloquer l'emplacement de {$toolLabel} via l'arbre de compétences."];
        }

        $tool = $this->gearHelper->getEquippedToolByType($requiredToolType);
        if ($tool === null) {
            return ['ok' => false, 'message' => "Équipez {$toolLabel} dans votre emplacement d'outil pour fabriquer."];
        }

        if ($tool->getCurrentDurability() !== null && $tool->getCurrentDurability() <= 0) {
            return ['ok' => false, 'message' => 'Votre outil est cassé. Réparez-le avant de continuer.'];
        }

        $toolSlug = $tool->getGenericItem()->getSlug();
        if (!$this->playerActionHelper->canEquipTool($toolSlug)) {
            return ['ok' => false, 'message' => "Vous n'avez pas la compétence requise pour utiliser cet outil."];
        }

        return ['ok' => true, 'message' => ''];
    }

    /**
     * Calcule le nombre maximum de fois qu'un joueur peut fabriquer une recette.
     */
    public function maxCraftable(Player $player, Recipe $recipe): int
    {
        $bagItems = $this->getBagItemsBySlug($player);
        $max = PHP_INT_MAX;

        foreach ($recipe->getIngredients() as $ingredient) {
            $slug = $ingredient['slug'];
            $requiredQty = $ingredient['quantity'] ?? 1;
            $available = $bagItems[$slug] ?? 0;

            $possibleForIngredient = (int) floor($available / $requiredQty);
            $max = min($max, $possibleForIngredient);
        }

        return $max === PHP_INT_MAX ? 0 : $max;
    }

    /**
     * Qualite obtenue par ce joueur sur cette recette, tirage compris.
     *
     * Publique depuis ECO-20 : les commandes de craft doivent produire une
     * qualite par la **meme** formule que l'etabli. La dupliquer aurait laisse
     * les deux voies diverger au premier reglage d'equilibrage.
     */
    public function computeQuality(Player $player, Recipe $recipe): string
    {
        $craft = $recipe->getCraft();

        // FOY-07 : le lieu compte. Le bonus d'atelier du foyer s'ajoute a celui
        // de la specialisation, dans la meme unite — c'est ce qui fait du
        // deplacement une decision plutot qu'une formalite. Il se lit sur la
        // **zone courante** : il n'y a pas d'atelier a distance.
        return $this->qualityCalculator->calculateQuality(
            $recipe->getQuality() ?? QualityCalculator::QUALITY_NORMAL,
            $this->getCraftingLevel($player, $craft),
            $this->craftSpecializationService->getQualityBonusFor($player, $craft)
                + $this->workshopBonus->bonusFor($player->getCurrentZone(), $craft)
        );
    }

    /**
     * Verifie qu'un artisan peut entreprendre cette recette **maintenant**.
     *
     * @return array{ok: bool, message: string}
     */
    public function checkCanStart(Player $player, Recipe $recipe): array
    {
        $toolCheck = $this->checkCraftTool($player, $recipe->getCraft());
        if (!$toolCheck['ok']) {
            return $toolCheck;
        }

        $requiredSpec = $recipe->getRequiredSpecialization();
        if ($requiredSpec !== null && $player->getCraftSpecialization() !== $requiredSpec) {
            return ['ok' => false, 'message' => sprintf('Cette recette est reservee aux %s.', $requiredSpec->label())];
        }

        // ECO-20 : l'ecran ne proposait que les recettes disponibles, mais rien
        // ne verifiait le niveau ni le plan **a l'execution** — une requete
        // forgee suffisait a fabriquer n'importe quoi. Le filtre d'affichage
        // n'est pas une regle metier.
        if (!$this->isRecipeUnlocked($player, $recipe)) {
            return ['ok' => false, 'message' => 'Vous n\'avez pas appris cette recette.'];
        }

        return ['ok' => true, 'message' => ''];
    }

    public function getActiveJob(Player $player): ?CraftJob
    {
        return $this->craftJobRepository->findActiveForPlayer($player);
    }

    /**
     * Met une fabrication en chantier (ECO-20).
     *
     * Les ingredients sont consommes **au depart**, comme l'escrow d'une
     * commande : sans cela, un artisan lancerait un travail puis revendrait sa
     * matiere avant de recuperer l'objet.
     *
     * @return array{success: bool, job: ?CraftJob, message: string}
     */
    public function startCraft(Player $player, Recipe $recipe, int $quantity = 1): array
    {
        $quantity = max(1, $quantity);

        if (null !== $this->getActiveJob($player)) {
            return ['success' => false, 'job' => null, 'message' => 'Votre etabli est deja occupe.'];
        }

        $check = $this->checkCanStart($player, $recipe);
        if (!$check['ok']) {
            return ['success' => false, 'job' => null, 'message' => $check['message']];
        }

        $affordable = min($quantity, $this->maxCraftable($player, $recipe));
        if ($affordable < 1) {
            $missing = array_map(
                fn (array $m) => sprintf('%s (%d/%d)', $m['slug'], $m['have'], $m['need']),
                $this->canCraft($player, $recipe)['missing']
            );

            return ['success' => false, 'job' => null, 'message' => 'Ingredients manquants : ' . implode(', ', $missing)];
        }

        for ($i = 0; $i < $affordable; ++$i) {
            $this->removeIngredients($player, $recipe);
        }

        $this->wearCraftTool($recipe, $affordable);

        $job = new CraftJob();
        $job->setPlayer($player);
        $job->setRecipe($recipe);
        $job->setQuantity($affordable);
        // Un lot de dix occupe dix fois plus longtemps : c'est ce qui donne son
        // sens a la quantite, au lieu de dix crafts instantanes enchaines.
        $job->setReadyAt(new \DateTimeImmutable(sprintf('+%d seconds', max(1, $recipe->getCraftingTime()) * $affordable)));

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        return [
            'success' => true,
            'job' => $job,
            'message' => sprintf('Fabrication lancee : %dx %s dans %ds.', $affordable, $recipe->getResult()->getName(), $job->getRemainingSeconds()),
        ];
    }

    /**
     * Recupere le travail termine (ECO-20).
     *
     * La qualite est tiree **par piece** : un lot n'est pas homogene, et c'est
     * la que le savoir-faire de l'artisan se voit.
     *
     * @return array{success: bool, crafted: int, totalXp: int, message: string}
     */
    public function collectCraft(Player $player): array
    {
        $job = $this->getActiveJob($player);
        if (null === $job) {
            return ['success' => false, 'crafted' => 0, 'totalXp' => 0, 'message' => 'Aucune fabrication en cours.'];
        }

        if (!$job->isReady()) {
            return ['success' => false, 'crafted' => 0, 'totalXp' => 0, 'message' => sprintf('Encore %d seconde(s) de travail.', $job->getRemainingSeconds())];
        }

        $recipe = $job->getRecipe();
        $resultItem = $recipe->getResult();
        $units = $job->getQuantity() * max(1, $recipe->getResultQuantity());

        for ($i = 0; $i < $units; ++$i) {
            $playerItem = $this->playerItemGenerator->generateFromItemId($resultItem->getId());
            $playerItem->setCraftQuality($this->computeQuality($player, $recipe));
            $this->inventoryHelper->addItem($playerItem, false);
        }

        $totalXp = 0;
        for ($i = 0; $i < $job->getQuantity(); ++$i) {
            $totalXp += $this->grantCraftingXp($player, $recipe->getCraft(), $recipe->getXpReward());
        }

        $this->entityManager->remove($job);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new CraftEvent($player, $recipe, $resultItem, $units),
            CraftEvent::NAME
        );

        return [
            'success' => true,
            'crafted' => $units,
            'totalXp' => $totalXp,
            'message' => sprintf('Vous recuperez %dx %s (+%d XP).', $units, $resultItem->getName(), $totalXp),
        ];
    }

    /**
     * Reduit la durabilite de l'outil de craft, une fois par piece entreprise.
     */
    private function wearCraftTool(Recipe $recipe, int $times): void
    {
        $requiredToolType = Item::CRAFT_TOOL_TYPES[$recipe->getCraft()] ?? null;
        if ($requiredToolType === null) {
            return;
        }

        $craftTool = $this->gearHelper->getEquippedToolByType($requiredToolType);
        if ($craftTool !== null) {
            $craftTool->reduceDurability($times);
            $this->entityManager->persist($craftTool);
        }
    }

    /**
     * Fabrication immediate, reservee a l'experimentation (ECO-20).
     *
     * Depuis que l'etabli est temporise (`startCraft()` / `collectCraft()`),
     * c'est le **seul** chemin qui produise un objet sans attente — et il n'est
     * atteignable que par `ExperimentationManager`, ou la decouverte d'une
     * recette est elle-meme le cout. Aucune route ne l'expose : l'ouvrir
     * rouvrirait le contournement que ce jalon ferme.
     *
     * @return array{success: bool, item: ?Item, quality: ?string, message: string}
     */
    public function craft(Player $player, Recipe $recipe): array
    {
        // Vérifier l'outil de craft
        $toolCheck = $this->checkCraftTool($player, $recipe->getCraft());
        if (!$toolCheck['ok']) {
            return [
                'success' => false,
                'item' => null,
                'quality' => null,
                'message' => $toolCheck['message'],
            ];
        }

        // Vérifier la spécialisation requise (recettes exclusives task 122)
        $requiredSpec = $recipe->getRequiredSpecialization();
        if ($requiredSpec !== null && $player->getCraftSpecialization() !== $requiredSpec) {
            return [
                'success' => false,
                'item' => null,
                'quality' => null,
                'message' => sprintf('Cette recette est reservee aux %s.', $requiredSpec->label()),
            ];
        }

        // ECO-20 : l'ecran ne proposait que les recettes disponibles, mais rien
        // ne verifiait le niveau ni le plan **a l'execution** — une requete
        // forgee suffisait a fabriquer n'importe quoi. Le filtre d'affichage
        // n'est pas une regle metier.
        if (!$this->isRecipeUnlocked($player, $recipe)) {
            return [
                'success' => false,
                'item' => null,
                'quality' => null,
                'message' => 'Vous n\'avez pas appris cette recette.',
            ];
        }

        $check = $this->canCraft($player, $recipe);

        if (!$check['possible']) {
            $missingNames = array_map(
                fn (array $m) => sprintf('%s (%d/%d)', $m['slug'], $m['have'], $m['need']),
                $check['missing']
            );

            return [
                'success' => false,
                'item' => null,
                'quality' => null,
                'message' => 'Ingredients manquants : ' . implode(', ', $missingNames),
            ];
        }

        // Retirer les ingredients de l'inventaire
        $this->removeIngredients($player, $recipe);

        $finalQuality = $this->computeQuality($player, $recipe);

        // Creer l'item resultat
        $resultItem = $recipe->getResult();
        $lastPlayerItem = null;

        for ($i = 0; $i < $recipe->getResultQuantity(); ++$i) {
            $playerItem = $this->playerItemGenerator->generateFromItemId($resultItem->getId());
            // ECO-20 : la qualite calculee survit desormais au craft. Elle etait
            // affichee une fois dans le message de retour, puis perdue.
            $playerItem->setCraftQuality($finalQuality);
            $this->inventoryHelper->addItem($playerItem, false);
            $lastPlayerItem = $playerItem;
        }

        // Réduire la durabilité de l'outil de craft
        $requiredToolType = Item::CRAFT_TOOL_TYPES[$recipe->getCraft()] ?? null;
        if ($requiredToolType !== null) {
            $craftTool = $this->gearHelper->getEquippedToolByType($requiredToolType);
            if ($craftTool !== null) {
                $craftTool->reduceDurability(1);
                $this->entityManager->persist($craftTool);
            }
        }

        // Accorder l'XP de domaine (avec bonus evenement)
        $grantedXp = $this->grantCraftingXp($player, $recipe->getCraft(), $recipe->getXpReward());

        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new CraftEvent($player, $recipe, $resultItem, $recipe->getResultQuantity()),
            CraftEvent::NAME
        );

        $qualityLabel = QualityCalculator::getQualityLabel($finalQuality);

        return [
            'success' => true,
            'item' => $resultItem,
            'quality' => $finalQuality,
            'message' => sprintf(
                'Vous avez fabrique : %s x%d (Qualite : %s, +%d XP)',
                $resultItem->getName(),
                $recipe->getResultQuantity(),
                $qualityLabel,
                $grantedXp
            ),
        ];
    }

    /**
     * Retourne le niveau d'artisanat du joueur pour un type de craft donne.
     * Calcule a partir de l'XP totale du domaine correspondant.
     */
    public function getCraftingLevel(Player $player, string $craft): int
    {
        foreach ($player->getDomainExperiences() as $domainExperience) {
            $domain = $domainExperience->getDomain();
            $domainSlug = strtolower(str_replace(' ', '-', $domain->getTitle()));

            if ($domainSlug === $craft) {
                // Conversion XP -> niveau : chaque 100 XP = 1 niveau
                return (int) floor($domainExperience->getTotalExperience() / 100) + 1;
            }
        }

        return 1;
    }

    /**
     * Accorde de l'XP de craft au joueur dans le domaine correspondant.
     *
     * Publique depuis ECO-07 : une commande de craft est du travail d'atelier
     * comme un autre, elle doit faire progresser l'artisan. Dupliquer le calcul
     * cote commandes aurait fait diverger les deux voies de progression.
     */
    public function grantCraftingXp(Player $player, string $craft, int $xpAmount): int
    {
        $xpMultiplier = $this->gameEventBonusProvider->getXpMultiplier($player->getMap());
        $finalXp = (int) round($xpAmount * $xpMultiplier);

        foreach ($player->getDomainExperiences() as $domainExperience) {
            $domain = $domainExperience->getDomain();
            $domainSlug = strtolower(str_replace(' ', '-', $domain->getTitle()));

            if ($domainSlug === $craft) {
                $oldLevel = $domainExperience->getLevel();
                $domainExperience->setTotalExperience(
                    $domainExperience->getTotalExperience() + $finalXp
                );
                $this->entityManager->persist($domainExperience);

                $newLevel = $domainExperience->getLevel();
                if ($newLevel > $oldLevel) {
                    $this->eventDispatcher->dispatch(
                        new DomainLevelUpEvent($player, $domain, $oldLevel, $newLevel),
                        DomainLevelUpEvent::NAME
                    );
                }

                return $finalXp;
            }
        }

        return $finalXp;
    }

    /**
     * Retire les ingredients de l'inventaire du joueur.
     */
    private function removeIngredients(Player $player, Recipe $recipe): void
    {
        $inventory = null;
        foreach ($player->getInventories() as $inv) {
            if ($inv->isBag()) {
                $inventory = $inv;
                break;
            }
        }

        if ($inventory === null) {
            throw new \RuntimeException('Inventaire non trouve.');
        }

        foreach ($recipe->getIngredients() as $ingredient) {
            $slug = $ingredient['slug'];
            $remainingToRemove = $ingredient['quantity'] ?? 1;

            foreach ($inventory->getItems()->toArray() as $playerItem) {
                if ($remainingToRemove <= 0) {
                    break;
                }

                if ($playerItem->getGenericItem()->getSlug() === $slug) {
                    $inventory->removeItem($playerItem);
                    $playerItem->setInventory(null);
                    $this->entityManager->remove($playerItem);
                    --$remainingToRemove;
                }
            }
        }
    }

    /**
     * Compte les items du sac par slug.
     *
     * @return array<string, int>
     */
    private function getBagItemsBySlug(Player $player): array
    {
        $counts = [];

        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }
            foreach ($inventory->getItems() as $playerItem) {
                $slug = $playerItem->getGenericItem()->getSlug();
                $counts[$slug] = ($counts[$slug] ?? 0) + 1;
            }
        }

        return $counts;
    }
}
