<?php

namespace App\Controller\Admin;

use App\Entity\Game\Monster;
use App\Form\Admin\MonsterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/monsters', name: 'admin_monster_')]
class MonsterController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $qb = $this->em->getRepository(Monster::class)->createQueryBuilder('m');

        if ($search) {
            $qb->where('LOWER(m.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $qb->orderBy('m.name', 'ASC');
        $monsters = $qb->getQuery()->getResult();

        return $this->render('admin/monster/index.html.twig', [
            'monsters' => $monsters,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $monster = new Monster();
        $form = $this->createForm(MonsterType::class, $monster);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($monster);
            $this->em->flush();
            $this->addFlash('success', 'Monstre "' . $monster->getName() . '" cree avec succes.');

            return $this->redirectToRoute('admin_monster_index');
        }

        return $this->render('admin/monster/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, Monster $monster): Response
    {
        $form = $this->createForm(MonsterType::class, $monster);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Monstre "' . $monster->getName() . '" modifie avec succes.');

            return $this->redirectToRoute('admin_monster_index');
        }

        return $this->render('admin/monster/edit.html.twig', [
            'monster' => $monster,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Monster $monster): Response
    {
        if ($this->isCsrfTokenValid('delete' . $monster->getId(), $request->request->get('_token'))) {
            $this->em->remove($monster);
            $this->em->flush();
            $this->addFlash('success', 'Monstre supprime avec succes.');
        }

        return $this->redirectToRoute('admin_monster_index');
    }
}
