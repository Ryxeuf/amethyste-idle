<?php

namespace App\Enum;

/**
 * Etat d'une commission hebdomadaire (RET-02).
 *
 * Trois etats, et pas de quatrieme : ouverte, livree, expiree. Pas d'etat
 * « abandonnee » — une commission qu'on peut rendre est une commission qu'on
 * rejoue jusqu'a tomber sur celle qui arrange, et le rendez-vous perd ce qui
 * fait sa valeur.
 */
enum WeeklyCommissionStatus: string
{
    case Open = 'open';
    case Delivered = 'delivered';
    case Expired = 'expired';

    public function isClosed(): bool
    {
        return $this !== self::Open;
    }
}
