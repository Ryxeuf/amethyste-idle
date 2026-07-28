<?php

namespace App\GameEngine\Retention;

/**
 * Table de paliers d'assiduite invalide (RET-04).
 *
 * Meme parti pris que les autres briques hebdomadaires : la validation echoue
 * **a la lecture**, pour que la CI rougisse au lieu qu'un joueur decouvre un
 * lundi matin qu'aucun palier ne se franchit plus.
 */
class WeeklyAttendanceException extends \RuntimeException
{
}
