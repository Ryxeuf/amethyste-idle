<?php

namespace App\Enum;

/**
 * Cycle de vie d'une commande de craft (ECO-05).
 *
 * Le sens de lecture : une commande **ouverte** attend un artisan ; **prise en
 * charge**, elle lui est reservee ; **honoree**, l'objet est livre. Les deux
 * etats terminaux restants — **expiree** et **annulee** — se distinguent par qui
 * y met fin, le temps ou le commanditaire, mais rendent tous deux l'escrow.
 */
enum CraftOrderStatus: string
{
    case Open = 'open';
    case Claimed = 'claimed';
    case Fulfilled = 'fulfilled';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::Claimed => 'Prise en charge',
            self::Fulfilled => 'Honoree',
            self::Expired => 'Expiree',
            self::Cancelled => 'Annulee',
        };
    }

    /**
     * Une commande vivante immobilise l'escrow ; une commande terminee l'a rendu
     * ou consomme. Aucun etat intermediaire ne doit exister entre les deux.
     */
    public function isActive(): bool
    {
        return self::Open === $this || self::Claimed === $this;
    }

    /**
     * Un etat terminal ou l'escrow **retourne au commanditaire**, par opposition
     * a `fulfilled` ou il est consomme par le craft.
     */
    public function refundsEscrow(): bool
    {
        return self::Expired === $this || self::Cancelled === $this;
    }
}
