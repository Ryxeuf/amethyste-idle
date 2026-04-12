<?php

namespace App\Enum;

enum AuctionStatus: string
{
    case Active = 'active';
    case Sold = 'sold';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'En vente',
            self::Sold => 'Vendu',
            self::Expired => 'Expiree',
            self::Cancelled => 'Annulee',
        };
    }
}
