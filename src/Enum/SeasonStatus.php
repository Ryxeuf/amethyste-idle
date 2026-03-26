<?php

namespace App\Enum;

enum SeasonStatus: string
{
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programmée',
            self::Active => 'En cours',
            self::Completed => 'Terminée',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Scheduled => 'text-blue-400',
            self::Active => 'text-green-400',
            self::Completed => 'text-gray-400',
        };
    }

    public function bgClass(): string
    {
        return match ($this) {
            self::Scheduled => 'bg-blue-900/50 text-blue-400',
            self::Active => 'bg-green-900/50 text-green-400',
            self::Completed => 'bg-gray-900/50 text-gray-400',
        };
    }
}
