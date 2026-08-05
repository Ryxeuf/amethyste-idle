<?php

namespace App\Enum;

/**
 * La nature d'une condition de passif, et ce qu'elle vaut (ARC-12).
 *
 * GAME_ARCHETYPES § 4.3 : un passif peut ne s'appliquer que **sous condition**,
 * et c'est ce qui fait que *l'equipement est le build* au lieu d'etre un total
 * — la promesse de GAME_DOMAINS § 3, qui n'avait jamais eu de quoi la tenir.
 *
 * > **Le budget compte l'effet moyen, pas l'effet affiche.** Un passif qui ne
 * > vaut que la moitie du temps peut afficher davantage pour le meme prix ; ce
 * > qu'on paie, c'est ce qu'on obtient *en moyenne*. Les plafonds restent donc
 * > exprimes en points de budget et ne bougent pas.
 *
 * Deux natures, et la frontiere n'est pas cosmetique :
 *
 *  - une condition de **build** se decide a l'inventaire — on la remplit ou non
 *    pour la journee, et on le sait en s'equipant ;
 *  - une condition de **combat** se decide dans le tour — elle peut manquer au
 *    moment ou on en aurait besoin.
 */
enum SkillConditionKind: string
{
    /** Ce qu'on porte : famille d'arme, ligne d'armure, bouclier, mains. */
    case Build = 'build';

    /** Ce qui se passe dans le tour : cible marquee, coup encaisse, PV bas. */
    case Combat = 'combat';

    /**
     * Le multiplicateur d'effet **par defaut** de cette nature.
     *
     * `Combat` vaut x2,0 **quand la condition peut manquer**. La correction du
     * § 9 bis empeche de s'arreter la : le multiplicateur suit la **frequence
     * mesuree**, pas la famille. Voir `SkillCondition::multiplier()`.
     */
    public function defaultMultiplier(): float
    {
        return match ($this) {
            self::Build => 1.4,
            self::Combat => 2.0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Build => 'Build',
            self::Combat => 'Combat',
        };
    }
}
