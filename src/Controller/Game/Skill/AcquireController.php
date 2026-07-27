<?php

namespace App\Controller\Game\Skill;

use App\Entity\Game\Skill;
use App\GameEngine\Progression\SkillAcquiring;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/game/skills/acquire', name: 'app_game_skill_acquire', methods: ['POST'])]
class AcquireController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SkillAcquiring $skillAcquiring,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $skill = $this->entityManager->getRepository(Skill::class)->find($request->request->getInt('skill_id'));

        if (null === $skill) {
            $this->addFlash('error', $this->translator->trans('game.skills.acquire.not_found'));

            return $this->redirectToRoute('app_game_skills');
        }

        // Le domaine n'etait la que pour etre valide : l'acquisition credite tous
        // les domaines de la competence, elle n'a que faire de celui d'ou vient
        // le clic. Une competence rattachee a un domaine absent du formulaire
        // faisait echouer la requete pour rien.
        $result = $this->skillAcquiring->acquireSkill($skill);

        if ($result->acquired) {
            $this->addFlash('success', $this->translator->trans('game.skills.acquire.success'));
        } else {
            // Un refus muet accompagne d'un message de reussite est pire qu'une
            // erreur : le joueur clique en boucle sans savoir ce qui manque.
            $this->addFlash('warning', $this->translator->trans('game.skills.acquire.refused.' . $result->refusal));
        }

        return $this->redirectToRoute('app_game_skills');
    }
}
