<?php

namespace App\Controller\Game\Skill;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\SkillAcquiring;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/skills/acquire', name: 'app_game_skill_acquire', methods: ['POST'])]
class AcquireController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly SkillAcquiring $skillAcquiring)
    {
    }

    public function __invoke(Request $request): Response
    {
        $skillId = $request->request->get('skill_id');
        $domainId = $request->request->get('domain_id');

        $skill = $this->entityManager->getRepository(Skill::class)->find($skillId);
        $domain = $this->entityManager->getRepository(Domain::class)->find($domainId);

        if (!$skill || !$domain) {
            $this->addFlash('error', 'Compétence ou domaine non trouvé');

            return $this->redirectToRoute('app_game_skills');
        }

        $this->skillAcquiring->acquireSkill($skill);

        $this->addFlash('success', 'Compétence acquise avec succès !');

        return $this->redirectToRoute('app_game_skills');
    }
}
