<?php

namespace App\Enum;

/**
 * Etat d'une echoppe joueur (ECO-10).
 */
enum ShopStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    /**
     * Loyer impaye : l'echoppe cesse de vendre sans rien confisquer.
     *
     * Meme regle que la demeure (HOU-04) : on ne prend pas les affaires d'un
     * joueur parce qu'il a manque une echeance. Le stock reste en escrow et
     * revient des le paiement.
     */
    case Arrears = 'arrears';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::Closed => 'Fermee',
            self::Arrears => 'Loyer impaye',
        };
    }

    /**
     * Une echoppe ne vend que grande ouverte.
     */
    public function sells(): bool
    {
        return self::Open === $this;
    }
}
