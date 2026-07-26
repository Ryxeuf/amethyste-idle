<?php

namespace App\GameEngine\Crafting;

use App\Entity\App\CrafterReputation;
use App\Entity\App\CraftOrder;
use App\Entity\App\Player;
use App\Repository\CrafterReputationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Reputation d'artisan (ECO-08b).
 *
 * Seule une commande **honoree** en accorde. Ni le craft a l'etabli ni la prise
 * en charge ne comptent : la reputation mesure les services rendus a d'autres
 * joueurs, et c'est precisement ce qui la rend informative pour un client.
 */
class CrafterReputationManager
{
    /**
     * Une commande de maitre ne vaut pas dix commandes de debutant.
     *
     * Les points suivent le palier de la recette : sans cela, la strategie
     * optimale serait d'enchainer les commandes les plus triviales, et le
     * classement remonterait les artisans les plus disponibles plutot que les
     * plus competents.
     */
    public const POINTS_PER_RECIPE_LEVEL = 2;

    /** Plancher : meme la commande la plus simple vaut d'avoir ete honoree. */
    public const MINIMUM_POINTS = 1;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CrafterReputationRepository $repository,
    ) {
    }

    public function recordDelivery(Player $crafter, CraftOrder $order): CrafterReputation
    {
        $craft = $order->getRecipe()->getCraft();
        $reputation = $this->repository->findOneForPlayerAndCraft($crafter, $craft);

        if (null === $reputation) {
            $reputation = new CrafterReputation();
            $reputation->setPlayer($crafter);
            $reputation->setCraft($craft);
            $this->entityManager->persist($reputation);
        }

        $reputation->recordDelivery($this->pointsFor($order));

        return $reputation;
    }

    public function pointsFor(CraftOrder $order): int
    {
        return max(
            self::MINIMUM_POINTS,
            $order->getRecipe()->getRequiredLevel() * self::POINTS_PER_RECIPE_LEVEL
        );
    }

    public function getPoints(Player $crafter, string $craft): int
    {
        return $this->repository->findOneForPlayerAndCraft($crafter, $craft)?->getPoints() ?? 0;
    }
}
