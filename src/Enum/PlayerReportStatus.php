<?php

namespace App\Enum;

enum PlayerReportStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Accepted => 'Accepte',
            self::Rejected => 'Rejete',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-yellow-900 text-yellow-300',
            self::Accepted => 'bg-red-900 text-red-300',
            self::Rejected => 'bg-gray-800 text-gray-400',
        };
    }
}
