<?php

namespace App\Controller\Admin;

use App\Entity\App\Pnj;
use App\Form\Admin\PnjType;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pnjs', name: 'admin_pnj_')]
#[IsGranted('ROLE_ADMIN')]
class PnjController extends AbstractController
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
        $qb = $this->em->getRepository(Pnj::class)->createQueryBuilder('p');

        if ($search) {
            $qb->where('LOWER(p.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $qb->orderBy('p.name', 'ASC');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $total = (int) (clone $qb)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $pnjs = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/pnj/index.html.twig', [
            'pnjs' => $pnjs,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $pnj = new Pnj();
        $form = $this->createForm(PnjType::class, $pnj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dialogJson = $form->get('dialogJson')->getData();
            $pnj->setDialog($dialogJson ? json_decode($dialogJson, true) ?? [] : []);

            $this->em->persist($pnj);
            $this->em->flush();
            $this->adminLogger->log('create', 'Pnj', $pnj->getId(), $pnj->getName());
            $this->addFlash('success', 'PNJ "' . $pnj->getName() . '" cree avec succes.');

            return $this->redirectToRoute('admin_pnj_index');
        }

        return $this->render('admin/pnj/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, Pnj $pnj): Response
    {
        $form = $this->createForm(PnjType::class, $pnj);

        $form->get('dialogJson')->setData(
            json_encode($pnj->getDialog(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dialogJson = $form->get('dialogJson')->getData();
            $pnj->setDialog($dialogJson ? json_decode($dialogJson, true) ?? [] : []);

            $this->em->flush();
            $this->adminLogger->log('update', 'Pnj', $pnj->getId(), $pnj->getName());
            $this->addFlash('success', 'PNJ "' . $pnj->getName() . '" modifie avec succes.');

            return $this->redirectToRoute('admin_pnj_index');
        }

        return $this->render('admin/pnj/edit.html.twig', [
            'pnj' => $pnj,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Pnj $pnj): Response
    {
        if ($this->isCsrfTokenValid('delete' . $pnj->getId(), $request->request->get('_token'))) {
            $name = $pnj->getName();
            $this->em->remove($pnj);
            $this->em->flush();
            $this->adminLogger->log('delete', 'Pnj', null, $name);
            $this->addFlash('success', 'PNJ supprime avec succes.');
        }

        return $this->redirectToRoute('admin_pnj_index');
    }
}
