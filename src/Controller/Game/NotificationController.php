<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerNotification;
use App\Helper\PlayerHelper;
use App\Repository\PlayerNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/notifications')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerNotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_game_notifications', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $notifications = $this->notificationRepository->findRecentByPlayer($player);
        $unreadCount = $this->notificationRepository->countUnreadByPlayer($player);

        return $this->render('game/notifications/index.html.twig', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'player' => $player,
        ]);
    }

    #[Route('/panel', name: 'app_game_notifications_panel', methods: ['GET'])]
    public function panel(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $notifications = $this->notificationRepository->findRecentByPlayer($player, 15);
        $unreadCount = $this->notificationRepository->countUnreadByPlayer($player);

        return $this->render('game/notifications/_panel.html.twig', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/unread-count', name: 'app_game_notifications_unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $count = $this->notificationRepository->countUnreadByPlayer($player);

        return $this->json(['count' => $count]);
    }

    #[Route('/mark-all-read', name: 'app_game_notifications_mark_all_read', methods: ['POST'])]
    public function markAllRead(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $this->notificationRepository->markAllAsReadForPlayer($player);

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/read', name: 'app_game_notifications_mark_read', methods: ['POST'])]
    public function markRead(PlayerNotification $notification): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if ($notification->getPlayer()->getId() !== $player->getId()) {
            throw $this->createAccessDeniedException();
        }

        $notification->markAsRead();
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }
}
