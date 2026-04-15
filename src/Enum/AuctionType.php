<?php

namespace App\Enum;

enum AuctionType: string
{
    case Fixed = 'fixed';
    case Auction = 'auction';
    case Flash = 'flash';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Prix fixe',
            self::Auction => 'Enchere',
            self::Flash => 'Vente flash',
        };
    }
}
