<?php

namespace App\GameEngine\Notification;

use App\Entity\App\Player;
use App\Entity\App\PlayerNotification;
use App\Repository\PlayerNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerNotificationRepository $repository,
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notify(
        Player $player,
        string $type,
        string $title,
        string $message,
        ?string $icon = null,
        ?string $link = null,
    ): PlayerNotification {
        $notification = new PlayerNotification();
        $notification->setPlayer($player);
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setComment($message);
        $notification->setIcon($icon);
        $notification->setLink($link);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->repository->pruneOldNotifications($player);

        $this->publishMercure($player, $notification);

        return $notification;
    }

    private function publishMercure(Player $player, PlayerNotification $notification): void
    {
        try {
            $topic = 'player/' . $player->getId() . '/notifications';

            $update = new Update(
                $topic,
                json_encode([
                    'topic' => $topic,
                    'type' => 'new_notification',
                    'notification' => [
                        'id' => $notification->getId(),
                        'type' => $notification->getType(),
                        'title' => $notification->getTitle(),
                        'message' => $notification->getComment(),
                        'icon' => $notification->getIcon(),
                        'link' => $notification->getLink(),
                        'createdAt' => $notification->getCreatedAt()->format('c'),
                    ],
                ], JSON_THROW_ON_ERROR),
            );

            $this->hub->publish($update);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to publish notification via Mercure: {error}', [
                'error' => $e->getMessage(),
                'player' => $player->getId(),
            ]);
        }
    }
}
