<?php

namespace App\Controller\Admin;

use App\Entity\Game\Spell;
use App\Form\Admin\SpellType;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/spells', name: 'admin_spell_')]
#[IsGranted('ROLE_GAME_DESIGNER')]
class SpellController extends AbstractController
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
        $qb = $this->em->getRepository(Spell::class)->createQueryBuilder('s');

        if ($search) {
            $qb->where('LOWER(s.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $qb->orderBy('s.name', 'ASC');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $total = (int) (clone $qb)->select('COUNT(s.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        $spells = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/spell/index.html.twig', [
            'spells' => $spells,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
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
            $this->adminLogger->log('create', 'Spell', $spell->getId(), $spell->getName());
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
            $this->adminLogger->log('update', 'Spell', $spell->getId(), $spell->getName());
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
            $name = $spell->getName();
            $this->em->remove($spell);
            $this->em->flush();
            $this->adminLogger->log('delete', 'Spell', null, $name);
            $this->addFlash('success', 'Sort supprime avec succes.');
        }

        return $this->redirectToRoute('admin_spell_index');
    }
}
