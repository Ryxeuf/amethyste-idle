<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Pnj;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\GameEngine\Economy\PurityPricer;
use Doctrine\ORM\EntityManagerInterface;

/**
 * A quoi sert une matiere (ONB-07b, palier 2 du catalogue).
 *
 * Le catalogue affichait le **badge** du palier « recettes » sans jamais
 * afficher les recettes : le palier se gagnait et ne montrait rien. Ce
 * service construit ce que le badge promettait — les recettes qui consomment
 * la matiere, les marchands qui la vendent, et ce qu'un guichet en donne.
 *
 * **Une seule paire de requetes pour tout l'ecran.** Les recettes et les
 * marchands sont charges une fois puis indexes par slug : le catalogue d'un
 * joueur avance compte des dizaines d'entrees, et une requete par ligne
 * transformerait un ecran de lecture en test de charge.
 *
 * Ce que ce service ne fait **pas** : il ne dit pas ou se recolte la matiere
 * (c'est le palier 1, et surtout l'information du prospecteur — RET-06), et
 * il ne cree aucune donnee. Il **lit** ce qui existe deja, ce qui est
 * exactement la borne d'une capacite de peuple : *elle touche ce qu'on sait,
 * jamais ce qu'on produit*.
 */
class ResourceUsesReader
{
    /**
     * @var array<string, list<array{recipe: Recipe, quantity: int}>>|null
     */
    private ?array $recipesByIngredient = null;

    /**
     * @var array<string, list<Pnj>>|null
     */
    private ?array $sellersBySlug = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PurityPricer $purityPricer,
    ) {
    }

    /**
     * Les usages d'une matiere : ce qu'on en fait, qui en vend, ce qu'elle
     * rend au rachat.
     *
     * @return array{recipes: list<array{recipe: Recipe, quantity: int}>, sellers: list<Pnj>, buybackValue: int}
     */
    public function forItem(Item $item): array
    {
        $slug = $item->getSlug();

        return [
            'recipes' => $this->recipesByIngredient()[$slug] ?? [],
            'sellers' => $this->sellersBySlug()[$slug] ?? [],
            // Le rachat commun porte la bande (MET-01) ; sans lot en main, la
            // valeur affichee est celle du prix de reference — ce que rend un
            // lot ordinaire, jamais une promesse sur la bande a venir.
            'buybackValue' => max(1, (int) (($item->getPrice() ?? 0) * PurityPricer::BUYBACK_RATE)),
        ];
    }

    /**
     * @return array<string, list<array{recipe: Recipe, quantity: int}>>
     */
    private function recipesByIngredient(): array
    {
        if ($this->recipesByIngredient !== null) {
            return $this->recipesByIngredient;
        }

        $index = [];
        /** @var list<Recipe> $recipes */
        $recipes = $this->entityManager->getRepository(Recipe::class)->findAll();

        foreach ($recipes as $recipe) {
            foreach ($recipe->getIngredients() as $ingredient) {
                $slug = $ingredient['slug'] ?? null;
                if (!\is_string($slug) || $slug === '') {
                    continue;
                }

                $index[$slug][] = [
                    'recipe' => $recipe,
                    'quantity' => (int) ($ingredient['quantity'] ?? 1),
                ];
            }
        }

        return $this->recipesByIngredient = $index;
    }

    /**
     * Les marchands qui vendent la matiere.
     *
     * `Pnj::shopItems` est une liste de ce qu'un marchand **vend** ; aucun ne
     * declare ce qu'il achete, parce que tout marchand rachete tout au taux
     * commun. « Ou en trouver » est donc l'information reelle, la ou « qui
     * l'achete » listerait tous les guichets du monde sans rien apprendre.
     *
     * @return array<string, list<Pnj>>
     */
    private function sellersBySlug(): array
    {
        if ($this->sellersBySlug !== null) {
            return $this->sellersBySlug;
        }

        $index = [];
        /** @var list<Pnj> $pnjs */
        $pnjs = $this->entityManager->getRepository(Pnj::class)->findAll();

        foreach ($pnjs as $pnj) {
            foreach ($pnj->getShopItems() ?? [] as $slug) {
                if (\is_string($slug) && $slug !== '') {
                    $index[$slug][] = $pnj;
                }
            }
        }

        return $this->sellersBySlug = $index;
    }
}
