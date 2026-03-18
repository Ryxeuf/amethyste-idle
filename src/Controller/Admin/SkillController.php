<?php

namespace App\Controller\Admin;

use App\Entity\Game\Skill;
use App\Form\Admin\SkillType;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/skills', name: 'admin_skill_')]
#[IsGranted('ROLE_GAME_DESIGNER')]
class SkillController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminLogger $adminLogger,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $qb = $this->em->getRepository(Skill::class)->createQueryBuilder('s');

        if ($search) {
            $qb->where('LOWER(s.title) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $qb->orderBy('s.title', 'ASC');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $total = (int) (clone $qb)->select('COUNT(s.id)')->getQuery()->getSingleScalarResult();
        $skills = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/skill/index.html.twig', [
            'skills' => $skills,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $skill = new Skill();
        $form = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($skill);
            $this->em->flush();
            $this->adminLogger->log('create', 'Skill', $skill->getId(), $skill->getTitle());
            $this->addFlash('success', 'Talent "' . $skill->getTitle() . '" cree avec succes.');

            return $this->redirectToRoute('admin_skill_index');
        }

        return $this->render('admin/skill/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, Skill $skill): Response
    {
        $form = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->adminLogger->log('update', 'Skill', $skill->getId(), $skill->getTitle());
            $this->addFlash('success', 'Talent "' . $skill->getTitle() . '" modifie avec succes.');

            return $this->redirectToRoute('admin_skill_index');
        }

        return $this->render('admin/skill/edit.html.twig', [
            'skill' => $skill,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Skill $skill): Response
    {
        if ($this->isCsrfTokenValid('delete' . $skill->getId(), $request->request->get('_token'))) {
            $title = $skill->getTitle();
            $this->em->remove($skill);
            $this->em->flush();
            $this->adminLogger->log('delete', 'Skill', null, $title);
            $this->addFlash('success', 'Talent supprime avec succes.');
        }

        return $this->redirectToRoute('admin_skill_index');
    }
}
