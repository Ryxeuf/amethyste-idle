<?php

namespace App\Enum;

enum FriendshipStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Accepted => 'Ami',
            self::Blocked => 'Bloqué',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Pending => 'text-yellow-400 bg-yellow-400/10',
            self::Accepted => 'text-green-400 bg-green-400/10',
            self::Blocked => 'text-red-400 bg-red-400/10',
        };
    }
}
