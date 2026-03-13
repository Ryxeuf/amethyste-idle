<?php

namespace App\Controller\Admin;

use App\Entity\Game\Spell;
use App\Form\Admin\SpellType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/spells', name: 'admin_spell_')]
class SpellController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $qb = $this->em->getRepository(Spell::class)->createQueryBuilder('s');

        if ($search) {
            $qb->where('LOWER(s.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $qb->orderBy('s.name', 'ASC');
        $spells = $qb->getQuery()->getResult();

        return $this->render('admin/spell/index.html.twig', [
            'spells' => $spells,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $spell = new Spell();
        $form = $this->createForm(SpellType::class, $spell);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($spell);
            $this->em->flush();
            $this->addFlash('success', 'Sort "' . $spell->getName() . '" cree avec succes.');

            return $this->redirectToRoute('admin_spell_index');
        }

        return $this->render('admin/spell/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, Spell $spell): Response
    {
        $form = $this->createForm(SpellType::class, $spell);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Sort "' . $spell->getName() . '" modifie avec succes.');

            return $this->redirectToRoute('admin_spell_index');
        }

        return $this->render('admin/spell/edit.html.twig', [
            'spell' => $spell,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Spell $spell): Response
    {
        if ($this->isCsrfTokenValid('delete' . $spell->getId(), $request->request->get('_token'))) {
            $this->em->remove($spell);
            $this->em->flush();
            $this->addFlash('success', 'Sort supprime avec succes.');
        }

        return $this->redirectToRoute('admin_spell_index');
    }
}
