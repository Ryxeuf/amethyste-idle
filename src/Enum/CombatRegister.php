<?php

namespace App\Enum;

/**
 * Le registre d'une action de combat — la seconde borne des passifs (DOM-01).
 *
 * GAME_DOMAINS § 2 : « tout passif de combat est borne deux fois, par l'element
 * de son domaine et par son registre ». L'element dit *de quoi* le geste est
 * fait ; le registre dit *comment* il se porte — un sort qu'on canalise, une
 * arme qu'on abat, un trait qu'on decoche.
 *
 * Trois valeurs, et pas de quatrieme : la recolte et l'artisanat n'ont **pas**
 * de registre (§ 2, derniere phrase). Leurs passifs sont bornes a leur metier,
 * ce qui est une borne d'une autre nature — le domaine lui-meme. Un `null` sur
 * `Domain::register` dit donc « hors combat », jamais « registre inconnu ».
 */
enum CombatRegister: string
{
    case Spell = 'spell';
    case Melee = 'melee';
    case Ranged = 'ranged';

    public function label(): string
    {
        return match ($this) {
            self::Spell => 'Sorts',
            self::Melee => 'Mêlée',
            self::Ranged => 'Distance',
        };
    }
}
