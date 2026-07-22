<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Api\LegacyResponseEnveloper;
use App\Controller\Game\NotificationController as LegacyNotificationController;
use App\Entity\App\PlayerNotification;
use App\Helper\PlayerHelper;
use App\Repository\PlayerNotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Notifications joueur sous /api/v1 (migration API-first, phase 4.4).
 * Lecture construite ici (le legacy rend du Twig) ; les actions marquer-lu
 * deleguent au legacy (deja JSON) avec l'enveloppe v1.
 */
#[Route('/api/v1/notifications')]
class NotificationsController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerNotificationRepository $notificationRepository,
        private readonly LegacyNotificationController $legacyNotificationController,
        private readonly LegacyResponseEnveloper $enveloper,
    ) {
    }

    #[Route('', name: 'api_v1_notifications', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        $limit = min(100, max(1, $request->query->getInt('limit', 30)));

        $notifications = [];
        foreach ($this->notificationRepository->findRecentByPlayer($player, $limit) as $notification) {
            $notifications[] = $this->serialize($notification);
        }

        return ApiResponse::success([
            'notifications' => $notifications,
            'unreadCount' => $this->notificationRepository->countUnreadByPlayer($player),
        ]);
    }

    #[Route('/mark-all-read', name: 'api_v1_notifications_mark_all_read', methods: ['POST'])]
    public function markAllRead(Request $request): JsonResponse
    {
        $error = $this->requireJsonContentType($request);
        if ($error !== null) {
            return $error;
        }

        return $this->enveloper->envelope($this->legacyNotificationController->markAllRead());
    }

    #[Route('/{id}/read', name: 'api_v1_notifications_mark_read', methods: ['POST'])]
    public function markRead(Request $request, PlayerNotification $notification): JsonResponse
    {
        $error = $this->requireJsonContentType($request);
        if ($error !== null) {
            return $error;
        }

        return $this->enveloper->envelope($this->legacyNotificationController->markRead($notification));
    }

    private function requireJsonContentType(Request $request): ?JsonResponse
    {
        $contentType = (string) $request->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'json')) {
            return ApiResponse::error('bad_request', 'Content-Type application/json requis.', 400);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(PlayerNotification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'type' => $notification->getType(),
            'title' => $notification->getTitle(),
            'comment' => $notification->getComment(),
            'icon' => $notification->getIcon(),
            'link' => $notification->getLink(),
            'isRead' => $notification->isRead(),
            'readAt' => $notification->getReadAt()?->format(\DateTimeInterface::ATOM),
            'createdAt' => $notification->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
