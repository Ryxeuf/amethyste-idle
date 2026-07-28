<?php

namespace App\Controller\Game;

use App\Enum\WeeklyCommissionReward;
use App\GameEngine\Retention\WeeklyCommissionDelivery;
use App\GameEngine\Retention\WeeklyCommissionException;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Livraison de la commission de la semaine (RET-02b).
 *
 * Un seul point d'entree, et il est **sur place** : la commission se livre dans
 * la zone visee, depuis l'ecran de zone. Sans ce deplacement, la zone de
 * livraison ne serait qu'une decoration sur une carte.
 */
class WeeklyCommissionController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly WeeklyCommissionDelivery $delivery,
    ) {
    }

    #[Route('/game/commission/deliver', name: 'app_game_commission_deliver', methods: ['POST'])]
    public function deliver(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('commission_deliver', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.zone.travel.error.invalid_token');

            return $this->redirectToRoute('app_game_zone');
        }

        $reward = WeeklyCommissionReward::tryFrom((string) $request->request->get('reward'));
        if (null === $reward) {
            // Aucun repli silencieux sur une recompense par defaut : le choix est
            // le point de la brique, et en choisir un a la place du joueur lui
            // retirerait la seule decision que la livraison lui demande.
            $this->addFlash('error', 'game.commission.error.unknown_reward');

            return $this->redirectToRoute('app_game_zone');
        }

        try {
            $result = $this->delivery->deliver($player, $reward);
        } catch (WeeklyCommissionException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_game_zone');
        }

        $this->addFlash('commission_result', [
            'reward' => $result->reward->value,
            'grains' => $result->grainsDeposited,
            'gils' => $result->gils,
            'energy' => $result->energy,
        ]);

        return $this->redirectToRoute('app_game_zone');
    }
}
