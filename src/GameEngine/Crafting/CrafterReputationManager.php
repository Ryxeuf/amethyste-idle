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

    /**
     * Une non-livraison coute plus cher qu'une livraison ne rapporte.
     *
     * Sinon accaparer des commandes serait rentable en moyenne : l'artisan
     * prendrait tout et ne livrerait que le plus lucratif, en absorbant la
     * sanction avec ce qu'il a gagne ailleurs.
     */
    public const FAILURE_MULTIPLIER = 2;

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

    /**
     * Sanctionne un artisan qui a pris une commande sans la livrer (ECO-09).
     *
     * Sans cette contrepartie, accaparer les commandes du tableau serait gratuit
     * — et le classement d'ECO-08b deviendrait manipulable par la seule
     * inaction, un artisan pouvant assecher le tableau sans jamais rien risquer.
     */
    public function recordFailure(Player $crafter, CraftOrder $order): ?CrafterReputation
    {
        $reputation = $this->repository->findOneForPlayerAndCraft($crafter, $order->getRecipe()->getCraft());

        // Rien a sanctionner chez un artisan qui n'a jamais rien livre : creer
        // une reputation a zero pour la punir n'aurait aucun effet.
        $reputation?->recordFailure($this->pointsFor($order) * self::FAILURE_MULTIPLIER);

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
