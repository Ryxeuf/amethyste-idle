<?php

namespace App\GameEngine\Bestiary;

use App\Enum\MonsterRank;

/**
 * Le gabarit de stats par case tier × rang (BES-02, GAME_BESTIARY §3).
 *
 * Comme les filons ont des profils de palier et les materia une derivation
 * depuis le sort, les stats d'un monstre ne s'ecrivent pas a la main : elles
 * se derivent de sa case. C'est ce qui rend la courbe lisible et
 * l'equilibrage possible — la faille du milieu venait d'un saut de vie de ×4
 * entre le bloc de depart et le bloc de fin.
 *
 * La grille progresse de ×~2,2 par palier et ×~3 par rang, calee sur les
 * extremes observes (T1 commun ~30, T4 boss ~2400). Elle est un point de
 * depart a valider en jeu, pas une table figee.
 *
 * **La derivation est un defaut, pas une prison** : un monstre peut s'ecarter
 * du gabarit quand la fiction l'exige, mais l'ecart est alors explicite et
 * commente dans la fixture. Ce qui est interdit, c'est l'absence de gabarit.
 */
final class MonsterStatTemplate
{
    /**
     * Points de vie par case tier × rang. T0 ne sert qu'aux mannequins
     * d'entrainement, qui declarent leurs valeurs (ecart pedagogique).
     *
     * @var array<int, array<string, int>>
     */
    public const LIFE = [
        0 => ['common' => 30, 'elite' => 60, 'boss' => 100],
        1 => ['common' => 30, 'elite' => 90, 'boss' => 250],
        2 => ['common' => 70, 'elite' => 200, 'boss' => 550],
        3 => ['common' => 150, 'elite' => 420, 'boss' => 1100],
        4 => ['common' => 300, 'elite' => 850, 'boss' => 2400],
    ];

    public static function lifeFor(int $tier, MonsterRank $rank): int
    {
        return self::LIFE[max(0, min(4, $tier))][$rank->value];
    }

    /**
     * Precision : 70 + 5 par palier, +5 pour l'elite et le boss.
     */
    public static function hitFor(int $tier, MonsterRank $rank): int
    {
        return 70 + 5 * max(0, min(4, $tier)) + ($rank === MonsterRank::Common ? 0 : 5);
    }

    /**
     * Vitesse par defaut d'une case — la vitesse reste d'abord un trait
     * d'espece (la chauve-souris file, le zombie traine) : une valeur
     * declaree dans la fixture est l'ecart explicite, le gabarit ne sert
     * que de repli.
     */
    public static function speedFor(int $tier, MonsterRank $rank): int
    {
        return 6 + max(0, min(4, $tier)) + ($rank === MonsterRank::Boss ? 2 : 0);
    }
}
