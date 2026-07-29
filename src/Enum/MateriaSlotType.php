<?php

namespace App\Enum;

/**
 * Ce qu'un emplacement de materia accepte (DOM-03).
 *
 * GAME_DOMAINS § 3 : « la robe porte des emplacements de sort et des bonus de
 * magie ; la plaque, des emplacements de technique et de l'armure ; le cuir,
 * l'entre-deux ». Le typage est **de la donnee**, pas un moteur : c'est la piece
 * qui declare ce qu'elle accepte, et rien d'autre ne le decide.
 *
 * `Free` est le defaut, et il est charge de sens : une piece qui ne dit rien
 * accepte tout. C'est ce qui rend le typage **additif** — les 121 pieces livrees
 * continuent de se comporter comme avant tant que personne ne les type, et le
 * plancher jour 1 (« la premiere materia se sertit toujours, quelle que soit la
 * tenue ») tient sans qu'on ait a l'ecrire piece par piece.
 */
enum MateriaSlotType: string
{
    case Spell = 'spell';
    case Technique = 'technique';
    case Free = 'free';

    public function label(): string
    {
        return match ($this) {
            self::Spell => 'Sort',
            self::Technique => 'Technique',
            self::Free => 'Libre',
        };
    }

    /**
     * Cet emplacement accepte-t-il une materia de ce genre ?
     *
     * Un emplacement libre accepte tout — c'est sa definition, pas une
     * tolerance. Les deux autres n'acceptent que leur genre.
     */
    public function accepts(self $materiaKind): bool
    {
        return $this === self::Free || $this === $materiaKind;
    }
}
