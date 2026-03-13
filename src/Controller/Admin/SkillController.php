<?php

namespace App\Controller\Admin;

use App\Entity\Game\Skill;
use App\Form\Admin\SkillType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/skills', name: 'admin_skill_')]
class SkillController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
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
        $skills = $qb->getQuery()->getResult();

        return $this->render('admin/skill/index.html.twig', [
            'skills' => $skills,
            'search' => $search,
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
            $this->em->remove($skill);
            $this->em->flush();
            $this->addFlash('success', 'Talent supprime avec succes.');
        }

        return $this->redirectToRoute('admin_skill_index');
    }
}
