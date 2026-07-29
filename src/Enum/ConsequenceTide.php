<?php

namespace App\Enum;

/**
 * Une maree **declenchee**, jamais datee (FOY-15, GAME_SEASONS § 3).
 *
 * C'est ce qui transforme la saison en **boucle** plutot qu'en calendrier : le
 * theme de la marée qui vient ne sort pas d'une partition ecrite d'avance, il
 * sort de ce que le serveur a fait le mois d'avant. Une maree canon se joue
 * quoi qu'il arrive ; une maree consequence n'arrive que si le monde l'a
 * meritee.
 *
 * **La Paleur passe devant l'Appel** quand les deux sont vraies. La consequence
 * *negative* ne doit jamais attendre : c'est elle qui enseigne, et la faire
 * ceder a une bonne nouvelle reviendrait a dire au serveur que sa
 * sur-extraction est sans suite.
 */
enum ConsequenceTide: string
{
    case Paleness = 'paleness';
    case CrueCall = 'crue_call';

    /**
     * Priorite de preemption : le plus petit passe d'abord.
     */
    public function precedence(): int
    {
        return match ($this) {
            self::Paleness => 1,
            self::CrueCall => 2,
        };
    }
}
