<?php

namespace App\GameEngine\Shop;

use App\Entity\App\PlayerShop;
use App\Entity\App\ShopListing;
use App\Entity\Game\Recipe;
use App\Enum\ShopStatus;
use App\GameEngine\Crafting\CraftingManager;
use Doctrine\ORM\EntityManagerInterface;

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
 */
class ShopSearchService
{
    public const RESULT_LIMIT = 30;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
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

        return $this->entityManager->createQueryBuilder()
            ->select('l', 's', 'i')
            ->from(ShopListing::class, 'l')
            ->join('l.shop', 's')
            ->join('l.playerItem', 'pi')
            ->join('pi.genericItem', 'i')
            ->andWhere('s.status = :open')
            ->andWhere('LOWER(i.name) LIKE :needle')
            ->setParameter('open', ShopStatus::Open->value)
            ->setParameter('needle', '%' . mb_strtolower($query) . '%')
            ->orderBy('l.unitPrice', 'ASC')
            ->setMaxResults(self::RESULT_LIMIT)
            ->getQuery()
            ->getResult();
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

        /** @var Recipe[] $recipes */
        $recipes = $this->entityManager->createQueryBuilder()
            ->select('r', 'i')
            ->from(Recipe::class, 'r')
            ->join('r.result', 'i')
            ->andWhere('LOWER(i.name) LIKE :needle OR LOWER(r.name) LIKE :needle')
            ->setParameter('needle', '%' . mb_strtolower($query) . '%')
            ->setMaxResults(self::RESULT_LIMIT)
            ->getQuery()
            ->getResult();

        if ([] === $recipes) {
            return [];
        }

        /** @var PlayerShop[] $shops */
        $shops = $this->entityManager->getRepository(PlayerShop::class)->findBy(['status' => ShopStatus::Open]);

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
