<?php

namespace App\Enum;

enum AuctionType: string
{
    case Fixed = 'fixed';
    case Auction = 'auction';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Prix fixe',
            self::Auction => 'Enchere',
        };
    }
}
