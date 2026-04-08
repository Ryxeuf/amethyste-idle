<?php

namespace App\GameEngine\Fight;

use App\Dto\Fight\FightNotification;

class FightNotificationHandler
{
    /** @var FightNotification[] */
    protected array $notifications = [];

    public function getNotifications(): array
    {
        return $this->notifications;
    }

    public function addNotification(FightNotification $notification): void
    {
        $this->notifications[] = $notification;
    }
}
