<?php

namespace App\Controller\Game;

use App\Entity\App\TimeTrial;
use App\GameEngine\Zone\NotEnoughActionEnergyException;
use App\GameEngine\Zone\TimeTrialService;
use App\GameEngine\Zone\ZoneActionException;
use App\Helper\PlayerHelper;
use App\Repository\TimeTrialRepository;
use App\Repository\TimeTrialRunRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Parcours chronometres (tache 133).
 */
#[Route('/game/time-trials')]
#[IsGranted('ROLE_USER')]
class TimeTrialController extends AbstractController
{
    private const int BOARD_LIMIT = 20;

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly TimeTrialService $timeTrialService,
        private readonly TimeTrialRepository $trialRepository,
        private readonly TimeTrialRunRepository $runRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('', name: 'app_game_time_trials', methods: ['GET'])]
    public function index(): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        // Constat paresseux du depassement, comme l'arrivee de voyage : une
        // tentative oubliee ne doit pas exiger un cron pour liberer le joueur.
        $run = $this->timeTrialService->settleRunning($player);
        $now = new \DateTimeImmutable();

        $trials = [];
        foreach ($this->trialRepository->findEnabled() as $trial) {
            $trials[] = [
                'trial' => $trial,
                'personalBest' => $this->runRepository->findPersonalBest($player, $trial),
                'leaderboard' => $this->runRepository->findLeaderboard($trial, self::BOARD_LIMIT),
                'canStart' => null === $run && $player->getCurrentZone()?->getSlug() === $trial->getStartZone()->getSlug(),
            ];
        }

        return $this->render('game/time_trial/index.html.twig', [
            'player' => $player,
            'trials' => $trials,
            'run' => $run,
            'runElapsed' => $run?->elapsedSecondsAt($now),
            'zone' => $player->getCurrentZone(),
        ]);
    }

    #[Route('/{id}/start', name: 'app_game_time_trial_start', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function start(TimeTrial $trial, Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('time_trial_start_' . $trial->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_time_trials');
        }

        try {
            $this->timeTrialService->start($player, $trial);
            $this->addFlash('success', $this->translator->trans('game.time_trial.started', ['%trial%' => $trial->getName()]));
        } catch (ZoneActionException|NotEnoughActionEnergyException $e) {
            $this->addFlash('error', $this->translator->trans($e->getMessage()));
        }

        return $this->redirectToRoute('app_game_time_trials');
    }

    #[Route('/abandon', name: 'app_game_time_trial_abandon', methods: ['POST'])]
    public function abandon(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('time_trial_abandon', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_time_trials');
        }

        try {
            $this->timeTrialService->abandon($player);
            $this->addFlash('success', $this->translator->trans('game.time_trial.abandoned'));
        } catch (ZoneActionException $e) {
            $this->addFlash('error', $this->translator->trans($e->getMessage()));
        }

        return $this->redirectToRoute('app_game_time_trials');
    }
}
