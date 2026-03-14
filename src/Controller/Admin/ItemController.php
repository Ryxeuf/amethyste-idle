<?php

namespace App\Controller\Admin;

use App\Entity\Game\Item;
use App\Form\Admin\ItemType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/items', name: 'admin_item_')]
class ItemController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
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
        $items = $qb->getQuery()->getResult();

        return $this->render('admin/item/index.html.twig', [
            'items' => $items,
            'search' => $search,
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
            $this->em->remove($item);
            $this->em->flush();
            $this->addFlash('success', 'Item supprime avec succes.');
        }

        return $this->redirectToRoute('admin_item_index');
    }
}
