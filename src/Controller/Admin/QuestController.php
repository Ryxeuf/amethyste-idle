<?php

namespace App\Controller\Admin;

use App\Entity\Game\Quest;
use App\Form\Admin\QuestType;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/quests', name: 'admin_quest_')]
#[IsGranted('ROLE_GAME_DESIGNER')]
class QuestController extends AbstractController
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
        $qb = $this->em->getRepository(Quest::class)->createQueryBuilder('q');

        if ($search) {
            $qb->where('LOWER(q.name) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('q.name', 'ASC');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $total = (int) (clone $qb)->select('COUNT(q.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        $quests = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/quest/index.html.twig', [
            'quests' => $quests,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $quest = new Quest();
        $form = $this->createForm(QuestType::class, $quest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requirementsJson = $form->get('requirementsJson')->getData();
            $rewardsJson = $form->get('rewardsJson')->getData();

            $quest->setRequirements($requirementsJson ? json_decode($requirementsJson, true) ?? [] : []);
            $quest->setRewards($rewardsJson ? json_decode($rewardsJson, true) ?? [] : []);

            $this->em->persist($quest);
            $this->em->flush();
            $this->adminLogger->log('create', 'Quest', $quest->getId(), $quest->getName());
            $this->addFlash('success', 'Quete "' . $quest->getName() . '" creee avec succes.');

            return $this->redirectToRoute('admin_quest_index');
        }

        return $this->render('admin/quest/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, Quest $quest): Response
    {
        $form = $this->createForm(QuestType::class, $quest);

        $form->get('requirementsJson')->setData(
            json_encode($quest->getRequirements(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        $form->get('rewardsJson')->setData(
            json_encode($quest->getRewards(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requirementsJson = $form->get('requirementsJson')->getData();
            $rewardsJson = $form->get('rewardsJson')->getData();

            $quest->setRequirements($requirementsJson ? json_decode($requirementsJson, true) ?? [] : []);
            $quest->setRewards($rewardsJson ? json_decode($rewardsJson, true) ?? [] : []);

            $this->em->flush();
            $this->adminLogger->log('update', 'Quest', $quest->getId(), $quest->getName());
            $this->addFlash('success', 'Quete "' . $quest->getName() . '" modifiee avec succes.');

            return $this->redirectToRoute('admin_quest_index');
        }

        return $this->render('admin/quest/edit.html.twig', [
            'quest' => $quest,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Quest $quest): Response
    {
        if ($this->isCsrfTokenValid('delete' . $quest->getId(), $request->request->get('_token'))) {
            $name = $quest->getName();
            $this->em->remove($quest);
            $this->em->flush();
            $this->adminLogger->log('delete', 'Quest', null, $name);
            $this->addFlash('success', 'Quete supprimee avec succes.');
        }

        return $this->redirectToRoute('admin_quest_index');
    }
}
