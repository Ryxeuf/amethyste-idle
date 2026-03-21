<?php

namespace App\Controller\Game\Skill;

use App\GameEngine\Progression\SkillRespecManager;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/skills/respec', name: 'app_game_skill_respec', methods: ['POST'])]
class RespecController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly SkillRespecManager $respecManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();

        if (!$player) {
            return $this->redirectToRoute('app_game_skills');
        }

        if (!$this->isCsrfTokenValid('respec', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');

            return $this->redirectToRoute('app_game_skills');
        }

        if ($this->respecManager->respec($player)) {
            $this->addFlash('success', 'Compétences redistribuées ! Tous vos points sont à nouveau disponibles.');
        } else {
            if ($player->getSkills()->isEmpty()) {
                $this->addFlash('error', 'Vous n\'avez aucune compétence à redistribuer.');
            } elseif ($player->getFight() !== null) {
                $this->addFlash('error', 'Impossible de redistribuer en combat.');
            } else {
                $cost = $this->respecManager->getRespecCost($player);
                $this->addFlash('error', sprintf('Fonds insuffisants. Il vous faut %d gils.', $cost));
            }
        }

        return $this->redirectToRoute('app_game_skills');
    }
}
