<?php

namespace App\Controller\Admin;

use App\Entity\Game\MonsterItem;
use App\Form\Admin\MonsterItemType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/loot-tables', name: 'admin_loot_table_')]
#[IsGranted('ROLE_ADMIN')]
class LootTableController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $qb = $this->em->getRepository(MonsterItem::class)->createQueryBuilder('mi')
            ->leftJoin('mi.monster', 'm')
            ->leftJoin('mi.item', 'i')
            ->addSelect('m', 'i');

        if ($search) {
            $qb->where('LOWER(m.name) LIKE LOWER(:q) OR LOWER(i.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $qb->orderBy('m.name', 'ASC');
        $lootEntries = $qb->getQuery()->getResult();

        return $this->render('admin/loot_table/index.html.twig', [
            'lootEntries' => $lootEntries,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $monsterItem = new MonsterItem();
        $form = $this->createForm(MonsterItemType::class, $monsterItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($monsterItem);
            $this->em->flush();
            $this->addFlash('success', 'Entree de loot creee avec succes.');

            return $this->redirectToRoute('admin_loot_table_index');
        }

        return $this->render('admin/loot_table/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, MonsterItem $monsterItem): Response
    {
        $form = $this->createForm(MonsterItemType::class, $monsterItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Entree de loot modifiee avec succes.');

            return $this->redirectToRoute('admin_loot_table_index');
        }

        return $this->render('admin/loot_table/edit.html.twig', [
            'monsterItem' => $monsterItem,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, MonsterItem $monsterItem): Response
    {
        if ($this->isCsrfTokenValid('delete' . $monsterItem->getId(), $request->request->get('_token'))) {
            $this->em->remove($monsterItem);
            $this->em->flush();
            $this->addFlash('success', 'Entree de loot supprimee avec succes.');
        }

        return $this->redirectToRoute('admin_loot_table_index');
    }
}
