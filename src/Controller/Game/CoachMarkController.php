<?php

namespace App\Controller\Game;

use App\Enum\CoachMark;
use App\GameEngine\Onboarding\CoachMarkResolver;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Fermer un encart de coach (ONB-17).
 *
 * Une seule route pour les dix : l'encart se nomme dans l'URL, et rien d'autre
 * ne distingue une fermeture d'une autre. Dix routes auraient fait dix endroits
 * ou oublier de verifier que le joueur ferme bien **son** encart.
 */
#[Route('/game/coach')]
#[IsGranted('ROLE_USER')]
class CoachMarkController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly CoachMarkResolver $resolver,
    ) {
    }

    #[Route('/dismiss/{mark}', name: 'app_game_coach_dismiss', methods: ['POST'])]
    public function dismiss(string $mark): Response
    {
        $coachMark = CoachMark::tryFrom($mark);
        $player = $this->playerHelper->getPlayer();

        if (null === $coachMark || null === $player) {
            return new JsonResponse(['dismissed' => false], Response::HTTP_NOT_FOUND);
        }

        $this->resolver->dismiss($player, $coachMark);

        return new JsonResponse(['dismissed' => true]);
    }
}
