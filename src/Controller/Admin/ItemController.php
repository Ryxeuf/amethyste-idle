<?php

namespace App\Controller\Admin;

use App\Entity\Game\Item;
use App\Form\Admin\ItemType;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/items', name: 'admin_item_')]
#[IsGranted('ROLE_GAME_DESIGNER')]
class ItemController extends AbstractController
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
        $qb = $this->em->getRepository(Item::class)->createQueryBuilder('i');

        if ($search) {
            $qb->where('LOWER(i.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $qb->orderBy('i.name', 'ASC');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $total = (int) (clone $qb)->select('COUNT(i.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        $items = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/item/index.html.twig', [
            'items' => $items,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $item = new Item();
        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($item);
            $this->em->flush();
            $this->adminLogger->log('create', 'Item', $item->getId(), $item->getName());
            $this->addFlash('success', 'Item "' . $item->getName() . '" cree avec succes.');

            return $this->redirectToRoute('admin_item_index');
        }

        return $this->render('admin/item/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, Item $item): Response
    {
        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->adminLogger->log('update', 'Item', $item->getId(), $item->getName());
            $this->addFlash('success', 'Item "' . $item->getName() . '" modifie avec succes.');

            return $this->redirectToRoute('admin_item_index');
        }

        return $this->render('admin/item/edit.html.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Item $item): Response
    {
        if ($this->isCsrfTokenValid('delete' . $item->getId(), $request->request->get('_token'))) {
            $name = $item->getName();
            $this->em->remove($item);
            $this->em->flush();
            $this->adminLogger->log('delete', 'Item', null, $name);
            $this->addFlash('success', 'Item supprime avec succes.');
        }

        return $this->redirectToRoute('admin_item_index');
    }
}
