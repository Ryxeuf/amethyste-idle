<?php

namespace App\GameEngine\Crafting;

use App\Entity\App\Player;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Event\CraftEvent;
use App\GameEngine\Event\GameEventBonusProvider;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\Helper\InventoryHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CraftingManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
        private readonly PlayerHelper $playerHelper,
        private readonly QualityCalculator $qualityCalculator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly GameEventBonusProvider $gameEventBonusProvider,
    ) {
    }

    /**
     * Retourne les recettes disponibles pour un joueur et un type d'artisanat.
     *
     * @return Recipe[]
     */
    public function getAvailableRecipes(Player $player, string $craft): array
    {
        $recipes = $this->entityManager->getRepository(Recipe::class)->findBy([
            'craft' => $craft,
        ]);

        $craftingLevel = $this->getCraftingLevel($player, $craft);

        return array_filter($recipes, function (Recipe $recipe) use ($craftingLevel) {
            return $craftingLevel >= $recipe->getRequiredLevel();
        });
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
     * Execute la fabrication : consomme les ingredients, cree l'item, accorde l'XP.
     *
     * @return array{success: bool, item: ?Item, quality: ?string, message: string}
     */
    public function craft(Player $player, Recipe $recipe): array
    {
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

        // Determiner la qualite
        $baseQuality = $recipe->getQuality() ?? QualityCalculator::QUALITY_NORMAL;
        $skillLevel = $this->getCraftingLevel($player, $recipe->getCraft());
        $finalQuality = $this->qualityCalculator->calculateQuality($baseQuality, $skillLevel);

        // Creer l'item resultat
        $resultItem = $recipe->getResult();
        $lastPlayerItem = null;

        for ($i = 0; $i < $recipe->getResultQuantity(); ++$i) {
            $playerItem = $this->playerItemGenerator->generateFromItemId($resultItem->getId());
            $this->inventoryHelper->addItem($playerItem, false);
            $lastPlayerItem = $playerItem;
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
     */
    private function grantCraftingXp(Player $player, string $craft, int $xpAmount): int
    {
        $xpMultiplier = $this->gameEventBonusProvider->getXpMultiplier($player->getMap());
        $finalXp = (int) round($xpAmount * $xpMultiplier);

        foreach ($player->getDomainExperiences() as $domainExperience) {
            $domain = $domainExperience->getDomain();
            $domainSlug = strtolower(str_replace(' ', '-', $domain->getTitle()));

            if ($domainSlug === $craft) {
                $domainExperience->setTotalExperience(
                    $domainExperience->getTotalExperience() + $finalXp
                );
                $this->entityManager->persist($domainExperience);

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
