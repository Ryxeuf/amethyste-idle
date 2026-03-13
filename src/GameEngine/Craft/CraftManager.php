<?php

namespace App\GameEngine\Craft;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\CraftRecipe;
use App\Entity\Game\Item;
use App\Event\Game\CraftEvent;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\Helper\InventoryHelper;
use App\Helper\PlayerDomainHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CraftManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerDomainHelper $playerDomainHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Retourne les recettes disponibles pour un joueur et une profession donnée.
     *
     * @return CraftRecipe[]
     */
    public function getAvailableRecipes(Player $player, string $profession): array
    {
        $recipes = $this->entityManager->getRepository(CraftRecipe::class)->findBy([
            'profession' => $profession,
        ]);

        return array_filter($recipes, function (CraftRecipe $recipe) use ($player) {
            // Recettes non découvrables sont toujours visibles
            if (!$recipe->isDiscoverable()) {
                return true;
            }

            // Les recettes découvrables ne sont visibles que si découvertes
            return $recipe->isDiscovered();
        });
    }

    /**
     * Vérifie si le joueur peut fabriquer la recette.
     */
    public function canCraft(Player $player, CraftRecipe $recipe): bool
    {
        // Vérifier le skill requis
        if ($recipe->getRequiredSkillSlug() !== null) {
            $hasSkill = false;
            foreach ($player->getSkills() as $skill) {
                if ($skill->getSlug() === $recipe->getRequiredSkillSlug()) {
                    $hasSkill = true;
                    break;
                }
            }
            if (!$hasSkill) {
                return false;
            }
        }

        // Vérifier le niveau de domaine requis
        if ($recipe->getRequiredLevel() > 1) {
            $domainXp = $this->getPlayerProfessionLevel($player, $recipe->getProfession());
            if ($domainXp < $recipe->getRequiredLevel()) {
                return false;
            }
        }

        // Vérifier les ingrédients
        return $this->hasIngredients($player, $recipe);
    }

    /**
     * Exécute la fabrication.
     */
    public function craft(Player $player, CraftRecipe $recipe): CraftResult
    {
        // 1. Vérifier les ingrédients
        if (!$this->hasIngredients($player, $recipe)) {
            throw new \RuntimeException('Ingrédients insuffisants.');
        }

        // 2. Retirer les ingrédients de l'inventaire
        $this->removeIngredients($player, $recipe);

        // 3. Déterminer la qualité
        $skillLevel = $this->getPlayerProfessionLevel($player, $recipe->getProfession());
        $quality = CraftQuality::rollQuality($skillLevel);

        // 4. Créer l'item résultat
        $resultItem = $this->entityManager->getRepository(Item::class)->findOneBy([
            'slug' => $recipe->getResultItemSlug(),
        ]);

        if ($resultItem === null) {
            throw new \RuntimeException("Item résultat '{$recipe->getResultItemSlug()}' introuvable.");
        }

        $lastPlayerItem = null;
        for ($i = 0; $i < $recipe->getResultQuantity(); $i++) {
            $playerItem = $this->playerItemGenerator->generateFromItemId($resultItem->getId());
            $this->inventoryHelper->addItem($playerItem, false);
            $lastPlayerItem = $playerItem;
        }

        // 5. Sauvegarder
        $this->entityManager->flush();

        // 6. Dispatch event pour l'XP
        $this->eventDispatcher->dispatch(
            new CraftEvent($player, $recipe, $quality->value, $recipe->getExperienceGain()),
            CraftEvent::NAME
        );

        return new CraftResult(
            item: $lastPlayerItem,
            quality: $quality->value,
            experienceGained: $recipe->getExperienceGain(),
        );
    }

    /**
     * Expérimentation alchimiste : vérifie si la combinaison d'items correspond à une recette découvrable.
     *
     * @param string[] $itemSlugs
     */
    public function experiment(Player $player, array $itemSlugs): ?CraftRecipe
    {
        sort($itemSlugs);

        $discoverableRecipes = $this->entityManager->getRepository(CraftRecipe::class)->findBy([
            'isDiscoverable' => true,
            'isDiscovered' => false,
            'profession' => 'alchemist',
        ]);

        foreach ($discoverableRecipes as $recipe) {
            $recipeSlugs = array_map(
                fn(array $ingredient) => $ingredient['item_slug'],
                $recipe->getIngredients()
            );
            sort($recipeSlugs);

            if ($recipeSlugs === $itemSlugs) {
                // Vérifier que le joueur a les quantités nécessaires
                if ($this->hasIngredients($player, $recipe)) {
                    $recipe->setIsDiscovered(true);
                    $this->entityManager->persist($recipe);
                    $this->entityManager->flush();

                    return $recipe;
                }
            }
        }

        return null;
    }

    /**
     * Vérifie si le joueur possède tous les ingrédients d'une recette.
     */
    public function hasIngredients(Player $player, CraftRecipe $recipe): bool
    {
        $bagItems = $this->getBagItemsBySlug($player);

        foreach ($recipe->getIngredients() as $ingredient) {
            $slug = $ingredient['item_slug'];
            $requiredQty = $ingredient['quantity'] ?? 1;

            $available = $bagItems[$slug] ?? 0;
            if ($available < $requiredQty) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retire les ingrédients de l'inventaire du joueur.
     */
    private function removeIngredients(Player $player, CraftRecipe $recipe): void
    {
        $inventory = null;
        foreach ($player->getInventories() as $inv) {
            if ($inv->isBag()) {
                $inventory = $inv;
                break;
            }
        }

        if ($inventory === null) {
            throw new \RuntimeException('Inventaire non trouvé.');
        }

        foreach ($recipe->getIngredients() as $ingredient) {
            $slug = $ingredient['item_slug'];
            $remainingToRemove = $ingredient['quantity'] ?? 1;

            foreach ($inventory->getItems()->toArray() as $playerItem) {
                if ($remainingToRemove <= 0) {
                    break;
                }

                if ($playerItem->getGenericItem()->getSlug() === $slug) {
                    $inventory->removeItem($playerItem);
                    $playerItem->setInventory(null);
                    $this->entityManager->remove($playerItem);
                    $remainingToRemove--;
                }
            }
        }
    }

    /**
     * Retourne le niveau de profession (XP totale du domaine associé).
     */
    private function getPlayerProfessionLevel(Player $player, string $profession): int
    {
        foreach ($player->getDomainExperiences() as $domainExperience) {
            $domain = $domainExperience->getDomain();
            $domainSlug = strtolower(str_replace(' ', '-', $domain->getTitle()));

            if ($domainSlug === $profession) {
                return $domainExperience->getTotalExperience();
            }
        }

        return 0;
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
