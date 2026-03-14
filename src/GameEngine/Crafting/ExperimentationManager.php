<?php

namespace App\GameEngine\Crafting;

use App\Entity\App\Player;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\Helper\InventoryHelper;
use Doctrine\ORM\EntityManagerInterface;

class ExperimentationManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
        private readonly CraftingManager $craftingManager,
    ) {
    }

    /**
     * Experimentation : le joueur combine des items sans recette connue.
     *
     * Si la combinaison correspond a une recette cachee, elle est debloquee.
     * Sinon, certains materiaux sont consommes avec une chance de decouvrir un indice.
     *
     * @param string[] $itemSlugs
     *
     * @return array{success: bool, recipe: ?Recipe, item: ?Item, message: string}
     */
    public function experiment(Player $player, array $itemSlugs): array
    {
        if (empty($itemSlugs)) {
            return [
                'success' => false,
                'recipe' => null,
                'item' => null,
                'message' => 'Vous devez selectionner des ingredients pour experimenter.',
            ];
        }

        // Normaliser et trier les slugs pour la comparaison
        $itemSlugs = array_filter(array_map('trim', $itemSlugs));
        sort($itemSlugs);

        if (count($itemSlugs) < 2) {
            return [
                'success' => false,
                'recipe' => null,
                'item' => null,
                'message' => 'Vous devez combiner au moins 2 ingredients.',
            ];
        }

        // Verifier que le joueur possede les items
        $bagItems = $this->getBagItemsBySlug($player);
        foreach (array_count_values($itemSlugs) as $slug => $needed) {
            $available = $bagItems[$slug] ?? 0;
            if ($available < $needed) {
                return [
                    'success' => false,
                    'recipe' => null,
                    'item' => null,
                    'message' => sprintf('Vous ne possedez pas assez de "%s" (%d/%d).', $slug, $available, $needed),
                ];
            }
        }

        // Chercher une recette correspondante parmi toutes les recettes
        $allRecipes = $this->entityManager->getRepository(Recipe::class)->findAll();
        $discoveredRecipes = $player->getDiscoveredRecipes();

        foreach ($allRecipes as $recipe) {
            $recipeSlugs = array_map(
                fn (array $ingredient) => $ingredient['slug'],
                $recipe->getIngredients()
            );

            // Developper les quantites pour la comparaison
            $expandedRecipeSlugs = [];
            foreach ($recipe->getIngredients() as $ingredient) {
                $qty = $ingredient['quantity'] ?? 1;
                for ($i = 0; $i < $qty; ++$i) {
                    $expandedRecipeSlugs[] = $ingredient['slug'];
                }
            }
            sort($expandedRecipeSlugs);

            if ($expandedRecipeSlugs === $itemSlugs) {
                // La combinaison correspond a une recette
                $recipeSlug = $recipe->getSlug();

                if (in_array($recipeSlug, $discoveredRecipes, true)) {
                    // Recette deja decouverte : fabriquer directement
                    $result = $this->craftingManager->craft($player, $recipe);

                    return [
                        'success' => $result['success'],
                        'recipe' => $recipe,
                        'item' => $result['item'],
                        'message' => $result['message'],
                    ];
                }

                // Nouvelle decouverte
                $discoveredRecipes[] = $recipeSlug;
                $player->setDiscoveredRecipes($discoveredRecipes);
                $this->entityManager->persist($player);
                $this->entityManager->flush();

                return [
                    'success' => true,
                    'recipe' => $recipe,
                    'item' => null,
                    'message' => sprintf(
                        'Vous avez decouvert une nouvelle recette : %s ! Elle est maintenant disponible dans votre livre de recettes.',
                        $recipe->getName()
                    ),
                ];
            }
        }

        // Pas de correspondance : consommer une partie des materiaux (50% de chance)
        $consumed = false;
        if (random_int(1, 100) <= 50) {
            $this->consumeRandomIngredient($player, $itemSlugs);
            $consumed = true;
        }

        // Chance de decouvrir un indice (25%)
        $hint = null;
        if (random_int(1, 100) <= 25) {
            $hint = $this->generateHint($itemSlugs, $allRecipes);
        }

        $message = 'Cette combinaison ne donne rien...';
        if ($consumed) {
            $message .= ' Certains materiaux ont ete consommes dans le processus.';
        }
        if ($hint !== null) {
            $message .= ' ' . $hint;
        }

        $this->entityManager->flush();

        return [
            'success' => false,
            'recipe' => null,
            'item' => null,
            'message' => $message,
        ];
    }

    /**
     * Consomme un ingredient aleatoire de la liste fournie.
     *
     * @param string[] $itemSlugs
     */
    private function consumeRandomIngredient(Player $player, array $itemSlugs): void
    {
        $slugToRemove = $itemSlugs[array_rand($itemSlugs)];

        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }

            foreach ($inventory->getItems()->toArray() as $playerItem) {
                if ($playerItem->getGenericItem()->getSlug() === $slugToRemove) {
                    $inventory->removeItem($playerItem);
                    $playerItem->setInventory(null);
                    $this->entityManager->remove($playerItem);

                    return;
                }
            }
        }
    }

    /**
     * Genere un indice base sur les recettes existantes.
     *
     * @param string[] $usedSlugs
     * @param Recipe[] $allRecipes
     */
    private function generateHint(array $usedSlugs, array $allRecipes): ?string
    {
        foreach ($allRecipes as $recipe) {
            $recipeSlugs = array_map(
                fn (array $ingredient) => $ingredient['slug'],
                $recipe->getIngredients()
            );

            $overlap = array_intersect($usedSlugs, $recipeSlugs);

            if (!empty($overlap) && count($overlap) < count($recipeSlugs)) {
                $missingCount = count($recipeSlugs) - count($overlap);

                return sprintf(
                    'Indice : vous etes sur la bonne piste ! Il vous manque %d ingredient(s) pour une recette de %s.',
                    $missingCount,
                    $recipe->getCraft()
                );
            }
        }

        return null;
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
