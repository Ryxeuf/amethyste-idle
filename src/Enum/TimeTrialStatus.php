<?php

namespace App\Enum;

/**
 * Etat d'une tentative de parcours chronometre (tache 133).
 */
enum TimeTrialStatus: string
{
    case Running = 'running';
    case Finished = 'finished';
    case Abandoned = 'abandoned';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Running => 'En cours',
            self::Finished => 'Termine',
            self::Abandoned => 'Abandonne',
            self::Expired => 'Delai depasse',
        };
    }
}
