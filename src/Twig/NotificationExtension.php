<?php

namespace App\Twig;

use App\Helper\PlayerHelper;
use App\Repository\PlayerNotificationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class NotificationExtension extends AbstractExtension
{
    private ?int $cachedCount = null;

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerNotificationRepository $repository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('notification_unread_count', $this->getUnreadCount(...)),
        ];
    }

    public function getUnreadCount(): int
    {
        if ($this->cachedCount !== null) {
            return $this->cachedCount;
        }

        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return 0;
        }

        return $this->cachedCount = $this->repository->countUnreadByPlayer($player);
    }
}
