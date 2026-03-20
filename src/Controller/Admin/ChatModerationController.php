<?php

namespace App\Controller\Admin;

use App\Entity\App\ChatMessage;
use App\Entity\App\Player;
use App\GameEngine\Social\ChatManager;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/chat', name: 'admin_chat_')]
#[IsGranted('ROLE_MODERATOR')]
class ChatModerationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ChatManager $chatManager,
        private readonly AdminLogger $adminLogger,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $channel = $request->query->get('channel', '');
        $search = $request->query->get('q', '');
        $showDeleted = $request->query->getBoolean('deleted', false);

        $qb = $this->em->getRepository(ChatMessage::class)->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->leftJoin('m.recipient', 'r')
            ->addSelect('r')
            ->leftJoin('m.map', 'map')
            ->addSelect('map');

        if ($channel) {
            $qb->andWhere('m.channel = :channel')
               ->setParameter('channel', $channel);
        }

        if ($search) {
            $qb->andWhere('LOWER(m.content) LIKE LOWER(:q) OR LOWER(s.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        if (!$showDeleted) {
            $qb->andWhere('m.isDeleted = false');
        }

        $qb->orderBy('m.createdAt', 'DESC');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 50;
        $total = (int) (clone $qb)->select('COUNT(m.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        $messages = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/chat/index.html.twig', [
            'messages' => $messages,
            'channel' => $channel,
            'search' => $search,
            'showDeleted' => $showDeleted,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, ChatMessage $message): Response
    {
        if ($this->isCsrfTokenValid('delete_chat' . $message->getId(), $request->request->get('_token'))) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $moderator = $user->getUsername() ?? $user->getUserIdentifier();
            $this->chatManager->deleteMessage($message->getId(), $moderator);
            $this->adminLogger->log('chat_delete', 'ChatMessage', $message->getId(), 'Message de ' . $message->getSender()->getName());
            $this->addFlash('success', 'Message supprime.');
        }

        return $this->redirectToRoute('admin_chat_index', $request->query->all());
    }

    #[Route('/player/{id}', name: 'player', requirements: ['id' => '\d+'])]
    public function playerMessages(Player $player, Request $request): Response
    {
        $qb = $this->em->getRepository(ChatMessage::class)->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->leftJoin('m.recipient', 'r')
            ->addSelect('r')
            ->leftJoin('m.map', 'map')
            ->addSelect('map')
            ->where('m.sender = :player OR m.recipient = :player')
            ->setParameter('player', $player)
            ->orderBy('m.createdAt', 'DESC');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 50;
        $total = (int) (clone $qb)->select('COUNT(m.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        $messages = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/chat/player.html.twig', [
            'player' => $player,
            'messages' => $messages,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }
}
