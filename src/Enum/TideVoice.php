<?php

namespace App\Enum;

/**
 * Les trois voix de la partition (GAME_SEASONS § 0, NAR-15).
 *
 * L'ordre de priorite est celui du canon, et il se lit ici :
 *
 *     consequence declenchee  >  colonne vertebrale datee  >  rotation
 *
 * `None` n'est pas une quatrieme voix, c'est l'absence de choix — le cas normal
 * quand un creneau porte deja son theme.
 */
enum TideVoice: string
{
    case Consequence = 'consequence';
    case Canon = 'canon';
    case Rotation = 'rotation';
    case None = 'none';
}
