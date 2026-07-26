<?php

namespace App\GameEngine\Shop;

use App\Entity\App\PlayerShop;
use App\Entity\App\ShopListing;
use App\Entity\Game\Recipe;
use App\Enum\ShopStatus;
use App\GameEngine\Crafting\CraftingManager;
use App\Repository\PlayerShopRepository;
use App\Repository\RecipeRepository;
use App\Repository\ShopListingRepository;

/**
 * Recherche transversale du marche joueur (ECO-12b).
 *
 * « Qui vend l'objet X, et dans quelle zone ? » — et, quand personne ne le
 * vend, « qui saurait me le fabriquer ? ».
 *
 * Les deux moities repondent a la meme question sous deux angles, et c'est
 * pour cela qu'elles vivent dans le meme service. Un marche joueur ou l'on ne
 * trouve **ni** l'objet **ni** l'artisan est un marche qu'on cesse de
 * consulter : l'echoppe vide doit renvoyer vers la commande, sinon la
 * decouvrabilite s'arrete au premier resultat vide.
 *
 * Les deux requetes vivent dans leur **depot** respectif, pas ici. La lecon
 * d'ECO-16b vaut aussi pour le code neuf : un service qui construit ses
 * requetes lui-meme ne se teste qu'en simulant le constructeur de requetes
 * Doctrine — ce qui est le signe qu'elles sont mal placees, pas qu'il faut un
 * meilleur mock.
 */
class ShopSearchService
{
    public const RESULT_LIMIT = 30;

    public function __construct(
        private readonly ShopListingRepository $listingRepository,
        private readonly RecipeRepository $recipeRepository,
        private readonly PlayerShopRepository $shopRepository,
        private readonly CraftingManager $craftingManager,
    ) {
    }

    /**
     * Lots en vente dont l'objet correspond a la recherche.
     *
     * Seules les echoppes **ouvertes** repondent : un rideau baisse ne doit pas
     * faire esperer un achat impossible.
     *
     * @return ShopListing[]
     */
    public function findOnSale(string $query): array
    {
        $query = trim($query);
        if ('' === $query) {
            return [];
        }

        return $this->listingRepository->searchOnSale($query, self::RESULT_LIMIT);
    }

    /**
     * Artisans capables de fabriquer un objet correspondant a la recherche.
     *
     * Le critere est **le niveau de metier du tenancier**, pas le plan appris :
     * un plan est une information privee, et l'exposer reviendrait a publier la
     * feuille de progression de chaque joueur. Le niveau suffit a orienter une
     * commande — c'est ensuite a l'artisan d'accepter ou non.
     *
     * @return list<array{shop: PlayerShop, recipe: Recipe, level: int}>
     */
    public function findCrafters(string $query): array
    {
        $query = trim($query);
        if ('' === $query) {
            return [];
        }

        $recipes = $this->recipeRepository->searchByName($query, self::RESULT_LIMIT);
        if ([] === $recipes) {
            return [];
        }

        $shops = $this->shopRepository->findBy(['status' => ShopStatus::Open]);

        $matches = [];
        foreach ($recipes as $recipe) {
            foreach ($shops as $shop) {
                $level = $this->craftingManager->getCraftingLevel($shop->getOwner(), $recipe->getCraft());
                if ($level < $recipe->getRequiredLevel()) {
                    continue;
                }

                $matches[] = ['shop' => $shop, 'recipe' => $recipe, 'level' => $level];
            }
        }

        // Le plus competent d'abord : a commande egale, on s'adresse au meilleur.
        usort($matches, static fn (array $a, array $b) => $b['level'] <=> $a['level']);

        return \array_slice($matches, 0, self::RESULT_LIMIT);
    }
}
