<?php

namespace App\Controller\Game;

use App\GameEngine\Tutorial\TutorialManager;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/tutorial')]
class TutorialController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly TutorialManager $tutorialManager,
    ) {
    }

    #[Route('/skip', name: 'app_game_tutorial_skip', methods: ['POST'])]
    public function skip(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        if (!$player) {
            return $this->redirectToRoute('app_home');
        }

        if (!$this->isCsrfTokenValid('tutorial_skip', $request->request->get('_token'))) {
            return $this->redirectToRoute('app_game');
        }

        $this->tutorialManager->skip($player);

        return $this->redirectToRoute('app_game');
    }

    #[Route('/status', name: 'app_game_tutorial_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        if (!$player) {
            return new JsonResponse(['error' => 'No player'], Response::HTTP_NOT_FOUND);
        }

        $step = $this->tutorialManager->getCurrentStep($player);

        return new JsonResponse([
            'inTutorial' => null !== $step,
            'step' => $step?->value,
            'label' => $step?->label(),
            'objective' => $step?->objective(),
            'stepNumber' => $step?->stepNumber(),
            'totalSteps' => $step ? $step::totalSteps() : null,
        ]);
    }
}
